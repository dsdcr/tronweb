<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\Modules\Contract\ContractInstance;

/**
 * Contract模块 - Tron网络智能合约操作的核心模块
 *
 * 提供完整的智能合约交互功能，包括：
 * - 合约实例创建和链式调用
 * - 智能合约部署到区块链
 * - 合约事件日志查询和监听
 * - 合约信息和ABI查询
 * - 合约方法调用（通过ContractInstance）
 *
 * 主要特性：
 * - 支持标准TRON合约ABI接口
 * - 提供合约部署的完整流程
 * - 支持事件过滤和历史查询
 * - 支持Payable和Non-Payable合约
 * - 自动处理地址格式转换（Base58/Hex）
 *
 * @package Dsdcr\TronWeb\Modules
 * @since 1.0.0
 */
class Contract extends BaseModule
{
    /**
     * 创建智能合约实例
     *
     * 返回ContractProxy实例，支持TypeScript风格的链式调用：
     * $contract = $tronWeb->contract()->at('合约地址');
     * $balance = $contract->balanceOf('地址');
     *
     * @param array|string|null $abi 合约的ABI接口定义（可选）
     * @param string|null $address 合约地址（Base58格式），可选参数
     *
     *
     * @example
     * // TypeScript风格（推荐）
     * $contract = $tronWeb->contract($usdtABI)->at('USDT合约地址');
     * $balance = $contract->balanceOf('钱包地址');
     *
     * @example 传统方式
     * $contract = $tronWeb->contract($abi, '合约地址');
     * $balance = $contract->balanceOf('钱包地址');
     */
    public function contract($abi = [], ?string $address = null): ContractInstance
    {
        return new ContractInstance($this->tronWeb, $abi, $address);
    }

    /**
     * 部署智能合约到Tron网络
     *
     * 将编译后的智能合约字节码部署到Tron区块链上。
     * 部署成功后返回交易信息和生成的合约地址。
     *
     * @param string $abi 合约的ABI接口定义，支持：
     *                   - JSON字符串：'[{"type": "function", "name": "...", ...}]'
     *                   - 必须是有效的JSON格式
     * @param string $bytecode 合约编译后的字节码（十六进制字符串）
     *                        通常由TronStudio或Solidity编译器生成
     *                        格式：'60806040523480156100105760...'
     * @param int $feeLimit 最大费用限制（单位：SUN）
     *                     最大值为1,000,000,000 SUN（1000 TRX）
     *                     设置过低可能导致部署失败
     * @param string $address 部署者地址（Base58格式）
     *                       必须是有效的Tron地址
     *                       该地址需要有足够的TRX支付部署费用
     * @param int $callValue 部署时发送的TRX金额（可选，默认0）
     *                      仅当合约构造函数需要接收TRX时设置
     *                      需与ABI中constructor的payable属性匹配
     * @param int $bandwidthLimit 带宽消耗百分比（可选，默认0，范围0-100）
     *                           用户愿意承担的带宽费用比例
     *                           0表示由合约创建者承担全部费用
     *                           100表示由调用者承担全部费用
     *
     * @return array 部署结果，包含：
     *               - transaction: 交易详情
     *               - [其他区块链返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - fee_limit超过最大限制
     *                      - call_value与payable属性不匹配
     *                      - 地址格式无效
     *                      - 余额不足
     *                      - 字节码格式错误
     *
     * @example
     * $abi = '[{"type":"constructor","inputs":[],"stateMutability":"nonpayable","type":"constructor"}]';
     * $bytecode = '608060405234801561001057600080fd5b50...';
     *
     * $result = $tronWeb->contract->deploy(
     *     $abi,
     *     $bytecode,
     *     1000000000,  // fee limit: 1000 TRX
     *     'TXYZ...',   // deployer address
     *     0,           // call value
     *     100          // bandwidth limit
     * );
     *
     * echo "交易ID: " . $result['transaction']['txID'];
     * echo "合约地址: " . $result['contract_address'];
     */
    public function deploy( string $abi, string $bytecode, int $feeLimit, string $address, int $callValue = 0, int $bandwidthLimit = 0): array
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
            'owner_address' => TronUtils::toHex($address),
            'fee_limit' => $feeLimit,
            'call_value' => $callValue,
            'consume_user_resource_percent' => $bandwidthLimit,
            'abi' => $abi,
            'bytecode' => $bytecode
        ]);
    }

    /**
     * 检查合约构造函数是否可接收TRX支付
     *
     * 内部方法，用于验证合约的payable属性。
     * 部署可支付合约时必须提供callValue。
     *
     * @param string $abi 合约的ABI接口定义（JSON字符串格式）
     *
     * @return bool
     *         - true: 构造函数标记为payable，可以接收TRX
     *         - false: 构造函数不可支付或格式无效
     *
     * @internal 此方法为内部方法，仅供deploy()使用
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
     * 查询智能合约的事件日志
     *
     * 从事件服务器获取指定合约的历史事件记录。
     * 支持按时间、事件名称和区块号进行筛选。
     *
     * 注意：使用此方法需要配置事件服务器（eventServer）。
     *
     * @param string $contractAddress 合约地址（Base58格式）
     *                               要查询事件的智能合约地址
     * @param int $sinceTimestamp 起始时间戳（可选，默认0）
     *                           Unix时间戳（毫秒），0表示不过滤时间
     *                           例如：1680000000000
     * @param string|null $eventName 事件名称筛选（可选）
     *                             仅返回指定名称的事件日志
     *                             例如：'Transfer'、'Approval'
     * @param int $blockNumber 区块号筛选（可选，默认0）
     *                        仅返回指定区块的事件日志
     *                        需要与eventName配合使用
     *
     * @return array 事件日志数组，每个元素包含：
     *               - block: 区块号
     *               - timestamp: 时间戳
     *               - name: 事件名称
     *               - topics: 事件主题（索引参数）
     *               - data: 事件数据（非索引参数）
     *               - transaction: 触发事件的交易ID
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 未配置事件服务器
     *                      - 使用eventName但未提供contractAddress
     *                      - 使用blockNumber但未提供eventName
     *
     * @example
     * // 获取指定合约的所有事件
     * $events = $tronWeb->contract->getEvents('TXYZ...');
     *
     * // 获取指定时间之后的事件
     * $events = $tronWeb->contract->getEvents('TXYZ...', 1680000000000);
     *
     * // 获取指定事件的日志
     * $events = $tronWeb->contract->getEvents('TXYZ...', 0, 'Transfer');
     *
     * @see getEventsByTransaction() 通过交易ID查询事件
     */
    public function getEvents(string $contractAddress, int $sinceTimestamp = 0, ?string $eventName = null, int $blockNumber = 0): array
    {
        if (!$this->tronWeb->isProviderConfigured('eventServer')) {
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
     * 通过交易ID查询该交易触发的事件日志
     *
     * 查询指定交易执行过程中产生的所有事件记录。
     * 适用于追踪特定交易的合约执行结果。
     *
     * 注意：使用此方法需要配置事件服务器（eventServer）。
     *
     * @param string $transactionID 交易ID（32字节十六进制字符串）
     *                             格式：'a1b2c3d4e5f6...'（64个字符）
     *                             通常为getTransaction()或deploy()返回的txID
     *
     * @return array 事件日志数组，每个元素包含：
     *               - block: 区块号
     *               - timestamp: 时间戳
     *               - name: 事件名称
     *               - topics: 事件主题
     *               - data: 事件数据
     *               - contract: 触发事件的合约地址
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 未配置事件服务器
     *                      - 交易ID格式无效
     *                      - 交易不存在或未产生事件
     *
     * @example
     * // 通过交易ID查询事件
     * $txId = 'abc123def456...';
     * $events = $tronWeb->contract->getEventsByTransaction($txId);
     *
     * foreach ($events as $event) {
     *     echo "事件: " . $event['name'];
     *     echo "数据: " . json_encode($event['data']);
     * }
     *
     * @see getEvents() 获取合约的所有事件
     */
    public function getEventsByTransaction(string $transactionID): array
    {
        if (!$this->tronWeb->isProviderConfigured('eventServer')) {
            throw new TronException('No event server configured');
        }

        return $this->request("event/transaction/{$transactionID}", [], 'eventServer');
    }

    /**
     * 查询已部署合约的详细信息
     *
     * 获取智能合约的元数据信息，包括：
     * - ABI接口定义
     * - 字节码
     * - 合约创建者地址
     * - 其他合约属性
     *
     * @param string $contractAddress 合约地址（Base58格式）
     *                               要查询的已部署合约地址
     *
     * @return array 合约详细信息，包含：
     *               - abi: 合约ABI定义
     *               - bytecode: 合约字节码
     *               - origin_address: 合约创建者地址
     *               - contract_address: 合约地址
     *               - name: 合约名称
     *               - version: 编译器版本
     *               - [其他合约元数据字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 地址格式无效
     *                      - 合约不存在或未部署
     *
     * @example
     * // 获取合约详细信息
     * $info = $tronWeb->contract->getInfo('TXYZ...');
     *
     * echo "合约名称: " . $info['name'];
     * echo "创建者: " . $info['origin_address'];
     * echo "字节码长度: " . strlen($info['bytecode']);
     *
     * // 使用ABI创建合约实例
     * $abi = $info['abi'];
     * $contract = $tronWeb->contract($abi, 'TXYZ...');
     */
    public function getInfo(string $contractAddress): array
    {
        return $this->request('wallet/getcontract', [
            'value' => TronUtils::toHex($contractAddress)
        ]);
    }
}