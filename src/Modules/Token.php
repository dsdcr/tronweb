<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
/**
 * Token模块 - Tron网络代币管理的核心模块
 *
 * 提供完整的代币管理功能，包括：
 * - TRC10/TRC20代币转账
 * - 代币创建（TRC10）
 * - 代币发行参与
 * - 代币信息查询
 * - 代币更新和管理
 *
 * 主要特性：
 * - 支持TRC10标准代币操作
 * - 完整的代币生命周期管理
 * - 批量代币信息查询
 * - 自动签名和广播交易
 * - 代币名称和ID双模式查询
 *
 * 代币说明：
 * - TRC10: Tron原生代币标准，使用数字ID
 * - TRC20: 智能合约代币标准，使用合约地址
 *
 * @package Dsdcr\TronWeb\Modules
 * @since 1.0.0
 */
class Token extends BaseModule
{

    /**
     * 发送代币（TRC10标准）
     *
     * 向指定地址发送TRC10代币。
     * 这是最常用的代币转账方法。
     *
     * @param string $to 接收方地址（Base58格式）
     *                   必须是有效的Tron地址
     * @param float $amount 转账金额（代币单位）
     *                      会自动转换为SUN单位
     *                      必须大于0
     * @param string $tokenId 代币ID或名称
     *                        支持代币名称（如'MyToken'）或数字ID（如'1000001'）
     *                        必须是已发行的TRC10代币
     * @param string|null $from 发送方地址（Base58格式，可选）
     *                         如不提供则使用当前账户地址
     *                         必须拥有足够的代币余额
     *
     * @return array 交易广播结果，包含：
     *               - result: 交易结果
     *               - txid: 交易ID
     *               - [其他区块链返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 地址格式无效
     *                      - 金额无效或为0
     *                      - 代币不存在
     *                      - 代币余额不足
     *                      - 签名失败
     *                      - 广播失败
     *
     * @example
     * // 发送代币
     * $result = $tronWeb->token->send('TXYZ...', 100, 'MyToken');
     *
     * // 发送指定数量的代币
     * $result = $tronWeb->token->send('TXYZ...', 50.5, '1000001');
     *
     * // 从指定账户发送
     * $result = $tronWeb->token->send('TXYZ...', 100, 'MyToken', 'TABC...');
     *
     * @see sendToken() 备选发送方法
     * @see sendTransaction() SUN金额发送
     * @see getById() 查询代币详情
     */
    public function send(string $to, float $amount, string $tokenId, ?string $from = null): array
    {
        $from = $from ? TronUtils::toHex($from) : $this->tronWeb->getAddress()['hex'];

        $transaction = $this->tronWeb->transactionBuilder->sendToken($to, $tokenId, TronUtils::toSun($amount), $from);
        $signedTransaction = $this->tronWeb->trx->signTransaction($transaction);

        return $this->tronWeb->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 发送代币（选项模式）
     *
     * 通过选项数组指定发送参数。
     * 提供更灵活的参数传递方式。
     *
     * @param string $to 接收方地址（Base58格式）
     * @param float $amount 转账金额（代币单位）
     * @param string $tokenID 代币ID或名称
     * @param array $options 选项数组，可包含：
     *                      - from: 发送方地址（Base58格式）
     *                      - [其他选项]
     *
     * @return array 交易广播结果
     *
     * @throws TronException 当参数无效时抛出
     *
     * @example
     * // 使用选项数组发送
     * $result = $tronWeb->token->sendToken(
     *     'TXYZ...',
     *     100,
     *     'MyToken',
     *     ['from' => 'TABC...']
     * );
     *
     * @see send() 简化发送方法
     */
    public function sendToken(string $to, float $amount, string $tokenID, array $options = []): array
    {
        $from = $options['from'] ?? null;
        return $this->send($to, $amount, $tokenID, $from);
    }

    /**
     * 发送代币交易（SUN单位）
     *
     * 使用SUN单位进行代币转账。
     * 适用于需要精确控制转账金额的场景。
     *
     * @param string $to 接收方地址（Base58格式）
     * @param float $amount 转账金额（SUN单位）
     *                      1代币 = 10^精度 SUN
     * @param string|null $tokenID 代币ID或名称（必填）
     * @param string|null $from 发送方地址（Base58格式，可选）
     *
     * @return array 交易广播结果
     *
     * @throws TronException 当以下情况时抛出：
     *                      - tokenID未提供
     *                      - 其他参数无效
     *
     * @example
     * // SUN单位发送（精度6的代币，发送1.5个代币）
     * $result = $tronWeb->token->sendTransaction(
     *     'TXYZ...',
     *     1500000,  // 1.5 * 10^6 SUN
     *     'MyToken'
     * );
     *
     * @see send() 自动转换单位发送
     */
    public function sendTransaction(string $to, float $amount, ?string $tokenID = null, ?string $from = null): array
    {
        if (!$tokenID) {
            throw new TronException('Token ID is required for token transaction');
        }

        return $this->send($to, $amount, $tokenID, $from);
    }

    /**
     * 创建TRC10代币
     *
     * 在Tron网络上发行新的TRC10标准代币。
     *
     * @param array $tokenOptions 代币配置参数数组，必需包含：
     *                           - name: 代币名称（字符串）
     *                           - abbr: 代币缩写（字符串，最大6字符）
     *                           - total_supply: 总发行量（整数）
     *                           - trx_num: TRX兑换比例（整数，每TRX兑换多少代币）
     *                           - num: 代币兑换比例（整数，每多少代币换1 TRX）
     *                           - start_time: 销售开始时间（Unix时间戳，毫秒）
     *                           - end_time: 销售结束时间（Unix时间戳，毫秒）
     *                           可选包含：
     *                           - description: 代币描述
     *                           - url: 代币官网URL
     *                           - precision: 代币精度（默认6）
     *                           - freeBandwidth: 免费带宽总量
     *                           - freeBandwidthLimit: 每账户免费带宽限制
     *                           - frozen_supply: 冻结供应量数组
     *
     * @return array 交易广播结果，包含：
     *               - transaction: 交易详情
     *               - token_id: 生成的代币ID
     *
     * @throws TronException 当必填参数缺失或格式无效时抛出
     *
     * @example
     * $options = [
     *     'name' => 'My Token',
     *     'abbr' => 'MTK',
     *     'total_supply' => 1000000,
     *     'trx_num' => 1,
     *     'num' => 1,
     *     'start_time' => time() * 1000,
     *     'end_time' => (time() + 86400000) * 1000,
     *     'description' => 'My first token',
     *     'url' => 'https://mytoken.com'
     * ];
     *
     * $result = $tronWeb->token->createToken($options);
     * echo "代币ID: " . $result['token_id'];
     *
     * @see updateToken() 更新代币信息
     * @see purchaseToken() 购买代币
     */
    public function createToken(array $tokenOptions): array
    {

        $requiredFields = ['name', 'abbr', 'total_supply', 'trx_num', 'num', 'start_time', 'end_time'];
        foreach ($requiredFields as $field) {
            if (!isset($tokenOptions[$field])) {
                throw new TronException("Missing required field: {$field}");
            }
        }

        return $this->tronWeb->transactionBuilder->createToken($tokenOptions);
    }

    /**
     * 购买代币（参与发行）
     *
     * 参与TRC10代币的公开销售。
     * 向代币发行者支付TRX以获得代币。
     *
     * @param string $issuerAddress 代币发行者地址（Base58格式）
     * @param string $tokenID 代币ID或名称
     * @param float $amount 购买金额（TRX单位）
     *                     会自动转换为SUN单位
     * @param string|null $buyer 购买者地址（Base58格式，可选）
     *                          如不提供则使用当前账户地址
     *
     * @return array 交易广播结果
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 代币销售已结束
     *                      - 发行者地址无效
     *                      - TRX余额不足
     *
     * @example
     * // 购买代币
     * $result = $tronWeb->token->purchaseToken(
     *     'TXYZ...',  // 发行者地址
     *     'MyToken',  // 代币ID
     *     100         // 支付100 TRX
     * );
     *
     * echo "购买成功！交易ID: " . $result['txid'];
     *
     * @see createToken() 创建代币
     * @see send() 发送代币
     */
    public function purchaseToken(string $issuerAddress, string $tokenID, float $amount, ?string $buyer = null): array
    {
        $buyer = $buyer ?: $this->tronWeb->getAddress()['hex'];

        $transaction = $this->tronWeb->transactionBuilder->purchaseToken(
            TronUtils::toHex($issuerAddress),
            $tokenID,
            TronUtils::toSun($amount),
            $buyer
        );
        $signedTransaction = $this->tronWeb->trx->signTransaction($transaction);
        return $this->tronWeb->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 更新代币信息
     *
     * 修改已发行代币的描述、URL和带宽参数。
     * 只能由代币创建者调用。
     *
     * @param string $description 代币描述（UTF-8字符串）
     * @param string $url 代币官网URL（有效URL格式）
     * @param int $freeBandwidth 免费带宽总量（可选，默认0）
     *                          为所有持有者提供的总免费带宽
     * @param int $freeBandwidthLimit 每账户免费带宽限制（可选，默认0）
     *                               每个账户可使用的免费带宽上限
     * @param string|null $ownerAddress 代币所有者地址（Base58格式，可选）
     *                                  如不提供则使用当前账户地址
     *
     * @return array 交易广播结果
     *
     * @throws TronException 当以下情况时抛出：
     *                      - URL格式无效
     *                      - 带宽参数无效
     *                      - 不是代币所有者
     *
     * @example
     * // 更新代币信息
     * $result = $tronWeb->token->updateToken(
     *     'This is an updated description',
     *     'https://new-website.com',
     *     1000000,
     *     10000
     * );
     *
     * @see createToken() 创建代币
     * @see getIssuedByAddress() 查询地址发行的代币
     */
    public function updateToken(string $description, string $url, int $freeBandwidth = 0, int $freeBandwidthLimit = 0, ?string $ownerAddress = null): array
    {
        $owner = $ownerAddress ?: $this->tronWeb->getAddress()['hex'];

        $transaction = $this->tronWeb->transactionBuilder->updateToken(
            $description,
            $url,
            $freeBandwidth,
            $freeBandwidthLimit,
            $owner
        );

        $signedTransaction = $this->tronWeb->trx->signTransaction($transaction);
        return $this->tronWeb->trx->sendRawTransaction($signedTransaction);
    }

    /**
     * 查询地址发行的代币
     *
     * 获取指定地址发行的所有TRC10代币列表。
     *
     * @param string|null $address 账户地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     *
     * @return array 代币发行信息，包含：
     *               - assetIssue: 代币列表数组
     *               - [其他字段]
     *
     * @throws TronException 当地址格式无效时抛出
     *
     * @example
     * // 查询发行的代币
     * $tokens = $tronWeb->token->getIssuedByAddress('TXYZ...');
     *
     * foreach ($tokens['assetIssue'] ?? [] as $token) {
     *     echo "代币名称: " . $token['name'];
     *     echo "代币ID: " . $token['id'];
     * }
     *
     * @see getTokensIssuedByAddress() 获取代币详细信息
     */
    public function getIssuedByAddress(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        return $this->tronWeb->request('wallet/getassetissuebyaccount', [
            'address' => $addressHex
        ]);
    }

    /**
     * 通过代币名称查询
     *
     * 根据代币名称获取代币详细信息。
     *
     * @param string $tokenName 代币名称
     *                         精确匹配代币的name字段
     *                         区分大小写
     *
     * @return array 代币详细信息，包含：
     *               - id: 代币ID
     *               - name: 代币名称
     *               - abbr: 代币缩写
     *               - total_supply: 总发行量
     *               - precision: 精度
     *               - [其他代币属性]
     *
     * @throws TronException 当代币不存在时抛出
     *
     * @example
     * $token = $tronWeb->token->getFromName('MyToken');
     *
     * echo "代币ID: " . $token['id'];
     * echo "总发行量: " . $token['total_supply'];
     * echo "精度: " . $token['precision'];
     *
     * @see getById() 通过ID查询
     * @see list() 获取所有代币
     */
    public function getFromName(string $tokenName): array
    {
        return $this->tronWeb->request('wallet/getassetissuebyname', [
            'value' => TronUtils::stringToHex($tokenName)
        ]);
    }

    /**
     * 通过代币ID查询
     *
     * 根据代币ID获取代币详细信息。
     * ID是代币的唯一数字标识符。
     *
     * @param string $tokenId 代币ID
     *                       格式：数字字符串，如'1000001'
     *
     * @return array 代币详细信息
     *
     * @throws TronException 当代币不存在时抛出
     *
     * @example
     * $token = $tronWeb->token->getById('1000001');
     *
     * echo "代币名称: " . $token['name'];
     * echo "缩写: " . $token['abbr'];
     *
     * @see getFromName() 通过名称查询
     * @see getTokenFromID() 兼容查询方法
     */
    public function getById(string $tokenId): array
    {
        return $this->tronWeb->request('wallet/getassetissuebyid', [
            'value' => $tokenId
        ]);
    }

    /**
     * 获取代币列表
     *
     * 查询所有已发行的TRC10代币。
     * 支持分页查询大量代币。
     *
     * @param int $limit 每次获取数量（可选，默认0获取全部）
     *                   范围：1-100
     * @param int $offset 分页偏移量（可选，默认0）
     *
     * @return array 代币列表数组
     *               每个元素包含代币基本信息
     *
     * @throws TronException 当参数无效时抛出
     *
     * @example
     * // 获取全部代币
     * $allTokens = $tronWeb->token->list();
     *
     * // 分页获取
     * $page1 = $tronWeb->token->list(20, 0);
     * $page2 = $tronWeb->token->list(20, 20);
     *
     * @see getById() 获取特定代币详情
     */
    public function list(int $limit = 0, int $offset = 0): array
    {
        if ($limit <= 0) {
            return $this->tronWeb->request('wallet/getassetissuelist', [])['assetIssue'] ?? [];
        }

        return $this->tronWeb->request('wallet/getpaginatedassetissuelist', [
            'limit' => $limit,
            'offset' => $offset
        ])['assetIssue'] ?? [];
    }

    /**
     * 批量查询代币信息
     *
     * 根据名称或ID批量获取代币信息。
     * 如果找不到会尝试通过另一种方式查找。
     *
     * @param array|string $tokenNames 代币名称或ID
     *                                 支持单个字符串或字符串数组
     *
     * @return array 代币信息数组
     *               单个输入时返回单个结果
     *               数组输入时返回结果数组
     *               查找失败的元素包含error字段
     *
     * @throws TronException 当输入格式无效时抛出
     *
     * @example
     * // 单个查询
     * $token = $tronWeb->token->getTokenListByName('MyToken');
     *
     * // 批量查询
     * $tokens = $tronWeb->token->getTokenListByName(['Token1', 'Token2', 'Token3']);
     *
     * @see getFromName() 单个名称查询
     * @see getById() 单个ID查询
     */
    public function getTokenListByName(array|string $tokenNames): array
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
     * 通过ID获取代币信息
     *
     * 统一的代币ID查询方法。
     * 自动处理数字和字符串格式。
     *
     * @param mixed $tokenId 代币ID
     *                      支持整数或字符串格式
     *                      如：1000001 或 '1000001'
     *
     * @return array 代币详细信息
     *
     * @throws TronException 当ID格式无效或代币不存在时抛出
     *
     * @example
     * // 数字ID
     * $token = $tronWeb->token->getTokenFromID(1000001);
     *
     * // 字符串ID
     * $token = $tronWeb->token->getTokenFromID('1000001');
     *
     * @see getById() 字符串ID查询
     */
    public function getTokenFromID(mixed $tokenId): array
    {
        if (is_int($tokenId)) {
            $tokenId = (string)$tokenId;
        }

        if (!is_string($tokenId) || empty($tokenId)) {
            throw new TronException('Invalid token ID provided');
        }

        return $this->getById($tokenId);
    }

    /**
     * 查询地址发行的代币详情
     *
     * 获取指定地址发行的所有代币的完整信息。
     * 比getIssuedByAddress()返回更详细的数据。
     *
     * @param string|null $address 账户地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     *
     * @return array 代币详细信息列表
     *               每个元素包含代币的全部属性
     *
     * @throws TronException 当地址格式无效时抛出
     *
     * @example
     * $tokens = $tronWeb->token->getTokensIssuedByAddress('TXYZ...');
     *
     * foreach ($tokens as $token) {
     *     echo "代币: {$token['name']} ({$token['abbr']})";
     *     echo "总发行量: {$token['total_supply']}";
     * }
     *
     * @see getIssuedByAddress() 获取代币基本信息
     */
    public function getTokensIssuedByAddress(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        if (!TronUtils::isAddress(TronUtils::fromHex($addressHex))) {
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