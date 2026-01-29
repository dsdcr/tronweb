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
class TronUtils extends Utils
{
    const TRX_TO_SUN = 1000000;           // TRX到SUN的转换比率
    const ADDRESS_SIZE = 34;              // 地址长度
    const ADDRESS_PREFIX = "41";          // 地址前缀
    const ADDRESS_PREFIX_BYTE = 0x41;     // 地址前缀字节

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
     * toSun的别名（向后兼容）
     *
     * @param float $trx TRX数量
     * @return int SUN数量
     */
    public static function toTron(float $trx): int
    {
        return self::toSun($trx);
    }

    /**
     * fromSun的别名（向后兼容）
     *
     * @param int $sun SUN数量
     * @return float TRX数量
     */
    public static function fromTron(int $sun): float
    {
        return self::fromSun($sun);
    }

    /**
     * 将Tron地址转换为十六进制格式
     *
     * @param string $address Tron地址
     * @return string 十六进制地址
     * @throws TronException
     */
    public static function addressToHex(string $address): string
    {
        if (!preg_match('/^T[A-Za-z0-9]{33}$/', $address)) {
            throw new TronException('Invalid Tron address format');
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
    public static function hexToAddress(string $hexAddress): string
    {
        if (!parent::isHex($hexAddress)) {
            throw new TronException('Invalid hex address');
        }

        return Base58Check::encode($hexAddress, 0, false);
    }

    /**
     * 验证Tron地址格式
     *
     * @param string $address 地址字符串
     * @return bool 是否有效
     */
    public static function isValidTronAddress(string $address): bool
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
     * addressToHex的别名（向后兼容）
     *
     * @param string $address Tron地址
     * @return string 十六进制地址
     * @throws TronException
     */
    public static function address2HexString(string $address): string
    {
        return self::addressToHex($address);
    }

    /**
     * hexToAddress的别名（向后兼容）
     *
     * @param string $hexAddress 十六进制地址
     * @return string base58格式地址
     * @throws TronException
     */
    public static function hexString2Address(string $hexAddress): string
    {
        return self::hexToAddress($hexAddress);
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
}