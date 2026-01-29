<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;

/**
 * 资源模块 - 用于带宽、能量和质押操作
 *
 * @package Dsdcr\TronWeb\Modules
 */
class Resource extends BaseModule
{

    /**
     * 冻结TRX以获取带宽或能量
     *
     * @param float $amount 金额（TRX）
     * @param int $duration 冻结时长（天，默认3天）
     * @param string $resource 资源类型：'BANDWIDTH' 或 'ENERGY'
     * @param string|null $ownerAddress 所有者地址
     * @return array 冻结结果
     * @throws TronException
     */
    public function freeze(
        float $amount = 0,
        int $duration = 3,
        string $resource = 'BANDWIDTH',
        ?string $ownerAddress = null
    ): array {
        if (empty($ownerAddress)) {
            throw new TronException('Owner Address not specified');
        }

        $owner = TronUtils::addressToHex($ownerAddress);

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        if ($duration < 3) {
            throw new TronException('Invalid duration provided, minimum of 3 days');
        }

        $transaction = $this->getTronWeb()->request('wallet/freezebalance', [
            'owner_address' => $owner,
            'frozen_balance' => TronUtils::toSun($amount),
            'frozen_duration' => $duration,
            'resource' => $resource
        ]);

        $signedTransaction = $this->getTronWeb()->trx->signTransaction($transaction);
        return $this->getTronWeb()->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 冻结余额（freeze的别名）
     *
     * @param float $amount 金额（TRX）
     * @param int $duration 冻结时长（天）
     * @param string $resource 资源类型
     * @param string|null $ownerAddress 所有者地址
     * @return array 冻结结果
     * @throws TronException
     */
    public function freezeBalance(
        float $amount = 0,
        int $duration = 3,
        string $resource = 'BANDWIDTH',
        ?string $ownerAddress = null
    ): array {
        return $this->freeze($amount, $duration, $resource, $ownerAddress);
    }

    /**
     * 解冻资源
     *
     * @param string $resource 资源类型：'BANDWIDTH' 或 'ENERGY'
     * @param string|null $ownerAddress 所有者地址
     * @return array 解冻结果
     * @throws TronException
     */
    public function unfreeze(string $resource = 'BANDWIDTH', ?string $ownerAddress = null): array
    {
        if (empty($ownerAddress)) {
            throw new TronException('Owner Address not specified');
        }

        $owner = TronUtils::addressToHex($ownerAddress);

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        $transaction = $this->getTronWeb()->request('wallet/unfreezebalance', [
            'owner_address' => $owner,
            'resource' => $resource
        ]);

        $signedTransaction = $this->getTronWeb()->trx->signTransaction($transaction);
        return $this->getTronWeb()->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 解冻余额（unfreeze的别名）
     *
     * @param string $resource 资源类型
     * @param string|null $ownerAddress 所有者地址
     * @return array 解冻结果
     * @throws TronException
     */
    public function unfreezeBalance(string $resource = 'BANDWIDTH', ?string $ownerAddress = null): array
    {
        return $this->unfreeze($resource, $ownerAddress);
    }

    /**
     * 提取区块奖励
     *
     * @param string|null $ownerAddress 所有者地址
     * @return array 提取结果
     * @throws TronException
     */
    public function withdrawRewards(?string $ownerAddress = null): array
    {
        $owner = $ownerAddress ?: $this->getDefaultAddress()['hex'];

        // 提取区块奖励的API调用
        $transaction = $this->getTronWeb()->request('wallet/withdrawbalance', [
            'owner_address' => $owner
        ]);

        $signedTransaction = $this->getTronWeb()->trx->signTransaction($transaction);
        return $this->getTronWeb()->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 提取区块奖励（别名）
     *
     * @param string|null $ownerAddress 所有者地址
     * @return array 提取结果
     * @throws TronException
     */
    public function withdrawBlockRewards(?string $ownerAddress = null): array
    {
        return $this->withdrawRewards($ownerAddress);
    }

    /**
     * 获取账户资源信息（包括冻结金额）
     *
     * @param string|null $address 地址
     * @return array 资源信息
     * @throws TronException
     */
    public function getResources(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        $account = $this->getTronWeb()->trx->getAccountInfo($addressHex);

        return [
            'balance' => $account['balance'] ?? 0,
            'frozen_balance' => $account['frozen'] ?? [],
            'bandwidth' => $account['net_usage'] ?? 0,
            'energy' => $account['energy_usage'] ?? 0,
            'tron_power' => $account['tron_power'] ?? 0,
        ];
    }

    /**
     * 获取冻结余额
     *
     * @param string|null $address 地址
     * @return array 冻结余额信息
     * @throws TronException
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
     * 获取V2版本的委托资源信息
     * 查询从fromAddress委托到toAddress的特定资源信息
     *
     * @param string|null $fromAddress 委托方地址
     * @param string|null $toAddress 接收方地址
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 委托资源信息
     * @throws TronException
     */
    public function getDelegatedResourceV2(
        ?string $fromAddress = null,
        ?string $toAddress = null,
        array $options = ['confirmed' => true]
    ): array {
        $fromAddr = $fromAddress ? TronUtils::addressToHex($fromAddress) : $this->getDefaultAddress()['hex'];
        $toAddr = $toAddress ? TronUtils::addressToHex($toAddress) : $this->getDefaultAddress()['hex'];

        // 验证地址格式
        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($fromAddr))) {
            throw new TronException('Invalid from address provided');
        }

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($toAddr))) {
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
     * 获取委托资源账户索引V2
     * 查询账户的资源委托索引信息
     *
     * @param string|null $address 查询的地址
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 委托资源账户索引信息
     * @throws TronException
     */
    public function getDelegatedResourceAccountIndexV2(
        ?string $address = null,
        array $options = ['confirmed' => true]
    ): array {
        $addr = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addr))) {
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
     * 获取可委托的最大资源数量
     * 查询指定地址对特定资源类型的可委托最大数量（单位：SUN）
     *
     * @param string|null $address 查询的地址
     * @param string $resource 资源类型：'BANDWIDTH' 或 'ENERGY'
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 可委托最大数量信息
     * @throws TronException
     */
    public function getCanDelegatedMaxSize(
        ?string $address = null,
        string $resource = 'BANDWIDTH',
        array $options = ['confirmed' => true]
    ): array {
        $addr = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addr))) {
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
     * 获取可用解冻次数
     * 查询指定地址的可用解冻次数
     *
     * @param string|null $address 查询的地址
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 可用解冻次数信息
     * @throws TronException
     */
    public function getAvailableUnfreezeCount(
        ?string $address = null,
        array $options = ['confirmed' => true]
    ): array {
        $addr = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addr))) {
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
     * 获取可提取的解冻金额
     * 查询指定时间戳下可提取的解冻金额
     *
     * @param string|null $address 查询的地址
     * @param int $timestamp 时间戳（毫秒）
     * @param array $options 选项数组，包含 confirmed 字段
     * @return array 可提取的解冻金额信息
     * @throws TronException
     */
    public function getCanWithdrawUnfreezeAmount(
        ?string $address = null,
        int $timestamp = null,
        array $options = ['confirmed' => true]
    ): array {
        $addr = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];
        $ts = $timestamp ?? (int)(microtime(true) * 1000);

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addr))) {
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
     * 获取带宽价格
     * 查询网络当前的带宽价格
     *
     * @return string 带宽价格信息
     * @throws TronException
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
     * 获取能量价格
     * 查询网络当前的能量价格
     *
     * @return string 能量价格信息
     * @throws TronException
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