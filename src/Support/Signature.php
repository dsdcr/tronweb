<?php

declare(strict_types=1);

namespace Dsdcr\TronWeb\Support;

class Signature
{
    private string $r;
    private string $s;
    private int $recoveryParam;

    public function __construct(string $r, string $s, int $recoveryParam = 0)
    {
        $this->r = $r;
        $this->s = $s;
        $this->recoveryParam = $recoveryParam;
    }

    /**
     * Get R component
     *
     * @return string
     */
    public function getR(): string
    {
        return $this->r;
    }

    /**
     * Get S component
     *
     * @return string
     */
    public function getS(): string
    {
        return $this->s;
    }

    /**
     * Get recovery parameter
     *
     * @return int
     */
    public function getRecoveryParam(): int
    {
        return $this->recoveryParam;
    }

    /**
     * Convert signature to hex string
     *
     * @return string
     */
    public function toHex(): string
    {
        $r = str_pad($this->r, 64, '0', STR_PAD_LEFT);
        $s = str_pad($this->s, 64, '0', STR_PAD_LEFT);
        return $r . $s;
    }

    /**
     * Convert signature to DER format
     *
     * @return string
     */
    public function toDER(): string
    {
        $r = hex2bin(str_pad($this->r, 64, '0', STR_PAD_LEFT));
        $s = hex2bin(str_pad($this->s, 64, '0', STR_PAD_LEFT));

        // Remove leading zeros
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        // Add 0x00 if first byte >= 0x80 (to ensure positive integer)
        if (ord($r[0]) >= 0x80) {
            $r = "\x00" . $r;
        }
        if (ord($s[0]) >= 0x80) {
            $s = "\x00" . $s;
        }

        $rLength = chr(strlen($r));
        $sLength = chr(strlen($s));
        $totalLength = chr(strlen($r) + strlen($s) + 4);

        return "\x30" . $totalLength . "\x02" . $rLength . $r . "\x02" . $sLength . $s;
    }

    /**
     * Create signature from hex string
     *
     * @param string $hex
     * @param int $recoveryParam
     * @return static
     */
    public static function fromHex(string $hex, int $recoveryParam = 0): self
    {
        $hex = str_replace('0x', '', $hex);
        $r = substr($hex, 0, 64);
        $s = substr($hex, 64, 64);

        return new self($r, $s, $recoveryParam);
    }

    /**
     * Create signature from DER format
     *
     * @param string $der
     * @param int $recoveryParam
     * @return static
     */
    public static function fromDER(string $der, int $recoveryParam = 0): self
    {
        $offset = 0;

        // Skip sequence tag and length
        $offset += 2;

        // Read R
        $offset++; // Skip integer tag
        $rLength = ord($der[$offset++]);
        $r = bin2hex(substr($der, $offset, $rLength));
        $offset += $rLength;

        // Read S
        $offset++; // Skip integer tag
        $sLength = ord($der[$offset++]);
        $s = bin2hex(substr($der, $offset, $sLength));

        return new self($r, $s, $recoveryParam);
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'r' => $this->r,
            's' => $this->s,
            'recoveryParam' => $this->recoveryParam
        ];
    }

    /**
     * Convert to string representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toHex();
    }
}
