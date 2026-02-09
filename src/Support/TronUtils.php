<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Support;

use Dsdcr\TronWeb\Exception\TronException;

/**
 * Tron特定工具类，用于区块链操作
 * 扩展基础工具类，添加Tron特定功能
 *
 * @package Dsdcr\TronWeb\Support
 */
class TronUtils
{
    const TRX_TO_SUN = 1000000;           // TRX到SUN的转换比率
    const ADDRESS_SIZE = 34;              // 地址长度
    const ADDRESS_PREFIX = "41";          // 地址前缀
    const ADDRESS_PREFIX_BYTE = 0x41;     // 地址前缀字节

    /**
     * 链接验证
     *
     * @param $url
     * @return bool
     */
    public static function isValidUrl($url) :bool {
        return (bool)parse_url($url);
    }

    /**
     * 检查字符串是否为十六进制表示
     *
     * @param $str
     * @return bool
     */
    public static function isHex($str) : bool {
        return is_string($str) and ctype_xdigit($str);
    }

    /**
     * 检查传递的参数是否是数组
     *
     * @param $array
     * @return bool
     */
    public static function isArray($array) : bool {
        return is_array($array);
    }

    /**
     * 将TRX转换为SUN（最小单位）
     *
     * @param float $trx TRX数量
     * @return int SUN数量
     */
    public static function toSun(float $trx): int
    {
        return (int)($trx * self::TRX_TO_SUN);
    }

    /**
     * 将SUN转换为TRX
     *
     * @param int $sun SUN数量
     * @return float TRX数量
     */
    public static function fromSun(int $sun): float
    {
        return $sun / self::TRX_TO_SUN;
    }

    /**
     * 将Tron地址转换为十六进制格式
     *
     * @param string $address Tron地址
     * @return string 十六进制地址
     */
    public static function toHex(string $address): string
    {
        if(strlen($address) == 42 && mb_strpos($address, '41') == 0) {
            return $address;
        }
        return Base58Check::decode($address, 0, 3);
    }

    /**
     * 将十六进制地址转换为Tron base58格式
     *
     * @param string $hexAddress 十六进制地址
     * @return string base58格式地址
     * @throws TronException
     */
    public static function fromHex(string $hexAddress): string
    {
        if(!ctype_xdigit($hexAddress)) {
            return $hexAddress;
        }

        if(strlen($hexAddress) < 2 || (strlen($hexAddress) & 1) != 0) {
            return '';
        }
        return Base58Check::encode($hexAddress, 0, false);
    }

    /**
     * 验证Tron地址格式
     *
     * @param string $address 地址字符串
     * @return bool 是否有效
     */
    public static function isAddress(string $address): bool
    {
        if (strlen($address) !== self::ADDRESS_SIZE) {
            return false;
        }

        try {
            $decoded = Base58Check::decode($address, 0, 0, false);
            $utf8 = hex2bin($decoded);

            if (strlen($utf8) !== 25) return false;
            if (strpos($utf8, chr(self::ADDRESS_PREFIX_BYTE)) !== 0) return false;

            $checkSum = substr($utf8, 21);
            $addressData = substr($utf8, 0, 21);

            $hash0 = Hash::SHA256($addressData);
            $hash1 = Hash::SHA256($hash0);
            $checkSum1 = substr($hash1, 0, 4);

            return $checkSum === $checkSum1;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 从二进制地址数据获取base58校验地址
     *
     * @param string $addressBin 二进制地址数据
     * @return string base58校验地址
     */
    public static function getBase58CheckAddress(string $addressBin): string
    {
        $hash0 = Hash::SHA256($addressBin);
        $hash1 = Hash::SHA256($hash0);
        $checksum = substr($hash1, 0, 4);
        $checksum = $addressBin . $checksum;

        return Base58::encode(Crypto::bin2bc($checksum));
    }

    /**
     * 从公钥二进制数据生成地址十六进制
     *
     * @param string $pubKeyBin 公钥二进制数据
     * @return string 地址十六进制
     */
    public static function getAddressHex(string $pubKeyBin): string
    {
        if (strlen($pubKeyBin) == 65) {
            $pubKeyBin = substr($pubKeyBin, 1);
        }

        $hash = Keccak::hash($pubKeyBin, 256);
        return self::ADDRESS_PREFIX . substr($hash, 24);
    }

    /**
     * 格式化TRX金额，指定精度
     *
     * @param float $amount 金额
     * @param int $decimals 小数位数
     * @return string 格式化后的金额
     */
    public static function formatTrx(float $amount, int $decimals = 6): string
    {
        return number_format($amount, $decimals, '.', '');
    }

    /**
     * 将微秒时间戳转换为秒
     *
     * @param int $microTimestamp 微秒时间戳
     * @return int 秒时间戳
     */
    public static function microToSeconds(int $microTimestamp): int
    {
        return (int)($microTimestamp / 1000);
    }

    /**
     * 将秒时间戳转换为微秒
     *
     * @param int $secondsTimestamp 秒时间戳
     * @return int 微秒时间戳
     */
    public static function secondsToMicro(int $secondsTimestamp): int
    {
        return $secondsTimestamp * 1000;
    }

    /**
     * 生成加密安全的随机十六进制字符串
     *
     * @param int $length 长度（字符数）
     * @return string 随机十六进制字符串
     */
    public static function randomHex(int $length = 64): string
    {
        $bytes = random_bytes($length / 2);
        return bin2hex($bytes);
    }

    /**
     * 检查值是否为有效的区块标识符
     *
     * @param mixed $block 区块标识符
     * @return bool 是否有效
     */
    public static function isValidBlockIdentifier($block): bool
    {
        if ($block === null || $block === 'latest' || $block === 'earliest') {
            return true;
        }

        if (is_int($block) && $block >= 0) {
            return true;
        }

        if (is_string($block) && self::isHex($block)) {
            return true;
        }

        return false;
    }

    /**
     * 将字符串转换为UTF-8十六进制（别名）
     * 注意：这是一个简化的实现，用于向后兼容
     *
     * @param string $str 输入字符串
     * @return string 十六进制字符串
     */
    public static function toUtf8(string $str): string
    {
        // Simple implementation for backward compatibility
        $hex = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $hex .= dechex(ord($str[$i]));
        }
        return $hex;
    }

    /**
     * 将十六进制转换为UTF-8字符串（别名）
     * 注意：这是一个简化的实现，用于向后兼容
     *
     * @param string $hex 十六进制字符串
     * @return string UTF-8字符串
     */
    public static function fromUtf8(string $hex): string
    {
        // Simple implementation for backward compatibility
        $str = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }
        return $str;
    }

    /**
     * 将字符串转换为十六进制表示
     *
     * @param string $str 输入字符串
     * @return string 十六进制表示
     */
    public static function stringToHex(string $str): string
    {
        $hex = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $hex .= sprintf('%02x', ord($str[$i]));
        }
        return $hex;
    }

    /**
     * 将十六进制表示转换为字符串
     *
     * @param string $hex 十六进制字符串
     * @return string 原始字符串
     */
    public static function hexToString(string $hex): string
    {
        $str = '';
        $length = strlen($hex);
        for ($i = 0; $i < $length; $i += 2) {
            $str .= chr(hexdec(substr($hex, $i, 2)));
        }
        return $str;
    }

    /**
     * 从私钥获取地址
     *
     * 将私钥转换为对应地址。
     * 这是最常用的私钥转地址方法。
     *
     * @param string $privateKey 私钥（可选）
     *                          如不提供则使用当前账户私钥
     *                          64字符十六进制字符串
     * @param string $format 返回地址格式（可选，默认'base58'）
     *                      - 'base58': 返回Base58格式地址
     *                      - 'hex': 返回十六进制格式地址
     *
     * @return string 指定格式的地址字符串
     *
     * @throws TronException 当私钥未提供或格式无效时抛出
     *
     * @example
     * // 获取Base58地址
     * $address = $tronWeb->account->fromPrivateKey('private_key...');
     * echo "Base58地址: " . $address;
     *
     * // 获取Hex地址
     * $address = $tronWeb->account->fromPrivateKey('private_key...', 'hex');
     * echo "Hex地址: " . $address;
     *
     * // 使用当前账户私钥
     * $address = $tronWeb->account->fromPrivateKey();
     *
     * @see createWithPrivateKey() 创建完整账户对象
     */
    public static function fromPrivateKey(string $privateKey, string $format = 'base58'): string
    {

        if(!$privateKey) {
            throw new TronException('Missing private key');
        }
        $ec = new \Elliptic\EC('secp256k1');
        $priv = $ec->keyFromPrivate($privateKey);
        $pubKeyHex = $priv->getPublic(false, "hex");

        $pubKeyBin = hex2bin($pubKeyHex);
        $addressHex = TronUtils::getAddressHex($pubKeyBin);
        $addressBin = hex2bin($addressHex);
        $addressBase58 = TronUtils::getBase58CheckAddress($addressBin);
        if ($format === 'hex') {
            return $addressHex;
        }
        return $addressBase58;
    }

    /**
     * 从最小单位转换为指定精度的数值
     *
     * @param mixed $amount 金额（最小单位）
     * @param int $decimals 小数位数，默认6位
     * @return string 格式化后的金额字符串
     */
    public static function fromWei($amount, $decimals = 6): string
    {
        return number_format($amount / (10 ** $decimals), $decimals, '.', '');
    }
    
    /**
     * 转换科学记数法字符串为整数
     * 适用于处理大数字符串，如2.0E+25
     *
     * @param mixed $sciNotation 科学记数法字符串或数字
     * @return string 转换后的整数字符串
     *
     * @example
     * TronUtils::toDecimal('2.0E+25') // returns '20000000000000000000000000'
     * TronUtils::toDecimal('5e18')    // returns '5000000000000000000'
     */
    public static function toDecimal($sciNotation): string
    {
        if (is_array($sciNotation)) {
            // 如果是数组，尝试查找常见的键名
            $keys = ['balance', 'amount', 'value', 'amountIn', 'amountOut', 'result'];
            foreach ($keys as $key) {
                if (isset($sciNotation[$key])) {
                    return self::toDecimal($sciNotation[$key]);
                }
            }
            // 支持空键名数组（如 [''] => 1414389）
            if (isset($sciNotation['']) && (is_numeric($sciNotation['']) || is_string($sciNotation['']))) {
                return self::toDecimal($sciNotation['']);
            }
            // 如果包含数值键，使用第一个
            foreach ($sciNotation as $value) {
                if (is_numeric($value) || is_string($value)) {
                    return self::toDecimal($value);
                }
            }
            return '0';
        }

        $str = (string)$sciNotation;

        // 如果是普通数字，直接返回
        if (!preg_match('/[eE]/', $str)) {
            // 移除可能的小数部分
            if (strpos($str, '.') !== false) {
                return preg_replace('/\.0*$/', '', rtrim($str, '0'));
            }
            return $str;
        }

        // 处理科学记数法
        list($number, $exponent) = preg_split('/[eE]/', $str);
        $exponent = (int)$exponent;

        // 分离整数和小数部分
        if (strpos($number, '.') !== false) {
            list($integer, $fraction) = explode('.', $number);
            $fraction = rtrim($fraction, '0');
            $totalLength = strlen($integer) + strlen($fraction);

            if ($exponent > 0) {
                // 正指数：向右移动小数点
                $moveRight = $exponent - strlen($fraction);
                if ($moveRight >= 0) {
                    return $integer . $fraction . str_repeat('0', $moveRight);
                } else {
                    return $integer . substr($fraction, 0, $exponent) . '.' . substr($fraction, $exponent);
                }
            } else {
                // 负指数：向左移动小数点
                $moveLeft = abs($exponent);
                if ($moveLeft <= strlen($integer)) {
                    return substr($integer, 0, -$moveLeft) . '.' . substr($integer, -$moveLeft) . $fraction;
                } else {
                    return '0.' . str_repeat('0', $moveLeft - strlen($integer)) . $integer . $fraction;
                }
            }
        } else {
            // 没有小数部分
            if ($exponent > 0) {
                return $number . str_repeat('0', $exponent);
            } else {
                $moveLeft = abs($exponent);
                if ($moveLeft <= strlen($number)) {
                    return substr($number, 0, -$moveLeft) . '.' . substr($number, -$moveLeft);
                } else {
                    return '0.' . str_repeat('0', $moveLeft - strlen($number)) . $number;
                }
            }
        }
    }

}