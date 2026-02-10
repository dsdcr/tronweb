<?php

declare(strict_types=1);

namespace Dsdcr\TronWeb\Support;

use Dsdcr\TronWeb\Exception\TronException;

/**
 * 纯 PHP 实现的 secp256k1 椭圆曲线加密算法
 * 无需外部依赖
 */
class Secp256K1
{
    // secp256k1 曲线参数
    private const CURVE_ORDER = '0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141';
    private const CURVE_P = '0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F';
    private const CURVE_A = '0x0000000000000000000000000000000000000000000000000000000000000000';
    private const CURVE_B = '0x0000000000000000000000000000000000000000000000000000000000000007';
    private const CURVE_GX = '0x79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798';
    private const CURVE_GY = '0x483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8';

    // 预先计算的值（用于提高效率）
    private \GMP $n;
    private \GMP $p;
    private \GMP $a;
    private \GMP $b;
    private \GMP $gx;
    private \GMP $gy;
    private \GMP $three;
    private \GMP $four;
    private \GMP $zero;
    private \GMP $one;
    private \GMP $two;

    public function __construct()
    {
        $this->n = gmp_init(self::CURVE_ORDER, 16);
        $this->p = gmp_init(self::CURVE_P, 16);
        $this->a = gmp_init(self::CURVE_A, 16);
        $this->b = gmp_init(self::CURVE_B, 16);
        $this->gx = gmp_init(self::CURVE_GX, 16);
        $this->gy = gmp_init(self::CURVE_GY, 16);
        $this->three = gmp_init(3);
        $this->four = gmp_init(4);
        $this->zero = gmp_init(0);
        $this->one = gmp_init(1);
        $this->two = gmp_init(2);
    }

    /**
     * 生成新的私钥
     */
    public function generatePrivateKey(): string
    {
        do {
            $bytes = random_bytes(32);
            $privateKey = gmp_init(bin2hex($bytes), 16);
        } while (gmp_cmp($privateKey, $this->one) <= 0 || gmp_cmp($privateKey, gmp_sub($this->n, $this->one)) >= 0);

        return gmp_strval($privateKey, 16);
    }

    /**
     * 从私钥获取公钥（未压缩格式）
     */
    public function getPublicKey(string $privateKey): string
    {
        $privateKeyInt = gmp_init($privateKey, 16);
        $publicKey = $this->scalarMul($privateKeyInt, $this->gx, $this->gy);

        if ($publicKey === null) {
            throw new TronException('Invalid private key');
        }

        // 返回未压缩的公钥 (04 + x + y)
        $x = str_pad(gmp_strval($publicKey['x'], 16), 64, '0', STR_PAD_LEFT);
        $y = str_pad(gmp_strval($publicKey['y'], 16), 64, '0', STR_PAD_LEFT);

        return '04' . $x . $y;
    }

    /**
     * 获取压缩公钥
     */
    public function getPublicKeyCompressed(string $privateKey): string
    {
        $privateKeyInt = gmp_init($privateKey, 16);
        $publicKey = $this->scalarMul($privateKeyInt, $this->gx, $this->gy);

        if ($publicKey === null) {
            throw new TronException('Invalid private key');
        }

        $yVal = gmp_intval($publicKey['y']);
        $prefix = $yVal % 2 === 0 ? '02' : '03';
        $x = str_pad(gmp_strval($publicKey['x'], 16), 64, '0', STR_PAD_LEFT);

        return $prefix . $x;
    }

    /**
     * 使用私钥对消息进行签名
     */
    public function sign(string $message, string $privateKey, array $options = []): Signature
    {
        $canonical = $options['canonical'] ?? true;

        // 如果消息不是哈希值，则转换为哈希
        if (strlen($message) !== 64) {
            $messageHash = hash('sha256', $message, false);
        } else {
            $messageHash = $message;
        }

        $messageHashInt = gmp_init($messageHash, 16);
        $privateKeyInt = gmp_init($privateKey, 16);

        // 生成随机数 k
        do {
            $k = gmp_init(bin2hex(random_bytes(32)), 16);
        } while (gmp_cmp($k, $this->one) <= 0 || gmp_cmp($k, gmp_sub($this->n, $this->one)) >= 0);

        // 计算 (x, y) = k * G（基点）
        $point = $this->scalarMul($k, $this->gx, $this->gy);
        if ($point === null) {
            return $this->sign($message, $privateKey, $options); // 使用不同的 k 重试
        }

        $r = gmp_mod($point['x'], $this->n);
        if (gmp_cmp($r, $this->zero) === 0) {
            return $this->sign($message, $privateKey, $options); // 重试
        }

        $s = gmp_mul(gmp_add($messageHashInt, gmp_mul($r, $privateKeyInt)), gmp_invert($k, $this->n));
        $s = gmp_mod($s, $this->n);

        if (gmp_cmp($s, $this->zero) === 0) {
            return $this->sign($message, $privateKey, $options); // Retry
        }

        // 规范签名：确保 S 值为低值
        $sHigh = gmp_sub($this->n, $s);
        if ($canonical && gmp_cmp($sHigh, $s) < 0) {
            $s = $sHigh;
        }

        $rHex = str_pad(gmp_strval($r, 16), 64, '0', STR_PAD_LEFT);
        $sHex = str_pad(gmp_strval($s, 16), 64, '0', STR_PAD_LEFT);

        // 计算恢复参数
        $recoveryParam = $this->calculateRecoveryParam($messageHash, $r, $s, $point['y']);

        return new Signature($rHex, $sHex, $recoveryParam);
    }

    /**
     * 验证签名
     */
    public function verify(string $message, Signature $signature, string $publicKey): bool
    {
        try {
            if (strlen($publicKey) !== 130) {
                return false;
            }

            if (strlen($message) !== 64) {
                $messageHash = hash('sha256', $message, false);
            } else {
                $messageHash = $message;
            }

            $r = gmp_init($signature->getR(), 16);
            $s = gmp_init($signature->getS(), 16);


            // 验证 r 和 s 是否在 [1, n-1] 范围内
            if (gmp_cmp($r, $this->one) < 0 || gmp_cmp($r, gmp_sub($this->n, $this->one)) > 0) {
                return false;
            }
            if (gmp_cmp($s, $this->one) < 0 || gmp_cmp($s, gmp_sub($this->n, $this->one)) > 0) {
                return false;
            }

            $messageHashInt = gmp_init($messageHash, 16);


            // 解压缩公钥
            $pubKeyPoint = $this->decompressPublicKey($publicKey);
            if ($pubKeyPoint === null) {
                return false;
            }

            // 计算签名验证：s^(-1) * messageHash * G + s^(-1) * r * Q
            $sInv = gmp_invert($s, $this->n);

            $u1 = gmp_mod(gmp_mul($messageHashInt, $sInv), $this->n);
            $u2 = gmp_mod(gmp_mul($r, $sInv), $this->n);

            // u1 * G（基点）
            $point1 = $this->scalarMul($u1, $this->gx, $this->gy);
            if ($point1 === null) {
                return false;
            }

            // u2 * Q（公钥点）
            $point2 = $this->scalarMul($u2, $pubKeyPoint['x'], $pubKeyPoint['y']);
            if ($point2 === null) {
                return false;
            }

            // X = u1 * G + u2 * Q
            $result = $this->pointAdd($point1['x'], $point1['y'], $point2['x'], $point2['y']);
            if ($result === null) {
                return false;
            }

            // 检查 x 坐标是否等于 r
            $x = gmp_mod($result['x'], $this->n);

            return gmp_cmp($x, $r) === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 从签名中恢复公钥
     */
    public function recoverPublicKey(string $message, Signature $signature): ?string
    {
        if (strlen($message) !== 64) {
            $messageHash = hash('sha256', $message, false);
        } else {
            $messageHash = $message;
        }

        $r = gmp_init($signature->getR(), 16);
        $s = gmp_init($signature->getS(), 16);
        $recId = $signature->getRecoveryParam();

        // 计算 i（即 recId // 2，确定两个可能的 x 值中的哪一个）
        $i = intdiv($recId, 2);

        // x = r + i * n
        $x = gmp_mod($r, $this->n);
        if ($i > 0) {
            $x = gmp_add($x, gmp_mul($i, $this->n));
        }

        // 从曲线上的 x 计算 y：y = sqrt(x^3 + b) mod p
        $xCubed = gmp_mod(gmp_pow($x, 3), $this->p);
        $ySquared = gmp_mod(gmp_add($xCubed, $this->b), $this->p);

        // y = (y^2)^((p+1)/4) mod p（Tonelli-Shanks 算法，p ≡ 3 mod 4）
        $exp = gmp_div_q(gmp_add($this->p, gmp_init(1)), $this->four);
        $y = gmp_mod(gmp_powm($ySquared, $exp, $this->p), $this->p);

        // 根据恢复参数确定正确的 y 值
        $yParity = gmp_intval(gmp_mod($y, $this->two));
        $expectedParity = $recId % 2;

        if ($yParity !== $expectedParity) {
            $y = gmp_mod(gmp_sub($this->p, $y), $this->p);
        }

        // Calculate Q = r^(-1) * (s * R - e * G)
        $sInv = gmp_invert($s, $this->n);
        $e = gmp_mod(gmp_init($messageHash, 16), $this->n);

        // R point
        $pointR = ['x' => $x, 'y' => $y];

        // Calculate Q using standard recovery formula: Q = r^(-1) * (s * R - e * G)
        // Steps:
        // 1. s * R
        $pointSR = $this->scalarMul($s, $pointR['x'], $pointR['y']);
        if ($pointSR === null) {
            return null;
        }

        // 2. e * G
        $pointEG = $this->scalarMul($e, $this->gx, $this->gy);
        if ($pointEG === null) {
            return null;
        }

        // 3. s * R - e * G (by adding the negative of e * G)
        $negPointEG = ['x' => $pointEG['x'], 'y' => gmp_mod(gmp_neg($pointEG['y']), $this->p)];
        $pointSRMinusEG = $this->pointAdd($pointSR['x'], $pointSR['y'], $negPointEG['x'], $negPointEG['y']);
        if ($pointSRMinusEG === null) {
            return null;
        }

        // 4. r^(-1) * (s * R - e * G)
        $rInv = gmp_invert($r, $this->n);
        $Q = $this->scalarMul($rInv, $pointSRMinusEG['x'], $pointSRMinusEG['y']);
        if ($Q === null) {
            return null;
        }

        $xHex = str_pad(gmp_strval($Q['x'], 16), 64, '0', STR_PAD_LEFT);
        $yHex = str_pad(gmp_strval($Q['y'], 16), 64, '0', STR_PAD_LEFT);

        return '04' . $xHex . $yHex;
    }

    /**
     * 计算恢复参数
     */
    private function calculateRecoveryParam(string $messageHash, $r, $s, $y): int
    {
        $messageHashInt = gmp_init($messageHash, 16);
        $sInv = gmp_invert($s, $this->n);

        // 尝试恢复参数的所有可能值（0 和 1，2 和 3）
        for ($i = 0; $i < 4; $i += 2) {
            $x = gmp_mod($r, $this->n);

            // 从曲线上的 x 计算 y：y = sqrt(x^3 + b) mod p
            $xCubed = gmp_mod(gmp_pow($x, 3), $this->p);
            $ySquared = gmp_mod(gmp_add($xCubed, $this->b), $this->p);

            // y = (y^2)^((p+1)/4) mod p（Tonelli-Shanks 算法，p ≡ 3 mod 4）
            $exp = gmp_div_q(gmp_add($this->p, gmp_init(1)), $this->four);
            $yCandidate = gmp_mod(gmp_powm($ySquared, $exp, $this->p), $this->p);
            $yParity = gmp_intval(gmp_mod($yCandidate, gmp_init(2)));

            if ($yParity === ($i % 2)) {
                $pointR = $this->scalarMul($x, $this->gx, $this->gy);
                if ($pointR !== null) {
                    return $i;
                }
            }

            // 尝试 y = p - y
            $yCandidate = gmp_mod(gmp_sub($this->p, $yCandidate), $this->p);
            $yParity = gmp_intval(gmp_mod($yCandidate, gmp_init(2)));

            if ($yParity === ($i % 2)) {
                $pointR = $this->scalarMul($x, $this->gx, $this->gy);
                if ($pointR !== null) {
                    return $i + 1;
                }
            }
        }

        return 0;
    }

    /**
     * 解压缩公钥
     */
    private function decompressPublicKey(string $publicKey): ?array
    {
        if (strlen($publicKey) === 130 && $publicKey[0] === '0' && $publicKey[1] === '4') {
            $x = gmp_init(substr($publicKey, 2, 64), 16);
            $y = gmp_init(substr($publicKey, 66, 64), 16);
        } elseif ((strlen($publicKey) === 66 || strlen($publicKey) === 67) && in_array($publicKey[0], ['2', '3'])) {
            $x = gmp_init(substr($publicKey, 2), 16);

        // 从曲线上的 x 计算 y：y = sqrt(x^3 + b) mod p
        $xCubed = gmp_mod(gmp_pow($x, 3), $this->p);
            $ySquared = gmp_mod(gmp_add($xCubed, $this->b), $this->p);

        // y = (y^2)^((p+1)/4) mod p（Tonelli-Shanks 算法，p ≡ 3 mod 4）
        $exp = gmp_div_q(gmp_add($this->p, gmp_init(1)), $this->four);
            $y = gmp_mod(gmp_powm($ySquared, $exp, $this->p), $this->p);

        // 根据前缀确定正确的 y 值
        $expectedParity = $publicKey[0] === '2' ? 0 : 1;
            $yParity = gmp_intval(gmp_mod($y, gmp_init(2)));

            if ($yParity !== $expectedParity) {
                $y = gmp_mod(gmp_sub($this->p, $y), $this->p);
            }
        } else {
            return null;
        }

        // 验证点是否在曲线上
        if (!$this->isOnCurve($x, $y)) {
            return null;
        }

        return ['x' => $x, 'y' => $y];
    }

    /**
     * 检查点是否在曲线上
     */
    private function isOnCurve($x, $y): bool
    {
        $ySquared = gmp_mod(
            gmp_mul($y, $y),
            $this->p
        );

        $xCubed = gmp_mod(gmp_pow($x, 3), $this->p);
        $xCubedPlusB = gmp_mod(gmp_add($xCubed, $this->b), $this->p);

        return gmp_cmp($ySquared, $xCubedPlusB) === 0;
    }

    /**
     * 标量乘法：k * (x, y)
     */
    private function scalarMul($k, $x, $y): ?array
    {
        if (gmp_cmp($k, $this->zero) === 0) {
            return null;
        }

        $resultX = $this->zero;
        $resultY = $this->zero;
        $currentX = $x;
        $currentY = $y;

        while (gmp_cmp($k, $this->zero) > 0) {
            if (gmp_intval(gmp_mod($k, $this->two)) === 1) {
                $temp = $this->pointAdd($resultX, $resultY, $currentX, $currentY);
                if ($temp === null) {
                    return null;
                }
                $resultX = $temp['x'];
                $resultY = $temp['y'];
            }

            $temp = $this->pointDouble($currentX, $currentY);
            if ($temp === null) {
                return null;
            }
            $currentX = $temp['x'];
            $currentY = $temp['y'];

            $k = gmp_div_q($k, $this->two);
        }

        return ['x' => $resultX, 'y' => $resultY];
    }

    /**
     * 点加法：(x1, y1) + (x2, y2)
     */
    private function pointAdd($x1, $y1, $x2, $y2): ?array
    {
        // 检查无穷远点
        if (gmp_cmp($x1, $this->zero) === 0 && gmp_cmp($y1, $this->zero) === 0) {
            return ['x' => $x2, 'y' => $y2];
        }
        if (gmp_cmp($x2, $this->zero) === 0 && gmp_cmp($y2, $this->zero) === 0) {
            return ['x' => $x1, 'y' => $y1];
        }

        // 检查点是否为逆元
        if (gmp_cmp($x1, $x2) === 0) {
            if (gmp_cmp($y1, $y2) === 0) {
                return $this->pointDouble($x1, $y1);
            } else {
                return null; // 无穷远点
            }
        }

        // Calculate slope: (y2 - y1) / (x2 - x1)
        $slope = gmp_mod(
            gmp_mul(
                gmp_invert(gmp_mod(gmp_sub($x2, $x1), $this->p), $this->p),
                gmp_mod(gmp_sub($y2, $y1), $this->p)
            ),
            $this->p
        );

        // x3 = slope^2 - x1 - x2
        $x3 = gmp_mod(
            gmp_sub(
                gmp_sub(
                    gmp_mod(gmp_powm($slope, $this->two, $this->p), $this->p),
                    $x1
                ),
                $x2
            ),
            $this->p
        );

        // y3 = 斜率 * (x1 - x3) - y1
        $y3 = gmp_mod(
            gmp_sub(
                gmp_mul($slope, gmp_mod(gmp_sub($x1, $x3), $this->p)),
                $y1
            ),
            $this->p
        );

        return ['x' => $x3, 'y' => $y3];
    }

    /**
     * 点倍乘：2 * (x, y)
     */
    private function pointDouble($x, $y): ?array
    {
        if (gmp_cmp($y, $this->zero) === 0) {
            return null; // 无穷远点
        }

        // 计算斜率：(3x^2 + a) / 2y
        $xSquared = gmp_mod(gmp_mul($x, $x), $this->p);
        $threeXSquared = gmp_mod(gmp_mul($this->three, $xSquared), $this->p);
        $numerator = gmp_mod(gmp_add($threeXSquared, $this->a), $this->p);
        $denominator = gmp_mod(gmp_mul(gmp_init(2), $y), $this->p);
        $slope = gmp_mod(gmp_mul(gmp_invert($denominator, $this->p), $numerator), $this->p);

        // x3 = 斜率^2 - 2x
        $slopeSquared = gmp_mod(gmp_pow($slope, 2), $this->p);
        $twoX = gmp_mod(gmp_mul(gmp_init(2), $x), $this->p);
        $x3 = gmp_mod(gmp_sub($slopeSquared, $twoX), $this->p);

        // y3 = 斜率 * (x - x3) - y
        $xMinusX3 = gmp_mod(gmp_sub($x, $x3), $this->p);
        $y3 = gmp_mod(gmp_sub(gmp_mul($slope, $xMinusX3), $y), $this->p);

        return ['x' => $x3, 'y' => $y3];
    }
}