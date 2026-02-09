<?php

namespace Dsdcr\TronWeb\Modules;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\Ethabi;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\TronWeb;

/**
 * TransactionBuilder模块 - Tron网络交易构建的核心引擎
 *
 * 提供完整的区块链交易构建功能，包括：
 * - TRX和代币转账交易构建
 * - 智能合约部署和调用
 * - 资源冻结和解冻操作
 * - 代币创建和管理
 * - 权限控制和多签支持
 * - 交易参数验证和格式化
 *
 * 主要特性：
 * - 支持所有TRON标准交易类型
 * - 完整的参数验证和错误处理
 * - 自动单位转换（TRX ↔ SUN）
 * - 地址格式自动处理（Base58 ↔ Hex）
 * - 权限ID和多签支持
 * - 智能合约ABI编码和解码
 *
 * @package Dsdcr\TronWeb\Modules
 * @since 1.0.0
 */
class TransactionBuilder
{
    /**
     * TronWeb主实例引用
     * 用于访问配置、工具方法和API请求
     *
     * @var TronWeb
     */
    protected $tronWeb;

    /**
     * 创建TransactionBuilder实例
     *
     * @param TronWeb $tronWeb TronWeb主实例
     *                        必须包含有效的配置和工具方法
     *
     * @throws TronException 当TronWeb实例无效时抛出
     */
    public function __construct(TronWeb $tronWeb)
    {
        $this->tronWeb = $tronWeb;
    }

    /**
     * 构建TRX转账交易
     *
     * 创建从指定地址向目标地址发送TRX的交易数据。
     * 这是最基本的TRX转账功能，支持权限控制和多签。
     *
     * @param string $to 接收方地址（Base58格式）
     *                   必须是有效的Tron地址
     *                   格式：'TXYZ...'（34字符）
     * @param float $amount 转账金额（单位：TRX）
     *                      会自动转换为SUN单位（1 TRX = 1,000,000 SUN）
     *                      必须大于0
     * @param string|null $from 发送方地址（Base58格式，可选）
     *                         如不提供则使用当前账户地址
     *                         必须拥有足够的TRX余额
     * @param array $options 交易选项数组（可选），可包含：
     *                      - permissionId: 权限ID（用于多签账户）
     *                      - [其他交易选项字段]
     *
     * @return array 构建的交易数据，包含：
     *               - raw_data: 原始交易数据（用于签名）
     *               - txID: 交易哈希ID
     *               - [其他区块链返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 地址格式无效
     *                      - 发送方和接收方地址相同
     *                      - 金额无效或为0
     *                      - 余额不足
     *                      - 权限验证失败
     *
     * @example
     * // 简单TRX转账
     * $transaction = $builder->sendTrx('TXYZ...', 1.5);
     *
     * // 使用指定发送地址
     * $transaction = $builder->sendTrx('TXYZ...', 0.5, 'TABC...');
     *
     * // 使用权限ID（多签账户）
     * $transaction = $builder->sendTrx('TXYZ...', 1.0, null, ['permissionId' => 2]);
     *
     * // 签名并广播交易
     * $signed = $tronWeb->trx->signTransaction($transaction);
     * $result = $tronWeb->trx->sendRawTransaction($signed);
     *
     * @see \Dsdcr\TronWeb\Modules\Trx::signTransaction() 交易签名
     * @see \Dsdcr\TronWeb\Modules\Trx::sendRawTransaction() 交易广播
     */
    public function sendTrx(string $to, float $amount = 0, string $from = null, array $options = [] ): array
    {
        $amount = (int)$amount;
        // 处理from地址
        $from = $from ?? ($this->tronWeb->getAddress()['hex'] ?? null);
        if (is_null($from)) {
            throw new TronException('From address is required');
        }
        // 如果是Base58地址，先转换为hex
        if ($this->tronWeb->utils->isAddress($from) && !$this->tronWeb->utils->isHex($from)) {
            $from = $this->tronWeb->toHex($from);
        }

        $toHex = $this->tronWeb->utils->isHex($to) ? $to : $this->tronWeb->toHex($to);
        $fromHex = $this->tronWeb->utils->isHex($from) ? $from : $this->tronWeb->toHex($from);

        if ($toHex === $fromHex) {
            throw new TronException('Receiver address must not be the same as owner address');
        }

        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        $data = [
            'to_address' => $toHex,
            'owner_address' => $fromHex,
            'amount' => $this->tronWeb->utils->toSun($amount),
        ];
        if (isset($options['message'])) {
            $data['extra_data'] = $this->tronWeb->utils->stringToHex($options['message']);
        }
        if (isset($options['permissionId'])) {
            $data['permissionId']= $options['permissionId'];
        }

        $result = $this->tronWeb->request('wallet/createtransaction', $data);

        return $result;
    }


    /**
     * 构建代币转账交易（TRC10标准）
     *
     * 创建从指定地址向目标地址发送TRC10代币的交易数据。
     * 支持标准的TRC10代币转账操作。
     *
     * @param string $to 接收方地址（Base58格式）
     *                   必须是有效的Tron地址
     * @param string $tokenId 代币ID或名称
     *                        代币的唯一标识符
     *                        例如：'1000001' 或 'MyToken'
     * @param float|int $amount 转账金额（代币单位）
     *                          必须大于0
     *                          注意：不是TRX单位，是代币本身的单位
     * @param string|null $from 发送方地址（Base58格式，可选）
     *                         如不提供则使用当前账户地址
     *                         必须拥有足够的代币余额
     * @param array $options 交易选项数组（可选），可包含：
     *                      - permissionId: 权限ID（用于多签账户）
     *                      - [其他交易选项字段]
     *
     * @return array 构建的交易数据，包含：
     *               - raw_data: 原始交易数据
     *               - txID: 交易哈希ID
     *               - [其他区块链返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 地址格式无效
     *                      - 发送方和接收方地址相同
     *                      - 代币ID无效
     *                      - 金额无效或为0
     *                      - 代币余额不足
     *                      - 权限验证失败
     *
     * @example
     * // 代币转账
     * $transaction = $builder->sendToken('TXYZ...', 'MyToken', 100);
     *
     * // 使用数字代币ID
     * $transaction = $builder->sendToken('TXYZ...', '1000001', 50);
     *
     * // 使用权限ID
     * $transaction = $builder->sendToken('TXYZ...', 'MyToken', 200, null, ['permissionId' => 2]);
     *
     * @see sendTrx() TRX转账方法
     * @see purchaseToken() 购买代币方法
     */
    public function sendToken( string $to, string $tokenId, float|int $amount = 0, string $from = null, array $options = [] ): array
    {
        $amount = (int)$amount;

        $from = $from ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        if (is_null($from)) {
            throw new TronException('From address is required');
        }

        $toHex = $this->tronWeb->toHex($to);
        $fromHex = $this->tronWeb->toHex($from);

        if ($toHex === $fromHex) {
            throw new TronException('Cannot transfer tokens to the same account');
        }

        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        if (!is_string($tokenId) || empty($tokenId)) {
            throw new TronException('Invalid token ID provided');
        }

        $data = [
            'owner_address' => $fromHex,
            'to_address' => $toHex,
            'asset_name' => $this->tronWeb->utils->toUtf8($tokenId),
            'amount' => $amount,
        ];

        // 处理options参数
        $permissionId = $options['permissionId'] ?? 0;

        // 如果提供了permissionId，添加到请求数据中
        if ($permissionId > 0) {
            $data['Permission_id'] = $permissionId;
        }

        return $this->tronWeb->request('wallet/transferasset', $data);
    }

    /**
     * 构建代币购买交易（参与代币发行）
     *
     * 参与TRC10代币的公开销售或发行。
     * 向代币发行者支付TRX以获得相应的代币。
     *
     * @param string $issuerAddress 代币发行者地址（Base58格式）
     *                             代币创建者的地址
     * @param string $tokenID 代币ID或名称
     *                        要购买的目标代币标识
     * @param int $amount 购买金额（单位：TRX）
     *                   支付给发行者的TRX数量
     *                   会自动转换为SUN单位
     *                   必须大于0
     * @param string $buyer 购买者地址（Base58格式）
     *                     购买代币的账户地址
     *                     必须拥有足够的TRX余额
     *
     * @return array 购买结果，包含：
     *               - transaction: 交易详情
     *               - [其他区块链返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 地址格式无效
     *                      - 代币ID无效
     *                      - 金额无效或为0
     *                      - TRX余额不足
     *                      - 代币销售已结束或暂停
     *
     * @example
     * // 购买代币
     * $result = $builder->purchaseToken(
     *     'TXYZ...',     // 发行者地址
     *     'MyToken',     // 代币ID
     *     100,           // 支付100 TRX
     *     'TABC...'      // 购买者地址
     * );
     *
     * @see createToken() 创建代币方法
     * @see sendToken() 代币转账方法
     */
    public function purchaseToken(string $issuerAddress, string $tokenID, int $amount, string $buyer): array
    {
        if (!is_string($tokenID)) {
            throw new TronException('Invalid token ID provided');
        }

        if (!is_integer($amount) and $amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        $purchase = $this->tronWeb->request('wallet/participateassetissue', [
            'to_address' => $this->tronWeb->toHex($issuerAddress),
            'owner_address' => $this->tronWeb->toHex($buyer),
            'asset_name' => $this->tronWeb->utils->toUtf8($tokenID),
            'amount' => $this->tronWeb->utils->toSun($amount)
        ]);

        if (array_key_exists('Error', $purchase)) {
            throw new TronException($purchase['Error']);
        }
        return $purchase;
    }

    /**
     * 创建新的TRC10代币
     *
     * 在Tron网络上发行新的TRC10标准代币。
     * 代币创建需要支付一定的TRX费用，成功后返回代币ID。
     *
     * @param array $options 代币配置选项数组，必需包含：
     *                      - name: 代币名称（字符串，必填）
     *                      - abbreviation: 代币缩写（字符串，必填）
     *                      - totalSupply: 总发行量（整数，>0）
     *                      - trxRatio: TRX兑换比例（整数，>0）
     *                      - tokenRatio: 代币兑换比例（整数，>0）
     *                      - saleStart: 销售开始时间（Unix时间戳，毫秒）
     *                      - saleEnd: 销售结束时间（Unix时间戳，毫秒）
     *                      - description: 代币描述（字符串）
     *                      - url: 代币官方网站（有效URL）
     *                      可选包含：
     *                      - freeBandwidth: 免费带宽总量（整数，>=0）
     *                      - freeBandwidthLimit: 每账户免费带宽限制（整数，>=0）
     *                      - frozenAmount: 冻结供应量（整数，>=0）
     *                      - frozenDuration: 冻结天数（整数，>=0）
     *                      - precision: 代币精度（整数，默认6）
     *                      - voteScore: 投票分数（整数）
     * @param string|null $issuerAddress 发行者地址（Base58格式，可选）
     *                                  如不提供则使用当前账户地址
     *                                  需要支付代币创建费用
     *
     * @return array 代币创建结果，包含：
     *               - transaction: 交易详情
     *               - token_id: 代币ID（创建成功后）
     *               - [其他区块链返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 必填参数缺失或格式错误
     *                      - 时间戳无效
     *                      - URL格式无效
     *                      - TRX余额不足支付创建费用
     *                      - 代币名称已存在
     *
     * @example
     * $options = [
     *     'name' => 'MyToken',
     *     'abbreviation' => 'MTK',
     *     'totalSupply' => 1000000,
     *     'trxRatio' => 1,
     *     'tokenRatio' => 1,
     *     'saleStart' => time() * 1000 + 3600000, // 1小时后开始
     *     'saleEnd' => time() * 1000 + 86400000,  // 1天后结束
     *     'description' => 'My awesome token',
     *     'url' => 'https://mytoken.com',
     *     'precision' => 6
     * ];
     *
     * $result = $builder->createToken($options);
     * echo "代币创建成功，ID: " . $result['token_id'];
     *
     * @see updateToken() 更新代币信息
     * @see purchaseToken() 购买代币
     */
    public function createToken($options = [], $issuerAddress = null): array
    {
        $startDate = new \DateTime();
        $startTimeStamp = $startDate->getTimestamp() * 1000;

        // Create default parameters in case of their absence
        if (!$options['totalSupply']) $options['totalSupply'] = 0;
        if (!$options['trxRatio']) $options['trxRatio'] = 1;
        if (!$options['tokenRatio']) $options['tokenRatio'] = 1;
        if (!$options['freeBandwidth']) $options['freeBandwidth'] = 0;
        if (!$options['freeBandwidthLimit']) $options['freeBandwidthLimit'] = 0;
        if (!$options['frozenAmount']) $options['frozenAmount'] = 0;
        if (!$options['frozenDuration']) $options['frozenDuration'] = 0;

        if (is_null($issuerAddress)) {
            $issuerAddress = $this->tronWeb->getAddress()['hex'];
        }

        if (!$options['name'] or !is_string($options['name'])) {
            throw new TronException('Invalid token name provided');
        }

        if (!$options['abbreviation'] or !is_string($options['abbreviation'])) {
            throw new TronException('Invalid token abbreviation provided');
        }

        if (!is_integer($options['totalSupply']) or $options['totalSupply'] <= 0) {
            throw new TronException('Invalid supply amount provided');
        }

        if (!is_integer($options['trxRatio']) or $options['trxRatio'] <= 0) {
            throw new TronException('TRX ratio must be a positive integer');
        }

        if (!is_integer($options['saleStart']) or $options['saleStart'] <= $startTimeStamp) {
            throw new TronException('Invalid sale start timestamp provided');
        }

        if (!is_integer($options['saleEnd']) or $options['saleEnd'] <= $options['saleStart']) {
            throw new TronException('Invalid sale end timestamp provided');
        }

        if (!$options['description'] or !is_string($options['description'])) {
            throw new TronException('Invalid token description provided');
        }

        if (!is_string($options['url']) || !filter_var($options['url'], FILTER_VALIDATE_URL)) {
            throw new TronException('Invalid token url provided');
        }

        if (!is_integer($options['freeBandwidth']) || $options['freeBandwidth'] < 0) {
            throw new TronException('Invalid free bandwidth amount provided');
        }

        if (!is_integer($options['freeBandwidthLimit']) || $options['freeBandwidthLimit '] < 0 ||
            ($options['freeBandwidth'] && !$options['freeBandwidthLimit'])
        ) {
            throw new TronException('Invalid free bandwidth limit provided');
        }

        if (!is_integer($options['frozenAmount']) || $options['frozenAmount '] < 0 ||
            (!$options['frozenDuration'] && $options['frozenAmount'])
        ) {
            throw new TronException('Invalid frozen supply provided');
        }

        if (!is_integer($options['frozenDuration']) || $options['frozenDuration '] < 0 ||
            ($options['frozenDuration'] && !$options['frozenAmount'])
        ) {
            throw new TronException('Invalid frozen duration provided');
        }

        $data = [
            'owner_address' => $this->tronWeb->toHex($issuerAddress),
            'name' => $this->tronWeb->utils->toUtf8($options['name']),
            'abbr' => $this->tronWeb->utils->toUtf8($options['abbreviation']),
            'description' => $this->tronWeb->utils->toUtf8($options['description']),
            'url' => $this->tronWeb->utils->toUtf8($options['url']),
            'total_supply' => intval($options['totalSupply']),
            'trx_num' => intval($options['trxRatio']),
            'num' => intval($options['tokenRatio']),
            'start_time' => intval($options['saleStart']),
            'end_time' => intval($options['saleEnd']),
            'free_asset_net_limit' => intval($options['freeBandwidth']),
            'public_free_asset_net_limit' => intval($options['freeBandwidthLimit']),
            'frozen_supply' => [
                'frozen_amount' => intval($options['frozenAmount']),
                'frozen_days' => intval($options['frozenDuration']),
            ]
        ];

        if ($options['precision'] && !is_nan(intval($options['precision']))) {
            $data['precision'] = intval($options['precision']);
        }

        if ($options['voteScore'] && !is_nan(intval($options['voteScore']))) {
            $data['vote_score'] = intval($options['voteScore']);
        }

        return $this->tronWeb->request('wallet/createassetissue', $data);
    }

    /**
     * 冻结TRX获取资源
     *
     * @param float|int $amount 冻结金额（单位：TRX）
     * @param int $duration 冻结时长（单位：天，最小3天）
     * @param string $resource 资源类型（'BANDWIDTH'或'ENERGY'）
     * @param string|null $ownerAddress 所有者地址（可选，默认使用当前账户地址）
     * @param string|null $receiverAddress 资源接收者地址（可选，用于资源委托）
     * @param array $options 选项（可选，可包含permissionId等）
     * @return array 交易数据
     * @throws TronException
     */
    public function freezeBalance( int $amount = 0, int $duration = 3, string $resource = 'BANDWIDTH', string $ownerAddress = null, string $receiverAddress = null, array $options = [] ): array
    {
        $amount = (int)$amount;

        $ownerAddress = $ownerAddress ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        if (is_null($ownerAddress)) {
            throw new TronException('Owner address is required');
        }

        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        if ($duration < 3) {
            throw new TronException('Invalid duration provided, minimum of 3 days');
        }

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        $ownerAddressHex = $this->tronWeb->toHex($ownerAddress);

        $data = [
            'owner_address' => $ownerAddressHex,
            'frozen_balance' => $this->tronWeb->utils->toSun($amount),
            'frozen_duration' => $duration,
        ];

        if ($resource !== 'BANDWIDTH') {
            $data['resource'] = $resource;
        }

        if (!is_null($receiverAddress)) {
            $receiverAddressHex = $this->tronWeb->toHex($receiverAddress);
            if ($receiverAddressHex !== $ownerAddressHex) {
                $data['receiver_address'] = $receiverAddressHex;
            }
        }

        // 处理options参数
        $permissionId = $options['permissionId'] ?? 0;

        // 如果提供了permissionId，添加到请求数据中
        if ($permissionId > 0) {
            $data['Permission_id'] = $permissionId;
        }

        return $this->tronWeb->request('wallet/freezebalance', $data);
    }

    /**
     * 解冻已冻结的资源
     *
     * @param string $resource 资源类型（'BANDWIDTH'或'ENERGY'）
     * @param string|null $ownerAddress 所有者地址（可选，默认使用当前账户地址）
     * @param string|null $receiverAddress 资源接收者地址（可选）
     * @param array $options 选项（可选，可包含permissionId等）
     * @return array 交易数据
     * @throws TronException
     */
    public function unfreezeBalance( string $resource = 'BANDWIDTH', string $ownerAddress = null, string $receiverAddress = null, array $options = [] ): array
    {
        $ownerAddress = $ownerAddress ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        if (is_null($ownerAddress)) {
            throw new TronException('Owner address is required');
        }

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        $ownerAddressHex = $this->tronWeb->toHex($ownerAddress);

        $data = [
            'owner_address' => $ownerAddressHex,
        ];

        if ($resource !== 'BANDWIDTH') {
            $data['resource'] = $resource;
        }

        if (!is_null($receiverAddress)) {
            $receiverAddressHex = $this->tronWeb->toHex($receiverAddress);
            if ($receiverAddressHex !== $ownerAddressHex) {
                $data['receiver_address'] = $receiverAddressHex;
            }
        }

        // 处理options参数
        $permissionId = $options['permissionId'] ?? 0;

        // 如果提供了permissionId，添加到请求数据中
        if ($permissionId > 0) {
            $data['Permission_id'] = $permissionId;
        }

        return $this->tronWeb->request('wallet/unfreezebalance', $data);
    }

    /**
     * 冻结TRX获取资源V2版本
     *
     * @param float|int $amount 冻结金额（单位：TRX）
     * @param string $resource 资源类型（'BANDWIDTH'或'ENERGY'）
     * @param string|null $address 所有者地址（可选，默认使用当前账户地址）
     * @param array $options 选项（可选，可包含permissionId等）
     * @return array 交易数据
     * @throws TronException
     */
    public function freezeBalanceV2( float|int $amount = 0, string $resource = 'BANDWIDTH', string $address = null, array $options = [] ): array
    {
        $amount = (int)$amount;

        $address = $address ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        if (is_null($address)) {
            throw new TronException('Address is required');
        }

        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        $data = [
            'owner_address' => $this->tronWeb->toHex($address),
            'frozen_balance' => $this->tronWeb->utils->toSun($amount),
        ];

        if ($resource !== 'BANDWIDTH') {
            $data['resource'] = $resource;
        }

        // 处理options参数
        $permissionId = $options['permissionId'] ?? 0;

        // 如果提供了permissionId，添加到请求数据中
        if ($permissionId > 0) {
            $data['Permission_id'] = $permissionId;
        }

        return $this->tronWeb->request('wallet/freezebalancev2', $data);
    }

    /**
     * 解冻已冻结的资源V2版本
     *
     * @param float|int $amount 解冻金额（单位：TRX）
     * @param string $resource 资源类型（'BANDWIDTH'或'ENERGY'）
     * @param string|null $address 所有者地址（可选，默认使用当前账户地址）
     * @param array $options 选项（可选，可包含permissionId等）
     * @return array 交易数据
     * @throws TronException
     */
    public function unfreezeBalanceV2( float|int $amount = 0, string $resource = 'BANDWIDTH', string $address = null, array $options = [] ): array
    {
        $amount = (int)$amount;

        $address = $address ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        if (is_null($address)) {
            throw new TronException('Address is required');
        }

        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        $data = [
            'owner_address' => $this->tronWeb->toHex($address),
            'unfreeze_balance' => $this->tronWeb->utils->toSun($amount),
        ];

        if ($resource !== 'BANDWIDTH') {
            $data['resource'] = $resource;
        }

        // 处理options参数
        $permissionId = $options['permissionId'] ?? 0;

        // 如果提供了permissionId，添加到请求数据中
        if ($permissionId > 0) {
            $data['Permission_id'] = $permissionId;
        }

        return $this->tronWeb->request('wallet/unfreezebalancev2', $data);
    }

    /**
     * 取消待解冻的TRX（V2版本）
     *
     * @param string|null $address 所有者地址（可选，默认使用当前账户地址）
     * @param array $options 选项（可选，可包含permissionId等）
     * @return array 交易数据
     * @throws TronException
     */
    public function cancelUnfreezeBalanceV2( string $address = null, array $options = []): array
    {
        $address = $address ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        if (is_null($address)) {
            throw new TronException('Address is required');
        }

        $data = [
            'owner_address' => $this->tronWeb->toHex($address),
        ];

        // 处理options参数
        $permissionId = $options['permissionId'] ?? 0;

        // 如果提供了permissionId，添加到请求数据中
        if ($permissionId > 0) {
            $data['Permission_id'] = $permissionId;
        }

        return $this->tronWeb->request('wallet/cancelallunfreezev2', $data);
    }

    /**
     * 委托资源给其他账户
     *
     * @param string $receiverAddress 资源接收者地址
     * @param float|int $amount 委托金额（单位：TRX）
     * @param string $resource 资源类型（带宽：'BANDWIDTH'、能量：'ENERGY'）
     * @param string|null $address 委托方地址（可选，默认使用当前账户地址）
     * @param bool $lock 是否锁定委托资源
     * @param int|null $lockPeriod 锁定周期（单位：天）
     * @param array $options 选项（可选，可包含permissionId等）
     * @return array 交易数据
     * @throws TronException
     */
    public function delegateResource( string $receiverAddress, float $amount = 0, string $resource = 'BANDWIDTH', string $address = null, bool $lock = false, ?int $lockPeriod = null, array $options = [] ): array
    {

        $address = $address ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        if (is_null($address)) {
            throw new TronException('Address is required');
        }

        $amount = (int)$amount;
        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        $receiverAddressHex = $this->tronWeb->toHex($receiverAddress);
        $ownerAddressHex = $this->tronWeb->toHex($address);

        if ($receiverAddressHex === $ownerAddressHex) {
            throw new TronException('Receiver address must not be the same as owner address');
        }

        $data = [
            'owner_address' => $ownerAddressHex,
            'receiver_address' => $receiverAddressHex,
            'balance' => $this->tronWeb->utils->toSun($amount),
        ];

        if ($resource !== 'BANDWIDTH') {
            $data['resource'] = $resource;
        }

        if ($lock) {
            $data['lock'] = $lock;
            if (!is_null($lockPeriod)) {
                $data['lock_period'] = $lockPeriod;
            }
        }

        // 处理options参数
        $permissionId = $options['permissionId'] ?? 0;

        // 如果提供了permissionId，添加到请求数据中
        if ($permissionId > 0) {
            $data['Permission_id'] = $permissionId;
        }

        return $this->tronWeb->request('wallet/delegateresource', $data);
    }

    /**
     * 取消委托资源
     *
     * @param string $receiverAddress 资源接收者地址
     * @param float|int $amount 取消委托的金额（单位：TRX）
     * @param string $resource 资源类型（'BANDWIDTH'或'ENERGY'）
     * @param string|null $address 委托方地址（可选，默认使用当前账户地址）
     * @param array $options 选项（可选，可包含permissionId等）
     * @return array 交易数据
     * @throws TronException
     */
    public function undelegateResource( string $receiverAddress, float|int $amount = 0, string $resource = 'BANDWIDTH', string $address = null, array $options = [] ): array
    {

        $address = $address ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        if (is_null($address)) {
            throw new TronException('Address is required');
        }

        $amount = (int)$amount;
        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        $receiverAddressHex = $this->tronWeb->toHex($receiverAddress);
        $ownerAddressHex = $this->tronWeb->toHex($address);

        if ($receiverAddressHex === $ownerAddressHex) {
            throw new TronException('Receiver address must not be the same as owner address');
        }

        $data = [
            'owner_address' => $ownerAddressHex,
            'receiver_address' => $receiverAddressHex,
            'balance' => $this->tronWeb->utils->toSun($amount),
        ];

        if ($resource !== 'BANDWIDTH') {
            $data['resource'] = $resource;
        }

        // 处理options参数
        $permissionId = $options['permissionId'] ?? 0;

        // 如果提供了permissionId，添加到请求数据中
        if ($permissionId > 0) {
            $data['Permission_id'] = $permissionId;
        }

        return $this->tronWeb->request('wallet/undelegateresource', $data);
    }

    /**
     * 提取已到期的解冻金额
     *
     * @param string|null $address 地址（可选，默认使用当前账户地址）
     * @param array $options 选项（可选，可包含permissionId等）
     * @return array 交易数据
     * @throws TronException
     */
    public function withdrawExpireUnfreeze( string|null $address = null, array $options = [] ): array
    {
        $address = $address ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        if (is_null($address)) {
            throw new TronException('Address is required');
        }

        $data = [
            'owner_address' => $this->tronWeb->toHex($address),
        ];

        // 处理options参数
        $permissionId = $options['permissionId'] ?? 0;

        // 如果提供了permissionId，添加到请求数据中
        if ($permissionId > 0) {
            $data['Permission_id'] = $permissionId;
        }

        return $this->tronWeb->request('wallet/withdrawexpireunfreeze', $data);
    }

    /**
     * 提取超级代表区块奖励
     *
     * 提取成为超级代表后获得的区块奖励，每24小时可使用一次
     *
     * @param string|null $owner_address 所有者地址（可选，默认使用当前账户地址）
     * @return array API响应结果
     * @throws TronException
     */
    public function withdrawBlockRewards($owner_address = null): array
    {
        $withdraw = $this->tronWeb->request('wallet/withdrawbalance', [
            'owner_address' => $this->tronWeb->toHex($owner_address)
        ]);

        if (array_key_exists('Error', $withdraw)) {
            throw new TronException($withdraw['Error']);
        }
        return $withdraw;
    }

    /**
     * 更新已发行代币的信息
     *
     * @param string $description 代币描述
     * @param string $url 代币网站URL
     * @param int $freeBandwidth 免费带宽总量
     * @param int $freeBandwidthLimit 每账户免费带宽限制
     * @param string|null $address 代币所有者地址（可选，默认使用当前账户地址）
     * @return array API响应结果
     * @throws TronException
     */
    public function updateToken(string $description, string $url, int $freeBandwidth = 0, int $freeBandwidthLimit = 0, $address = null): array
    {
        if (is_null($address)) {
            throw new TronException('Owner Address not specified');
        }

        if (!is_integer($freeBandwidth) || $freeBandwidth < 0) {
            throw new TronException('Invalid free bandwidth amount provided');
        }

        if (!is_integer($freeBandwidthLimit) || $freeBandwidthLimit < 0 && ($freeBandwidth && !$freeBandwidthLimit)) {
            throw new TronException('Invalid free bandwidth limit provided');
        }

        return $this->tronWeb->request('wallet/updateasset', [
            'owner_address' => $this->tronWeb->toHex($address),
            'description' => $this->tronWeb->utils->toUtf8($description),
            'url' => $this->tronWeb->utils->toUtf8($url),
            'new_limit' => intval($freeBandwidth),
            'new_public_limit' => intval($freeBandwidthLimit)
        ]);
    }

    /**
     * 更新合约的能量限制
     *
     * @param string $contractAddress 合约地址
     * @param int $originEnergyLimit 新的能量限制值（范围：0-10000000）
     * @param string $ownerAddress 合约所有者地址
     * @return array API响应结果
     * @throws TronException
     */
    public function updateEnergyLimit(string $contractAddress, int $originEnergyLimit, string $ownerAddress): array
    {
        $contractAddress = $this->tronWeb->toHex($contractAddress);
        $ownerAddress = $this->tronWeb->toHex($ownerAddress);

        if ($originEnergyLimit < 0 || $originEnergyLimit > 10000000) {
            throw new TronException('Invalid originEnergyLimit provided');
        }

        return $this->tronWeb->request('wallet/updateenergylimit', [
            'owner_address' => $this->tronWeb->toHex($ownerAddress),
            'contract_address' => $this->tronWeb->toHex($contractAddress),
            'origin_energy_limit' => $originEnergyLimit
        ]);
    }

    /**
     * 更新合约的资源费用设置
     *
     * @param string $contractAddress 合约地址
     * @param int $userFeePercentage 用户费用百分比（范围：0-1000，代表0%-100%）
     * @param string $ownerAddress 合约所有者地址
     * @return array API响应结果
     * @throws TronException
     */
    public function updateSetting(string $contractAddress, int $userFeePercentage, string $ownerAddress): array
    {
        $contractAddress = $this->tronWeb->toHex($contractAddress);
        $ownerAddress = $this->tronWeb->toHex($ownerAddress);

        if ($userFeePercentage < 0 || $userFeePercentage > 1000) {
            throw new TronException('Invalid userFeePercentage provided');
        }

        return $this->tronWeb->request('wallet/updatesetting', [
            'owner_address' => $this->tronWeb->toHex($ownerAddress),
            'contract_address' => $this->tronWeb->toHex($contractAddress),
            'consume_user_resource_percent' => $userFeePercentage
        ]);
    }

    /**
     * 触发智能合约（写入操作）
     *
     * @param array $abi ABI接口定义数组
     * @param string $contract 合约地址（十六进制格式）
     * @param string $function 函数名
     * @param array $params 函数参数数组（如array("0"=>$value)）
     * @param int $feeLimit 最大费用限制（单位：SUN，最大1000000000）
     * @param string|null $address 调用者地址（十六进制格式）
     * @param int $callValue 调用值（单位：SUN，可选，默认0）
     * @param int $bandwidthLimit 带宽消耗百分比（可选，默认0）
     * @param array $abi 合约ABI定义
     * @param string $contract 合约地址（十六进制格式）
     * @param string $function 函数名
     * @param array $params 函数参数数组
     * @param int|float $feeLimit 最大费用限制（SUN单位）
     * @param string $address 调用者地址（十六进制格式）
     * @param int|float $callValue 调用值（向合约发送的TRX，SUN单位）
     * @param int $bandwidthLimit 带宽消耗百分比（0-100）
     * @param int|null $permissionId 多重签名权限ID（可选）
     * @param int|float|null $userFeePercentage 用户费用百分比（可选，0-100）
     * @param int|float|null $originEnergyLimit 原始能量限制（可选）
     * @param string|null $tokenId TRC10代币ID（可选）
     * @param int|float|null $tokenValue TRC10代币数量（可选）
     * @return array 触发结果，如果是只读调用返回解码后的结果，否则返回交易数据
     * @return array
     * @throws TronException
    * /**
     * 触发智能合约
     *
     * 支持 TypeScript 风格的完整选项参数
     *
     * @throws TronException
     */
    /**
     * 触发智能合约
     *
     * 优化版本：参考 TypeScript 风格的简洁参数设计
     * 所有选项通过 $options 数组传递
     *
     * @param array $abi 合约ABI定义
     * @param string $contract 合约地址（十六进制格式）
     * @param string $function 函数名
     * @param array $params 函数参数数组
     * @param array $options 选项数组（可选），包含：
     *                      - feeLimit: 最大费用限制（SUN单位，默认1000000）
     *                      - callValue: 调用值（向合约发送的TRX，SUN单位，默认0）
     *                      - fromAddress: 调用者地址（Base58或十六进制格式）
     *                      - bandwidthLimit: 带宽消耗百分比（0-100，默认0）
     *                      - permissionId: 多重签名权限ID（可选）
     *                      - userFeePercentage: 用户费用百分比（可选，0-100）
     *                      - originEnergyLimit: 原始能量限制（可选）
     *                      - tokenId: TRC10代币ID（可选）
     *                      - tokenValue: TRC10代币数量（可选）
     * @return array
     * @throws TronException
     */
    public function triggerSmartContract(
        array  $abi,
        string $contract,
        string $function,
        array  $params,
        array  $options = []
    ): array
    {
        // 解析选项参数
        $feeLimit = $options['feeLimit'] ?? 20000000;
        $callValue = $options['callValue'] ?? 0;
        $bandwidthLimit = $options['bandwidthLimit'] ?? 0;
        $permissionId = $options['permissionId'] ?? null;
        $userFeePercentage = $options['userFeePercentage'] ?? null;
        $originEnergyLimit = $options['originEnergyLimit'] ?? null;
        $tokenId = $options['tokenId'] ?? null;
        $tokenValue = $options['tokenValue'] ?? null;

        // 处理 fromAddress：支持 Base58 或十六进制格式
        $fromAddress = $options['fromAddress'] ?? null;
        if ($fromAddress && TronUtils::isAddress($fromAddress)) {
            $fromAddress = TronUtils::toHex($fromAddress);
        }
        $address = $fromAddress ?? ($this->tronWeb->getAddress()['hex'] ?? null);

        $func_abi = [];
        foreach ($abi as $key => $item) {
            if (isset($item['name']) && $item['name'] === $function) {
                $func_abi = $item;
                break;
            }
        }

        if (count($func_abi) === 0)
            throw new TronException("Function {$function} not defined in ABI");

        if (!is_array($params))
            throw new TronException("Function params must be an array");

        if (count($func_abi['inputs']) !== count($params))
            throw new TronException("Count of params and abi inputs must be identical");

        if ($feeLimit > 1000000000)
            throw new TronException('fee_limit must not be greater than 1000000000');


        $inputs = array_map(function ($item) {
            return $item['type'];
        }, $func_abi['inputs']);
        $signature = $func_abi['name'] . '(';
        if (count($inputs) > 0)
            $signature .= implode(',', $inputs);
        $signature .= ')';


        $eth_abi = new \Dsdcr\TronWeb\Support\Ethabi();
        $parameters = substr($eth_abi->encodeParameters($func_abi, $params), 2);

        // 构建请求参数
        $requestParams = [
            'contract_address' => $contract,
            'function_selector' => $signature,
            'parameter' => $parameters,
            'owner_address' => $address,
            'fee_limit' => $feeLimit,
            'call_value' => $callValue,
            'consume_user_resource_percent' => $bandwidthLimit,
        ];

        // 添加可选参数
        if ($permissionId !== null) {
            $requestParams['permission_id'] = $permissionId;
        }

        if ($userFeePercentage !== null) {
            $requestParams['user_fee_percentage'] = $userFeePercentage;
        }

        if ($originEnergyLimit !== null) {
            $requestParams['origin_energy_limit'] = $originEnergyLimit;
        }

        if ($tokenId !== null) {
            $requestParams['token_id'] = $tokenId;
        }

        if ($tokenValue !== null) {
            $requestParams['token_value'] = $tokenValue;
        }

        $result = $this->tronWeb->request('wallet/triggersmartcontract', $requestParams);

        if (!isset($result['result'])) {
            throw new TronException('No result field in response. Raw response:' . print_r($result, true));
        }
        if (isset($result['result']['result'])) {
            if (count($func_abi['outputs']) >= 0 && isset($result['constant_result'])) {
                return $eth_abi->decodeParameters($func_abi, $result['constant_result'][0]);
            }
            return $result['transaction'];
        }
        $message = isset($result['result']['message']) ?
            $this->tronWeb->utils->fromUtf8($result['result']['message']) : '';

        throw new TronException('Failed to execute. Error:' . $message);
    }

    /**
     * 调用智能合约的只读方法（Constant操作）
     *
     * @param array $abi ABI接口定义数组
     * @param string $contract 合约地址（十六进制格式）
     * @param string $function 函数名
     * @param array $params 函数参数数组（可选，如array("0"=>$value)）
     * @param string $address 调用者地址（十六进制格式，可选，默认随机地址）
     * @return array 解码后的返回结果
     * @throws TronException
     */
    public function triggerConstantContract($abi, $contract, $function, $params = [], $address = '410000000000000000000000000000000000000000'): array
    {
        $func_abi = [];
        foreach ($abi as $key => $item) {
            if (isset($item['name']) && $item['name'] === $function) {
                $func_abi = $item + ['inputs' => []];
                break;
            }
        }

        if (count($func_abi) === 0)
            throw new TronException("Function $function not defined in ABI");

        if (!is_array($params))
            throw new TronException("Function params must be an array");

        if (count($func_abi['inputs']) !== count($params))
            throw new TronException("Count of params and abi inputs must be identical");


        $inputs = array_map(function ($item) {
            return $item['type'];
        }, $func_abi['inputs']);
        $signature = $func_abi['name'] . '(';
        if (count($inputs) > 0)
            $signature .= implode(',', $inputs);
        $signature .= ')';


        $eth_abi = new \Dsdcr\TronWeb\Support\Ethabi();
        $parameters = substr($eth_abi->encodeParameters($func_abi, $params), 2);

        $result = $this->tronWeb->request('wallet/triggerconstantcontract', [
            'contract_address' => $contract,
            'function_selector' => $signature,
            'parameter' => $parameters,
            'owner_address' => $address,
        ]);

        if (!isset($result['result'])) {
            throw new TronException('No result field in response. Raw response:' . print_r($result, true));
        }
        if (isset($result['result']['result'])) {
            if (count($func_abi['outputs']) >= 0 && isset($result['constant_result'])) {
                return $eth_abi->decodeParameters($func_abi, $result['constant_result'][0]);
            }
            return $result['transaction'];
        }
        $message = isset($result['result']['message']) ?
            $this->tronWeb->utils->fromUtf8($result['result']['message']) : '';

        throw new TronException('Failed to execute. Error:' . $message);
    }
}
