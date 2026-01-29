<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Exception;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\Support\Ethabi;

/**
 * TRX模块 - 处理TRX交易和查询
 *
 * @package Dsdcr\TronWeb\Modules
 */
class Trx extends BaseModule
{
    /**
     * 获取交易构建器实例（已废弃，保留向后兼容）
     *
     * @return void
     * @throws TronException
     */
    public function getTransactionBuilder(): void
    {
        throw new TronException('TransactionBuilder has been deprecated. Please use direct module methods instead.');
    }

    /**
     * 获取地址余额
     *
     * @param string|null $address 地址
     * @param bool $fromTron 是否从SUN转换为TRX
     * @return float 余额
     * @throws TronException
     */
    public function getBalance(?string $address = null, bool $fromTron = false): float
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        $account = $this->request('walletsolidity/getaccount', [
            'address' => $addressHex
        ]);

        if (!isset($account['balance'])) {
            return 0.0;
        }

        $balance = (int)$account['balance'];
        return $fromTron ? TronUtils::fromSun($balance) : $balance;
    }

    /**
     * 发送TRX到另一个地址
     *
     * @param string $to 接收地址
     * @param float $amount 金额
     * @param string|null $from 发送地址
     * @param string|null $message 附加消息
     * @return array 交易结果
     * @throws TronException
     */
    public function send(string $to, float $amount, ?string $from = null, ?string $message = null): array
    {
        $from = $from ? TronUtils::addressToHex($from) : $this->getDefaultAddress()['hex'];
        $to = TronUtils::addressToHex($to);

        if ($from === $to) {
            throw new TronException('Cannot transfer TRX to the same account');
        }

        $options = [
            'to_address' => $to,
            'owner_address' => $from,
            'amount' => TronUtils::toSun($amount),
        ];

        if (!is_null($message)) {
            $options['extra_data'] = TronUtils::toUtf8($message);
        }

        $transaction = $this->getTronWeb()->request('wallet/createtransaction', $options);
        $signedTransaction = $this->signTransaction($transaction);

        return $this->sendRawTransaction($signedTransaction);
    }

    /**
     * 签名交易
     *
     * @param array $transaction 交易数据
     * @return array 签名后的交易
     * @throws TronException
     */
    public function signTransaction(array $transaction): array
    {
        if (isset($transaction['Error'])) {
            throw new TronException($transaction['Error']);
        }

        if (isset($transaction['signature'])) {
            throw new TronException('Transaction is already signed');
        }

        $privateKey = $this->getPrivateKey();
        $signature = \Dsdcr\TronWeb\Support\Secp::sign($transaction['txID'], $privateKey);

        $transaction['signature'] = [$signature];
        return $transaction;
    }

    /**
     * 广播已签名的交易
     *
     * @param array $signedTransaction 已签名的交易
     * @return array 广播结果
     * @throws TronException
     */
    public function sendRawTransaction(array $signedTransaction): array
    {
        if (!isset($signedTransaction['signature']) || !is_array($signedTransaction['signature'])) {
            throw new TronException('Transaction is not signed');
        }

        return $this->request('wallet/broadcasttransaction', $signedTransaction);
    }

    /**
     * 获取当前区块信息
     *
     * @return array 区块信息
     * @throws TronException
     */
    public function getCurrentBlock(): array
    {
        return $this->request('wallet/getnowblock');
    }

    /**
     * 通过区块号或哈希获取区块
     *
     * @param mixed $block 区块号或哈希
     * @return array 区块信息
     * @throws TronException
     */
    public function getBlock($block = null): array
    {
        if ($block === null) {
            return $this->getCurrentBlock();
        }

        if (TronUtils::isHex($block)) {
            return $this->getBlockByHash($block);
        }

        return $this->getBlockByNumber((int)$block);
    }

    /**
     * 通过哈希获取区块
     *
     * @param string $hash 区块哈希
     * @return array 区块信息
     * @throws TronException
     */
    public function getBlockByHash(string $hash): array
    {
        return $this->request('wallet/getblockbyid', ['value' => $hash]);
    }

    /**
     * 通过区块号获取区块
     *
     * @param int $number 区块号
     * @return array 区块信息
     * @throws TronException
     */
    public function getBlockByNumber(int $number): array
    {
        if ($number < 0) {
            throw new TronException('Block number must be non-negative');
        }

        return $this->request('wallet/getblockbynum', ['num' => $number]);
    }

    /**
     * 通过交易ID获取交易
     *
     * @param string $transactionID 交易ID
     * @return array 交易信息
     * @throws TronException
     */
    public function getTransaction(string $transactionID): array
    {
        return $this->request('wallet/gettransactionbyid', [
            'value' => $transactionID
        ]);
    }

    /**
     * 通过交易ID获取交易信息
     *
     * @param string $transactionID 交易ID
     * @return array 交易详细信息
     * @throws TronException
     */
    public function getTransactionInfo(string $transactionID): array
    {
        return $this->request('wallet/gettransactioninfobyid', [
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
        return $this->request('walletsolidity/gettransactionbyid', [
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
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addressHex))) {
            throw new TronException('Invalid address provided');
        }

        return $this->request('wallet/getaccount', [
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
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addressHex))) {
            throw new TronException('Invalid address provided');
        }

        $account = $this->getAccountInfo($addressHex);

        return [
            'free_bandwidth' => $account['free_net_usage'] ?? 0,
            'bandwidth_limit' => $account['net_limit'] ?? 0,
            'bandwidth_used' => $account['net_usage'] ?? 0,
            'bandwidth_percentage' => $account['net_limit'] > 0 ? ($account['net_usage'] / $account['net_limit']) * 100 : 0
        ];
    }

    /**
     * 获取账户资源信息
     *
     * @param string|null $address 地址
     * @return array 账户资源信息
     * @throws TronException
     */
    public function getAccountResources(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addressHex))) {
            throw new TronException('Invalid address provided');
        }

        $response = $this->request('wallet/getaccountresource', [
            'address' => $addressHex
        ], 'post');

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
     * 获取区块中的交易数量
     *
     * @param mixed $block 区块号、哈希，或null表示最新区块
     * @return int 交易数量
     * @throws TronException
     */
    public function getTransactionCount($block = null): int
    {
        $blockData = $this->getBlock($block);
        return count($blockData['transactions'] ?? []);
    }

    /**
     * 通过索引从区块中获取交易
     *
     * @param mixed $block 区块号、哈希，或null表示最新区块
     * @param int $index 交易索引
     * @return array 交易信息
     * @throws TronException
     */
    public function getTransactionFromBlock($block = null, int $index = 0): array
    {
        $blockData = $this->getBlock($block);

        if (!isset($blockData['transactions'][$index])) {
            throw new TronException('Transaction not found at index ' . $index);
        }

        return $blockData['transactions'][$index];
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
     * 发送交易（详细参数，send的别名）
     *
     * @param string $to 接收地址
     * @param float $amount 金额
     * @param string|null $from 发送地址
     * @param string|null $message 附加消息
     * @return array 交易结果
     * @throws TronException
     */
    public function sendTransaction(string $to, float $amount, ?string $from = null, ?string $message = null): array
    {
        return $this->send($to, $amount, $from, $message);
    }

    /**
     * send的别名（向后兼容）
     *
     * @param string $to 接收地址
     * @param float $amount 金额
     * @param string|null $from 发送地址
     * @param string|null $message 附加消息
     * @return array 交易结果
     * @throws TronException
     */
    public function sendTrx(string $to, float $amount, ?string $from = null, ?string $message = null): array
    {
        return $this->send($to, $amount, $from, $message);
    }

    /**
     * 获取区块交易数量（getTransactionCount的别名）
     *
     * @param mixed $block 区块号、哈希，或null表示最新区块
     * @return int 交易数量
     * @throws TronException
     */
    public function getBlockTransactionCount($block = null): int
    {
        return $this->getTransactionCount($block);
    }

    /**
     * 通过十六进制地址获取账户信息
     *
     * @param string $addressHex 十六进制地址
     * @return array 账户信息
     * @throws TronException
     */
    public function getAccountInfo(string $addressHex): array
    {
        return $this->request('walletsolidity/getaccount', [
            'address' => $addressHex
        ]);
    }

    /**
     * 多签交易，包含完整的权限验证
     *
     * @param array $transaction 交易数据
     * @param string|null $privateKey 私钥
     * @param array $options 选项
     * @return array 签名结果
     * @throws TronException
     */
    public function multiSign(array $transaction, ?string $privateKey = null, array $options = []): array
    {
        $privateKey = $privateKey ?: $this->getPrivateKey();
        if (!$privateKey) {
            throw new TronException('No private key provided for multi-signature');
        }

        $permissionId = $options['permissionId'] ?? 0;

        // 验证交易格式
        if (!isset($transaction['raw_data']) || !isset($transaction['raw_data']['contract'])) {
            throw new TronException('Invalid transaction provided');
        }

        // 如果权限ID存在于交易中或用户传递了权限ID，进行完整的权限验证
        if ((!isset($transaction['raw_data']['contract'][0]['Permission_id']) && $permissionId > 0) ||
            (isset($transaction['raw_data']['contract'][0]['Permission_id']) &&
             $transaction['raw_data']['contract'][0]['Permission_id'] != $permissionId)) {

            // 设置权限ID
            $transaction['raw_data']['contract'][0]['Permission_id'] = $permissionId;

            // 检查私钥是否在权限列表中
            $address = strtolower($this->getAddressFromPrivateKey($privateKey));

            $signWeight = $this->getSignWeight($transaction, ['permissionId' => $permissionId]);

            if (isset($signWeight['result']['code']) && $signWeight['result']['code'] === 'PERMISSION_ERROR') {
                throw new TronException($signWeight['result']['message'] ?? 'Permission error');
            }

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

        return $this->request('wallet/multisign', [
            'transaction' => $transaction,
            'privateKey' => $privateKey
        ], 'post');
    }

    /**
     * 从私钥获取地址
     *
     * @param string $privateKey 私钥
     * @return string 十六进制地址
     */
    private function getAddressFromPrivateKey(string $privateKey): string
    {
        // 使用Account模块的方法从私钥获取地址
        $tronAddress = $this->getTronWeb()->account->createWithPrivateKey($privateKey);
        return $tronAddress->getAddress(false); // false = hex format
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

        return $this->request('wallet/getsignweight', $transaction, 'post');
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
        return $this->request('wallet/getapprovedlist', [
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
        $fromAddressHex = $fromAddress ? TronUtils::addressToHex($fromAddress) : $this->getDefaultAddress()['hex'];
        $params = ['fromAddress' => $fromAddressHex];

        if ($toAddress) {
            $params['toAddress'] = TronUtils::addressToHex($toAddress);
        }

        return $this->request('wallet/getdelegatedresource', $params);
    }

    /**
     * 获取链参数
     *
     * @return array 链参数
     * @throws TronException
     */
    public function getChainParameters(): array
    {
        return $this->request('wallet/getchainparameters');
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
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        return $this->request('walletsolidity/getreward', [
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
        $fromHex = $from ? TronUtils::addressToHex($from) : $this->getDefaultAddress()['hex'];

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

            if ($validate && !$this->getTronWeb()->account->isValidAddress($toAddress)) {
                throw new TronException("Recipient {$index} invalid address: {$toAddress}");
            }

            try {
                $result = $this->send($toAddress, (float)$amount, $from);
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
        $addr = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addr))) {
            throw new TronException('Invalid address provided');
        }

        $providerType = $options['confirmed'] ? 'solidityNode' : 'fullNode';
        $endpointPrefix = $options['confirmed'] ? 'walletsolidity' : 'wallet';

        $data = ['address' => $addr];
        $result = $this->request("{$endpointPrefix}/getReward", $data, $providerType);

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
        $addr = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addr))) {
            throw new TronException('Invalid address provided');
        }

        $providerType = $options['confirmed'] ? 'solidityNode' : 'fullNode';
        $endpointPrefix = $options['confirmed'] ? 'walletsolidity' : 'wallet';

        $data = ['address' => $addr];
        $result = $this->request("{$endpointPrefix}/getBrokerage", $data, $providerType);

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
            $block = $this->request('wallet/getblock', ['detail' => false], 'post');

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
        return $this->request('wallet/getnodeinfo', [], 'post');
    }

    /**
     * 验证消息签名
     *
     * @param string $message 消息内容
     * @param string $signature 签名
     * @param string $address 地址（base58格式）
     * @param bool $useTronHeader 是否使用TRON消息头
     * @return bool 签名是否有效
     * @throws TronException
     */
    public function verifyMessage(string $message, string $signature, string $address, bool $useTronHeader = true): bool
    {
        return \Dsdcr\TronWeb\Support\Message::verifyMessageWithAddress(
            $message, $signature, $address, $useTronHeader
        );
    }

    /**
     * 验证消息签名（兼容TypeScript版本）
     *
     * @param string $messageHex 十六进制格式的消息
     * @param string $signature 签名
     * @param string $address 地址（base58格式）
     * @param bool $useTronHeader 是否使用TRON消息头
     * @return bool 签名是否有效
     * @throws TronException
     */
    public function verifySignature(string $messageHex, string $address, string $signature, bool $useTronHeader = true): bool
    {
        return \Dsdcr\TronWeb\Support\Message::verifySignature(
            $messageHex, $address, $signature, $useTronHeader
        );
    }

    /**
     * 验证消息签名V2版本
     *
     * @param string|array $message 消息内容
     * @param string $signature 签名
     * @return string 恢复出的地址
     * @throws TronException
     */
    public function verifyMessageV2($message, string $signature): string
    {
        return \Dsdcr\TronWeb\Support\Message::verifyMessage($message, $signature);
    }

    /**
     * 签名消息
     *
     * @param string|array $message 要签名的消息
     * @param string|null $privateKey 私钥
     * @param bool $useTronHeader 是否使用TRON消息头
     * @return string 签名结果
     * @throws TronException
     */
    public function signMessage($message, ?string $privateKey = null, bool $useTronHeader = true): string
    {
        $privateKey = $privateKey ?: $this->getPrivateKey();
        return \Dsdcr\TronWeb\Support\Message::signMessage($message, $privateKey, $useTronHeader);
    }

    /**
     * 触发智能合约
     *
     * @param array $abi ABI接口定义
     * @param string $contract 合约地址
     * @param string $function 函数名
     * @param array $params 参数数组
     * @param int $feeLimit 费用限制
     * @param string $address 调用者地址
     * @param int $callValue 调用值
     * @param int $bandwidthLimit 带宽限制
     * @return array 交易结果
     * @throws TronException
     */
    public function triggerSmartContract(
        array $abi,
        string $contract,
        string $function,
        array $params,
        int $feeLimit,
        string $address,
        int $callValue = 0,
        int $bandwidthLimit = 0
    ): array {
        // 构建函数签名
        $funcAbi = [];
        foreach ($abi as $item) {
            if (isset($item['name']) && $item['name'] === $function) {
                $funcAbi = $item;
                break;
            }
        }

        if (empty($funcAbi)) {
            throw new TronException("Function {$function} not defined in ABI");
        }

        $inputs = array_map(function ($item) {
            return $item['type'];
        }, $funcAbi['inputs'] ?? []);
        $signature = $funcAbi['name'] . '(' . implode(',', $inputs) . ')';

        // 编码参数
        $ethAbi = new Ethabi();
        $parameters = substr($ethAbi->encodeParameters($funcAbi, $params), 2);

        // 发送交易
        return $this->getTronWeb()->request('wallet/triggersmartcontract', [
            'contract_address' => $contract,
            'function_selector' => $signature,
            'parameter' => $parameters,
            'owner_address' => $address,
            'fee_limit' => $feeLimit,
            'call_value' => $callValue,
            'consume_user_resource_percent' => $bandwidthLimit,
        ]);
    }

    /**
     * 触发常量合约（只读操作）
     *
     * @param array $abi ABI接口定义
     * @param string $contract 合约地址
     * @param string $function 函数名
     * @param array $params 参数数组
     * @param string $address 调用者地址
     * @return array 合约调用结果
     * @throws TronException
     */
    public function triggerConstantContract(
        array $abi,
        string $contract,
        string $function,
        array $params = [],
        string $address = '410000000000000000000000000000000000000000'
    ): array {
        // 构建函数签名
        $funcAbi = [];
        foreach ($abi as $item) {
            if (isset($item['name']) && $item['name'] === $function) {
                $funcAbi = $item + ['inputs' => []];
                break;
            }
        }

        if (empty($funcAbi)) {
            throw new TronException("Function {$function} not defined in ABI");
        }

        $inputs = array_map(function ($item) {
            return $item['type'];
        }, $funcAbi['inputs'] ?? []);
        $signature = $funcAbi['name'] . '(' . implode(',', $inputs) . ')';

        // 编码参数
        $ethAbi = new Ethabi();
        $parameters = substr($ethAbi->encodeParameters($funcAbi, $params), 2);

        // 发送只读请求
        $result = $this->getTronWeb()->request('wallet/triggerconstantcontract', [
            'contract_address' => $contract,
            'function_selector' => $signature,
            'parameter' => $parameters,
            'owner_address' => $address,
        ]);

        if (!isset($result['result'])) {
            throw new TronException('No result field in response');
        }

        if (isset($result['result']['result']) && isset($result['constant_result'])) {
            return $ethAbi->decodeParameters($funcAbi, $result['constant_result'][0]);
        }

        $message = $result['result']['message'] ?? '';
        throw new TronException('Failed to execute: ' . $message);
    }
}