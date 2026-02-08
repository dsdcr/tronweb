<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
/**
 * Resource模块 - Tron网络资源管理的核心模块
 *
 * 提供完整的资源管理功能，包括：
 * - 带宽和能量资源冻结/解冻
 * - 区块奖励提取
 * - 资源委托查询和管理
 * - 资源价格查询
 * - 资源余额和委托详情查询
 *
 * 主要特性：
 * - 支持BANDWIDTH和ENERGY两种资源类型
 * - 完整的资源生命周期管理
 * - 资源委托（V2）支持
 * - 自动签名和广播交易
 * - 批量资源信息查询
 *
 * 资源说明：
 * - BANDWIDTH（带宽）：用于普通交易，每账户每天免费带宽有限
 * - ENERGY（能量）：用于智能合约执行，需要冻结TRX获取
 *
 * @package Dsdcr\TronWeb\Modules
 * @since 1.0.0
 */
class Resource extends BaseModule
{

    /**
     * 冻结TRX获取带宽或能量资源
     *
     * 通过冻结TRX获取网络资源，用于支付交易费用。
     * 冻结的TRX在解冻前不可使用，冻结时长最短3天。
     *
     * @param float $amount 冻结金额（单位：TRX）
     *                      会自动转换为SUN单位
     *                      必须大于0
     * @param int $duration 冻结时长（单位：天，最小3天，默认3天）
     *                      在Tron网络中，冻结时长至少3天
     *                      到期后可以解冻提取TRX
     * @param string $resource 资源类型（可选，默认'BANDWIDTH'）
     *                        - 'BANDWIDTH': 冻结获取带宽
     *                        - 'ENERGY': 冻结获取能量
     * @param string|null $ownerAddress 账户地址（Base58格式，可选）
     *                                  如不提供则使用当前账户地址
     *
     * @return array 交易广播结果，包含：
     *               - result: 交易结果
     *               - txid: 交易ID
     *               - [其他区块链返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 金额无效或为0
     *                      - 冻结时长小于3天
     *                      - 资源类型无效
     *                      - 余额不足
     *                      - 签名失败
     *                      - 广播失败
     *
     * @example
     * // 冻结100 TRX获取带宽
     * $result = $tronWeb->resource->freeze(100);
     *
     * // 冻结50 TRX获取能量，冻结30天
     * $result = $tronWeb->resource->freeze(50, 30, 'ENERGY');
     *
     * // 冻结到指定账户
     * $result = $tronWeb->resource->freeze(100, 3, 'BANDWIDTH', 'TXYZ...');
     *
     * @see unfreeze() 解冻资源
     * @see getResources() 查询资源余额
     */
    public function freeze(float $amount, int $duration = 3, string $resource = 'BANDWIDTH', ?string $ownerAddress = null ): array {

        $owner = $ownerAddress ?: $this->tronWeb->getAddress()['hex'];

        $transaction = $this->tronWeb->transactionBuilder->freezeBalance(
            TronUtils::toSun($amount),
            $duration,
            $resource,
            $owner
        );

        $signedTransaction = $this->tronWeb->trx->signTransaction($transaction);
        return $this->tronWeb->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 解冻已冻结的资源
     *
     * 解冻之前冻结的TRX，解冻后需要等待14天才能提取到余额。
     * 解冻操作不可逆，但可以取消解冻（V2）。
     *
     * @param string $resource 资源类型（可选，默认'BANDWIDTH'）
     *                        - 'BANDWIDTH': 解冻带宽
     *                        - 'ENERGY': 解冻能量
     * @param string|null $ownerAddress 账户地址（Base58格式，可选）
     *                                  如不提供则使用当前账户地址
     *
     * @return array 交易广播结果，包含：
     *               - result: 交易结果
     *               - txid: 交易ID
     *               - [其他区块链返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 资源类型无效
     *                      - 没有可解冻的资源
     *                      - 签名失败
     *                      - 广播失败
     *
     * @example
     * // 解冻带宽
     * $result = $tronWeb->resource->unfreeze('BANDWIDTH');
     *
     * // 解冻能量
     * $result = $tronWeb->resource->unfreeze('ENERGY');
     *
     * @see freeze() 冻结资源
     * @see getFrozenBalance() 查询冻结余额
     */
    public function unfreeze(string $resource = 'BANDWIDTH', ?string $ownerAddress = null): array
    {
        $owner = $ownerAddress ?: $this->tronWeb->getAddress()['hex'];

        $transaction = $this->tronWeb->transactionBuilder->unfreezeBalance($resource, $owner);

        $signedTransaction = $this->tronWeb->trx->signTransaction($transaction);
        return $this->tronWeb->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 提取超级代表区块奖励
     *
     * 提取成为超级代表后获得的区块奖励到账户余额。
     * 每24小时可以提取一次。
     *
     * @param string|null $ownerAddress 账户地址（Base58格式，可选）
     *                                  如不提供则使用当前账户地址
     *                                  必须是超级代表账户
     *
     * @return array 交易广播结果，包含：
     *               - result: 交易结果
     *               - txid: 交易ID
     *               - [其他区块链返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 不是超级代表
     *                      - 距离上次提取不足24小时
     *                      - 签名失败
     *                      - 广播失败
     *
     * @example
     * // 提取奖励
     * $result = $tronWeb->resource->withdrawRewards();
     *
     * echo "提取成功！交易ID: " . $result['txid'];
     *
     * @see freeze() 获取资源
     * @see getResources() 查询奖励余额
     */
    public function withdrawRewards(?string $ownerAddress = null): array
    {
        $owner = $ownerAddress ?: $this->tronWeb->getAddress()['hex'];

        $transaction = $this->tronWeb->transactionBuilder->withdrawBlockRewards($owner);

        $signedTransaction = $this->tronWeb->trx->signTransaction($transaction);
        return $this->tronWeb->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 查询账户完整资源信息
     *
     * 获取账户的全面资源状态，包括余额、冻结量、使用量等。
     * 这是最常用的资源查询方法。
     *
     * @param string|null $address 账户地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     *
     * @return array 资源信息数组，包含：
     *               - balance: 账户余额（SUN单位）
     *               - frozen_balance: 冻结信息数组
     *               - bandwidth: 已使用带宽
     *               - energy: 已使用能量
     *               - tron_power: TronPower投票权重
     *
     * @throws TronException 当地址格式无效或查询失败时抛出
     *
     * @example
     * // 查询资源信息
     * $resources = $tronWeb->resource->getResources();
     *
     * echo "余额: " . $resources['balance'] . " SUN";
     * echo "已用带宽: " . $resources['bandwidth'];
     * echo "已用能量: " . $resources['energy'];
     *
     * @see getFrozenBalance() 查询冻结详情
     * @see getAccountresource() 获取资源使用详情
     */
    public function getResources(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        $account = $this->tronWeb->trx->getAccount($addressHex);

        return [
            'balance' => $account['balance'] ?? 0,
            'frozen_balance' => $account['frozen'] ?? [],
            'bandwidth' => $account['net_usage'] ?? 0,
            'energy' => $account['energy_usage'] ?? 0,
            'tron_power' => $account['tron_power'] ?? 0,
        ];
    }

    /**
     * 查询账户冻结资源详情
     *
     * 分别获取冻结的带宽和能量数量。
     *
     * @param string|null $address 账户地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     *
     * @return array 冻结余额详情，包含：
     *               - bandwidth: 冻结的带宽（SUN单位）
     *               - energy: 冻结的能量（SUN单位）
     *               - total: 总冻结金额（SUN单位）
     *
     * @throws TronException 当查询失败时抛出
     *
     * @example
     * // 查询冻结余额
     * $frozen = $tronWeb->resource->getFrozenBalance();
     *
     * echo "冻结带宽: " . $frozen['bandwidth'] . " SUN";
     * echo "冻结能量: " . $frozen['energy'] . " SUN";
     * echo "总冻结: " . $frozen['total'] . " SUN";
     *
     * @see freeze() 冻结资源
     * @see unfreeze() 解冻资源
     */
    public function getFrozenBalance(?string $address = null): array
    {
        $resources = $this->getResources($address);

        $bandwidth = 0;
        $energy = 0;

        if (isset($resources['frozen_balance']['frozen_balance'])) {
            $bandwidth = $resources['frozen_balance']['frozen_balance'];
        }

        if (isset($resources['frozen_balance']['frozen_energy'])) {
            $energy = $resources['frozen_balance']['frozen_energy'];
        }

        return [
            'bandwidth' => $bandwidth,
            'energy' => $energy,
            'total' => $bandwidth + $energy,
        ];
    }

    /**
     * 查询资源委托详情（V2版本）
     *
     * 查询从委托方向接收方委托的资源详情。
     * V2版本支持更详细的委托信息。
     *
     * @param string|null $fromAddress 委托方地址（Base58格式，可选）
     *                                 如不提供则使用当前账户地址
     * @param string|null $toAddress 接收方地址（Base58格式，可选）
     *                                如不提供则使用当前账户地址
     * @param array $options 查询选项（可选，默认使用确认数据）
     *                      - confirmed: 是否使用已确认数据（true=使用solidityNode）
     *
     * @return array 委托资源详情，包含：
     *               - from: 委托方地址
     *               - to: 接收方地址
     *               - frozen_balance_for_bandwidth: 委托的带宽
     *               - frozen_balance_for_energy: 委托的能量
     *               - [其他委托信息字段]
     *
     * @throws TronException 当地址格式无效或查询失败时抛出
     *
     * @example
     * // 查询委托详情
     * $delegated = $tronWeb->resource->getDelegatedResourceV2('TXYZ...', 'TABC...');
     *
     * echo "委托方: " . $delegated['from'];
     * echo "接收方: " . $delegated['to'];
     * echo "委托带宽: " . $delegated['frozen_balance_for_bandwidth'];
     *
     * @see getDelegatedResourceAccountIndexV2() 查询委托账户索引
     */
    public function getDelegatedResourceV2( ?string $fromAddress = null, ?string $toAddress = null, array $options = ['confirmed' => true] ): array
    {
        $fromAddr = $fromAddress ? TronUtils::toHex($fromAddress) : $this->tronWeb->getAddress()['hex'];
        $toAddr = $toAddress ? TronUtils::toHex($toAddress) : $this->tronWeb->getAddress()['hex'];

        // 验证地址格式
        if (!TronUtils::isAddress(TronUtils::fromHex($fromAddr))) {
            throw new TronException('Invalid from address provided');
        }

        if (!TronUtils::isAddress(TronUtils::fromHex($toAddr))) {
            throw new TronException('Invalid to address provided');
        }

        $providerType = $options['confirmed'] ? 'solidityNode' : 'fullNode';
        $endpointPrefix = $options['confirmed'] ? 'walletsolidity' : 'wallet';

        return $this->request(
            "{$endpointPrefix}/getdelegatedresourcev2",
            [
                'fromAddress' => $fromAddr,
                'toAddress' => $toAddr,
            ],
            $providerType
        );
    }

    /**
     * 查询资源委托账户索引（V2版本）
     *
     * 查询指定地址参与的所有资源委托记录。
     * 返回该地址作为委托方或接收方的所有关联账户。
     *
     * @param string|null $address 账户地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     * @param array $options 查询选项（可选）
     *                      - confirmed: 是否使用已确认数据
     *
     * @return array 委托索引信息，包含：
     *               - account: 查询的地址
     *               - delegateAccounts: 作为委托方的接收方列表
     *               - receiveAccounts: 作为接收方的委托方列表
     *
     * @throws TronException 当地址格式无效或查询失败时抛出
     *
     * @example
     * // 查询委托索引
     * $index = $tronWeb->resource->getDelegatedResourceAccountIndexV2('TXYZ...');
     *
     * echo "我委托给的账户: " . json_encode($index['delegateAccounts']);
     * echo "委托给我的账户: " . json_encode($index['receiveAccounts']);
     *
     * @see getDelegatedResourceV2() 查询具体委托详情
     */
    public function getDelegatedResourceAccountIndexV2( ?string $address = null, array $options = ['confirmed' => true] ): array {
        $addr = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        if (!TronUtils::isAddress(TronUtils::fromHex($addr))) {
            throw new TronException('Invalid address provided');
        }

        $providerType = $options['confirmed'] ? 'solidityNode' : 'fullNode';
        $endpointPrefix = $options['confirmed'] ? 'walletsolidity' : 'wallet';

        return $this->request(
            "{$endpointPrefix}/getdelegatedresourceaccountindexv2",
            [
                'value' => $addr,
            ],
            $providerType
        );
    }

    /**
     * 查询可委托的最大资源量
     *
     * 查询账户还可以向他人委托的最大资源数量。
     * 用于确定可以委托多少资源给其他账户。
     *
     * @param string|null $address 账户地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     * @param string $resource 资源类型（可选，默认'BANDWIDTH'）
     *                        - 'BANDWIDTH': 查询可委托带宽
     *                        - 'ENERGY': 查询可委托能量
     * @param array $options 查询选项（可选）
     *                      - confirmed: 是否使用已确认数据
     *
     * @return array 可委托信息，包含：
     *               - owner_address: 账户地址
     *               - available: 可委托数量
     *               - type: 资源类型
     *
     * @throws TronException 当地址格式或资源类型无效时抛出
     *
     * @example
     * // 查询可委托带宽
     * $max = $tronWeb->resource->getCanDelegatedMaxSize('TXYZ...', 'BANDWIDTH');
     *
     * echo "可委托带宽: " . $max['available'];
     *
     * // 查询可委托能量
     * $max = $tronWeb->resource->getCanDelegatedMaxSize('TXYZ...', 'ENERGY');
     *
     * echo "可委托能量: " . $max['available'];
     */
    public function getCanDelegatedMaxSize( ?string $address = null, string $resource = 'BANDWIDTH', array $options = ['confirmed' => true] ): array
    {
        $addr = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        if (!TronUtils::isAddress(TronUtils::fromHex($addr))) {
            throw new TronException('Invalid address provided');
        }

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        $providerType = $options['confirmed'] ? 'solidityNode' : 'fullNode';
        $endpointPrefix = $options['confirmed'] ? 'walletsolidity' : 'wallet';
        $resourceType = $resource === 'ENERGY' ? 1 : 0;

        return $this->request(
            "{$endpointPrefix}/getcandelegatedmaxsize",
            [
                'owner_address' => $addr,
                'type' => $resourceType,
            ],
            $providerType
        );
    }

    /**
     * 查询可用解冻次数
     *
     * 查询账户还可以发起的解冻操作次数。
     * 每个账户每天有解冻次数限制。
     *
     * @param string|null $address 账户地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     * @param array $options 查询选项（可选）
     *                      - confirmed: 是否使用已确认数据
     *
     * @return array 解冻次数信息，包含：
     *               - owner_address: 账户地址
     *               - available_unfreeze_count: 剩余可解冻次数
     *
     * @throws TronException 当地址格式无效时抛出
     *
     * @example
     * // 查询可用解冻次数
     * $count = $tronWeb->resource->getAvailableUnfreezeCount('TXYZ...');
     *
     * echo "剩余解冻次数: " . $count['available_unfreeze_count'];
     */
    public function getAvailableUnfreezeCount( ?string $address = null, array $options = ['confirmed' => true] ): array
    {
        $addr = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        if (!TronUtils::isAddress(TronUtils::fromHex($addr))) {
            throw new TronException('Invalid address provided');
        }

        $providerType = $options['confirmed'] ? 'solidityNode' : 'fullNode';
        $endpointPrefix = $options['confirmed'] ? 'walletsolidity' : 'wallet';

        return $this->request(
            "{$endpointPrefix}/getavailableunfreezecount",
            [
                'owner_address' => $addr,
            ],
            $providerType
        );
    }

    /**
     * 查询可提取的解冻金额
     *
     * 查询在指定时间戳之后可以提取的已解冻金额。
     * 解冻后需要等待14天才能提取到余额。
     *
     * @param string|null $address 账户地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     * @param int $timestamp 时间戳（毫秒，可选）
     *                       默认使用当前时间
     *                       查询该时间点之后可提取的金额
     * @param array $options 查询选项（可选）
     *                      - confirmed: 是否使用已确认数据
     *
     * @return array 可提取金额信息，包含：
     *               - owner_address: 账户地址
     *               - amount: 可提取金额（SUN单位）
     *               - timestamp: 查询的时间点
     *
     * @throws TronException 当地址格式或时间戳无效时抛出
     *
     * @example
     * // 查询当前可提取金额
     * $amount = $tronWeb->resource->getCanWithdrawUnfreezeAmount('TXYZ...');
     *
     * echo "可提取金额: " . $amount['amount'] . " SUN";
     *
     * // 查询指定时间点
     * $future = $tronWeb->resource->getCanWithdrawUnfreezeAmount(
     *     'TXYZ...',
     *     (time() + 86400000) * 1000  // 1天后
     * );
     *
     * echo "1天后可提取: " . $future['amount'];
     */
    public function getCanWithdrawUnfreezeAmount(?string $address = null, int $timestamp = null, array $options = ['confirmed' => true] ): array {
        $addr = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];
        $ts = $timestamp ?? (int)(microtime(true) * 1000);

        if (!TronUtils::isAddress(TronUtils::fromHex($addr))) {
            throw new TronException('Invalid address provided');
        }

        if ($ts < 0) {
            throw new TronException('Invalid timestamp provided');
        }

        $providerType = $options['confirmed'] ? 'solidityNode' : 'fullNode';
        $endpointPrefix = $options['confirmed'] ? 'walletsolidity' : 'wallet';

        return $this->request(
            "{$endpointPrefix}/getcanwithdrawunfreezeamount",
            [
                'owner_address' => $addr,
                'timestamp' => $ts,
            ],
            $providerType
        );
    }

    /**
     * 查询网络带宽价格
     *
     * 获取当前网络中带宽的单价信息。
     * 带宽价格随网络拥堵程度动态变化。
     *
     * @return string 带宽价格信息（JSON格式）
     *               包含不同资源使用量级别的价格
     *
     * @throws TronException 当查询失败时抛出
     *
     * @example
     * // 查询带宽价格
     * $prices = $tronWeb->resource->getBandwidthPrices();
     *
     * $priceData = json_decode($prices, true);
     * echo "带宽价格: " . json_encode($priceData);
     *
     * @see getEnergyPrices() 查询能量价格
     */
    public function getBandwidthPrices(): string
    {
        $result = $this->request('wallet/getbandwidthprices', [], 'fullNode');

        if (!isset($result['prices'])) {
            throw new TronException('Bandwidth prices not found');
        }

        return $result['prices'];
    }

    /**
     * 查询网络能量价格
     *
     * 获取当前网络中能量的单价信息。
     * 能量价格随网络拥堵程度动态变化。
     *
     * @return string 能量价格信息（JSON格式）
     *               包含不同资源使用量级别的价格
     *
     * @throws TronException 当查询失败时抛出
     *
     * @example
     * // 查询能量价格
     * $prices = $tronWeb->resource->getEnergyPrices();
     *
     * $priceData = json_decode($prices, true);
     * echo "能量价格: " . json_encode($priceData);
     *
     * @see getBandwidthPrices() 查询带宽价格
     */
    public function getEnergyPrices(): string
    {
        $result = $this->request('wallet/getenergyprices', [], 'fullNode');

        if (!isset($result['prices'])) {
            throw new TronException('Energy prices not found');
        }

        return $result['prices'];
    }
}