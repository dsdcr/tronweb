<?php

/**
 * Tron API 工具辅助类
 *
 * 提供TRON区块链开发的各种实用功能，包括：
 * - URL验证和检查
 * - 数组和数据类型检查
 * - 十六进制字符串操作和验证
 * - 地址格式转换和验证
 * - 不同格式之间的数字转换
 * - 字符串编码和解码工具
 * - 数据结构验证辅助函数
 *
 * 此类包含在TRON API中常用的静态辅助方法，用于数据验证、转换和操作任务。
 *
 * @package Dsdcr\TronWeb\Support
 */

namespace Dsdcr\TronWeb\Support;

use Exception;
use InvalidArgumentException;

/**
 * 工具类
 *
 * 提供TRON区块链开发的各种实用功能，包括：
 * - URL验证和检查
 * - 数组和数据类型检查
 * - 十六进制字符串操作和验证
 * - 地址格式转换和验证
 * - 不同格式之间的数字转换
 * - 字符串编码和解码工具
 * - 数据结构验证辅助函数
 *
 * @package Dsdcr\TronWeb\Support
 */
class Utils
{
    /**
     * 验证URL链接是否有效
     *
     * @param string $url URL地址
     * @return bool 是否有效
     */
    public static function isValidUrl($url) :bool {
        return (bool)parse_url($url);
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
     * 是否以零前缀
     *
     * @param string
     * @return bool
     */
    public static function isZeroPrefixed($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('isZeroPrefixed函数的值必须是字符串');
        }
        return (strpos($value, '0x') === 0);
    }

    /**
     * 去除零前缀
     *
     * @param string $value
     * @return string
     */
    public static function stripZero($value)
    {
        if (self::isZeroPrefixed($value)) {
            $count = 1;
            return str_replace('0x', '', $value, $count);
        }
        return $value;
    }

    /**
     * 是否为负数
     *
     * @param string
     * @return bool
     */
    public static function isNegative($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('isNegative函数的值必须是字符串');
        }
        return (strpos($value, '-') === 0);
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
     * 十六进制转二进制
     *
     * @param string
     * @return string
     */
    public static function hexToBin($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('hexToBin函数的值必须是字符串');
        }
        if (self::isZeroPrefixed($value)) {
            $count = 1;
            $value = str_replace('0x', '', $value, $count);
        }
        return pack('H*', $value);
    }

    /**
     * 验证地址
     *
     * @param $address
     * @return bool
     * @throws Exception
     */
    public static function validate($address)
    {
        $decoded = Base58::decode($address);

        $d1 = hash("sha256", substr($decoded,0,21), true);
        $d2 = hash("sha256", $d1, true);

        if(substr_compare($decoded, $d2, 21, 4)){
            throw new \Exception("摘要错误");
        }
        return true;
    }

    /**
     * Base58解码
     *
     * @throws Exception
     */
    public static function decodeBase58($input)
    {
        $alphabet = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

        $out = array_fill(0, 25, 0);
        for($i=0;$i<strlen($input);$i++){
            if(($p=strpos($alphabet, $input[$i]))===false){
                throw new Exception("发现无效字符");
            }
            $c = $p;
            for ($j = 25; $j--; ) {
                $c += (int)(58 * $out[$j]);
                $out[$j] = (int)($c % 256);
                $c /= 256;
                $c = (int)$c;
            }
            if($c != 0){
                throw new Exception("地址过长");
            }
        }

        $result = "";
        foreach($out as $val){
            $result .= chr($val);
        }

        return $result;
    }

    /**
     * 公钥转地址
     *
     * @throws Exception
     */
    public static function pubKeyToAddress($pubkey) {
        return '41'. substr(Keccak::hash(substr(hex2bin($pubkey), 1), 256), 24);
    }

    /**
     * 测试字符串是否以"0x"为前缀。
     *
     * @param string $str
     *   要测试前缀的字符串。
     *
     * @return bool
     *   如果字符串有"0x"前缀返回TRUE，否则返回FALSE。
     */
    public static function hasHexPrefix($str)
    {
        return substr($str, 0, 2) === '0x';
    }

    /**
     * 移除十六进制前缀"0x"。
     *
     * @param string $str
     * @return string
     */
    public static function removeHexPrefix($str)
    {
        if (!self::hasHexPrefix($str)) {
            return $str;
        }
        return substr($str, 2);
    }
}
