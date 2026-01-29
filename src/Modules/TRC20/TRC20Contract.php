<?php

declare(strict_types=1);
namespace Dsdcr\TronWeb\Modules\TRC20;

use Dsdcr\TronWeb\Exception\TRC20Exception;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\TronWeb;

/**
 * TRC20合约类
 *
 * 用于处理TRC20代币的智能合约操作
 * @package Dsdcr\TronWeb\Modules\TRC20
 */
class TRC20Contract
{
    // TRX到SUN的转换比率
    const TRX_TO_SUN = 1000000;

    /***
     * 代币支持的最大小数位数
     *
     * @var integer|null
     */
    private ?int $_decimals = null;

    /***
     * 代币名称
     *
     * @var string|null
     */
    private ?string $_name = null;

    /***
     * 代币符号
     *
     * @var string|null
     */
    private ?string $_symbol = null;

    /**
     * 发行TRC20代币的智能合约地址
     *
     * @var string
    */
    private string $contractAddress;

    /**
     * ABI接口数据
     *
     * @var string|null
    */
    private $abiData;

    /**
     * 费用限制（TRX单位）
     *
     * @var integer
     */
    private int $feeLimit = 10;

    /**
     * TronWeb基础对象
     *
     * @var TronWeb
     */
    protected TronWeb $_tron;

    /**
     * 代币总供应量
     *
     * @var string|null
    */
    private ?string $_totalSupply = null;

    /**
     * 创建TRC20合约实例
     *
     * @param TronWeb $tron TronWeb实例
     * @param string $contractAddress 合约地址
     * @param string|null $abi ABI接口定义
     */
    public function __construct(TronWeb $tron, string $contractAddress, string $abi = null)
    {
        $this->_tron = $tron;

        // 如果未提供ABI，则使用默认的ABI文件
        if(is_null($abi)) {
            $abi = file_get_contents(__DIR__.'/trc20.json');
        }

        $this->abiData = json_decode($abi, true);
        $this->contractAddress = $contractAddress;
    }

    /**
     * 调试信息
     *
     * @return array 代币数据数组
     * @throws TronException
     */
    public function __debugInfo(): array
    {
        return $this->array();
    }

    /**
     * 清除缓存的值
     *
     * @return void
     */
    public function clearCached(): void
    {
        $this->_name = null;
        $this->_symbol = null;
        $this->_decimals = null;
        $this->_totalSupply = null;
    }

    /**
     * 获取所有代币数据
     *
     * @return array 代币数据数组
     * @throws TronException
     */
    public function array(): array
    {
        return [
            'name' => $this->name(),
            'symbol' => $this->symbol(),
            'decimals' => $this->decimals(),
            'totalSupply' => $this->totalSupply(true)
        ];
    }

    /**
     * 获取代币名称
     *
     * @return string 代币名称
     * @throws TronException
     */
    public function name(): string
    {
        if ($this->_name) {
            return $this->_name;
        }

        $result = $this->trigger('name', null, []);
        $name = $result[0] ?? null;

        if (!is_string($name)) {
            throw new TRC20Exception('获取TRC20代币名称失败');
        }

        $this->_name = $this->cleanStr($name);
        return $this->_name;
    }

    /**
     * 获取代币符号
     *
     * @return string 代币符号
     * @throws TronException
     */
    public function symbol(): string
    {
        if ($this->_symbol) {
            return $this->_symbol;
        }
        $result = $this->trigger('symbol', null, []);
        $code = $result[0] ?? null;

        if (!is_string($code)) {
            throw new TRC20Exception('获取TRC20代币符号失败');
        }

        $this->_symbol = $this->cleanStr($code);
        return $this->_symbol;
    }

    /**
     * 获取主网发行的代币总量
     *
     * @param bool $scaled 是否返回缩放后的值
     * @return string 总供应量
     * @throws Exception\TronException
     * @throws TRC20Exception
     */
    public function totalSupply(bool $scaled = true): string
    {
        if (!$this->_totalSupply) {

            $result = $this->trigger('totalSupply', null, []);
            $totalSupply = $result[0]->toString() ?? null;

            if (!is_string($totalSupply) || !preg_match('/^[0-9]+$/', $totalSupply)) {
                throw new TRC20Exception('Failed to retrieve TRC20 token totalSupply');
            }

            $this->_totalSupply = $totalSupply;
        }

        return $scaled ? $this->decimalValue($this->_totalSupply, $this->decimals()) : $this->_totalSupply;
    }
    /**
     * 获取代币支持的小数位数
     *
     * @return int 小数位数
     * @throws TRC20Exception
     * @throws TronException
     */
    public function decimals(): int
    {
        if ($this->_decimals) {
            return $this->_decimals;
        }

        $result = $this->trigger('decimals', null, []);
        $scale = intval($result[0]->toString() ?? null);

        if (is_null($scale)) {
            throw new TRC20Exception('获取TRC20代币小数位数失败');
        }

        $this->_decimals = $scale;
        return $this->_decimals;
    }

    /**
     * 获取TRC20合约余额
     *
     * @param string|null $address 地址
     * @param bool $scaled 是否返回缩放后的值
     * @return string 余额
     * @throws TRC20Exception
     * @throws TronException
     */
    public function balanceOf(string $address = null, bool $scaled = true): string
    {
        if(is_null($address))
            $address = $this->_tron->getDefaultAddress()['base58'];

        $addr = str_pad(TronUtils::addressToHex($address), 64, "0", STR_PAD_LEFT);
        $result = $this->trigger('balanceOf', $address, [$addr]);
        $balance = $result[0]->toString();

        if (!is_string($balance) || !preg_match('/^[0-9]+$/', (string)$balance)) {
            throw new TRC20Exception(
                sprintf('获取地址"%s"的TRC20代币余额失败', $addr)
            );
        }

        return $scaled ? $this->decimalValue($balance, $this->decimals()) : $balance;
    }

    /**
     * 发送TRC20合约交易
     *
     * @param string $to 接收地址
     * @param string $amount 金额
     * @param string|null $from 发送地址
     * @return array 交易结果
     * @throws TRC20Exception
     * @throws TronException
     */
    public function transfer(string $to, string $amount, string $from = null): array
    {
        if($from == null) {
            $from = $this->_tron->getDefaultAddress()['base58'];
        }

        if (!is_numeric($this->feeLimit) OR $this->feeLimit <= 0) {
            throw new TRC20Exception('fee_limit是必需的');
        } else if($this->feeLimit > 1000) {
            throw new TRC20Exception('fee_limit不能大于1000 TRX');
        }

        // 将费用限制转换为整数
        $feeLimitInSun = (int)bcmul((string)$this->feeLimit, (string)self::TRX_TO_SUN);

        $tokenAmount = bcmul($amount, bcpow("10", (string)$this->decimals(), 0), 0);

        // 直接使用Trx模块的triggerSmartContract方法
        $transfer = $this->_tron->trx->triggerSmartContract(
            $this->abiData,
            TronUtils::addressToHex($this->contractAddress),
            'transfer',
            [TronUtils::addressToHex($to), $tokenAmount],
            $feeLimitInSun,
            TronUtils::addressToHex($from)
        );

        $signedTransaction = $this->_tron->trx->signTransaction($transfer);
        $response = $this->_tron->trx->sendRawTransaction($signedTransaction);

        return array_merge($response, $signedTransaction);
    }

    /**
     * 获取TRC20所有交易记录
     *
     * @param string $address 地址
     * @param int $limit 限制数量
     * @return array 交易记录数组
     * @throws TronException
     */
    public function getTransactions(string $address, int $limit = 100): array
    {
        return $this->_tron->request("v1/accounts/{$address}/transactions/trc20?limit={$limit}&contract_address={$this->contractAddress}", [], 'get');
    }

    /**
     * 通过合约地址获取交易信息
     *
     * @param array $options 选项数组
     * @return array 交易信息
     * @throws TronException
     */
    public function getTransactionInfoByContract(array $options = []): array
    {
        return $this->_tron->request("v1/contracts/{$this->contractAddress}/transactions?".http_build_query($options), [],'get');
    }

    /**
     * 获取TRC20代币持有者余额
     *
     * @param array $options 选项数组
     * @return array 持有者余额信息
     * @throws TronException
     */
    public function getTRC20TokenHolderBalance(array $options = []): array
    {
        return $this->_tron->request("v1/contracts/{$this->contractAddress}/tokens?".http_build_query($options), [],'get');
    }

    /**
     * 查找交易信息
     *
     * @param string $transaction_id 交易ID
     * @return array 交易信息
     * @throws TronException
     */
    public function getTransaction(string $transaction_id): array
    {
        return $this->_tron->request('/wallet/gettransactioninfobyid', ['value' => $transaction_id], 'post');
    }

    /**
     * 配置触发合约调用
     *
     * @param string $function 函数名
     * @param string|null $address 地址
     * @param array $params 参数数组
     * @return mixed 调用结果
     * @throws TronException
     */
    private function trigger($function, $address = null, array $params = [])
    {
        $owner_address = is_null($address) ? '410000000000000000000000000000000000000000' : TronUtils::addressToHex($address);

        return $this->_tron->trx->triggerConstantContract(
            $this->abiData,
            TronUtils::addressToHex($this->contractAddress),
            $function,
            $params,
            $owner_address
        );
    }

    /**
     * 十进制值转换
     *
     * @param string $int 整数字符串
     * @param int $scale 小数位数
     * @return string 转换后的值
     */
    protected function decimalValue(string $int, int $scale = 18): string
    {
        return bcdiv($int, bcpow('10', (string)$scale, 0), $scale);
    }

    /**
     * @param string $str
     * @return string
     */
    public function cleanStr(string $str): string
    {
        return preg_replace('/[^\w.-]/', '', trim($str));
    }
    /**
     * 获取费用限制
     *
     * @return int 费用限制
     */
    public function getFeeLimit(): int
    {
        return $this->feeLimit;
    }

    /**
     * 设置费用限制
     *
     * @param int $fee_limit 费用限制
     * @return TRC20Contract 当前实例
     */
    public function setFeeLimit(int $fee_limit) : TRC20Contract
    {
        $this->feeLimit = $fee_limit;
        return $this;
    }
}
