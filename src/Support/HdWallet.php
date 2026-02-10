<?php
namespace Dsdcr\TronWeb\Support;

use Exception;
use Dsdcr\TronWeb\Support\Secp256K1;
/**
 * HD钱包支持 (BIP32/BIP44)
 * 提供分层确定性钱包的密钥派生功能
 */
class HdWallet
{
    /**
     * 从种子生成主密钥 (BIP32)
     */
    public static function hdMasterFromSeed(string $seed): array
    {
        $I = hash_hmac('sha512', $seed, 'Bitcoin seed', true);

        return [
            'key' => substr($I, 0, 32),
            'chain' => substr($I, 32),
            'depth' => 0,
            'index' => 0
        ];
    }

    /**
     * 派生BIP44路径
     */
    public static function derivePath(array $parent, string $path): array
    {
        $parts = explode('/', $path);
        $current = $parent;

        foreach ($parts as $part) {
            if ($part === 'm') continue;

            $hardened = strpos($part, "'") !== false;
            $index = intval(str_replace("'", "", $part));

            if ($hardened) {
                $index = 0x80000000 | $index;
            }

            $current = self::deriveChild($current, $index);
        }

        return $current;
    }

    /**
     * 派生子密钥
     */
    public static function deriveChild(array $parent, int $index): array
    {
        $key = $parent['key'];
        $chain = $parent['chain'];

        if ($index >= 0x80000000) {
            // 硬化派生
            $data = "\x00" . $key . pack('N', $index);
        } else {
            // 非硬化派生 - 使用压缩格式公钥（使用纯PHP实现）
            $secp = new Secp256K1();
            $privateKeyHex = bin2hex($key);
            $publicKeyHex = $secp->getPublicKey($privateKeyHex);

            // 获取压缩格式公钥（需要04前缀）
            $compressedPubKey = '04' . $publicKeyHex; // 非压缩格式
            $data = hex2bin($compressedPubKey) . pack('N', $index);
        }

        $I = hash_hmac('sha512', $data, $chain, true);
        $IL = substr($I, 0, 32);

        // 计算子私钥
        $childKey = self::addPrivateKeys($key, $IL);

        return [
            'key' => $childKey,
            'chain' => substr($I, 32),
            'depth' => $parent['depth'] + 1,
            'index' => $index
        ];
    }

    /**
     * 私钥相加（椭圆曲线模运算）
     */
    public static function addPrivateKeys(string $key1, string $key2): string
    {
        $k1 = gmp_init(bin2hex($key1), 16);
        $k2 = gmp_init(bin2hex($key2), 16);

        // secp256k1曲线的阶
        $n = gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16);

        $sum = gmp_mod(gmp_add($k1, $k2), $n);

        // 检查是否为0，如果是0则无效
        if (gmp_cmp($sum, 0) == 0) {
            throw new Exception("Invalid child key");
        }

        return hex2bin(str_pad(gmp_strval($sum, 16), 64, '0', STR_PAD_LEFT));
    }

    /**
     * 从助记词生成完整账户信息 (BIP39/BIP32/BIP44)
     *
     * @param string $mnemonic 助记词
     * @param string $passphrase 密码短语
     * @param string $path BIP44派生路径
     * @return array 包含私钥、公钥、地址等信息
     */
    public static function fromMnemonic(
        ?string $mnemonic = null,
        string $path = "m/44'/195'/0'/0/0",
        string $passphrase = '',
        int $wordCount= 12
    ): array {
        $mnemonic = $mnemonic ?? \Dsdcr\TronWeb\Support\Bip39::generateMnemonic($wordCount);
        // 验证助记词
        if (!Bip39::validateMnemonic($mnemonic)) {
            throw new Exception("Invalid mnemonic phrase");
        }

        // 从助记词生成种子
        $seed = Bip39::mnemonicToSeed($mnemonic, $passphrase);

        // 从种子生成主密钥
        $master = self::hdMasterFromSeed($seed);

        // 派生指定路径
        $derived = self::derivePath($master, $path);

        // 获取私钥十六进制
        $privateKeyHex = bin2hex($derived['key']);

        // 生成公钥（使用纯PHP实现）
        $secp = new Secp256K1();
        $publicKeyHex = $secp->getPublicKey($privateKeyHex);

        // 生成地址（使用现有的 TronUtils）
        $pubKeyBin = hex2bin($publicKeyHex);
        $addressHex = \Dsdcr\TronWeb\Support\TronUtils::getAddressHex($pubKeyBin);
        $addressBin = hex2bin($addressHex);
        $addressBase58 = \Dsdcr\TronWeb\Support\TronUtils::getBase58CheckAddress($addressBin);

        return [
            'mnemonic' => $mnemonic,
            'passphrase' => $passphrase,
            'private_key' => $privateKeyHex,
            'public_key' => $publicKeyHex,
            'address_hex' => $addressHex,
            'address_base58' => $addressBase58,
            'derivation_path' => $path,
            'seed' => bin2hex($seed)
        ];
    }

    /**
     * 生成新账户（随机助记词）
     *
     * @param int $wordCount 助记词单词数量（12, 15, 18, 21, 24）
     * @param string $passphrase 密码短语
     * @param string $path BIP44派生路径
     * @return array 账户信息
     */
    public static function createAccount(
        int $wordCount = 12,
        string $passphrase = '',
        string $path = "m/44'/195'/0'/0/0"
    ): array {
        // 生成随机助记词
        $mnemonic = Bip39::generateMnemonic($wordCount);

        // 生成账户
        return self::fromMnemonic($mnemonic, $passphrase, $path);
    }
}