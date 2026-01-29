<?php

declare(strict_types=1);

namespace Dsdcr\TronWeb\Support;

use Dsdcr\TronWeb\Exception\TronException;

/**
 * TRON消息签名工具类
 * 提供与TypeScript TronWeb兼容的消息签名和验证功能
 *
 * @package Dsdcr\TronWeb\Support
 */
class Message
{
    /**
     * TRON消息前缀
     */
    public const TRON_MESSAGE_PREFIX = "\x19TRON Signed Message:\n";

    /**
     * 以太坊消息前缀（兼容）
     */
    public const ETH_MESSAGE_PREFIX = "\x19Ethereum Signed Message:\n";

    /**
     * 对消息进行哈希处理
     *
     * @param string|array $message 要哈希的消息
     * @param bool $useTronHeader 是否使用TRON消息头
     * @return string 哈希后的消息
     */
    public static function hashMessage($message, bool $useTronHeader = true): string
    {
        if (is_array($message)) {
            $message = implode('', array_map('chr', $message));
        }

        $prefix = $useTronHeader ? self::TRON_MESSAGE_PREFIX : self::ETH_MESSAGE_PREFIX;
        $length = strlen($message);

        // 构建签名字符串: 前缀 + 长度 + 消息
        $signData = $prefix . $length . $message;

        return hash('sha3-256', $signData);
    }

    /**
     * 签名消息
     *
     * @param string|array $message 要签名的消息
     * @param string $privateKey 私钥
     * @param bool $useTronHeader 是否使用TRON消息头
     * @return string 签名结果（十六进制格式）
     * @throws TronException
     */
    public static function signMessage($message, string $privateKey, bool $useTronHeader = true): string
    {
        $messageHash = self::hashMessage($message, $useTronHeader);

        $secp = new Secp256k1();
        $signature = $secp->sign($messageHash, $privateKey);

        // 转换为TypeScript兼容的签名格式
        $r = str_pad($signature->getR(), 64, '0', STR_PAD_LEFT);
        $s = str_pad($signature->getS(), 64, '0', STR_PAD_LEFT);
        $v = dechex($signature->getRecoveryParam() + 27);

        return '0x' . $r . $s . $v;
    }

    /**
     * 验证消息签名
     *
     * @param string|array $message 原始消息
     * @param string $signature 签名（十六进制格式）
     * @param bool $useTronHeader 是否使用TRON消息头
     * @return string 恢复出的地址（base58格式）
     * @throws TronException
     */
    public static function verifyMessage($message, string $signature, bool $useTronHeader = true): string
    {
        // 移除签名前缀
        $signature = str_replace('0x', '', $signature);

        if (strlen($signature) !== 130) {
            throw new TronException('Invalid signature length');
        }

        // 解析签名
        $r = substr($signature, 0, 64);
        $s = substr($signature, 64, 64);
        $v = hexdec(substr($signature, 128, 2));

        if ($v < 27 || $v > 34) {
            throw new TronException('Invalid recovery parameter');
        }

        $recoveryParam = $v - 27;

        // 创建签名对象
        $sig = new Signature($r, $s, $recoveryParam);

        // 哈希消息
        $messageHash = self::hashMessage($message, $useTronHeader);

        // 恢复公钥
        $secp = new Secp256k1();
        $publicKey = $secp->recoverPublicKey($messageHash, $sig);

        if (!$publicKey) {
            throw new TronException('Failed to recover public key from signature');
        }

        // 从公钥生成地址（使用现有的Account模块方法逻辑）
        $secp = new Secp256k1();
        $addressHex = $secp->getAddressFromPublicKey($publicKey);
        return TronUtils::hexToAddress($addressHex);
    }

    /**
     * 验证消息签名并返回布尔结果
     *
     * @param string|array $message 原始消息
     * @param string $signature 签名
     * @param string $expectedAddress 期望的地址（base58格式）
     * @param bool $useTronHeader 是否使用TRON消息头
     * @return bool 签名是否有效
     */
    public static function verifyMessageWithAddress($message, string $signature, string $expectedAddress, bool $useTronHeader = true): bool
    {
        try {
            $recoveredAddress = self::verifyMessage($message, $signature, $useTronHeader);
            return $recoveredAddress === $expectedAddress;
        } catch (TronException $e) {
            return false;
        }
    }

    /**
     * 使用TRON特定的方法验证签名（兼容TypeScript版本）
     *
     * @param string $messageHex 十六进制格式的消息
     * @param string $address 地址（base58格式）
     * @param string $signature 签名
     * @param bool $useTronHeader 是否使用TRON消息头
     * @return bool 签名是否有效
     */
    public static function verifySignature(string $messageHex, string $address, string $signature, bool $useTronHeader = true): bool
    {
        try {
            // 移除十六进制前缀
            $messageHex = str_replace('0x', '', $messageHex);

            // 转换消息为字节数组
            $messageBytes = [];
            for ($i = 0; $i < strlen($messageHex); $i += 2) {
                $messageBytes[] = hexdec(substr($messageHex, $i, 2));
            }

            return self::verifyMessageWithAddress($messageBytes, $signature, $address, $useTronHeader);
        } catch (TronException $e) {
            return false;
        }
    }
}