<?php

declare(strict_types=1);

namespace Dsdcr\TronWeb\Support;

use Dsdcr\TronWeb\Exception\TronException;
use Exception;

class AbiEncoder
{
    /**
     * 根据 ABI 规范编码参数
     *
     * @param array $types 类型数组
     * @param array $values 值数组
     * @return string 编码后的十六进制字符串
     * @throws TronException
     */
    public static function encodeParameters(array $types, array $values): string
    {
        if (count($types) !== count($values)) {
            throw new TronException('Types and values count mismatch');
        }

        $encoded = '';
        $dynamicData = '';
        $dynamicOffset = count($types) * 32; // Each static type takes 32 bytes

        for ($i = 0; $i < count($types); $i++) {
            $type = $types[$i];
            $value = $values[$i];

            if (self::isDynamicType($type)) {
                // For dynamic types, encode the offset
                $encoded .= self::encodeUint256($dynamicOffset);
                $encodedValue = self::encodeType($type, $value);
                $dynamicData .= $encodedValue;
                $dynamicOffset += strlen($encodedValue) / 2;
            } else {
                // For static types, encode directly
                $encoded .= self::encodeType($type, $value);
            }
        }

        return $encoded . $dynamicData;
    }

    /**
     * 根据 ABI 规范解码参数
     *
     * @param array $types 类型数组
     * @param string $data 编码后的十六进制数据
     * @return array 解码后的值数组
     * @throws TronException
     */
    public static function decodeParameters(array $types, string $data): array
    {
        $data = self::stripHexPrefix($data);
        $result = [];
        $offset = 0;

        foreach ($types as $type) {
            if (self::isDynamicType($type)) {
                // For dynamic types, read the offset first
                $dataOffset = hexdec(substr($data, $offset, 64)) * 2;
                $result[] = self::decodeType($type, $data, $dataOffset);
            } else {
                // For static types, decode directly
                $result[] = self::decodeType($type, $data, $offset);
            }
            $offset += 64; // Move to next 32-byte slot
        }

        return $result;
    }

    /**
     * 检查类型是否为动态类型
     *
     * @param string $type 类型名称
     * @return bool 是否为动态类型
     */
    private static function isDynamicType(string $type): bool
    {
        return in_array($type, ['string', 'bytes', 'string[]']) ||
            (strpos($type, '[]') !== false && $type !== 'string[]') ||
            (strpos($type, 'bytes') === 0 && !preg_match('/^bytes\d+$/', $type));
    }

    /**
     * 检查类型是否为字符串数组
     *
     * @param string $type 类型名称
     * @return bool 是否为字符串数组
     */
    private static function isStringArrayType(string $type): bool
    {
        return $type === 'string[]';
    }

    /**
     * 检查类型是否为地址数组
     *
     * @param string $type 类型名称
     * @return bool 是否为地址数组
     */
    private static function isAddressArrayType(string $type): bool
    {
        return $type === 'address[]';
    }

    /**
     * 检查类型是否为无符号整数数组
     *
     * @param string $type 类型名称
     * @return bool 是否为无符号整数数组
     */
    private static function isUintArrayType(string $type): bool
    {
        return (bool)preg_match('/^uint\d*\[\]$/', $type);
    }

    /**
     * 检查类型是否为元组
     *
     * @param string $type 类型名称
     * @return bool 是否为元组
     */
    private static function isTupleType(string $type): bool
    {
        return $type === 'tuple';
    }

    /**
     * 编码单个类型
     *
     * @param string $type 类型名称
     * @param mixed $value 值
     * @return string 编码后的十六进制字符串
     * @throws TronException
     */
    private static function encodeType(string $type, $value): string
    {
        switch (true) {
            case $type === 'address':
                return self::encodeAddress($value);
            case $type === 'bool':
                return self::encodeBool($value);
            case $type === 'string':
                return self::encodeString($value);
            case $type === 'bytes':
                return self::encodeBytes($value);
            case preg_match('/^bytes(\d+)$/', $type, $matches):
                return self::encodeFixedBytes($value, (int)$matches[1]);
            case preg_match('/^uint(\d+)?$/', $type, $matches):
                $bits = isset($matches[1]) ? (int)$matches[1] : 256;
                return self::encodeUint($value, $bits);
            case preg_match('/^int(\d+)?$/', $type, $matches):
                $bits = isset($matches[1]) ? (int)$matches[1] : 256;
                return self::encodeInt($value, $bits);
            case self::isStringArrayType($type):
                return self::encodeStringArray($value);
            case self::isAddressArrayType($type):
                return self::encodeAddressArray($value);
            case self::isUintArrayType($type):
                return self::encodeUintArray($value, $type);
            case self::isTupleType($type):
                return self::encodeTuple($value);
            default:
                throw new TronException("Unsupported type: {$type}");
        }
    }

    /**
     * 解码单个类型
     *
     * @param string $type 类型名称
     * @param string $data 编码数据
     * @param int $offset 偏移量
     * @return mixed 解码后的值
     * @throws TronException
     */
    private static function decodeType(string $type, string $data, int $offset)
    {
        switch (true) {
            case $type === 'address':
                return self::decodeAddress($data, $offset);
            case $type === 'bool':
                return self::decodeBool($data, $offset);
            case $type === 'string':
                return self::decodeString($data, $offset);
            case $type === 'bytes':
                return self::decodeBytes($data, $offset);
            case preg_match('/^bytes(\d+)$/', $type, $matches):
                return self::decodeFixedBytes($data, $offset, (int)$matches[1]);
            case preg_match('/^uint(\d+)?$/', $type, $matches):
                $bits = isset($matches[1]) ? (int)$matches[1] : 256;
                return self::decodeUint($data, $offset, $bits);
            case preg_match('/^int(\d+)?$/', $type, $matches):
                $bits = isset($matches[1]) ? (int)$matches[1] : 256;
                return self::decodeInt($data, $offset, $bits);
            case self::isStringArrayType($type):
                return self::decodeStringArray($data, $offset);
            case self::isAddressArrayType($type):
                return self::decodeAddressArray($data, $offset);
            case self::isUintArrayType($type):
                return self::decodeUintArray($data, $offset, $type);
            case self::isTupleType($type):
                return self::decodeTuple($data, $offset);
            default:
                throw new TronException("Unsupported type: {$type}");
        }
    }

    /**
     * 编码地址类型
     */
    private static function encodeAddress(string $address): string
    {
        $address = self::stripHexPrefix($address);
        return str_pad($address, 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码布尔类型
     */
    private static function encodeBool(bool $value): string
    {
        return str_pad($value ? '1' : '0', 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码字符串类型
     */
    private static function encodeString(string $value): string
    {
        $hex = bin2hex($value);
        $length = self::encodeUint256(strlen($value));
        $paddedLength = (int)ceil(strlen($hex) / 64) * 64;
        $padded = str_pad($hex, $paddedLength, '0', STR_PAD_RIGHT);
        return $length . $padded;
    }

    /**
     * 编码字节类型
     */
    private static function encodeBytes(string $value): string
    {
        if (strpos($value, '0x') === 0) {
            $value = substr($value, 2);
        }
        $length = self::encodeUint256(strlen($value) / 2);
        $padded = str_pad($value, ceil(strlen($value) / 64) * 64, '0', STR_PAD_RIGHT);
        return $length . $padded;
    }

    /**
     * 编码固定长度字节
     */
    private static function encodeFixedBytes(string $value, int $size): string
    {
        if (strpos($value, '0x') === 0) {
            $value = substr($value, 2);
        }
        return str_pad($value, 64, '0', STR_PAD_RIGHT);
    }

    /**
     * 编码256位无符号整数
     */
    private static function encodeUint256(int $value): string
    {
        return str_pad(dechex($value), 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码无符号整数
     */
    private static function encodeUint($value, int $bits): string
    {
        if (is_string($value)) {
            $value = (int)$value;
        }
        return str_pad(dechex($value), 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码有符号整数
     */
    private static function encodeInt($value, int $bits): string
    {
        if (is_string($value)) {
            $value = (int)$value;
        }
        if ($value < 0) {
            // Two's complement for negative numbers
            $value = (1 << $bits) + $value;
        }
        return str_pad(dechex($value), 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码字符串数组
     */
    private static function encodeStringArray(array $strings): string
    {
        $encoded = self::encodeUint256(count($strings)); // Array length
        $encodedStrings = '';

        foreach ($strings as $string) {
            $encodedStrings .= self::encodeString($string);
        }

        return $encoded . $encodedStrings;
    }

    /**
     * 编码地址数组
     */
    private static function encodeAddressArray(array $addresses): string
    {
        $encoded = self::encodeUint256(count($addresses)); // Array length
        $encodedAddresses = '';

        foreach ($addresses as $address) {
            $encodedAddresses .= self::encodeAddress($address);
        }

        return $encoded . $encodedAddresses;
    }

    /**
     * 编码无符号整数数组
     */
    private static function encodeUintArray(array $values, string $type): string
    {
        $encoded = self::encodeUint256(count($values)); // Array length
        $encodedValues = '';

        // Extract bits from type name (e.g. uint256[] -> 256)
        preg_match('/^uint(\d*)\[\]$/', $type, $matches);
        $bits = !empty($matches[1]) ? (int)$matches[1] : 256;

        foreach ($values as $value) {
            $encodedValues .= self::encodeUint($value, $bits);
        }

        return $encoded . $encodedValues;
    }

    /**
     * 解码地址类型
     */
    private static function decodeAddress(string $data, int $offset): string
    {
        $hex = substr($data, $offset + 24, 40); // Skip first 24 chars (12 bytes)
        return '0x' . $hex;
    }

    /**
     * 解码布尔类型
     */
    private static function decodeBool(string $data, int $offset): bool
    {
        $hex = substr($data, $offset, 64);
        return hexdec($hex) !== 0;
    }

    /**
     * 解码字符串类型
     */
    private static function decodeString(string $data, int $offset): string
    {
        $lengthHex = substr($data, $offset, 64);
        $length = hexdec($lengthHex);
        $stringHex = substr($data, $offset + 64, $length * 2);
        return hex2bin($stringHex);
    }

    /**
     * 解码字节类型
     */
    private static function decodeBytes(string $data, int $offset): string
    {
        $lengthHex = substr($data, $offset, 64);
        $length = hexdec($lengthHex);
        $bytesHex = substr($data, $offset + 64, $length * 2);
        return '0x' . $bytesHex;
    }

    /**
     * 解码固定长度字节
     */
    private static function decodeFixedBytes(string $data, int $offset, int $size): string
    {
        $hex = substr($data, $offset, $size * 2);
        return '0x' . $hex;
    }

    /**
     * 解码无符号整数
     */
    private static function decodeUint(string $data, int $offset, int $bits): string
    {
        $hex = substr($data, $offset, 64);
        return (string)hexdec($hex);
    }

    /**
     * 解码有符号整数
     */
    private static function decodeInt(string $data, int $offset, int $bits): string
    {
        $hex = substr($data, $offset, 64);
        $value = hexdec($hex);

        // Check if it's a negative number (two's complement)
        if ($value >= (1 << ($bits - 1))) {
            $value = $value - (1 << $bits);
        }

        return (string)$value;
    }

    /**
     * 解码字符串数组
     */
    private static function decodeStringArray(string $data, int $offset): array
    {
        $lengthHex = substr($data, $offset, 64);
        $length = hexdec($lengthHex);
        $strings = [];

        $currentOffset = $offset + 64;
        for ($i = 0; $i < $length; $i++) {
            $stringLengthHex = substr($data, $currentOffset, 64);
            $stringLength = hexdec($stringLengthHex);
            $stringHex = substr($data, $currentOffset + 64, $stringLength * 2);
            $strings[] = hex2bin($stringHex);
            $currentOffset += 64 + (int)ceil($stringLength * 2 / 64) * 64; // Move to next string
        }

        return $strings;
    }

    /**
     * 解码地址数组
     */
    private static function decodeAddressArray(string $data, int $offset): array
    {
        $lengthHex = substr($data, $offset, 64);
        $length = hexdec($lengthHex);
        $addresses = [];

        $currentOffset = $offset + 64;
        for ($i = 0; $i < $length; $i++) {
            $addressHex = substr($data, $currentOffset + 24, 40); // Skip first 12 bytes (24 hex chars)
            $addresses[] = '0x' . $addressHex;
            $currentOffset += 64; // Each address takes 32 bytes
        }

        return $addresses;
    }

    /**
     * 解码无符号整数数组
     */
    private static function decodeUintArray(string $data, int $offset, string $type): array
    {
        $lengthHex = substr($data, $offset, 64);
        $length = hexdec($lengthHex);
        $values = [];

        // Extract bits from type name
        preg_match('/^uint(\d*)\[\]$/', $type, $matches);
        $bits = !empty($matches[1]) ? (int)$matches[1] : 256;

        $currentOffset = $offset + 64;
        for ($i = 0; $i < $length; $i++) {
            $valueHex = substr($data, $currentOffset, 64);
            $values[] = (string)hexdec($valueHex);
            $currentOffset += 64; // Each uint takes 32 bytes
        }

        return $values;
    }

    /**
     * 编码元组（占位符实现）
     * 注意：这是用于基本元组支持的简化实现
     */
    private static function encodeTuple(array $values): string
    {
        $encoded = '';
        foreach ($values as $value) {
            // Simple encoding assuming uint256 values for now
            if (is_numeric($value)) {
                $encoded .= self::encodeUint256((int)$value);
            } elseif (is_string($value) && strpos($value, '0x') === 0) {
                $encoded .= self::encodeAddress($value);
            } else {
                // Default to string encoding
                $encoded .= self::encodeString((string)$value);
            }
        }
        return $encoded;
    }

    /**
     * 解码元组（占位符实现）
     * 注意：这是用于基本元组支持的简化实现
     */
    private static function decodeTuple(string $data, int $offset): array
    {
        // Simple decoding assuming 32-byte elements
        $result = [];
        $currentOffset = $offset;

        // For now, just decode as uint256 values
        // In a real implementation, this would need to know the tuple structure
        for ($i = 0; $i < 4; $i++) { // Assuming 4 elements like SwapData
            try {
                $value = self::decodeUint($data, $currentOffset, 256);
                $result[] = $value;
                $currentOffset += 64;
            } catch (Exception $e) {
                break;
            }
        }

        return $result;
    }

    /**
     * 去除十六进制前缀
     */
    private static function stripHexPrefix(string $hex): string
    {
        return strpos($hex, '0x') === 0 ? substr($hex, 2) : $hex;
    }
}
