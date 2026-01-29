<?php
/**
 * 运行所有 Dsdcr\TronWeb 示例的脚本
 * 这个脚本会依次执行所有模块的示例演示
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

echo "========================================\n";
echo "IEXBase\\TronAPI 示例测试套件\n";
echo "========================================\n\n";

// 配置
$config = [
    'fullNode' => new HttpProvider('https://api.trongrid.io'),
    'solidityNode' => new HttpProvider('https://api.trongrid.io'),
    'eventServer' => new HttpProvider('https://api.trongrid.io')
];

$examples = [
    'tronweb_basic.php' => 'TronWeb 基础使用',
    'account_examples.php' => '账户管理模块',
    'trx_examples.php' => '交易模块',
    'contract_examples.php' => '智能合约模块',
    'token_examples.php' => '代币模块',
    'resource_examples.php' => '资源管理模块',
    'network_examples.php' => '网络信息模块',
    'utils_examples.php' => '工具函数模块'
];

$successCount = 0;
$totalCount = count($examples);

foreach ($examples as $file => $description) {
    echo "🚀 运行示例: {$description} ({$file})\n";
    echo str_repeat("-", 60) . "\n";

    try {
        // 执行示例文件
        include_once __DIR__ . '/' . $file;
        $successCount++;
        echo "✅ {$description} 执行成功\n";
    } catch (Exception $e) {
        echo "❌ {$description} 执行失败: " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n\n";
    sleep(1); // 防止请求过于频繁
}

// 汇总结果
echo "📊 测试结果汇总:\n";
echo "✅ 成功: {$successCount}/{$totalCount}\n";
echo "❌ 失败: " . ($totalCount - $successCount) . "/{$totalCount}\n\n";

if ($successCount === $totalCount) {
    echo "🎉 所有示例测试通过！\n";
} else {
    echo "⚠️  部分示例测试失败，请检查错误信息。\n";
}

echo "\n💡 使用说明:\n";
echo "- 本测试套件主要演示查询功能\n";
echo "- 需要网络连接访问 TRON Grid API\n";
echo "- 某些功能需要真实私钥才能完整测试\n";
echo "- 生产环境使用前请在测试网充分验证\n";

echo "\n📁 生成的示例文件:\n";
foreach ($examples as $file => $description) {
    echo "- {$file}: {$description}\n";
}
echo "- iexbase_tronapi_introduction.md: 库介绍文档\n";

echo "\n========================================\n";
echo "测试完成时间: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n";
?>