<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb;

use Dsdcr\TronWeb\Modules\Contract\ContractInstance;
use Dsdcr\TronWeb\Provider\HttpProviderInterface;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Provider\TronManager;

/**
 * TronWeb - Tron区块链API主入口类
 *
 * 提供与Tron区块链交互的完整功能：
 * - 账户管理和地址操作
 * - 交易创建和广播
 * - 智能合约交互
 * - 区块和交易查询
 * - 资源和代币管理
 * - 网络治理功能
 *
 * @package Dsdcr\TronWeb
 */
class TronWeb
{
    /**
     * Provider 管理器
     *
     * @var TronManager
     */
    protected $manager;

    /**
     * 私钥
     *
     * @var string|null
     */
    protected $privateKey;

    /**
     * 默认地址信息
     *
     * @var array|null
     */
    protected $address;

    /**
     * TRX模块实例
     *
     * @var Modules\Trx
     */
    public $trx;

    /**
     * 合约模块实例
     *
     * @var Modules\Contract
     */
    public $contract;

    /**
     * 账户模块实例
     *
     * @var Modules\Account
     */
    public $account;

    /**
     * 工具类实例
     *
     * @var TronUtils
     */
    public $utils;

    /**
     * 代币模块实例
     *
     * @var Modules\Token
     */
    public $token;

    /**
     * 资源模块实例
     *
     * @var Modules\Resource
     */
    public $resource;

    /**
     * 网络模块实例
     *
     * @var Modules\Network
     */
    public $network;

    /**
     * 交易构建器实例
     *
     * @var Modules\TransactionBuilder
     */
    public $transactionBuilder;


    /**
     * 创建新的TronWeb实例
     *
     *
     * @param HttpProviderInterface $fullNode 全节点Provider
     * @param HttpProviderInterface|null $solidityNode 固态节点Provider（可选）
     * @param HttpProviderInterface|null $eventServer 事件服务器Provider（可选）
     * @param HttpProviderInterface|null $signServer 签名服务器Provider（可选）
     * @param HttpProviderInterface|null $explorer 浏览器Provider（可选）
     * @param string|null $privateKey 私钥（可选）
     * @throws TronException
     *
     * @example 简单初始化（推荐）
     * $httpProvider = new HttpProvider('https://api.trongrid.io', [
     *     'timeout' => 30000,
     *     'headers' => [
     *         'Content-Type' => 'application/json',
     *         'TRON-PRO-API-KEY' => 'your_api_key'
     *     ]
     * ]);
     * $tron = new TronWeb($httpProvider);
     *
     * @example 多个Provider
     * $tron = new TronWeb(
     *     $fullNodeProvider,
     *     $solidityNodeProvider,
     *     $eventServerProvider
     * );
     */
    public function __construct(
        HttpProviderInterface $fullNode,
        ?HttpProviderInterface $solidityNode = null,
        ?HttpProviderInterface $eventServer = null,
        ?HttpProviderInterface $signServer = null,
        ?HttpProviderInterface $explorer = null,
        ?string $privateKey = null
    ) {
        if(!is_null($privateKey)) {
            $this->setPrivateKey($privateKey);
            $this->setAddress($this->FromPrivateKey($privateKey));
        }

        $this->manager = new TronManager([
            'fullNode'      => $fullNode,
            'solidityNode'  => $solidityNode,
            'eventServer'   => $eventServer,
            'signServer'    => $signServer,
            'explorer'       => $explorer
        ]);

        // 初始化所有模块
        $this->utils = new \Dsdcr\TronWeb\Support\TronUtils();
        $this->trx = new \Dsdcr\TronWeb\Modules\Trx($this);
        $this->account = new \Dsdcr\TronWeb\Modules\Account($this);
        $this->contract = new \Dsdcr\TronWeb\Modules\Contract($this);
        $this->token = new \Dsdcr\TronWeb\Modules\Token($this);
        $this->resource = new \Dsdcr\TronWeb\Modules\Resource($this);
        $this->network = new \Dsdcr\TronWeb\Modules\Network($this);
        $this->transactionBuilder = new \Dsdcr\TronWeb\Modules\TransactionBuilder($this);
    }

    /**
     * 设置 Provider 管理器
     *
     * @param TronManager $manager
     */
    public function setManager(TronManager $manager): void
    {
        $this->manager = $manager;
    }

    /**
     * 获取 Provider 管理器
     *
     * @return TronManager
     */
    public function getManager(): TronManager
    {
        return $this->manager;
    }

    /**
     * 设置用于交易签名的私钥
     *
     * @param string $privateKey 私钥
     */
    public function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    /**
     * 获取私钥
     *
     * @return string|null
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * 设置操作用的默认地址
     *
     * @param string $address 地址
     * @throws TronException
     */
    public function setAddress(string $address): void
    {
        $_toHex = $this->toHex($address);
        $_fromHex = $this->fromHex($address);

        $this->address = [
            'hex'       => $_toHex,
            'base58'    => $_fromHex
        ];
    }

    /**
     * 获取默认地址
     *
     * @return array|null
     */
    public function getAddress(): ?array
    {
        return $this->address;
    }

    /**
     * 获取 Provider 列表
     *
     * @return array
     */
    public function providers(): array
    {
        return $this->manager->getProviders();
    }

    /**
     * 检查连接状态
     *
     * @return array
     */
    public function isConnected(): array
    {
        return $this->manager->isConnected();
    }

    /**
     * 将地址转换为十六进制
     *
     * @param string $address
     * @return string
     */
    public function toHex(string $address): string
    {
        return \Dsdcr\TronWeb\Support\TronUtils::toHex($address);
    }

    /**
     * 将十六进制转换为地址
     *
     * @param string $addressHex
     * @return string
     */
    public function fromHex(string $addressHex): string
    {
        return \Dsdcr\TronWeb\Support\TronUtils::fromHex($addressHex);
    }

    /**
     * 转换科学记数法字符串为整数
     *
     * @param mixed $sciNotation
     * @return string
     */
    public function toDecimal(mixed $sciNotation): string
    {
        return \Dsdcr\TronWeb\Support\TronUtils::toDecimal($sciNotation);
    }

    /**
     * 验证Tron地址格式
     *
     * @param string $address
     * @return string
     */
    public function isAddress(string $address): bool
    {
        return \Dsdcr\TronWeb\Support\TronUtils::isAddress($address);
    }

    /**
     * 从私钥获取地址
     *
     * @param string $privateKey
     * @return string
     */
    public function fromPrivateKey(string $privateKey): string
    {
        $privateKey = (!is_null($privateKey) ? $privateKey : $this->getPrivateKey());
        return \Dsdcr\TronWeb\Support\TronUtils::fromPrivateKey($privateKey);
    }

    /**
     *
     * 通过助记词生成账户（BIP39/BIP44标准）
     *
     * @param string|null $mnemonic
     * @param string $path
     * @param string $passphrase
     * @param int $wordCount
     * @return array
     */
    public function fromMnemonic(?string $mnemonic = null, string $path = "m/44'/195'/0'/0/0", string $passphrase = '', int $wordCount= 12): array
    {
        return \Dsdcr\TronWeb\Support\HdWallet::fromMnemonic($mnemonic, $path, $passphrase,$wordCount);
    }

    /**
     *
     * 生成新的 TRON 地址、私钥、公钥、助记词（BIP39/BIP44标准）
     *
     * @param int $wordCount
     * @param string $passphrase
     * @param string $path
     * @return array
     */
    public function createAccount(int $wordCount= 12, string $passphrase = '', string $path = "m/44'/195'/0'/0/0"): array
    {
        return \Dsdcr\TronWeb\Support\HdWallet::createAccount( $wordCount, $passphrase,"m/44'/195'/0'/0/0");
    }

    /**
     * 将SUN转换为TRX
     *
     * @param int $sun SUN数量
     * @return float TRX数量
     */
    public function fromSun(int $sun): float
    {
        return \Dsdcr\TronWeb\Support\TronUtils::fromSun($sun);
    }

    /**
     * 获取TRC20代币的精度（小数位数）
     *
     * @param string $tokenAddress 代币合约地址
     * @return int 精度（小数位数）
     *
     * @example
     * $decimals = $tronWeb->getTokenDecimals('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
     * // 返回: 6
     */
    public function getTokenDecimals(string $tokenAddress): int
    {
        try {
            $trc20Abi = [
                [
                    'constant' => true,
                    'inputs' => [],
                    'name' => 'decimals',
                    'outputs' => [['name' => '', 'type' => 'uint8']],
                    'payable' => false,
                    'stateMutability' => 'view',
                    'type' => 'function'
                ]
            ];

            $contract = $this->contract($trc20Abi)->at($tokenAddress);
            $result = $contract->decimals();

            if (is_array($result) && isset($result[0])) {
                return (int)$result[0];
            }

            return 6; // 默认精度
        } catch (\Exception $e) {
            return 6; // 出错时返回默认值
        }
    }

    /**
     * Contract 模块
     *
     * @param array|string|null $abi ABI接口定义（可选）
     * @param string|null $address 合约地址（可选）
     * @return Modules\Contract\ContractInstance
     *
     * @example TypeScript风格链式调用
     * $contract = $tronWeb->contract()->at('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
     * $balance = $contract->balanceOf('T9yD14Nj9j7xAB4dbGeiX9h8unkKHxuWwb');
     *
     * @example 直接创建带地址的合约
     * $contract = $tronWeb->contract([], 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
     */
    public function contract($abi = [], ?string $address = null): Modules\Contract\ContractInstance
    {
        return new ContractInstance($this, $abi, $address);
    }

    /**
     * 基础查询方法，自动路由到正确的节点
     *
     * 代理方法，委托给 TronManager 处理
     *
     * @param string $endpoint 端点路径
     * @param array $params 请求参数
     * @param string $method 请求方法
     * @return array
     * @throws TronException
     */
    public function request(string $endpoint, array $params = [], string $method = 'post'): array
    {
        return $this->manager->request($endpoint, $params, $method);
    }

}