<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Exception;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\Entities\TronAddress;

/**
 * 账户模块 - 用于地址管理和生成
 *
 * @package Dsdcr\TronWeb\Modules
 */
class Account extends BaseModule
{
    const ADDRESS_SIZE = 34;           // 地址长度
    const ADDRESS_PREFIX = "41";       // 地址前缀
    const ADDRESS_PREFIX_BYTE = 0x41;  // 地址前缀字节

    /**
     * 生成新的Tron账户
     *
     * @return TronAddress
     * @throws TronException
     */
    public function create(): TronAddress
    {
        return $this->generateAddress();
    }

    /**
     * 生成包含私钥/公钥对的新地址
     *
     * @return TronAddress
     * @throws TronException
     */
    public function generateAddress(): TronAddress
    {
        $ec = new \Elliptic\EC('secp256k1');
        $key = $ec->genKeyPair();
        $priv = $ec->keyFromPrivate($key->priv);
        $pubKeyHex = $priv->getPublic(false, "hex");

        $pubKeyBin = hex2bin($pubKeyHex);
        $addressHex = $this->getAddressHex($pubKeyBin);
        $addressBin = hex2bin($addressHex);
        $addressBase58 = $this->getBase58CheckAddress($addressBin);

        return new TronAddress([
            'private_key' => $priv->getPrivate('hex'),
            'public_key' => $pubKeyHex,
            'address_hex' => $addressHex,
            'address_base58' => $addressBase58
        ]);
    }

    /**
     * 获取账户信息
     *
     * @param string|null $address 地址
     * @return array 账户信息
     * @throws TronException
     */
    public function getInfo(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        return $this->request('walletsolidity/getaccount', [
            'address' => $addressHex
        ]);
    }

    /**
     * 验证Tron地址
     *
     * @param string $address 地址
     * @param bool $hex 地址是否为十六进制格式
     * @return array 验证结果
     * @throws TronException
     */
    public function validate(string $address, bool $hex = false): array
    {
        if ($hex) {
            $address = TronUtils::addressToHex($address);
        }

        return $this->request('wallet/validateaddress', [
            'address' => $address
        ]);
    }

    /**
     * 本地验证地址
     *
     * @param string $address 地址
     * @return bool 是否有效
     */
    public function isValidAddress(string $address): bool
    {
        return TronUtils::isValidTronAddress($address);
    }

    /**
     * 将地址转换为十六进制格式
     *
     * @param string $address 地址
     * @return string 十六进制地址
     * @throws TronException
     */
    public function toHex(string $address): string
    {
        return TronUtils::addressToHex($address);
    }

    /**
     * 将十六进制地址转换为base58格式
     *
     * @param string $hexAddress 十六进制地址
     * @return string base58格式地址
     */
    public function toBase58(string $hexAddress): string
    {
        return TronUtils::hexToAddress($hexAddress);
    }

    /**
     * 从公钥获取十六进制地址
     *
     * @param string $pubKeyBin 公钥二进制数据
     * @return string 十六进制地址
     */
    protected function getAddressHex(string $pubKeyBin): string
    {
        return TronUtils::getAddressHex($pubKeyBin);
    }

    /**
     * 从二进制地址获取base58校验地址
     *
     * @param string $addressBin 二进制地址数据
     * @return string base58校验地址
     */
    protected function getBase58CheckAddress(string $addressBin): string
    {
        return TronUtils::getBase58CheckAddress($addressBin);
    }

    /**
     * 获取账户资源（包括带宽、能量等）
     *
     * @param string|null $address 地址
     * @return array 资源信息
     * @throws TronException
     */
    public function getResources(?string $address = null): array
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        return $this->request('wallet/getaccountresource', [
            'address' => $addressHex
        ]);
    }

    /**
     * 获取账户带宽信息
     *
     * @param string|null $address 地址
     * @return array 带宽信息
     * @throws TronException
     */
    public function getBandwidth(?string $address = null): array
    {
        $resources = $this->getResources($address);

        return [
            'free_bandwidth' => $resources['free_bandwidth'] ?? 0,
            'bandwidth_limit' => $resources['bandwidth_limit'] ?? 0,
            'bandwidth_used' => $resources['bandwidth_used'] ?? 0,
            'net_bandwidth_used' => $resources['net_bandwidth_used'] ?? 0,
            'net_bandwidth_limit' => $resources['net_bandwidth_limit'] ?? 0,
        ];
    }

    /**
     * 获取地址的代币余额
     *
     * @param int $tokenId 代币ID
     * @param string|null $address 地址
     * @param bool $fromTron 是否从SUN转换为TRX
     * @return float 代币余额
     * @throws TronException
     */
    public function getTokenBalance(int $tokenId, ?string $address = null, bool $fromTron = false): float
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        $account = $this->request('walletsolidity/getaccount', [
            'address' => $addressHex
        ]);

        if (!isset($account['assetV2']) || !isset($account['assetV2'][$tokenId])) {
            return 0.0;
        }

        $balance = (int)$account['assetV2'][$tokenId];
        return $fromTron ? TronUtils::fromSun($balance) : $balance;
    }

    /**
     * 获取与地址相关的交易
     *
     * @param string $address 地址
     * @param string $direction 方向: 'to', 'from', 或 'all'
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @return array 交易列表
     * @throws TronException
     */
    public function getTransactions(string $address, string $direction = 'all', int $limit = 30, int $offset = 0): array
    {
        $addressHex = TronUtils::addressToHex($address);

        switch ($direction) {
            case 'to':
                return $this->request('walletextension/gettransactionstoaddress', [
                    'address' => $addressHex,
                    'limit' => $limit,
                    'offset' => $offset
                ]);

            case 'from':
                return $this->request('walletextension/gettransactionsfromaddress', [
                    'address' => $addressHex,
                    'limit' => $limit,
                    'offset' => $offset
                ]);

            case 'all':
            default:
                return $this->request('walletextension/gettransactionsrelated', [
                    'address' => $addressHex,
                    'direction' => 'all',
                    'limit' => $limit,
                    'offset' => $offset
                ]);
        }
    }

    /**
     * getInfo的别名（向后兼容）
     *
     * @param string|null $address 地址
     * @return array 账户信息
     * @throws TronException
     */
    public function getAccount(?string $address = null): array
    {
        return $this->getInfo($address);
    }

    /**
     * validate的别名（向后兼容）
     *
     * @param string $address 地址
     * @return array 验证结果
     * @throws TronException
     */
    public function validateAddress(string $address): array
    {
        return $this->validate($address);
    }

    /**
     * 更改账户名称
     *
     * @param string $accountName 账户名称
     * @param string|null $address 地址
     * @return array 更改结果
     * @throws TronException
     */
    public function changeName(string $accountName, ?string $address = null): array
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        return $this->request('wallet/updateaccount', [
            'account_name' => TronUtils::toUtf8($accountName),
            'owner_address' => $addressHex
        ]);
    }

    /**
     * 注册新账户
     *
     * @param string $newAccountAddress 新账户地址
     * @param string|null $address 当前账户地址
     * @return array 注册结果
     * @throws TronException
     */
    public function register(string $newAccountAddress, ?string $address = null): array
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];
        $newAccountHex = TronUtils::addressToHex($newAccountAddress);

        return $this->request('wallet/createaccount', [
            'owner_address' => $addressHex,
            'account_address' => $newAccountHex
        ]);
    }

    /**
     * changeName的别名（向后兼容）
     *
     * @param string $address 地址
     * @param string $accountName 账户名称
     * @return array 更改结果
     * @throws TronException
     */
    public function changeAccountName(string $address, string $accountName): array
    {
        return $this->changeName($accountName, $address);
    }

    /**
     * register的别名（向后兼容）
     *
     * @param string $address 当前账户地址
     * @param string $newAccountAddress 新账户地址
     * @return array 注册结果
     * @throws TronException
     */
    public function registerAccount(string $address, string $newAccountAddress): array
    {
        return $this->register($newAccountAddress, $address);
    }

    /**
     * 简化的地址验证
     *
     * @param string $address 地址
     * @return bool 是否有效
     */
    public function isAddress(string $address): bool
    {
        return $this->isValidAddress($address);
    }

    /**
     * 从私钥创建地址
     *
     * @param string $privateKey 私钥
     * @return TronAddress Tron地址对象
     * @throws TronException
     */
    public function createWithPrivateKey(string $privateKey): TronAddress
    {
        $ec = new \Elliptic\EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKey);
        $pubKeyHex = $key->getPublic(false, "hex");

        $pubKeyBin = hex2bin($pubKeyHex);
        $addressHex = $this->getAddressHex($pubKeyBin);
        $addressBin = hex2bin($addressHex);
        $addressBase58 = $this->getBase58CheckAddress($addressBin);

        return new TronAddress([
            'private_key' => $privateKey,
            'public_key' => $pubKeyHex,
            'address_hex' => $addressHex,
            'address_base58' => $addressBase58
        ]);
    }

    /**
     * 将私钥转换为公钥
     *
     * @param string $privateKey 私钥
     * @return string 公钥
     * @throws TronException
     */
    public function privateKeyToPublicKey(string $privateKey): string
    {
        try {
            if (!$this->isValidPrivateKey($privateKey)) {
                throw new TronException('Invalid private key format');
            }

            $ec = new \Elliptic\EC('secp256k1');
            $key = $ec->keyFromPrivate($privateKey, 'hex');
            $publicKey = $key->getPublic(false, 'hex'); // false表示未压缩格式

            if (substr($publicKey, 0, 2) !== '04') {
                throw new TronException('Invalid public key format: not uncompressed');
            }

            return substr($publicKey, 2);

        } catch (Exception $e) {
            throw new TronException('Failed to generate public key: ' . $e->getMessage());
        }
    }

    /**
     * 将公钥转换为地址
     *
     * @param string $publicKey 公钥
     * @return string 地址
     * @throws TronException
     */
    public function publicKeyToAddress(string $publicKey): string
    {
        try {
            if (!$this->isValidPublicKey($publicKey)) {
                throw new TronException('Invalid public key format');
            }

            $publicKeyBin = hex2bin('04' . $publicKey);

            $hash = \Dsdcr\TronWeb\Support\Keccak::hash($publicKeyBin, 256);

            $addressHex = '41' . substr($hash, 24);
            $addressBin = hex2bin($addressHex);

            return TronUtils::getBase58CheckAddress($addressBin);

        } catch (Exception $e) {
            throw new TronException('Failed to generate address from public key: ' . $e->getMessage());
        }
    }

    /**
     * 验证私钥格式
     *
     * @param string $privateKey 私钥
     * @return bool 是否有效
     */
    public function isValidPrivateKey(string $privateKey): bool
    {
        if (!is_string($privateKey) || strlen($privateKey) !== 64) {
            return false;
        }
        return preg_match('/^[0-9a-f]{64}$/i', $privateKey) === 1;
    }

    /**
     * 验证公钥格式
     *
     * @param string $publicKey 公钥
     * @return bool 是否有效
     */
    public function isValidPublicKey(string $publicKey): bool
    {
        if (!is_string($publicKey) || !in_array(strlen($publicKey), [128, 130])) {
            return false;
        }
        return preg_match('/^[0-9a-f]{128,130}$/i', $publicKey) === 1;
    }

    /**
     * 从私钥恢复地址
     *
     * @param string $privateKey 私钥
     * @return string 恢复的地址
     * @throws TronException
     */
    public function recoverAddressFromPrivateKey(string $privateKey): string
    {
        if (!$this->isValidPrivateKey($privateKey)) {
            throw new TronException('Invalid private key format');
        }

        $publicKey = $this->privateKeyToPublicKey($privateKey);
        return $this->publicKeyToAddress($publicKey);
    }

    /**
     * 生成随机账户（generateAddress的别名）
     *
     * @return TronAddress
     * @throws TronException
     */
    public function generateRandom(): TronAddress
    {
        return $this->generateAddress();
    }

    /**
     * 生成账户（generateAddress的别名）
     *
     * @return TronAddress
     * @throws TronException
     */
    public function generateAccount(): TronAddress
    {
        return $this->generateAddress();
    }

    /**
     * 生成助记词短语（BIP39标准）
     *
     * @param int $wordCount 单词数量（12, 15, 18, 21, 24）
     * @return string 助记词
     * @throws TronException
     */
    public function generateMnemonic(int $wordCount = 12): string
    {
        try {
            return \Dsdcr\TronWeb\Support\Bip39::generateMnemonic($wordCount);
        } catch (Exception $e) {
            throw new TronException('Failed to generate mnemonic: ' . $e->getMessage());
        }
    }

    /**
     * 从助记词生成账户（BIP39/BIP44标准）
     *
     * @param string|null $mnemonic 助记词短语（如果为null，则生成新的）
     * @param string $path BIP44衍生路径
     * @param string $passphrase 种子生成的密码短语
     * @return array 账户信息
     * @throws TronException
     */
    public function generateAccountWithMnemonic(
        ?string $mnemonic = null,
        string $path = "m/44'/195'/0'/0/0",
        string $passphrase = ''
    ): array {
        try {
            $mnemonic = $mnemonic ?? $this->generateMnemonic();

            // 使用完整BIP39+BIP44实现
            $account = \Dsdcr\TronWeb\Support\HdWallet::mnemonicToAccount(
                $mnemonic,
                $passphrase,
                $path
            );

            return [
                'private_key' => $account['private_key'],
                'public_key' => $account['public_key'],
                'address_hex' => $account['address_hex'],
                'address_base58' => $account['address_base58'],
                'mnemonic' => $mnemonic,
                'derivation_path' => $path,
                'passphrase' => $passphrase
            ];

        } catch (Exception $e) {
            throw new TronException('Failed to generate account from mnemonic: ' . $e->getMessage());
        }
    }

    /**
     * 验证助记词短语
     *
     * @param string $mnemonic 助记词
     * @return bool 是否有效
     */
    public function validateMnemonic(string $mnemonic): bool
    {
        return \Dsdcr\TronWeb\Support\Bip39::validateMnemonic($mnemonic);
    }

    /**
     * 从助记词生成种子（PBKDF2 - BIP39标准）
     *
     * @param string $mnemonic 助记词
     * @param string $passphrase 密码短语
     * @return string 种子
     * @throws TronException
     */
    public function mnemonicToSeed(string $mnemonic, string $passphrase = ''): string
    {
        try {
            return \Dsdcr\TronWeb\Support\Bip39::mnemonicToSeed($mnemonic, $passphrase);
        } catch (Exception $e) {
            throw new TronException('Failed to generate seed from mnemonic: ' . $e->getMessage());
        }
    }

    /**
     * 检查私钥是否从助记词衍生而来
     *
     * @param string $privateKey 要验证的私钥
     * @param string $mnemonic 要检查的助记词短语
     * @param string $path BIP44衍生路径
     * @param string $passphrase 使用的密码短语
     * @return bool 如果私钥与助记词衍生匹配则为true
     * @throws TronException
     */
    public function isPrivateKeyFromMnemonic(
        string $privateKey,
        string $mnemonic,
        string $path = "m/44'/195'/0'/0/0",
        string $passphrase = ''
    ): bool {
        if (!$this->isValidPrivateKey($privateKey)) {
            throw new TronException('Invalid private key format');
        }

        if (!\Dsdcr\TronWeb\Support\Bip39::validateMnemonic($mnemonic)) {
            throw new TronException('Invalid mnemonic phrase');
        }

        try {
            // Generate account from mnemonic
            $account = \Dsdcr\TronWeb\Support\HdWallet::mnemonicToAccount(
                $mnemonic,
                $passphrase,
                $path
            );

            // Compare private keys (case-insensitive)
            return strtolower($privateKey) === strtolower($account['private_key']);

        } catch (Exception $e) {
            throw new TronException('Failed to verify private key: ' . $e->getMessage());
        }
    }

    /**
     * 查找私钥来自哪个衍生路径
     *
     * @param string $privateKey 要查找的私钥
     * @param string $mnemonic 助记词短语
     * @param array $paths 要检查的路径数组
     * @param string $passphrase 使用的密码短语
     * @return string|null 匹配的路径，如果未找到则为null
     * @throws TronException
     */
    public function findPrivateKeyDerivationPath(
        string $privateKey,
        string $mnemonic,
        array $paths = [
            "m/44'/195'/0'/0/0",
            "m/44'/195'/0'/0/1",
            "m/44'/195'/0'/0/2",
            "m/44'/195'/0'/1/0",
            "m/44'/195'/0'/1/1"
        ],
        string $passphrase = ''
    ): ?string {
        if (!$this->isValidPrivateKey($privateKey)) {
            throw new TronException('Invalid private key format');
        }

        if (!\Dsdcr\TronWeb\Support\Bip39::validateMnemonic($mnemonic)) {
            throw new TronException('Invalid mnemonic phrase');
        }

        $privateKey = strtolower($privateKey);

        foreach ($paths as $path) {
            try {
                $account = \Dsdcr\TronWeb\Support\HdWallet::mnemonicToAccount(
                    $mnemonic,
                    $passphrase,
                    $path
                );

                if ($privateKey === strtolower($account['private_key'])) {
                    return $path;
                }
            } catch (Exception $e) {
                // Skip invalid paths and continue
                continue;
            }
        }

        return null;
    }

    /**
     * 获取多个账户的余额
     *
     * @param array $accounts 要检查的账户地址数组
     * @param bool $fromTron 是否从SUN转换为TRX
     * @param bool $validate 查询前验证地址
     * @return array 余额结果
     * @throws TronException
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

            if ($validate && !$this->isValidAddress($account)) {
                throw new TronException("Invalid address: {$account}");
            }

            try {
                $balance = $this->getTronWeb()->trx->getBalance($account, $fromTron);
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
