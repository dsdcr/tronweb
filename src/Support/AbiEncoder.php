<?php

declare(strict_types=1);

namespace Dsdcr\TronWeb\Support;

use Dsdcr\TronWeb\Exception\TronException;

class AbiEncoder
{
    /**
     * Encode parameters according to ABI specification
     *
     * @param array $types
     * @param array $values
     * @return string
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
     * Decode parameters according to ABI specification
     *
     * @param array $types
     * @param string $data
     * @return array
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
     * Check if a type is dynamic
     *
     * @param string $type
     * @return bool
     */
    private static function isDynamicType(string $type): bool
    {
        return in_array($type, ['string', 'bytes']) ||
            strpos($type, '[]') !== false ||
            (strpos($type, 'bytes') === 0 && !preg_match('/^bytes\d+$/', $type));
    }

    /**
     * Encode a single type
     *
     * @param string $type
     * @param mixed $value
     * @return string
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
            default:
                throw new TronException("Unsupported type: {$type}");
        }
    }

    /**
     * Decode a single type
     *
     * @param string $type
     * @param string $data
     * @param int $offset
     * @return mixed
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
            default:
                throw new TronException("Unsupported type: {$type}");
        }
    }

    /**
     * Encode address
     */
    private static function encodeAddress(string $address): string
    {
        $address = self::stripHexPrefix($address);
        return str_pad($address, 64, '0', STR_PAD_LEFT);
    }

    /**
     * Encode boolean
     */
    private static function encodeBool(bool $value): string
    {
        return str_pad($value ? '1' : '0', 64, '0', STR_PAD_LEFT);
    }

    /**
     * Encode string
     */
    private static function encodeString(string $value): string
    {
        $hex = bin2hex($value);
        $length = self::encodeUint256(strlen($value));
        $padded = str_pad($hex, ceil(strlen($hex) / 64) * 64, '0', STR_PAD_RIGHT);
        return $length . $padded;
    }

    /**
     * Encode bytes
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
     * Encode fixed bytes
     */
    private static function encodeFixedBytes(string $value, int $size): string
    {
        if (strpos($value, '0x') === 0) {
            $value = substr($value, 2);
        }
        return str_pad($value, 64, '0', STR_PAD_RIGHT);
    }

    /**
     * Encode uint256
     */
    private static function encodeUint256(int $value): string
    {
        return str_pad(dechex($value), 64, '0', STR_PAD_LEFT);
    }

    /**
     * Encode uint
     */
    private static function encodeUint($value, int $bits): string
    {
        if (is_string($value)) {
            $value = (int)$value;
        }
        return str_pad(dechex($value), 64, '0', STR_PAD_LEFT);
    }

    /**
     * Encode int
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
     * Decode address
     */
    private static function decodeAddress(string $data, int $offset): string
    {
        $hex = substr($data, $offset + 24, 40); // Skip first 24 chars (12 bytes)
        return '0x' . $hex;
    }

    /**
     * Decode boolean
     */
    private static function decodeBool(string $data, int $offset): bool
    {
        $hex = substr($data, $offset, 64);
        return hexdec($hex) !== 0;
    }

    /**
     * Decode string
     */
    private static function decodeString(string $data, int $offset): string
    {
        $lengthHex = substr($data, $offset, 64);
        $length = hexdec($lengthHex);
        $stringHex = substr($data, $offset + 64, $length * 2);
        return hex2bin($stringHex);
    }

    /**
     * Decode bytes
     */
    private static function decodeBytes(string $data, int $offset): string
    {
        $lengthHex = substr($data, $offset, 64);
        $length = hexdec($lengthHex);
        $bytesHex = substr($data, $offset + 64, $length * 2);
        return '0x' . $bytesHex;
    }

    /**
     * Decode fixed bytes
     */
    private static function decodeFixedBytes(string $data, int $offset, int $size): string
    {
        $hex = substr($data, $offset, $size * 2);
        return '0x' . $hex;
    }

    /**
     * Decode uint
     */
    private static function decodeUint(string $data, int $offset, int $bits): string
    {
        $hex = substr($data, $offset, 64);
        return (string)hexdec($hex);
    }

    /**
     * Decode int
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
     * Strip hex prefix
     */
    private static function stripHexPrefix(string $hex): string
    {
        return strpos($hex, '0x') === 0 ? substr($hex, 2) : $hex;
    }
}
