<?php
namespace Dsdcr\TronWeb\Support;

/**
 * 哈希工具类
 *
 * 提供常用的哈希算法，包括SHA-256、RIPEMD-160等
 * 用于区块链地址生成、交易签名等加密操作
 *
 * @package Dsdcr\TronWeb\Support
 */
class Hash
{
    /**
     * 计算SHA-256哈希
     *
     * @param string $data 输入数据
     * @param bool $raw 是否返回原始二进制（true）或十六进制字符串（false）
     * @return string 哈希结果
     */
    public static function SHA256($data, $raw = true)
    {
        return hash('sha256', $data, $raw);
    }

    /**
     * 计算双重SHA-256哈希（用于比特币地址生成）
     *
     * @param string $data 输入数据
     * @return string 双重哈希结果（原始二进制）
     */
    public static function sha256d($data)
    {
        return hash('sha256', hash('sha256', $data, true), true);
    }

    /**
     * 计算RIPEMD-160哈希
     *
     * @param string $data 输入数据
     * @param bool $raw 是否返回原始二进制（true）或十六进制字符串（false）
     * @return string 哈希结果
     */
    public static function RIPEMD160($data, $raw = true)
    {
        return hash('ripemd160', $data, $raw);
    }
}
