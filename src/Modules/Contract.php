<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\Modules\TRC20\TRC20Contract;

/**
 * 合约模块 - 用于智能合约交互
 *
 * @package Dsdcr\TronWeb\Modules
 */
class Contract extends BaseModule
{
    /**
     * 创建TRC20合约实例
     *
     * @param string $contractAddress 合约地址
     * @param string|null $abi ABI接口定义（可选）
     * @return TRC20Contract TRC20合约实例
     */
    public function trc20(string $contractAddress, ?string $abi = null): TRC20Contract
    {
        return new TRC20Contract($this->getTronWeb(), $contractAddress, $abi);
    }

    /**
     * 部署新合约
     *
     * @param string $abi ABI接口定义
     * @param string $bytecode 合约字节码
     * @param int $feeLimit 费用限制
     * @param string $address 部署者地址
     * @param int $callValue 调用值（TRX数量）
     * @param int $bandwidthLimit 带宽消耗百分比
     * @return array 部署结果
     * @throws TronException
     */
    public function deploy(
        string $abi,
        string $bytecode,
        int $feeLimit,
        string $address,
        int $callValue = 0,
        int $bandwidthLimit = 0
    ): array
    {
        if ($feeLimit > 1000000000) {
            throw new TronException('fee_limit must not be greater than 1000000000');
        }

        // Check if contract is payable
        $payable = $this->isPayableContract($abi);

        if ($payable && $callValue == 0) {
            throw new TronException('call_value must be greater than 0 if contract is payable');
        }

        if (!$payable && $callValue > 0) {
            throw new TronException('call_value can only equal to 0 if contract is not payable');
        }

        return $this->request('wallet/deploycontract', [
            'owner_address' => TronUtils::addressToHex($address),
            'fee_limit' => $feeLimit,
            'call_value' => $callValue,
            'consume_user_resource_percent' => $bandwidthLimit,
            'abi' => $abi,
            'bytecode' => $bytecode
        ]);
    }

    /**
     * 检查合约是否可支付
     *
     * @param string $abi ABI接口定义
     * @return bool 是否可支付
     */
    protected function isPayableContract(string $abi): bool
    {
        $abiArray = json_decode($abi, true);

        if (!is_array($abiArray)) {
            return false;
        }

        foreach ($abiArray as $item) {
            if ($item['type'] === 'constructor' && isset($item['payable']) && $item['payable']) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取合约事件
     *
     * @param string $contractAddress 合约地址
     * @param int $sinceTimestamp 起始时间戳
     * @param string|null $eventName 事件名称（可选筛选条件）
     * @param int $blockNumber 区块号（可选筛选条件）
     * @return array 事件列表
     * @throws TronException
     */
    public function getEvents(
        string $contractAddress,
        int $sinceTimestamp = 0,
        ?string $eventName = null,
        int $blockNumber = 0
    ): array {
        if (!$this->getTronWeb()->isProviderConfigured('eventServer')) {
            throw new TronException('No event server configured');
        }

        if ($eventName && !$contractAddress) {
            throw new TronException('Usage of event name filtering requires a contract address');
        }

        if ($blockNumber && !$eventName) {
            throw new TronException('Usage of block number filtering requires an event name');
        }

        $routeParams = [];
        if ($contractAddress) {
            $routeParams[] = $contractAddress;
        }
        if ($eventName) {
            $routeParams[] = $eventName;
        }
        if ($blockNumber) {
            $routeParams[] = $blockNumber;
        }

        $routeParams = implode('/', $routeParams);
        return $this->request("event/contract/{$routeParams}?since={$sinceTimestamp}", [], 'eventServer');
    }

    /**
     * 通过交易ID获取事件
     *
     * @param string $transactionID 交易ID
     * @return array 事件列表
     * @throws TronException
     */
    public function getEventsByTransaction(string $transactionID): array
    {
        if (!$this->getTronWeb()->isProviderConfigured('eventServer')) {
            throw new TronException('No event server configured');
        }

        return $this->request("event/transaction/{$transactionID}", [], 'eventServer');
    }

    /**
     * 获取合约信息
     *
     * @param string $contractAddress 合约地址
     * @return array 合约信息
     * @throws TronException
     */
    public function getInfo(string $contractAddress): array
    {
        return $this->request('wallet/getcontract', [
            'value' => TronUtils::addressToHex($contractAddress)
        ]);
    }
}