<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;

/**
 * 代币模块 - 用于TRC20和其他代币操作
 *
 * @package Dsdcr\TronWeb\Modules
 */
class Token extends BaseModule
{

    /**
     * 发送代币（TRC20）到另一个地址
     *
     * @param string $to 接收地址
     * @param float $amount 金额（代币单位）
     * @param string $tokenId 代币ID（名称或ID）
     * @param string|null $from 发送地址
     * @return array 交易结果
     * @throws TronException
     */
    public function send(string $to, float $amount, string $tokenId, ?string $from = null): array
    {
        $from = $from ? TronUtils::addressToHex($from) : $this->getDefaultAddress()['hex'];
        $to = TronUtils::addressToHex($to);

        if ($to === $from) {
            throw new TronException('Cannot transfer tokens to the same account');
        }

        if (!is_integer($amount) || $amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        $transfer = $this->getTronWeb()->request('wallet/transferasset', [
            'owner_address' => $from,
            'to_address' => $to,
            'asset_name' => TronUtils::toUtf8($tokenId),
            'amount' => intval(TronUtils::toSun($amount))
        ]);

        if (isset($transfer['Error'])) {
            throw new TronException($transfer['Error']);
        }

        $signedTransaction = $this->getTronWeb()->trx->signTransaction($transfer);
        return $this->getTronWeb()->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * send的别名（向后兼容）
     *
     * @param string $to 接收地址
     * @param int $amount 金额（SUN单位）
     * @param string $tokenID 代币ID
     * @param string|null $from 发送地址
     * @return array 交易结果
     * @throws TronException
     */
    public function sendToken(string $to, int $amount, string $tokenID, ?string $from = null): array
    {
        return $this->send($to, TronUtils::fromSun($amount), $tokenID, $from);
    }

    /**
     * 发送代币交易（SUN金额）
     *
     * @param string $to 接收地址
     * @param float $amount 金额（SUN单位）
     * @param string|null $tokenID 代币ID
     * @param string|null $from 发送地址
     * @return array 交易结果
     * @throws TronException
     */
    public function sendTransaction(string $to, float $amount, ?string $tokenID = null, ?string $from = null): array
    {
        if (!$tokenID) {
            throw new TronException('Token ID is required for token transaction');
        }

        return $this->send($to, $amount, $tokenID, $from);
    }

    /**
     * 创建新代币
     *
     * @param array $tokenOptions 代币创建参数
     * @return array 创建结果
     * @throws TronException
     */
    public function createToken(array $tokenOptions): array
    {
        $requiredFields = ['name', 'abbr', 'total_supply', 'trx_num', 'num', 'start_time', 'end_time'];
        foreach ($requiredFields as $field) {
            if (!isset($tokenOptions[$field])) {
                throw new TronException("Missing required field: {$field}");
            }
        }

        // 转换必要的字段格式
        $createOptions = [
            'owner_address' => $this->getDefaultAddress()['hex'],
            'name' => TronUtils::toUtf8($tokenOptions['name']),
            'abbr' => TronUtils::toUtf8($tokenOptions['abbr']),
            'total_supply' => (int)$tokenOptions['total_supply'],
            'trx_num' => (int)$tokenOptions['trx_num'],
            'num' => (int)$tokenOptions['num'],
            'start_time' => (int)$tokenOptions['start_time'],
            'end_time' => (int)$tokenOptions['end_time'],
            'description' => TronUtils::toUtf8($tokenOptions['description'] ?? ''),
            'url' => TronUtils::toUtf8($tokenOptions['url'] ?? ''),
            'free_asset_net_limit' => (int)($tokenOptions['free_bandwidth'] ?? 0),
            'public_free_asset_net_limit' => (int)($tokenOptions['free_bandwidth_limit'] ?? 0),
            'frozen_supply' => $tokenOptions['frozen_supply'] ?? []
        ];

        return $this->getTronWeb()->request('wallet/createassetissue', $createOptions);
    }

    /**
     * 购买代币
     *
     * @param string $issuerAddress 代币发行者地址
     * @param string $tokenID 代币ID
     * @param float $amount 购买金额（TRX）
     * @param string|null $buyer 购买者地址
     * @return array 购买结果
     * @throws TronException
     */
    public function purchaseToken(string $issuerAddress, string $tokenID, float $amount, ?string $buyer = null): array
    {
        $buyer = $buyer ?: $this->getDefaultAddress()['hex'];
        $issuerHex = TronUtils::addressToHex($issuerAddress);

        if (!is_string($tokenID)) {
            throw new TronException('Invalid token ID provided');
        }

        if ($amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        $transaction = $this->getTronWeb()->request('wallet/participateassetissue', [
            'to_address' => $issuerHex,
            'owner_address' => $buyer,
            'asset_name' => TronUtils::toUtf8($tokenID),
            'amount' => TronUtils::toSun($amount)
        ]);

        if (isset($transaction['Error'])) {
            throw new TronException($transaction['Error']);
        }

        $signedTransaction = $this->getTronWeb()->trx->signTransaction($transaction);
        return $this->getTronWeb()->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 更新代币信息
     *
     * @param string $description 新描述
     * @param string $url 新URL
     * @param int $freeBandwidth 免费带宽限制
     * @param int $freeBandwidthLimit 每用户免费带宽限制
     * @param string|null $ownerAddress 所有者地址
     * @return array 更新结果
     * @throws TronException
     */
    public function updateToken(
        string $description,
        string $url,
        int $freeBandwidth = 0,
        int $freeBandwidthLimit = 0,
        ?string $ownerAddress = null
    ): array {
        $owner = $ownerAddress ?: $this->getDefaultAddress()['hex'];

        $transaction = $this->getTronWeb()->request('wallet/updateasset', [
            'owner_address' => $owner,
            'description' => TronUtils::toUtf8($description),
            'url' => TronUtils::toUtf8($url),
            'new_limit' => $freeBandwidth,
            'new_public_limit' => $freeBandwidthLimit
        ]);

        $signedTransaction = $this->getTronWeb()->trx->signTransaction($transaction);
        return $this->getTronWeb()->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 获取指定地址发行的代币
     *
     * @param string|null $address 地址
     * @return array 代币列表
     * @throws TronException
     */
    public function getIssuedByAddress(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        return $this->request('wallet/getassetissuebyaccount', [
            'address' => $addressHex
        ]);
    }

    /**
     * 通过名称获取代币信息
     *
     * @param string $tokenName 代币名称
     * @return array 代币信息
     * @throws TronException
     */
    public function getFromName(string $tokenName): array
    {
        return $this->request('wallet/getassetissuebyname', [
            'value' => TronUtils::stringToHex($tokenName)
        ]);
    }

    /**
     * 通过ID获取代币信息
     *
     * @param string $tokenId 代币ID
     * @return array 代币信息
     * @throws TronException
     */
    public function getById(string $tokenId): array
    {
        return $this->request('wallet/getassetissuebyid', [
            'value' => $tokenId
        ]);
    }

    /**
     * 列出所有代币（分页）
     *
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @return array 代币列表
     * @throws TronException
     */
    public function list(int $limit = 0, int $offset = 0): array
    {
        if ($limit <= 0) {
            return $this->request('wallet/getassetissuelist', [])['assetIssue'] ?? [];
        }

        return $this->request('wallet/getpaginatedassetissuelist', [
            'limit' => $limit,
            'offset' => $offset
        ])['assetIssue'] ?? [];
    }

    /**
     * 根据名称列表获取代币信息
     *
     * @param string|array $tokenNames 代币名称或名称数组
     * @return array 代币信息
     * @throws TronException
     */
    public function getTokenListByName($tokenNames): array
    {
        if (is_string($tokenNames)) {
            $tokenNames = [$tokenNames];
        }

        if (!is_array($tokenNames)) {
            throw new TronException('Token names must be a string or array');
        }

        $results = [];
        foreach ($tokenNames as $tokenName) {
            try {
                $result = $this->getFromName($tokenName);
                $results[] = $result;
            } catch (TronException $e) {
                // 如果通过名称找不到，尝试通过ID查找
                try {
                    $result = $this->getById($tokenName);
                    $results[] = $result;
                } catch (TronException $e2) {
                    // 如果两种方式都找不到，记录错误但继续处理其他代币
                    $results[] = [
                        'name' => $tokenName,
                        'error' => 'Token not found',
                        'details' => $e2->getMessage()
                    ];
                }
            }
        }

        return count($tokenNames) === 1 ? $results[0] : $results;
    }

    /**
     * 通过ID获取代币信息（别名，向后兼容）
     *
     * @param string|int $tokenId 代币ID
     * @return array 代币信息
     * @throws TronException
     */
    public function getTokenFromID($tokenId): array
    {
        if (is_int($tokenId)) {
            $tokenId = (string)$tokenId;
        }

        return $this->getById($tokenId);
    }

    /**
     * 获取地址发行的所有代币（包括详细信息）
     *
     * @param string|null $address 地址
     * @return array 代币详细信息列表
     * @throws TronException
     */
    public function getTokensIssuedByAddress(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        if (!TronUtils::isValidTronAddress(TronUtils::hexToAddress($addressHex))) {
            throw new TronException('Invalid address provided');
        }

        $issuedTokens = $this->getIssuedByAddress($address);

        // 获取每个代币的详细信息
        $detailedTokens = [];
        foreach ($issuedTokens['assetIssue'] ?? [] as $token) {
            if (isset($token['id'])) {
                try {
                    $detailedToken = $this->getById($token['id']);
                    $detailedTokens[] = $detailedToken;
                } catch (TronException $e) {
                    // 如果获取详细信息失败，使用基本信息
                    $detailedTokens[] = $token;
                }
            }
        }

        return $detailedTokens;
    }
}