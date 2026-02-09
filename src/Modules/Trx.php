<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Exception;
use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;

/**
 * TRX模块 - Tron网络原生代币操作的核心模块
 *
 * 提供完整的TRX代币操作功能，包括：
 * - TRX转账交易（send/sendTrx）
 * - 余额查询（getBalance/getUnconfirmedBalance）
 * - 区块和交易信息查询
 * - 资源冻结和解冻管理
 * - 消息签名和验证
 * - 多签交易处理
 * - 批量交易操作
 * - 奖励和佣金查询
 *
 * 主要特性：
 * - 支持主网和测试网操作
 * - 支持已确认和未确认交易状态
 * - 支持Base58和Hex格式地址
 * - 提供完整的事务处理流程（构建、签名、广播）
 * - 支持自定义权限和代理操作
 *
 * @package Dsdcr\TronWeb\Modules
 * @since 1.0.0
 */
class Trx extends BaseModule
{

    /**
     * 获取TransactionBuilder实例
     *
     * @return TransactionBuilder
     */
    public function getTransactionBuilder(): \Dsdcr\TronWeb\Modules\TransactionBuilder
    {
        return $this->tronWeb->transactionBuilder;
    }

    /**
     * 查询指定地址的TRX余额
     *
     * 从Solidity节点获取账户的实时余额信息，支持多种场景：
     * - 查询当前账户余额（默认）
     * - 查询指定地址余额
     * - 支持SUN和TRX单位转换
     *
     * @param string|null $address 要查询的地址（Base58格式），如为空则使用当前账户地址
     * @param bool $fromTron 是否从SUN单位转换为TRX单位
     *                      true: 返回TRX单位 (1 TRX = 1,000,000 SUN)
     *                      false: 返回SUN单位（默认）
     *
     * @return float 账户余额数值
     *
     * @throws TronException 当地址格式无效或查询失败时抛出
     *
     * @example
     * // 获取当前账户余额（SUN单位）
     * $balance = $trx->getBalance();
     *
     * // 获取指定地址余额（TRX单位）
     * $balance = $trx->getBalance('TXYZ...', true);
     *
     * // 获取当前账户余额（TRX单位）
     * $balance = $trx->getBalance(null, true);
     */
    public function getBalance(?string $address = null, bool $fromTron = false): float
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        $account = $this->tronWeb->request('walletsolidity/getaccount', [
            'address' => $addressHex
        ]);

        if (!isset($account['balance'])) {
            return 0.0;
        }

        $balance = (int)$account['balance'];
        return $fromTron ? TronUtils::fromSun($balance) : $balance;
    }

    /**
     * 发送TRX交易到指定地址
     *
     * 这是一个核心的TRX转账方法，支持多种调用方式：
     * 1. 使用预设的私钥和地址
     * 2. 动态传入私钥和地址
     * 3. 支持Base58和Hex格式地址
     * 4. 包含消息选项（memo）
     *
     * @param string $to 接收方地址（Base58格式）
     * @param float $amount 转账金额（单位为TRX）
     * @param array $options 选项参数数组，可选包含：
     *   - privateKey: 用于签名的私钥（字符串）
     *   - address: 发送方地址（Base58格式）
     *   - message: 交易附言/memo信息
     *
     * @return array 交易结果，包含：
     *   - result: 交易执行结果（true/false）
     *   - txid: 交易哈希ID
     *   - [其他区块链返回的字段]
     *
     * @throws TronException 当参数无效、地址格式错误、余额不足或交易失败时抛出
     *
     * @example
     * // 使用预设私钥发送
     * $result = $trx->send('TXYZ...', 1.5);
     *
     * // 使用动态私钥发送
     * $result = $trx->send('TXYZ...', 1.0, ['privateKey' => 'your_private_key']);
     *
     * // 发送带消息的交易
     * $result = $trx->send('TXYZ...', 0.5, ['message' => 'payment invoice #123']);
     */
    public function sendTrx(string $to, float $amount, array $options = []): array
    {
        if (is_string($options['privateKey'] ?? null)) {
            // 处理字符串形式的私钥选项
            $options = ['privateKey' => $options['privateKey']];
        }

        if (!TronUtils::isAddress($to)) {
            throw new TronException('Invalid recipient provided');
        }

        $amount = (int)$amount;
        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        $privateKey = $options['privateKey'] ?? $this->tronWeb->getPrivateKey();
        $address = $options['address'] ?? null;

        if (!$privateKey && !$address) {
            throw new TronException('Function requires either a private key or address to be set');
        }

        // 如果只提供了私钥，从中获取地址
        if ($privateKey && !$address) {
            $address = $this->tronWeb->fromPrivateKey($privateKey);
        }

        if (isset($options['privateKey'])) {
            unset($options['privateKey']);
        }

        // 使用TransactionBuilder构建交易
        $transaction = $this->tronWeb->transactionBuilder->sendTrx(
            $to,
            $amount,
            $address,
            $options
        );

        // 签名并广播交易
        $signedTransaction = $this->signTransaction($transaction);
        return $this->sendRawTransaction($signedTransaction);
    }

    /**
     * 对交易进行数字签名
     *
     * 使用当前账户的私钥对交易数据进行签名，生成有效的数字签名。
     * 签名过程使用Secp256k1椭圆曲线加密算法。
     *
     * @param array $transaction 待签名的交易数据数组，必须包含：
     *   - txID: 交易哈希ID
     *   - raw_data: 原始交易数据
     *
     * @return array 签名后的交易数据，包含：
     *   - 原始交易所有字段
     *   - signature: 数字签名数组
     *
     * @throws TronException 当交易已签名、包含错误或私钥未设置时抛出
     *
     * @example
     * 签名交易或消息
     *
     * 支持普通交易签名和多重签名。
     * 如果是多重签名模式，签名会被追加到签名列表中而不是覆盖。
     *
     * @param array $transaction 交易数据
     * @param string|null $privateKey 私钥（可选，默认使用TronWeb实例的私钥）
     * @param bool $multisig 是否多重签名模式（可选，默认false）
     * @return array 签名后的交易
     * @throws TronException
     */
    public function signTransaction(array $transaction, ?string $privateKey = null, bool $multisig = false): array
    {
        if (isset($transaction['Error'])) {
            throw new TronException($transaction['Error']);
        }

        // 非多重签名模式下，检查交易是否已签名
        if (!$multisig && isset($transaction['signature'])) {
            throw new TronException('Transaction is already signed');
        }

        // 获取私钥
        $privateKey = $privateKey ?: $this->tronWeb->getPrivateKey();
        if (!$privateKey) {
            throw new TronException('No private key provided');
        }

        // 非多重签名模式下，验证私钥是否与交易中的地址匹配
        if (!$multisig) {
            $address = strtolower($this->tronWeb->fromPrivateKey($privateKey));
            $ownerAddress = $transaction['raw_data']['contract'][0]['parameter']['value']['owner_address'] ?? null;

            if ($ownerAddress) {
                // 处理地址格式（可能有0x前缀）
                $ownerAddress = strtolower(preg_replace('/^0x/', '', $ownerAddress));
                if ($address !== $ownerAddress) {
                    throw new TronException('Private key does not match address in transaction');
                }
            }
        }

        // 生成签名
        $signature = \Dsdcr\TronWeb\Support\Secp::sign($transaction['txID'], $privateKey);

        // 根据是否是多重签名模式处理签名
        if ($multisig && isset($transaction['signature'])) {
            // 多重签名模式：追加签名
            $transaction['signature'][] = $signature;
        } else {
            // 普通模式：设置签名
            $transaction['signature'] = [$signature];
        }

        return $transaction;
    }

    /**
     * 广播已签名的交易到Tron网络
     *
     * 将已完成签名的交易提交到Tron网络进行广播和执行。
     * 这是交易生命周期的最后一步，交易将被包含在下一个区块中。
     *
     * @param array $signedTransaction 已签名的交易数据，必须包含：
     *   - signature: 有效的数字签名数组
     *   - txID: 交易哈希ID
     *   - raw_data: 原始交易数据
     *
     * @return array 广播结果，包含：
     *   - result: 广播是否成功（true/false）
     *   - txid: 交易哈希ID
     *   - [其他区块链返回的状态字段]
     *
     * @throws TronException 当交易未签名、签名无效或网络广播失败时抛出
     *
     * @example
     * // 构建、签名并广播交易
     * $transaction = $builder->createTransaction(...);
     * $signedTx = $trx->signTransaction($transaction);
     * $result = $trx->sendRawTransaction($signedTx);
     *
     * @see signTransaction() 交易签名方法
     * @see sendTrx() 完整的发送交易流程
     */
    public function sendRawTransaction(array $signedTransaction, array $options = []): array
    {
        if (!isset($signedTransaction['signature']) || !is_array($signedTransaction['signature'])) {
            throw new TronException('Transaction is not signed');
        }

        // 合并选项
        if (!empty($options)) {
            $signedTransaction = array_merge($signedTransaction, $options);
        }

        return $this->tronWeb->request('wallet/broadcasttransaction', $signedTransaction);
    }

    /**
     * 获取当前最新区块的完整信息
     *
     * 从全节点获取网络中最新确认的区块数据，包含：
     * - 区块头信息（版本、时间戳、父哈希等）
     * - 交易列表
     * - 区块生产见证人信息
     * - 其他区块链元数据
     *
     * @return array 当前区块的完整数据结构，主要包含：
     *   - blockID: 区块哈希ID
     *   - block_header: 区块头信息
     *   - transactions: 交易列表数组
     *
     * @throws TronException 当网络请求失败或返回数据格式错误时抛出
     *
     * @example
     * // 获取最新区块信息
     * $block = $trx->getCurrentBlock();
     * echo "区块高度: " . $block['block_header']['raw_data']['number'];
     * echo "交易数量: " . count($block['transactions']);
     *
     * @see getBlock() 获取指定区块信息
     * @see getBlockByNumber() 通过区块号获取区块
     */
    public function getCurrentBlock(): array
    {
        return $this->tronWeb->request('wallet/getnowblock');
    }

    /**
     * 获取指定区块的完整信息
     *
     * 支持多种区块标识符格式：
     * - 'earliest': 获取创世区块（区块号0）
     * - 'latest': 获取最新区块（等同于getCurrentBlock）
     * - 数字: 获取指定区块号的区块
     * - 哈希: 获取指定区块哈希的区块
     * - false: 使用默认区块标识符（通常为'latest'）
     *
     * @param mixed $block 区块标识符，支持：
     *   - 'earliest': 创世区块
     *   - 'latest': 最新区块
     *   - number: 区块号（整数）
     *   - string: 区块哈希（16进制）
     *   - false: 使用默认配置
     *
     * @return array 区块完整信息，包含：
     *   - blockID: 区块哈希ID
     *   - block_header: 区块头信息（版本、时间戳、见证人等）
     *   - transactions: 区块包含的交易列表
     *
     * @throws TronException 当区块标识符无效或区块不存在时抛出
     *
     * @example
     * // 获取最新区块
     * $block = $trx->getBlock('latest');
     *
     * // 获取指定区块号的区块
     * $block = $trx->getBlock(123456);
     *
     * // 获取创世区块
     * $block = $trx->getBlock('earliest');
     *
     * @see getCurrentBlock() 获取最新区块
     * @see getBlockByNumber() 通过区块号获取区块
     * @see getBlockByHash() 通过区块哈希获取区块
     */
    public function getBlock(mixed $block = false): array
    {
        if ($block === false) {
            $block = $this->tronWeb->defaultBlock ?? 'latest';
        }

        if ($block === 'earliest') {
            return $this->getBlockByNumber(0);
        }

        if ($block === 'latest') {
            return $this->getCurrentBlock();
        }

        if (is_numeric($block)) {
            return $this->getBlockByNumber((int)$block);
        }

        if (TronUtils::isHex($block)) {
            return $this->getBlockByHash($block);
        }

        throw new TronException('Invalid block identifier provided');
    }

    /**
     * getBlockByHash方法
     *
     * @param string $blockHash 区块哈希
     * @return array 区块信息
     * @throws TronException
     */
    public function getBlockByHash(string $blockHash): array
    {
        $block = $this->tronWeb->request('wallet/getblockbyid', [
            'value' => $blockHash
        ]);

        if (empty($block) || !isset($block['block_header'])) {
            throw new TronException('Block not found');
        }

        return $block;
    }

    /**
     * getBlockByNumber方法
     *
     * @param int $blockID 区块号
     * @return array 区块信息
     * @throws TronException
     */
    public function getBlockByNumber(int $blockID): array
    {
        if ($blockID < 0) {
            throw new TronException('Invalid block number provided');
        }

        $block = $this->tronWeb->request('wallet/getblockbynum', [
            'num' => $blockID
        ]);

        if (empty($block) || !isset($block['block_header'])) {
            throw new TronException('Block not found');
        }

        return $block;
    }

    /**
     * 通过交易哈希ID获取交易详情
     *
     * 从全节点查询指定交易ID的完整信息，包含：
     * - 交易原始数据（发送方、接收方、金额等）
     * - 交易签名信息
     * - 交易状态和确认信息
     * - 合约调用详情（如适用）
     *
     * @param string $transactionID 交易哈希ID（64字符十六进制字符串）
     *
     * @return array 交易详情数组，包含：
     *   - ret: 交易执行结果数组
     *   - signature: 签名列表
     *   - txID: 交易ID
     *   - raw_data: 原始交易数据
     *   - raw_data_hex: 原始数据十六进制
     *
     * @throws TronException 当交易ID格式错误或交易不存在时抛出
     *
     * @example
     * // 获取交易详情
     * $transaction = $trx->getTransaction('abc123...');
     * echo "发送方: " . $transaction['raw_data']['contract'][0]['parameter']['value']['owner_address'];
     *
     * @see getTransactionInfo() 获取交易执行信息
     * @see getConfirmedTransaction() 获取已确认的交易信息
     */
    public function getTransaction(string $transactionID): array
    {
        return $this->tronWeb->request('wallet/gettransactionbyid', [
            'value' => $transactionID
        ]);
    }

    /**
     * 获取交易的执行信息和状态详情
     *
     * 与getTransaction()不同，此方法返回交易的执行结果信息，包括：
     * - 交易执行结果（成功/失败）
     * - 消耗的资源信息（带宽、能量）
     * - 合约执行结果（如适用）
     * - 交易费用信息
     *
     * @param string $transactionID 交易哈希ID（64字符十六进制字符串）
     *
     * @return array 交易执行信息，包含：
     *   - id: 交易ID
     *   - fee: 交易费用（SUN）
     *   - blockNumber: 所在区块号
     *   - blockTimeStamp: 区块时间戳
     *   - contractResult: 合约执行结果
     *   - receipt: 交易收据信息
     *   - log: 事件日志（如适用）
     *
     * @throws TronException 当交易ID格式错误或交易不存在时抛出
     *
     * @example
     * // 获取交易执行信息
     * $info = $trx->getTransactionInfo('abc123...');
     * echo "交易费用: " . $info['fee'] . " SUN";
     * echo "执行结果: " . ($info['receipt']['result'] === 'SUCCESS' ? '成功' : '失败');
     *
     * @see getTransaction() 获取交易原始数据
     * @see getConfirmedTransaction() 获取已确认的交易信息
     */
    public function getTransactionInfo(string $transactionID): array
    {
        return $this->tronWeb->request('wallet/gettransactioninfobyid', [
            'value' => $transactionID
        ]);
    }

    /**
     * 获取已确认的交易信息
     *
     * @param string $transactionID 交易ID
     * @return array 已确认的交易信息
     * @throws TronException
     */
    public function getConfirmedTransaction(string $transactionID): array
    {
        return $this->tronWeb->request('walletsolidity/gettransactionbyid', [
            'value' => $transactionID
        ], 'post');
    }

    /**
     * 获取未确认的账户信息
     *
     * @param string|null $address 地址
     * @return array 未确认的账户信息
     * @throws TronException
     */
    public function getUnconfirmedAccount(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        if (!TronUtils::isAddress(TronUtils::fromHex($addressHex))) {
            throw new TronException('Invalid address provided');
        }

        return $this->tronWeb->request('wallet/getaccount', [
            'address' => $addressHex
        ]);
    }

    /**
     * 获取未确认的余额
     *
     * @param string|null $address 地址
     * @return float 未确认的余额
     * @throws TronException
     */
    public function getUnconfirmedBalance(?string $address = null): float
    {
        $account = $this->getUnconfirmedAccount($address);
        return (float)($account['balance'] ?? 0);
    }

    /**
     * 获取带宽信息
     *
     * @param string|null $address 地址
     * @return array 带宽信息
     * @throws TronException
     */
    public function getBandwidth(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        if (!TronUtils::isAddress(TronUtils::fromHex($addressHex))) {
            throw new TronException('Invalid address provided');
        }

        $account = $this->getAccount($addressHex);

        return [
            'free_bandwidth' => $account['free_net_usage'] ?? 0,
            'bandwidth_limit' => $account['net_limit'] ?? 0,
            'bandwidth_used' => $account['net_usage'] ?? 0,
            'bandwidth_percentage' => $account['net_limit'] > 0 ? ($account['net_usage'] / $account['net_limit']) * 100 : 0
        ];
    }

    /**
     * getTransactionsFromBlock方法
     *
     * @param mixed $block 区块标识符
     * @return array 交易列表
     * @throws TronException
     */
    public function getTransactionsFromBlock(mixed $block = false): array
    {
        $blockData = $this->getBlock($block);
        return $blockData['transactions'] ?? [];
    }

    /**
     * getTransactionFromBlock方法
     *
     * @param mixed $block 区块标识符
     * @param int $index 交易索引
     * @return array 交易信息
     * @throws TronException
     */
    public function getTransactionFromBlock(mixed $block = false, int $index = 0): array
    {
        $blockData = $this->getBlock($block);

        $transactions = $blockData['transactions'] ?? [];

        if (!isset($transactions[$index])) {
            throw new TronException('Transaction not found at index ' . $index);
        }

        return $transactions[$index];
    }

    /**
     * 获取区块范围内的交易
     *
     * @param int $start 起始区块号
     * @param int $end 结束区块号
     * @return array 区块列表
     * @throws TronException
     */
    public function getBlockRange(int $start = 0, int $end = 30): array
    {
        if ($start < 0 || $end < $start) {
            throw new TronException('Invalid block range');
        }

        $blocks = [];
        for ($i = $start; $i <= $end; $i++) {
            $blocks[] = $this->getBlockByNumber($i);
        }

        return $blocks;
    }

    /**
     * 获取最新区块
     *
     * @param int $limit 要检索的区块数量
     * @return array 区块列表
     * @throws TronException
     */
    public function getLatestBlocks(int $limit = 1): array
    {
        if ($limit <= 0) {
            throw new TronException('Limit must be greater than 0');
        }

        $currentBlock = $this->getCurrentBlock();
        $latestBlockNum = $currentBlock['block_header']['raw_data']['number'];

        $blocks = [];
        for ($i = 0; $i < $limit; $i++) {
            $blocks[] = $this->getBlockByNumber($latestBlockNum - $i);
        }

        return $blocks;
    }

    /**
     * getBlockTransactionCount方法
     *
     * @param mixed $block 区块标识符
     * @return int 交易数量
     * @throws TronException
     */
    public function getBlockTransactionCount(mixed $block = false): int
    {
        $blockData = $this->getBlock($block);
        return count($blockData['transactions'] ?? []);
    }

    /**
     * 通过十六进制地址获取账户信息
     *
     * @param string $address 地址
     * @return array 账户信息
     * @throws TronException
     */
    public function getAccount(string $address): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];
        return $this->tronWeb->request('walletsolidity/getaccount', [
            'address' => $addressHex
        ]);
    }

    /**
     * 多签交易方法
     *
     * 使用本地签名方式进行多重签名。
     * 支持权限验证、签名追加、重复签名检测等功能。
     *
     * @param array $transaction 交易数据
     * @param string|null $privateKey 私钥（可选，默认使用TronWeb实例的私钥）
     * @param int $permissionId 权限ID（可选，默认0表示Owner权限）
     * @return array 签名后的交易
     * @throws TronException
     */
    public function multiSign(array $transaction, ?string $privateKey = null, int $permissionId = 0): array
    {
        $privateKey = $privateKey ?: $this->tronWeb->getPrivateKey();
        if (!$privateKey) {
            throw new TronException('No private key provided for multi-signature');
        }

        // 验证交易格式
        if (!isset($transaction['raw_data']) || !isset($transaction['raw_data']['contract'])) {
            throw new TronException('Invalid transaction provided');
        }

        // 只有在交易中没有权限ID且用户提供了权限ID时才进行完整权限验证
        if (!isset($transaction['raw_data']['contract'][0]['Permission_id']) && $permissionId > 0) {
            // 设置权限ID
            $transaction['raw_data']['contract'][0]['Permission_id'] = $permissionId;

            // 检查私钥是否在权限列表中（通过getSignWeight验证）
            $address = strtolower($this->tronWeb->fromPrivateKey($privateKey));
            $signWeight = $this->getSignWeight($transaction, ['permissionId' => $permissionId]);

            if (isset($signWeight['result']['code']) && $signWeight['result']['code'] === 'PERMISSION_ERROR') {
                throw new TronException($signWeight['result']['message'] ?? 'Permission error');
            }

            // 验证私钥是否在权限列表中
            $foundKey = false;
            if (isset($signWeight['permission']['keys'])) {
                foreach ($signWeight['permission']['keys'] as $key) {
                    if (isset($key['address']) && strtolower($key['address']) === $address) {
                        $foundKey = true;
                        break;
                    }
                }
            }

            if (!$foundKey) {
                throw new TronException('Private key has no permission to sign');
            }

            // 检查是否已签名
            if (isset($signWeight['approved_list']) && in_array($address, $signWeight['approved_list'])) {
                throw new TronException('Private key already signed transaction');
            }

            // 重置交易（如果API返回了更新后的交易）
            if (isset($signWeight['transaction']['transaction'])) {
                $transaction = $signWeight['transaction']['transaction'];
                if ($permissionId > 0) {
                    $transaction['raw_data']['contract'][0]['Permission_id'] = $permissionId;
                }
            }
        }

        // 使用本地签名（多重签名模式，跳过地址验证）
        return $this->signTransaction($transaction, $privateKey, true);
    }


    /**
     * 获取交易的签名权重
     *
     * @param array $transaction 交易数据
     * @param array $options 选项
     * @return array 签名权重信息
     * @throws TronException
     */
    public function getSignWeight(array $transaction, array $options = []): array
    {
        if (!isset($transaction['raw_data']) || !isset($transaction['raw_data']['contract'])) {
            throw new TronException('Invalid transaction provided');
        }

        $permissionId = $options['permissionId'] ?? null;

        // 设置权限ID
        if (is_int($permissionId)) {
            $transaction['raw_data']['contract'][0]['Permission_id'] = (int)$permissionId;
        } elseif (!isset($transaction['raw_data']['contract'][0]['Permission_id']) ||
                 !is_int($transaction['raw_data']['contract'][0]['Permission_id'])) {
            $transaction['raw_data']['contract'][0]['Permission_id'] = 0;
        }

        return $this->tronWeb->request('wallet/getsignweight', $transaction, 'post');
    }

    /**
     * 获取交易的批准列表
     *
     * @param array $transaction 交易数据
     * @param array $options 选项
     * @return array 批准列表
     * @throws TronException
     */
    public function getApprovedList(array $transaction, array $options = []): array
    {
        return $this->tronWeb->request('wallet/getapprovedlist', [
            'transaction' => $transaction
        ], 'post');
    }

    /**
     * 获取委托资源信息
     *
     * @param string|null $fromAddress 委托方地址
     * @param string|null $toAddress 接收方地址
     * @param array $options 选项
     * @return array 委托资源信息
     * @throws TronException
     */
    public function getDelegatedResource(?string $fromAddress = null, ?string $toAddress = null, array $options = []): array
    {
        $fromAddressHex = $fromAddress ? TronUtils::toHex($fromAddress) : $this->tronWeb->getAddress()['hex'];
        $params = ['fromAddress' => $fromAddressHex];

        if ($toAddress) {
            $params['toAddress'] = TronUtils::toHex($toAddress);
        }

        return $this->tronWeb->request('wallet/getdelegatedresource', $params);
    }

    /**
     * 获取链参数
     *
     * @return array 链参数
     * @throws TronException
     */
    public function getChainParameters(): array
    {
        return $this->tronWeb->request('wallet/getchainparameters');
    }

    /**
     * 获取奖励信息
     *
     * @param string|null $address 地址
     * @return array 奖励信息
     * @throws TronException
     */
    public function getRewardInfo(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        return $this->tronWeb->request('walletsolidity/getreward', [
            'address' => $addressHex
        ]);
    }

    /**
     * 一次性发送TRX给多个接收者
     *
     * @param array $recipients [地址, 金额]对的数组
     * @param string|null $from 发送者地址
     * @param bool $validate 发送前验证接收者地址
     * @return array 发送结果
     * @throws TronException
     */
    public function sendToMultiple(array $recipients, ?string $from = null, bool $validate = false): array
    {
        if (count($recipients) > 10) {
            throw new TronException('Maximum 10 recipients allowed per batch');
        }

        $results = [];
        $fromHex = $from ? TronUtils::toHex($from) : $this->tronWeb->getAddress()['hex'];

        foreach ($recipients as $index => $recipient) {
            if (!is_array($recipient) || count($recipient) !== 2) {
                throw new TronException("Recipient {$index} must be an array [address, amount]");
            }

            [$toAddress, $amount] = $recipient;

            if (!is_string($toAddress)) {
                throw new TronException("Recipient {$index} address must be a string");
            }

            if (!is_numeric($amount) || $amount <= 0) {
                throw new TronException("Recipient {$index} amount must be a positive number");
            }

            if ($validate && !TronUtils::isAddress($toAddress)) {
                throw new TronException("Recipient {$index} invalid address: {$toAddress}");
            }

            try {
                $result = $this->sendTrx($toAddress, (float)$amount, $fromHex);
                $results[] = [
                    'recipient' => $toAddress,
                    'amount' => $amount,
                    'success' => true,
                    'transaction_id' => $result['txid'] ?? null,
                    'result' => $result
                ];
            } catch (Exception $e) {
                $results[] = [
                    'recipient' => $toAddress,
                    'amount' => $amount,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * 获取奖励信息（已确认）
     *
     * @param string|null $address 地址
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 奖励信息
     * @throws TronException
     */
    public function getReward(?string $address = null, array $options = ['confirmed' => true]): array
    {
        $options['confirmed'] = true;
        return $this->_getReward($address, $options);
    }

    /**
     * 获取未确认的奖励信息
     *
     * @param string|null $address 地址
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 未确认的奖励信息
     * @throws TronException
     */
    public function getUnconfirmedReward(?string $address = null, array $options = ['confirmed' => true]): array
    {
        $options['confirmed'] = false;
        return $this->_getReward($address, $options);
    }

    /**
     * 获取佣金信息（已确认）
     *
     * @param string|null $address 地址
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 佣金信息
     * @throws TronException
     */
    public function getBrokerage(?string $address = null, array $options = ['confirmed' => true]): array
    {
        $options['confirmed'] = true;
        return $this->_getBrokerage($address, $options);
    }

    /**
     * 获取未确认的佣金信息
     *
     * @param string|null $address 地址
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 未确认的佣金信息
     * @throws TronException
     */
    public function getUnconfirmedBrokerage(?string $address = null, array $options = ['confirmed' => true]): array
    {
        $options['confirmed'] = false;
        return $this->_getBrokerage($address, $options);
    }

    /**
     * 内部方法：获取奖励信息
     *
     * @param string|null $address 地址
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 奖励信息
     * @throws TronException
     */
    protected function _getReward(?string $address = null, array $options = ['confirmed' => true]): array
    {
        $addr = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        if (!TronUtils::isAddress(TronUtils::fromHex($addr))) {
            throw new TronException('Invalid address provided');
        }

        $providerType = $options['confirmed'] ? 'solidityNode' : 'fullNode';
        $endpointPrefix = $options['confirmed'] ? 'walletsolidity' : 'wallet';

        $data = ['address' => $addr];
        $result = $this->tronWeb->request("{$endpointPrefix}/getReward", $data, $providerType);

        if (!isset($result['reward'])) {
            throw new TronException('Reward not found');
        }

        return [
            'reward' => $result['reward'],
            'address' => $addr,
            'confirmed' => $options['confirmed']
        ];
    }

    /**
     * 内部方法：获取佣金信息
     *
     * @param string|null $address 地址
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 佣金信息
     * @throws TronException
     */
    protected function _getBrokerage(?string $address = null, array $options = ['confirmed' => true]): array
    {
        $addr = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        if (!TronUtils::isAddress(TronUtils::fromHex($addr))) {
            throw new TronException('Invalid address provided');
        }

        $providerType = $options['confirmed'] ? 'solidityNode' : 'fullNode';
        $endpointPrefix = $options['confirmed'] ? 'walletsolidity' : 'wallet';

        $data = ['address' => $addr];
        $result = $this->tronWeb->request("{$endpointPrefix}/getBrokerage", $data, $providerType);

        if (!isset($result['brokerage'])) {
            throw new TronException('Brokerage not found');
        }

        return [
            'brokerage' => $result['brokerage'],
            'address' => $addr,
            'confirmed' => $options['confirmed']
        ];
    }

    /**
     * 获取节点信息
     * 返回详细的TRON节点运行状态、配置和机器信息
     *
     * @return array 节点信息，包含：
     *   - beginSyncNum: 开始同步块号
     *   - block: 当前块哈希
     /**
     * 获取当前区块参考参数
     * 用于智能合约交易的区块参数参考
     *
     * @return array 区块参考参数，包含：
     *   - ref_block_bytes: 参考区块字节（16进制字符串）
     *   - ref_block_hash: 参考区块哈希（截取部分）
     *   - expiration: 交易过期时间戳（毫秒）
     *   - timestamp: 当前时间戳（毫秒）
     * @throws TronException
     */
    public function getCurrentRefBlockParams(): array
    {
        try {
            $block = $this->tronWeb->request('wallet/getblock', ['detail' => false], 'post');

            if (!isset($block['block_header']['raw_data']['number']) ||
                !isset($block['block_header']['raw_data']['timestamp']) ||
                !isset($block['blockID'])) {
                throw new TronException('Invalid block data received');
            }

            $blockNumber = $block['block_header']['raw_data']['number'];
            $timestamp = $block['block_header']['raw_data']['timestamp'];
            $blockID = $block['blockID'];

            // 转换区块号为16进制并取最后4个字符，左补0到4位
            $refBlockBytes = substr(str_pad(dechex($blockNumber), 8, '0', STR_PAD_LEFT), -4);
            $refBlockBytes = str_pad($refBlockBytes, 4, '0', STR_PAD_LEFT);

            // 取区块哈希的第16-32个字符（从0开始计数）
            $refBlockHash = substr($blockID, 16, 16);

            return [
                'ref_block_bytes' => $refBlockBytes,
                'ref_block_hash' => $refBlockHash,
                'expiration' => $timestamp + 60 * 1000, // 60秒过期
                'timestamp' => $timestamp
            ];
        } catch (TronException $e) {
            throw new TronException('Unable to get block reference parameters: ' . $e->getMessage());
        }
    }

    /**
     * 获取节点信息
     * 返回详细的TRON节点运行状态、配置和机器信息
     *
     * @return array 节点信息，包含：
     *   - beginSyncNum: 开始同步块号
     *   - block: 当前块哈希
     *   - solidityBlock: Solidity节点当前块
     *   - currentConnectCount: 当前连接数
     *   - activeConnectCount: 主动连接数
     *   - passiveConnectCount: 被动连接数
     *   - totalFlow: 总流量
     *   - peerInfoList: 对等节点信息列表
     *   - configNodeInfo: 节点配置信息
     *   - machineInfo: 机器运行信息
     * @throws TronException
     */
    public function getNodeInfo(): array
    {
        return $this->tronWeb->request('wallet/getnodeinfo', [], 'post');
    }

    /**
     * 验证消息签名的有效性
     *
     * @param array|string $message 消息内容（必须为十六进制格式，如"0x..."）
     * @param string $signature 待验证的签名（Hex格式）
     * @param string|null $address 签名者地址（Base58格式，可选，默认使用当前账户地址）
     * @param bool $useTronHeader 是否使用TRON消息头（默认true，用于兼容性）
     * @return bool 签名是否有效且匹配
     * @throws TronException
     */
    public function verifyMessage(array|string $message, string $signature, ?string $address = null, bool $useTronHeader = true): bool
    {
        if (!preg_match('/^0x[a-fA-F0-9]+$/i', $message)) {
            throw new TronException('Expected hex message input');
        }

        $address = $address ?? $this->tronWeb->getAddress()['base58'] ?? null;
        if (!$address) {
            throw new TronException('Address is required');
        }

        // 验证地址格式
        if (!TronUtils::isAddress($address)) {
            throw new TronException('Invalid address provided');
        }

        $result = \Dsdcr\TronWeb\Support\Message::verifyMessageWithAddress(
            $message, $signature, $address, $useTronHeader
        );

        if (!$result) {
            throw new TronException('Signature does not match');
        }

        return true;
    }

    /**
     * 验证消息签名
     *
     * @param string $messageHex 十六进制格式的消息内容
     * @param string $address 签名者地址（Base58格式）
     * @param string $signature 待验证的签名（Hex格式）
     * @param bool $useTronHeader 是否使用TRON消息头（默认true）
     * @return bool 签名是否有效且匹配指定地址
     */
    public function verifySignature(string $messageHex, string $address, string $signature, bool $useTronHeader = true): bool
    {
        return \Dsdcr\TronWeb\Support\Message::verifySignature(
            $messageHex, $address, $signature, $useTronHeader
        );
    }

    /**
     * 验证消息签名并恢复签名者地址（V2版本）
     *
     * @param array|string|int[] $message 消息内容（支持十六进制字符串、字节数组或数字数组）
     * @param string $signature 签名（Hex格式）
     * @return string 从签名中恢复出的签名者地址（Base58格式）
     * @throws TronException
     */
    public function verifyMessageV2(array|string $message, string $signature): string
    {
        return \Dsdcr\TronWeb\Support\Message::verifyMessage($message, $signature);
    }

    /**
     * 签名消息方法
     *
     * @param string $message 要签名的消息
     * @param string|null $privateKey 私钥
     * @param bool $useTronHeader 是否使用TRON消息头
     * @return string 签名结果
     * @throws TronException
     */
    public function signMessage(string $message, ?string $privateKey = null, bool $useTronHeader = true): string
    {
        $privateKey = $privateKey ?: $this->tronWeb->getPrivateKey();
        return \Dsdcr\TronWeb\Support\Message::signMessage($message, $privateKey, $useTronHeader);
    }

    /**
     * getAccountResources方法
     *
     * @param string|null $address 地址
     * @return array 账户资源信息
     * @throws TronException
     */
    public function getAccountResources(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        if (!TronUtils::isAddress(TronUtils::fromHex($addressHex))) {
            throw new TronException('Invalid address provided');
        }

        $response = $this->tronWeb->request('wallet/getaccountresource', [
            'address' => $addressHex
        ], 'post');
        return $response;
        return [
            'energy_limit' => $response['EnergyLimit'] ?? 0,
            'energy_used' => $response['EnergyUsed'] ?? 0,
            'free_net_limit' => $response['freeNetLimit'] ?? 0,
            'free_net_used' => $response['freeNetUsed'] ?? 0,
            'net_limit' => $response['NetLimit'] ?? 0,
            'net_used' => $response['NetUsed'] ?? 0,
            'asset_net_used' => $response['AssetNetUsed'] ?? [],
            'asset_net_limit' => $response['AssetNetLimit'] ?? [],
            'total_net_limit' => $response['TotalNetLimit'] ?? 0,
            'total_net_weight' => $response['TotalNetWeight'] ?? 0,
            'total_energy_limit' => $response['TotalEnergyLimit'] ?? 0,
            'total_energy_weight' => $response['TotalEnergyWeight'] ?? 0
        ];
    }

    /**
     * freezeBalance方法
     *
     * @param int $amount 冻结金额（TRX）
     * @param int $duration 冻结时长（天），最小3天
     * @param string $resource 资源类型：'BANDWIDTH' 或 'ENERGY'
     * @param array $options 选项参数（可包含privateKey或address）
     * @param string|null $receiverAddress 接收地址（可选，用于代理冻结）
     * @return array 交易结果
     * @throws TronException
     */
    public function freezeBalance(int $amount = 0, int $duration = 3, string $resource = 'BANDWIDTH', array $options = [], ?string $receiverAddress = null): array
    {
        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'], true)) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        if ($amount <= 0) {
            throw new TronException('Invalid amount provided: Amount must be greater than 0');
        }

        if ($duration < 3) {
            throw new TronException('Invalid duration provided, minimum of 3 days');
        }

        $privateKey = $options['privateKey'] ?? $this->tronWeb->getPrivateKey();
        $address = $options['address'] ?? null;

        if (!$privateKey && !$address) {
            throw new TronException('Function requires either a private key or address to be set');
        }

        // 如果只提供了私钥，从中获取地址
        if ($privateKey && !$address) {
            $address = $this->tronWeb->fromPrivateKey($privateKey);
        }

        // 如果只提供了地址，确保是十六进制格式
        if ($address && !TronUtils::isHex($address)) {
            $address = TronUtils::toHex($address);
        }

        $transaction = $this->tronWeb->transactionBuilder->freezeBalance(
            $amount,
            $duration,
            $resource,
            $address,
            $receiverAddress ? TronUtils::toHex($receiverAddress) : null
        );

        // 签名并广播交易
        $signedTransaction = $this->signTransaction($transaction);
        return $this->sendRawTransaction($signedTransaction);
    }

    /**
     * unfreezeBalance方法
     *
     * 解冻已过最小冻结时长的TRX，解冻将移除带宽和TRON Power
     *
     * @param string $resource 资源类型：'BANDWIDTH' 或 'ENERGY'
     * @param array $options 选项参数（可包含privateKey或address）
     * @param string|null $receiverAddress 接收地址（可选，用于代理解冻）
     * @return array 交易结果
     * @throws TronException
     */
    public function unfreezeBalance(string $resource = 'BANDWIDTH', array $options = [], ?string $receiverAddress = null): array
    {
        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'], true)) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        $privateKey = $options['privateKey'] ?? $this->tronWeb->getPrivateKey();
        $address = $options['address'] ?? null;

        if (!$privateKey && !$address) {
            throw new TronException('Function requires either a private key or address to be set');
        }

        // 如果只提供了私钥，从中获取地址
        if ($privateKey && !$address) {
            $address = $this->tronWeb->fromPrivateKey($privateKey);
        }

        // 如果只提供了地址，确保是十六进制格式
        if ($address && !TronUtils::isHex($address)) {
            $address = TronUtils::toHex($address);
        }

        $transaction = $this->tronWeb->transactionBuilder->unfreezeBalance(
            $resource,
            $address,
            $receiverAddress ? TronUtils::toHex($receiverAddress) : null
        );

        // 签名并广播交易
        $signedTransaction = $this->signTransaction($transaction);
        return $this->sendRawTransaction($signedTransaction);
    }

    /**
     * getTokenFromID方法
     *
     * @param mixed $tokenID 代币ID或名称
     * @return array 代币信息
     * @throws TronException
     */
    public function getTokenFromID(mixed $tokenID): array
    {
        if (is_int($tokenID)) {
            $tokenID = (string)$tokenID;
        }

        if (!is_string($tokenID) || empty($tokenID)) {
            throw new TronException('Invalid token ID provided');
        }

        $response = $this->tronWeb->request('wallet/getassetissuebyname', [
            'value' => TronUtils::toUtf8($tokenID)
        ], 'post');

        // 检查代币是否存在
        if (!isset($response['name'])) {
            throw new TronException('Token does not exist');
        }

        return $this->parseToken($response);
    }

    /**
     * 解析代币信息
     *
     * @param array $tokenData 原始代币数据
     * @return array 解析后的代币信息
     */
    private function parseToken(array $tokenData): array
    {
        return [
            'id' => $tokenData['id'] ?? null,
            'owner_address' => $tokenData['owner_address'] ?? null,
            'name' => TronUtils::fromUtf8($tokenData['name'] ?? ''),
            'abbr' => TronUtils::fromUtf8($tokenData['abbr'] ?? ''),
            'description' => TronUtils::fromUtf8($tokenData['description'] ?? ''),
            'url' => TronUtils::fromUtf8($tokenData['url'] ?? ''),
            'total_supply' => $tokenData['total_supply'] ?? 0,
            'precision' => $tokenData['precision'] ?? 0,
            'num' => $tokenData['num'] ?? 0,
            'start_time' => $tokenData['start_time'] ?? 0,
            'end_time' => $tokenData['end_time'] ?? 0,
            'vote_score' => $tokenData['vote_score'] ?? 0,
            'frozen_supply' => $tokenData['frozen_supply'] ?? [],
            'trx_num' => $tokenData['trx_num'] ?? 0,
            'asset_issue_contract' => $tokenData['asset_issue_contract'] ?? null
        ];
    }
}