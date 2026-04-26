<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Exception;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\Entities\TronAddress;

/**
 * Account模块 - Tron网络账户管理和地址操作的核心模块
 *
 * 提供完整的账户管理功能，包括：
 * - 新账户生成（随机、密钥对）
 * - 地址格式转换（Base58 ↔ Hex）
 * - 账户信息查询（余额、资源、交易历史）
 * - 账户注册和名称管理
 * - 批量余额查询
 * - 助记词账户生成（BIP39/BIP44标准）
 *
 * 主要特性：
 * - 完整的地址生命周期管理
 * - 支持多种账户创建方式
 * - 自动格式验证和转换
 * - 批量操作支持
 * - 符合BIP39/BIP44标准的钱包功能
 *
 * @package Dsdcr\TronWeb\Modules
 * @since 1.0.0
 */
class Account extends BaseModule
{
    /**
     * Tron地址标准长度
     * Base58格式地址固定34个字符
     */
    const ADDRESS_SIZE = 34;

    /**
     * Hex地址前缀
     * Tron地址十六进制格式以0x41开头
     */
    const ADDRESS_PREFIX = "41";

    /**
     * 地址前缀字节值
     * 0x41 对应ASCII字符'A'
     */
    const ADDRESS_PREFIX_BYTE = 0x41;

    /**
     * 生成新的随机账户
     *
     * 使用安全的椭圆曲线加密算法生成新的密钥对。
     * 这是创建新Tron账户的标准方式。
     *
     * @return TronAddress 账户信息对象，包含：
     *                    - private_key: 私钥（64字符十六进制）
     *                    - public_key: 公钥（未压缩格式）
     *                    - address_hex: 十六进制地址（42字符）
     *                    - address_base58: Base58地址（34字符）
     *
     * @throws TronException 当加密库初始化失败时抛出
     *
     * @example
     * // 创建新账户
     * $account = $tronWeb->account->create();
     *
     * echo "新地址: " . $account->getAddress();
     * echo "私钥: " . $account->getPrivateKey();
     *
     * // 后续充值后即可使用
     *
     * @see createWithPrivateKey() 通过私钥创建账户
     * @see generateAccountWithMnemonic() 通过助记词创建账户
     */
    public function create(): TronAddress
    {
        $secp = new \Dsdcr\TronWeb\Support\Secp256K1();
        $privateKey = $secp->generatePrivateKey();
        $pubKeyHex = $secp->getPublicKey($privateKey);

        $pubKeyBin = hex2bin($pubKeyHex);
        $addressHex = TronUtils::getAddressHex($pubKeyBin);
        $addressBin = hex2bin($addressHex);
        $addressBase58 = TronUtils::getBase58CheckAddress($addressBin);

        return new TronAddress([
            'private_key' => $privateKey,
            'public_key' => $pubKeyHex,
            'address_hex' => $addressHex,
            'address_base58' => $addressBase58
        ]);
    }

    /**
     * 查询账户详细信息
     *
     * 从固态节点获取指定账户的完整信息。
     * 这是最常用的账户查询方法。
     *
     * @param string|null $address 要查询的地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     *
     * @return array 账户信息，包含：
     *               - address: 地址
     *               - balance: 余额（SUN单位）
     *               - assetV2: 代币余额映射
     *               - [其他账户属性字段]
     *
     * @throws TronException 当地址格式无效或查询失败时抛出
     *
     * @example
     * // 查询当前账户信息
     * $account = $tronWeb->account->getAccount();
     *
     * echo "余额: " . $account['balance'] . " SUN";
     * echo "代币: " . json_encode($account['assetV2'] ?? []);
     *
     * // 查询指定地址
     * $account = $tronWeb->account->getAccount('TXYZ...');
     *
     * @see getAccountresource() 获取账户资源信息
     * @see validateAddress() 验证地址有效性
     */
    public function getAccount(?string $address = null): array
    {
        $addressHex = $address ? \Dsdcr\TronWeb\Support\TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];
        return $this->tronWeb->request('walletsolidity/getaccount', [
            'address' => $addressHex
        ]);
    }

    /**
     * 获取指定地址的账户信息
     *
     * 该方法通过Tron区块链API查询指定地址的账户详细信息，包括：
     * - 账户余额（TRX和代币余额）
     * - 账户创建时间
     * - 账户资源信息（带宽、能量等）
     * - 账户权限设置
     * - 关联的智能合约信息
     *
     * @param string $address 要查询的Tron账户地址（Base58格式）
     * @return array 返回账户信息数组，包含账户的基本信息和资产详情
     * @throws TronException 当查询失败或地址格式无效时抛出
     */
    public function getaccounts(string $address): array
    {
        return $this->tronWeb->request("v1/accounts/{$address}", [], 'get');
    }

    /**
     * 验证Tron地址的有效性
     *
     * 通过区块链API验证地址格式和有效性。
     * 与本地验证（TronUtils::isAddress）不同，
     * 此方法会向节点查询地址是否真实存在。
     *
     * @param string $address 要验证的地址
     *                       支持Base58格式或Hex格式
     * @param bool $hex 地址是否为十六进制格式（可选，默认false）
     *                  true: 输入为Hex格式
     *                  false: 输入为Base58格式
     *
     * @return array 验证结果，包含：
     *               - result: 验证是否通过（true/false）
     *               - [其他验证相关信息]
     *
     * @throws TronException 当网络请求失败时抛出
     *
     * @example
     * // 验证Base58地址
     * $result = $tronWeb->account->validateAddress('TXYZ...');
     * echo "地址有效: " . ($result['result'] ? '是' : '否');
     *
     * // 验证Hex地址
     * $result = $tronWeb->account->validateAddress('4100...', true);
     *
     * @see \Dsdcr\TronWeb\Support\TronUtils::isAddress() 本地验证
     */
    public function validateAddress(string $address, bool $hex = false): array
    {
        try {
            // 使用新的验证服务
            $normalizedAddress = $hex ? $address : \Dsdcr\TronWeb\Support\AddressValidator::validateAndNormalize($address, 'address', 'hex');

            return $this->tronWeb->request('wallet/validateaddress', [
                'address' => $normalizedAddress
            ]);
        } catch (TronException $e) {
            return [
                'result' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 查询账户资源详情
     *
     * 获取账户的带宽和能量资源信息。
     * 包括已使用量、剩余量、限制等详细数据。
     *
     * @param string|null $address 要查询的地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     *
     * @return array 资源信息，包含：
     *               - EnergyLimit: 能量上限
     *               - EnergyUsed: 已用能量
     *               - NetLimit: 带宽上限
     *               - NetUsed: 已用带宽
     *               - freeNetLimit: 免费带宽上限
     *               - freeNetUsed: 免费带宽已用量
     *               - [其他资源相关字段]
     *
     * @throws TronException 当地址格式无效或查询失败时抛出
     *
     * @example
     * // 获取资源信息
     * $resource = $tronWeb->account->getAccountresource();
     *
     * echo "能量上限: " . $resource['EnergyLimit'];
     * echo "已用能量: " . $resource['EnergyUsed'];
     * echo "能量剩余: " . ($resource['EnergyLimit'] - $resource['EnergyUsed']);
     *
     * @see getAccount() 获取账户基本信息
     */
    public function getAccountresource(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        return $this->tronWeb->request('wallet/getaccountresource', [
            'address' => $addressHex
        ]);
    }

    /**
     * 查询指定代币的余额
     *
     * 获取账户持有的TRC10代币数量。
     *
     * @param int $tokenId 代币ID
     *                    TRC10代币的唯一数字标识
     *                    例如：1000001
     * @param string|null $address 要查询的地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     * @param bool $fromTron 是否从SUN单位转换为代币精度单位（可选，默认false）
     *                      注意：大多数TRC10代币精度为6，但不是所有代币都如此
     *
     * @return float 代币余额
     *              如果账户不持有该代币，返回0.0
     *
     * @throws TronException 当地址格式无效或查询失败时抛出
     *
     * @example
     * // 查询代币余额
     * $balance = $tronWeb->account->getTokenBalance(1000001);
     * echo "代币余额: " . $balance;
     *
     * // 查询指定地址的代币余额
     * $balance = $tronWeb->account->getTokenBalance(1000001, 'TXYZ...');
     *
     * @see getAccount() 获取所有代币余额
     */
    public function getTokenBalance(int $tokenId, ?string $address = null, bool $fromTron = false): float
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        $account = $this->tronWeb->request('walletsolidity/getaccount', [
            'address' => $addressHex
        ]);

        if (!isset($account['assetV2']) || !isset($account['assetV2'][$tokenId])) {
            return 0.0;
        }

        $balance = (int)$account['assetV2'][$tokenId];
        return $fromTron ? TronUtils::sunToTrx($balance) : $balance;
    }

    /**
     * 查询账户的交易历史
     *
     * 获取与指定地址相关的所有交易记录。
     * 支持按交易方向（转入/转出）筛选和TRC20代币交易查询。
     *
     * @param string $address    钱包地址 (Base58 格式，如 T...)
     * @param string $direction  交易方向：'all' (全部), 'to' (入账), 'from' (出账)
     * @param int    $limit      每页返回的交易数量 (最大支持 200)
     * @param string $contract   合约地址 (留空表示查询 TRX；传 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t' 查询 USDT)
     * @param array  $options    可选的高级过滤参数，包含但不限于：
     *                           - 'fingerprint'    => (string) 分页标识。从上次请求结果的 meta.fingerprint 中获取，用于查询下一页。
     *                           - 'min_timestamp'  => (int)    起始时间戳 (毫秒)。查询此时间之后的交易。
     *                           - 'max_timestamp'  => (int)    结束时间戳 (毫秒)。查询此时间之前的交易。
     *                           - 'order_by'       => (string) 排序。常用 'block_timestamp,desc' (倒序) 或 'block_timestamp,asc' (正序)。
     *                           - 'only_confirmed' => (string) 是否仅查询已确认交易。'true' (默认) 或 'false'。
     *                           - 'only_unconfirmed' => (string) 是否仅查询未确认交易。'true' 或 'false' (仅在查询 TRX 时有效)。
     *
     * @return array 交易列表数组
     *               每个交易包含时间戳、金额、对方地址、代币信息等
     *
     * @throws TronException 当参数无效或查询失败时抛出
     *
     * @example
     * // 获取所有TRX交易
     * $transactions = $tronWeb->account->getTransactions('TXYZ...');
     *
     * // 获取最近的10笔转入交易
     * $transactions = $tronWeb->account->getTransactions('TXYZ...', 'to', 10);
     *
     * // 查询USDT代币交易
     * $usdtTransactions = $tronWeb->account->getTransactions(
     *     'TXYZ...',
     *     'all',
     *     30,
     *     'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
     * );
     *
     * // 使用分页指纹继续查询
     * $nextPage = $tronWeb->account->getTransactions(
     *     'TXYZ...',
     *     'all',
     *     30,
     *     '',
     *     ['fingerprint' => 'next_page_token']
     * );
     *
     * @see \Dsdcr\TronWeb\Modules\Trx::getTransaction() 通过交易ID查询详情
     */
    public function getTransactions(
        string $address,
        string $direction = 'all',
        int $limit = 30,
        string $contract = '',
        array $options = []
    ): array {
        if (!\Dsdcr\TronWeb\Support\TronUtils::isAddress($address)) {
            throw new TronException('Invalid TRON address: ' . $address);
        }

        if ($contract !== '' && !\Dsdcr\TronWeb\Support\TronUtils::isAddress($contract)) {
            throw new TronException('Invalid TRC20 contract address: ' . $contract);
        }

        $path = "v1/accounts/" . $address . ($contract !== '' ? "/transactions/trc20" : "/transactions");

        $params = [
            'limit' => min(max(1, $limit), 200), // 限制1-200之间
            'only_confirmed' => 'true'
        ];

        if ($direction === 'to') {
            $params['only_to'] = 'true';
        } elseif ($direction === 'from') {
            $params['only_from'] = 'true';
        } elseif ($direction !== 'all') {
            throw new TronException('Invalid direction parameter. Must be "all", "to", or "from"');
        }

        if ($contract !== '') {
            $params['contract_address'] = $contract;
        }

        $finalParams = array_merge($params, $options);

        return $this->tronWeb->request($path, $finalParams, 'get');
    }

    /**
     * 更新账户名称
     *
     * 设置或修改账户的可读名称。
     * 账户名称在区块链上公开可见。
     *
     * @param string $accountName 新的账户名称
     *                           UTF-8字符串，最长32字节
     *                           可以包含字母、数字、空格等
     * @param string|null $address 要修改的地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     *
     * @return array 操作结果，包含：
     *               - transaction: 交易详情
     *               - [其他返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 名称格式无效或过长
     *                      - 地址格式无效
     *                      - 账户余额不足支付交易费用
     *
     * @example
     * // 修改当前账户名称
     * $result = $tronWeb->account->changeName('My Tron Wallet');
     *
     * // 修改指定账户名称
     * $result = $tronWeb->account->changeName('Token Holder', 'TXYZ...');
     *
     * @see getAccount() 验证名称是否已更新
     */
    public function changeName(string $accountName, ?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];

        return $this->tronWeb->request('wallet/updateaccount', [
            'account_name' => TronUtils::stringToHex($accountName),
            'owner_address' => $addressHex
        ]);
    }

    /**
     * 创建新账户
     *
     * 为已存在的基础账户创建一个关联的新账户。
     * 新账户需要一个已激活的账户作为创建者。
     *
     * 注意：在Tron网络中，新账户需要由已激活账户创建。
     *
     * @param string $newAccountAddress 新账户地址（Base58格式）
     *                                 要创建的新账户地址
     *                                 该地址必须是未激活的新地址
     * @param string|null $address 创建者地址（Base58格式，可选）
     *                            如不提供则使用当前账户地址
     *                            创建者账户必须已激活
     *
     * @return array 创建结果，包含：
     *               - transaction: 交易详情
     *               - [其他返回字段]
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 地址格式无效
     *                      - 新账户地址已被使用
     *                      - 创建者账户余额不足
     *
     * @example
     * // 创建新账户
     * $result = $tronWeb->account->register('TABC...');
     *
     * echo "新账户创建成功: " . $result['transaction']['txID'];
     *
     * @see create() 创建全新随机账户
     */
    public function register(string $newAccountAddress, ?string $address = null): array
    {
        $addressHex = $address ? TronUtils::toHex($address) : $this->tronWeb->getAddress()['hex'];
        $newAccountHex = TronUtils::toHex($newAccountAddress);

        return $this->tronWeb->request('wallet/createaccount', [
            'owner_address' => $addressHex,
            'account_address' => $newAccountHex
        ]);
    }

    /**
     * 通过私钥创建账户对象
     *
     * 从已知的私钥生成对应的账户信息。
     * 用于从备份恢复账户。
     *
     * @param string $privateKey 私钥（64字符十六进制字符串）
     *                          必须是有效的secp256k1私钥
     *                          格式：'abc123...'
     *
     * @return TronAddress 账户信息对象，包含：
     *                    - private_key: 输入的私钥
     *                    - public_key: 对应的公钥
     *                    - address_hex: 十六进制地址
     *                    - address_base58: Base58地址
     *
     * @throws TronException 当私钥格式无效时抛出
     *
     * @example
     * // 从私钥恢复账户
     * $account = $tronWeb->account->createWithPrivateKey('your_private_key_here...');
     *
     * echo "恢复的地址: " . $account->getAddress();
     *
     * @see create() 创建全新随机账户
     */
    public function createWithPrivateKey(string $privateKey): TronAddress
    {
        $secp = new \Dsdcr\TronWeb\Support\Secp256K1();
        $pubKeyHex = $secp->getPublicKey($privateKey);

        $pubKeyBin = hex2bin($pubKeyHex);
        $addressHex = TronUtils::getAddressHex($pubKeyBin);
        $addressBin = hex2bin($addressHex);
        $addressBase58 = TronUtils::getBase58CheckAddress($addressBin);

        return new TronAddress([
            'private_key' => $privateKey,
            'public_key' => $pubKeyHex,
            'address_hex' => $addressHex,
            'address_base58' => $addressBase58
        ]);
    }

    /**
     * 批量查询账户余额
     *
     * 一次性查询多个地址的TRX余额。
     * 适用于钱包应用或批量监控场景。
     *
     * @param array $accounts 要查询的地址数组
     *                       每个元素为Base58格式地址字符串
     *                       最大支持20个地址
     * @param bool $fromTron 是否转换为TRX单位（可选，默认false）
     *                      true: 返回TRX单位（1 TRX = 1,000,000 SUN）
     *                      false: 返回SUN单位
     * @param bool $validate 是否验证地址格式（可选，默认false）
     *                      true: 查询前验证每个地址
     *                      false: 不验证，直接查询
     *
     * @return array 余额结果数组，每个元素包含：
     *               - address: 查询的地址
     *               - balance: 余额数值
     *               - success: 是否查询成功
     *               - error: 错误信息（如果失败）
     *
     * @throws TronException 当以下情况时抛出：
     *                      - 地址数量超过20个
     *                      - 地址格式无效（当validate=true时）
     *
     * @example
     * // 批量查询余额
     * $balances = $tronWeb->account->getBalances([
     *     'TXYZ...',
     *     'TABC...',
     *     'TDEF...'
     * ]);
     *
     * foreach ($balances as $item) {
     *     echo "地址: {$item['address']} - 余额: {$item['balance']} SUN";
     * }
     *
     * // 转换为TRX单位并验证地址
     * $balances = $tronWeb->account->getBalances(
     *     ['TXYZ...', 'TABC...'],
     *     true,  // 转换为TRX
     *     true   // 验证地址
     * );
     *
     * @see getBalance() 查询单个地址余额
     */
    public function getBalances(array $accounts, bool $fromTron = false, bool $validate = false): array
    {
        if (count($accounts) > 20) {
            throw new TronException('Maximum 20 accounts can be checked at once');
        }

        $results = [];

        foreach ($accounts as $account) {
            if (!is_string($account)) {
                throw new TronException('Account addresses must be strings');
            }

            if ($validate && !TronUtils::isAddress($account)) {
                throw new TronException("Invalid address: {$account}");
            }

            try {
                $balance = $this->tronWeb->trx->getBalance($account, $fromTron);
                $results[] = [
                    'address' => $account,
                    'balance' => $balance,
                    'success' => true
                ];
            } catch (Exception $e) {
                $results[] = [
                    'address' => $account,
                    'balance' => 0.0,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
