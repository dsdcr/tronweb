<?php
/**
 * 使用 contract($abi)->at($address) 方法测试 USDT 余额
 * 演示 TypeScript 风格的合约调用方式
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

// 辅助函数：解析合约返回值
function decodeReturnValue($value) {
    if (is_array($value)) {
        // 如果是关联数组，返回第一个值
        $values = array_values($value);
        return $values[0] ?? $value;
    }
    return $value;
}

echo "========================================\n";
echo "  USDT 余额查询测试 (contract->at 方式)\n";
echo "========================================\n\n";

// 测试地址（Binance Hot Wallet，通常有 USDT 余额）
$testAddress = 'TJvaAeFb8Lykt9RQcVyyTFN2iDvGMuyD4M';

// USDT TRC20 合约地址
$usdtContractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';

try {
    echo "1️⃣ 初始化 TronWeb\n";
    echo "========================================\n";

    // 创建 TronWeb 实例
    $tronWeb = new TronWeb(
        new HttpProvider('https://api.trongrid.io')
    );

    echo "✅ TronWeb 初始化成功\n\n";

    echo "2️⃣ 创建合约实例（使用 ABI）\n";
    echo "========================================\n";

    // 使用 contract($abi)->at($address) 方式
    $contract = $tronWeb->contract()->at($usdtContractAddress);

    echo "✅ 合约实例创建成功\n";
    echo "   合约地址: {$usdtContractAddress}\n";
    echo "   测试地址: {$testAddress}\n\n";

    echo "3️⃣ 查询代币信息\n";
    echo "========================================\n";

    // 获取小数位数（先获取，用于格式化）
    $decimalsResult = $contract->decimals();
    $decimals = decodeReturnValue($decimalsResult);
    echo "   小数位数: {$decimals}\n\n";

    // 等待避免 API 频率限制
    sleep(1);

    // 获取总供应量
    $totalSupplyResult = $contract->totalSupply();
    $totalSupply = decodeReturnValue($totalSupplyResult);
    echo "   总供应量: " . number_format($totalSupply / pow(10, $decimals), 2) . "\n\n";

    // 等待避免 API 频率限制
    sleep(1);

    // 获取代币名称
    $nameResult = $contract->name();
    $name = decodeReturnValue($nameResult);
    echo "   代币名称: {$name}\n";

    // 获取代币符号
    $symbolResult = $contract->symbol();
    $symbol = decodeReturnValue($symbolResult);
    echo "   代币符号: {$symbol}\n\n";

    echo "4️⃣ 查询余额\n";
    echo "========================================\n";

    // 等待避免 API 频率限制
    sleep(1);

    // 查询余额
    $balanceResult = $contract->balanceOf($testAddress);
    $balanceRaw = decodeReturnValue($balanceResult);

    // 查询原始余额（最小单位）
    echo "   原始余额: {$balanceRaw} (最小单位)\n\n";

    echo "5️⃣ 格式化并显示余额\n";
    echo "========================================\n";

    // 计算格式化余额
    $balanceFormatted = $balanceRaw / pow(10, $decimals);
    echo "   格式化余额: " . number_format($balanceFormatted, 6, '.', ',') . " {$symbol}\n\n";

    echo "6️⃣ 验证结果\n";
    echo "========================================\n";

    if ($balanceFormatted > 0) {
        echo "   ✅ 成功查询到余额\n";
        echo "   💰 当前余额: " . number_format($balanceFormatted, 6, '.', ',') . " {$symbol}\n";
    } else {
        echo "   ℹ️  该地址当前余额为 0\n";
    }

    echo "\n========================================\n";
    echo "✅ 测试完成！\n";
    echo "========================================\n\n";

    echo "📝 使用说明:\n";
    echo "- contract(\$abi)->at(\$address) 是 TypeScript 风格的链式调用\n";
    echo "- \$abi 是合约的 ABI 数组，定义了可用的方法\n";
    echo "- at(\$address) 设置合约地址\n";
    echo "- 之后可以直接调用合约方法，如 balanceOf()\n";
    echo "- 查询类操作（view/pure）不需要私钥\n";
    echo "- 返回值是解码后的关联数组，需要提取第一个值\n";
    echo "- 余额需要除以 10^decimals 得到实际数量\n";
    echo "- 避免 API 频率限制：查询之间添加延迟\n\n";

} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}
?>