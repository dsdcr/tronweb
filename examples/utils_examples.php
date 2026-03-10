<?php
/**
 * Utils 工具函数使用示例
 * 展示工具函数：地址转换、单位换算、格式验证等
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;

echo "=== Utils 工具函数使用示例 ===\n\n";

try {
    // 1. 地址转换功能
    echo "1. 地址转换功能:\n";

    $testAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';

    // Base58 转 Hex（使用 TronUtils 静态方法）
    $hexAddress = TronUtils::toHex($testAddress);
    echo "   Base58 → Hex: $hexAddress\n";

    // Hex 转 Base58（使用 TronUtils 静态方法）
    $base58Address = TronUtils::fromHex($hexAddress);
    echo "   Hex → Base58: $base58Address\n";

    // 一致性验证
    echo "   转换一致性: " . ($testAddress === $base58Address ? '✓ 一致' : '✗ 不一致') . "\n\n";

    // 2. 单位换算功能
    echo "2. 单位换算功能:\n";

    $trxAmount = 1.5;
    $sunAmount = TronUtils::trxToSun($trxAmount);
    $convertedBack = TronUtils::sunToTrx($sunAmount);

    echo "   TRX → SUN: $trxAmount TRX = $sunAmount SUN\n";
    echo "   SUN → TRX: $sunAmount SUN = $convertedBack TRX\n";

    // 别名方法测试（trxToSun 和 sunToTrx 是 trxToSun 和 sunToTrx 的别名）
    $tronAmount = TronUtils::trxToSun($trxAmount);
    $fromTron = TronUtils::sunToTrx($sunAmount);
    echo "   别名方法一致性: " . ($sunAmount === $tronAmount && $convertedBack === $fromTron ? '✓ 一致' : '✗ 不一致') . "\n\n";

    // 3. 地址验证功能
    echo "3. 地址验证功能:\n";

    $validAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';
    $invalidAddress = 'invalid_address_123';

    echo "   有效地址验证: " . (TronUtils::isAddress($validAddress) ? '✓ 有效' : '✗ 无效') . "\n";
    echo "   无效地址验证: " . (TronUtils::isAddress($invalidAddress) ? '✓ 有效' : '✗ 无效') . "\n\n";

    // 4. 字符串编码功能
    echo "4. 字符串编码功能:\n";

    $testString = 'Hello Tron!';
    $hexString = TronUtils::stringToHex($testString);
    $backToString = TronUtils::hexToString($hexString);

    echo "   原始字符串: $testString\n";
    echo "   Hex 编码: $hexString\n";
    echo "   解码回字符串: $backToString\n";
    echo "   编码一致性: " . ($testString === $backToString ? '✓ 一致' : '✗ 不一致') . "\n\n";

    // 5. UTF-8 编码功能
    echo "5. UTF-8 编码功能:\n";

    $utf8String = '你好，TRON！';
    $utf8Hex = TronUtils::toUtf8($utf8String);
    $fromUtf8 = TronUtils::fromUtf8($utf8Hex);

    echo "   UTF-8 字符串: $utf8String\n";
    echo "   UTF-8 Hex: $utf8Hex\n";
    echo "   解码回 UTF-8: $fromUtf8\n";
    echo "   UTF-8 一致性: " . ($utf8String === $fromUtf8 ? '✓ 一致' : '✗ 不一致') . "\n\n";

    // 6. 格式验证功能
    echo "6. 格式验证功能:\n";

    $validHex = '414243444546474849';
    $invalidHex = 'XYZ123';

    echo "   有效 Hex 验证: " . (TronUtils::isHex($validHex) ? '✓ 有效' : '✗ 无效') . "\n";
    echo "   无效 Hex 验证: " . (TronUtils::isHex($invalidHex) ? '✓ 有效' : '✗ 无效') . "\n";

    // 区块标识符验证
    $blockNumber = 12345;
    $blockHash = '0000000000000000000db6fae4c5c5c9e6a1d34c050e711622027d2';

    echo "   区块号验证: " . (TronUtils::isValidBlockIdentifier($blockNumber) ? '✓ 有效' : '✗ 无效') . "\n";
    echo "   区块哈希验证: " . (TronUtils::isValidBlockIdentifier($blockHash) ? '✓ 有效' : '✗ 无效') . "\n\n";

    // 7. 数值处理功能
    echo "7. 数值处理功能:\n";

    // 格式化 TRX 金额
    $formatted = TronUtils::formatTrx(1234.56789, 6);
    echo "   格式化 TRX: 1234.56789 → $formatted\n";

    // 时间戳转换
    $currentMicro = time() * 1000;
    $seconds = TronUtils::microToSeconds($currentMicro);
    $backToMicro = TronUtils::secondsToMicro($seconds);

    echo "   微秒转秒: $currentMicro → $seconds\n";
    echo "   秒转微秒: $seconds → $backToMicro\n";
    echo "   时间戳一致性: " . ($currentMicro === $backToMicro ? '✓ 一致' : '✗ 不一致') . "\n\n";

    // 8. 安全随机数生成
    echo "8. 安全随机数生成:\n";

    $randomHex = TronUtils::randomHex(32);
    echo "   32 字节随机 Hex: " . substr($randomHex, 0, 16) . "...\n";
    echo "   长度验证: " . (strlen($randomHex) === 64 ? '✓ 64 字符' : '✗ 长度错误') . "\n\n";

    // 9. 直接使用 TronUtils 静态方法
    echo "9. 直接使用 TronUtils 类:\n";

    // 调用 toHex（别名方法）
    $directHex = TronUtils::toHex($testAddress);
    $directValidation = TronUtils::isAddress($testAddress);

    echo "   直接调用地址转换: $directHex\n";
    echo "   直接调用地址验证: " . ($directValidation ? '✓ 有效' : '✗ 无效') . "\n";
    echo "   方法与实例一致性: " . ($hexAddress === $directHex ? '✓ 一致' : '✗ 不一致') . "\n\n";

    echo "=== Utils 工具函数示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron 异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Utils 工具函数主要方法:\n";
echo "- toHex() / fromHex(): 地址转换（支持 Base58 和 Hex 互转）\n";
echo "- trxToSun() / sunToTrx(): 单位换算（1 TRX = 1,000,000 SUN）\n";
echo "- trxToSun() / sunToTrx(): 单位换算别名方法\n";
echo "- isAddress(): 验证 TRON 地址格式\n";
echo "- stringToHex() / hexToString(): 字符串编码（十六进制）\n";
echo "- toUtf8() / fromUtf8(): UTF-8 字符串编码\n";
echo "- isHex(): Hex 格式验证\n";
echo "- isValidBlockIdentifier(): 区块标识符验证\n";
echo "- formatTrx(): TRX 金额格式化\n";
echo "- microToSeconds() / secondsToMicro(): 时间戳转换（毫秒/秒）\n";
echo "- randomHex(): 安全随机数生成\n";
echo "- toHex() / fromHex(): 地址转换别名方法\n";
echo "- 共 22+ 个工具函数\n";

echo "\n💡 使用提示:\n";
echo("- 工具函数都是静态方法，可以直接调用\n");
echo("- 地址转换时注意 Base58 和 Hex 格式\n");
echo("- 单位换算使用 SUN 作为最小单位（1 TRX = 1,000,000 SUN）\n");
echo("- 所有验证函数返回布尔值\n");
echo("- 随机数生成是加密安全的（使用 random_bytes）\n");

echo "\n🔧 实用场景:\n";
echo("- 地址格式转换和验证\n");
echo("- 交易金额单位处理\n");
echo("- 字符串和二进制数据编码\n");
echo("- 区块标识和时间戳验证\n");
echo("- 安全随机数生成（用于私钥等敏感操作）\n");

echo "\n⚠️ 注意:\n";
echo("- 地址验证只检查格式，不检查链上存在性\n");
echo("- 单位换算精度为 6 位小数\n");
echo("- 随机数生成用于创建私钥和助记词\n");
echo("- UTF-8 编码用于处理非 ASCII 字符（如中文）\n");
echo("- 所有 Hex 输出不包含 '0x' 前缀\n");
?>
