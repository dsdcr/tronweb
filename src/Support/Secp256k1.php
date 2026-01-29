<?php

declare(strict_types=1);

namespace Dsdcr\TronWeb\Support;

use Dsdcr\TronWeb\Exception\TronException;

class Secp256k1
{
    private const CURVE_ORDER = '0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141';
    private const CURVE_P = '0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F';
    private const CURVE_A = '0x0000000000000000000000000000000000000000000000000000000000000000';
    private const CURVE_B = '0x0000000000000000000000000000000000000000000000000000000000000007';
    private const CURVE_GX = '0x79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798';
    private const CURVE_GY = '0x483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8';

    /**
     * Sign a message with a private key
     *
     * @param string $message
     * @param string $privateKey
     * @param array $options
     * @return Signature
     * @throws TronException
     */
    public function sign(string $message, string $privateKey, array $options = []): Signature
    {
        $canonical = $options['canonical'] ?? true;

        // Convert message to hash if it's not already
        if (strlen($message) !== 64) {
            $messageHash = hash('sha256', $message);
        } else {
            $messageHash = $message;
        }

        // Use simplito/elliptic-php which is already a dependency
        $ec = new \Elliptic\EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKey, 'hex');

        // Sign the message
        $signature = $key->sign($messageHash, 'hex', ['canonical' => $canonical]);

        // Get recovery parameter
        $recoveryParam = $this->getRecoveryParam($messageHash, $signature, $key->getPublic());

        return new Signature(
            $signature->r->toString(16),
            $signature->s->toString(16),
            $recoveryParam
        );
    }

    /**
     * Verify a signature
     *
     * @param string $message
     * @param Signature $signature
     * @param string $publicKey
     * @return bool
     */
    public function verify(string $message, Signature $signature, string $publicKey): bool
    {
        try {
            $ec = new \Elliptic\EC('secp256k1');
            $key = $ec->keyFromPublic($publicKey, 'hex');

            if (strlen($message) !== 64) {
                $messageHash = hash('sha256', $message);
            } else {
                $messageHash = $message;
            }

            return $key->verify($messageHash, [
                'r' => $signature->getR(),
                's' => $signature->getS()
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Recover public key from signature
     *
     * @param string $message
     * @param Signature $signature
     * @return string|null
     */
    public function recoverPublicKey(string $message, Signature $signature): ?string
    {
        try {
            $ec = new \Elliptic\EC('secp256k1');

            if (strlen($message) !== 64) {
                $messageHash = hash('sha256', $message);
            } else {
                $messageHash = $message;
            }

            $recoveredKey = $ec->recoverPubKey(
                $messageHash,
                [
                    'r' => $signature->getR(),
                    's' => $signature->getS()
                ],
                $signature->getRecoveryParam(),
                'hex'
            );

            return $recoveredKey->encode('hex');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate a new private key
     *
     * @return string
     */
    public function generatePrivateKey(): string
    {
        $ec = new \Elliptic\EC('secp256k1');
        $key = $ec->genKeyPair();
        return $key->getPrivate('hex');
    }

    /**
     * Get public key from private key
     *
     * @param string $privateKey
     * @return string
     */
    public function getPublicKey(string $privateKey): string
    {
        $ec = new \Elliptic\EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKey, 'hex');
        return $key->getPublic('hex');
    }

    /**
     * Calculate recovery parameter
     *
     * @param string $messageHash
     * @param object $signature
     * @param object $publicKey
     * @return int
     */
    private function getRecoveryParam(string $messageHash, $signature, $publicKey): int
    {
        $ec = new \Elliptic\EC('secp256k1');

        for ($recovery = 0; $recovery < 4; $recovery++) {
            try {
                $recoveredKey = $ec->recoverPubKey(
                    $messageHash,
                    [
                        'r' => $signature->r->toString(16),
                        's' => $signature->s->toString(16)
                    ],
                    $recovery,
                    'hex'
                );

                if ($recoveredKey->encode('hex') === $publicKey->encode('hex')) {
                    return $recovery;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return 0;
    }

    /**
     * 从公钥生成地址
     *
     * @param string $publicKey 公钥（十六进制格式）
     * @return string 地址（十六进制格式）
     */
    public function getAddressFromPublicKey(string $publicKey): string
    {
        // 移除公钥前缀（如果有）
        $publicKey = str_replace('0x', '', $publicKey);

        if (strlen($publicKey) !== 130) {
            throw new \InvalidArgumentException('Invalid public key length');
        }

        // 从公钥生成地址哈希
        $publicKeyBytes = hex2bin($publicKey);
        $hash = hash('sha3-256', substr($publicKeyBytes, 1)); // 跳过04前缀

        // 取最后20个字节作为地址
        $addressBytes = substr(hex2bin($hash), -20);

        // 添加TRON地址前缀（0x41）
        $tronAddress = hex2bin('41') . $addressBytes;

        return bin2hex($tronAddress);
    }
}
