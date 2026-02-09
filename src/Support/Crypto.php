<?php
namespace Dsdcr\TronWeb\Support;

/**
 * 加密工具类
 *
 * 提供大数字进制转换功能，用于处理区块链地址和加密操作
 * 支持2-256任意进制之间的数字转换
 *
 * @package Dsdcr\TronWeb\Support
 */
class Crypto
{
    /**
     * 将大数字转换为二进制
     *
     * @param string $num 大数字字符串
     * @return string 二进制数据
     */
    public static function bc2bin($num)
    {
        return self::dec2base($num, 256);
    }

    /**
     * 将十进制数字转换为指定进制
     *
     * @param string $dec 十进制数字
     * @param int $base 目标进制（2-256）
     * @param string|array $digits 进制字符集
     * @return string 转换后的字符串
     */
    public static function dec2base($dec, $base, $digits = false)
    {
        if (extension_loaded('bcmath')) {
            if ($base < 2 || $base > 256) {
                die("Invalid Base: " . $base);
            }
            bcscale(0);
            $value = "";
            if (!$digits) {
                $digits = self::digits($base);
            }
            while ($dec > $base - 1) {
                $rest = bcmod($dec, $base);
                $dec = bcdiv($dec, $base);
                $value = $digits[$rest] . $value;
            }
            $value = $digits[intval($dec)] . $value;
            return (string)$value;
        } else {
            die('Please install BCMATH');
        }
    }

    /**
     * 将指定进制数字转换为十进制
     *
     * @param string $value 任意进制数字
     * @param int $base 原始进制（2-256）
     * @param string|array $digits 进制字符集
     * @return string 十进制数字字符串
     */
    public static function base2dec($value, $base, $digits = false)
    {
        if (extension_loaded('bcmath')) {
            if ($base < 2 || $base > 256) {
                die("Invalid Base: " . $base);
            }
            bcscale(0);
            if ($base < 37) {
                $value = strtolower($value);
            }
            if (!$digits) {
                $digits = self::digits($base);
            }
            $size = strlen($value);
            $dec = "0";
            for ($loop = 0; $loop < $size; $loop++) {
                $element = strpos($digits, $value[$loop]);
                $power = bcpow($base, $size - $loop - 1);
                $dec = bcadd($dec, bcmul($element, $power));
            }
            return (string)$dec;
        } else {
            die('Please install BCMATH');
        }
    }

    /**
     * 获取指定进制的字符集
     *
     * @param int $base 进制数（2-256）
     * @return string 进制字符集
     */
    public static function digits($base)
    {
        if ($base > 64) {
            $digits = "";
            for ($loop = 0; $loop < 256; $loop++) {
                $digits .= chr($loop);
            }
        } else {
            $digits = "0123456789abcdefghijklmnopqrstuvwxyz";
            $digits .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ-_";
        }
        $digits = substr($digits, 0, $base);
        return (string)$digits;
    }

    /**
     * 将二进制转换为大数字
     *
     * @param string $num 二进制数据
     * @return string 十进制数字字符串
     */
    public static function bin2bc($num)
    {
        return self::base2dec($num, 256);
    }
}
