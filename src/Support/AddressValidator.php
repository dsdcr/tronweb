<?php

namespace Dsdcr\TronWeb\Support;

use Dsdcr\TronWeb\Exception\TronException;

/**
 * 地址验证器 - 统一处理地址格式验证和转换
 */
class AddressValidator
{
    /**
     * 验证地址格式并返回标准化格式
     *
     * @param string $address 要验证的地址
     * @param string $context 上下文信息（用于错误消息）
     * @param string $format 返回格式 'hex' 或 'base58'
     * @return string 标准化后的地址
     * @throws TronException
     */
    public static function validateAndNormalize(
        string $address,
        string $context = 'address',
        string $format = 'hex'
    ): string {
        // 检查地址是否为空
        if (empty($address)) {
            throw new TronException("Empty {$context} provided");
        }

        // 如果是十六进制格式，先转换为Base58进行验证
        if (TronUtils::isHex($address) && strlen($address) === 42 && str_starts_with($address, '41')) {
            $base58Address = TronUtils::fromHex($address);
            if (!TronUtils::isAddress($base58Address)) {
                throw new TronException("Invalid {$context} in hex format: {$address}");
            }
            return $format === 'hex' ? $address : $base58Address;
        }

        // 检查Base58地址格式
        if (!TronUtils::isAddress($address)) {
            throw new TronException("Invalid {$context} in Base58 format: {$address}");
        }

        // 返回指定格式
        return $format === 'hex' ? TronUtils::toHex($address) : $address;
    }

    /**
     * 批量验证和标准化地址
     *
     * @param array $addresses 地址数组
     * @param string $context 上下文信息
     * @param string $format 返回格式
     * @return array 标准化后的地址数组
     * @throws TronException
     */
    public static function batchNormalize(
        array $addresses,
        string $context = 'addresses',
        string $format = 'hex'
    ): array {
        $results = [];
        foreach ($addresses as $index => $address) {
            try {
                $results[] = self::validateAndNormalize($address, "{$context}[{$index}]", $format);
            } catch (TronException $e) {
                throw new TronException("Validation failed for {$context} at index {$index}: " . $e->getMessage());
            }
        }
        return $results;
    }

    /**
     * 验证发送方和接收方地址不相同
     *
     * @param string $fromAddress 发送方地址
     * @param string $toAddress 接收方地址
     * @param string $context 上下文信息
     * @throws TronException
     */
    public static function validateDifferentAddresses(
        string $fromAddress,
        string $toAddress,
        string $context = 'transaction'
    ): void {
        $fromHex = self::validateAndNormalize($fromAddress, 'from address');
        $toHex = self::validateAndNormalize($toAddress, 'to address');

        if ($fromHex === $toHex) {
            throw new TronException("Sender and receiver addresses must be different in {$context}");
        }
    }
}