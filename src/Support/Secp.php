<?php

declare(strict_types=1);

namespace Dsdcr\TronWeb\Support;

use Dsdcr\TronWeb\Support\Secp256K1;

class Secp
{
    public static function sign(string $message, string $privateKey): string
    {
        $secp = new Secp256K1();
        $signature = $secp->sign($message, $privateKey);

        // 确保返回65字节的签名 (32字节r + 32字节s + 1字节v)
        return str_pad($signature->getR(), 64, '0', STR_PAD_LEFT)
             . str_pad($signature->getS(), 64, '0', STR_PAD_LEFT)
             . dechex($signature->getRecoveryParam() + 27); // Tron使用v=27/28
    }
}
