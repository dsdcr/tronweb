<?php
/**
 * 完整的 delegateResource 测试脚本
 * 演示如何委托资源（带宽或能量）给其他账户
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

echo "========================================\n";
echo "  委托资源完整测试 (delegateResource)\n";
echo "========================================\n\n";

// 统一 API KEY（所有测试中都使用这个）
$apiKey = 'dcc941d3-2634-4c04-a173-4615104e1e6a';

// 配置参数
$config = [
    'fullNodeUrl' => 'https://api.trongrid.io',
    'apiKey' => $apiKey,
    'privateKey' => '', // ⚠️ 必须设置：你的私钥用于签名
    'ownerAddress' => '', // ⚠️ 必须设置：委托方地址（Base58格式）
    'receiverAddress' => 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL', // 接收者地址
    'amount' => 100, // 委托金额（TRX）
    'resource' => 'BANDWIDTH', // 资源类型：BANDWIDTH 或 ENERGY
];

try {
    echo "1️⃣ 初始化 TronWeb\n";
    echo "========================================\n";

    // 创建带 API KEY 的 HttpProvider
    $httpProvider = new HttpProvider($config['fullNodeUrl'], [
        'headers' => [
            'TRON-PRO-API-KEY' => $apiKey
        ],
        'timeout' => 30000
    ]);

    // 创建 TronWeb 实例
    $tronWeb = new TronWeb($httpProvider);

    echo "✅ TronWeb 初始化成功\n";
    echo "✅ API KEY: {$apiKey}\n\n";

    // 检查必要参数
    if (empty($config['privateKey']) || empty($config['ownerAddress'])) {
        echo "❌ 缺少必要参数\n";
        echo "========================================\n";
        echo "⚠️  要执行实际交易，必须设置以下参数：\n";
        echo "   - privateKey: 委托方私钥\n";
        echo "   - ownerAddress: 委托方地址（Base58格式）\n\n";
        echo "请修改脚本中的 \$config 数组，设置这些参数后重试。\n\n";
        exit(1);
    }

    // 设置私钥和地址
    $tronWeb->setPrivateKey($config['privateKey']);
    $tronWeb->setAddress($config['ownerAddress']);

    echo "✅ 私钥已设置\n";
    echo "✅ 委托方地址: {$config['ownerAddress']}\n\n";

    echo "2️⃣ 构建委托资源交易\n";
    echo "========================================\n";

    $receiverAddr = $config['receiverAddress'];
    $amount = $config['amount'];
    $resourceType = $config['resource'];

    echo "   接收方: {$receiverAddr}\n";
    echo "   金额: {$amount} TRX\n";
    echo "   资源类型: {$resourceType}\n\n";

    // 构建交易
    $transaction = $tronWeb->transactionBuilder->delegateResource(
        $config['receiverAddress'],  // 接收者地址
        $config['amount'],          // 委托金额（TRX）
        $config['resource'],        // 资源类型
        $config['ownerAddress'],    // 委托方地址
        false,                      // 是否锁定
        null,                       // 锁定周期
        []                          // 选项
    );

    echo "✅ 交易构建成功\n";

    if (is_array($transaction) && isset($transaction['Error'])) {
        echo "❌ 构建失败: {$transaction['Error']}\n\n";
        exit(1);
    }

    echo "   交易ID: " . ($transaction['txID'] ?? 'N/A') . "\n\n";

    echo "3️⃣ 签名交易\n";
    echo "========================================\n";

    $signedTx = $tronWeb->trx->signTransaction($transaction);
    echo "✅ 交易签名成功\n";
    echo "   签名数量: " . count($signedTx['signature']) . "\n\n";

    echo "4️⃣ 广播交易\n";
    echo "========================================\n";

    $result = $tronWeb->trx->sendRawTransaction($signedTx);
    echo "✅ 交易广播结果:\n";
    echo "   " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

    if (!empty($result['txid'])) {
        echo "🎉 交易已成功提交！\n";
        echo "   交易ID: {$result['txid']}\n";
        echo "   可以在 Tronscan 查询交易状态\n\n";

        echo "💡 提示:\n";
        echo "- 交易确认需要几秒钟到几分钟\n";
        echo "- 可以在 https://tronscan.org 查询交易\n";
        echo "- 使用交易ID搜索查看状态\n\n";
    } else if (isset($result['Error'])) {
        echo "❌ 广播失败: {$result['Error']}\n\n";
    }

    echo "========================================\n";
    echo "✅ 测试完成！\n";
    echo "========================================\n\n";

    echo "📋 delegateResource 方法说明\n";
    echo "========================================\n";
    echo "功能：将 TRX 质押并委托资源（带宽/能量）给其他账户\n\n";

    echo "🔧 参数说明:\n";
    echo "1. receiverAddress (string) - 接收资源的目标地址\n";
    echo "2. amount (float) - 委托金额，单位为 TRX\n";
    echo "3. resource (string) - 资源类型: 'BANDWIDTH' 或 'ENERGY'\n";
    echo "4. address (string|null) - 委托方地址（可选）\n";
    echo "5. lock (bool) - 是否锁定委托资源\n";
    echo "6. lockPeriod (int|null) - 锁定周期（天）\n";
    echo "7. options (array) - 额外选项，如 permissionId\n\n";

    echo "💡 使用示例:\n";
    echo "// 委托 100 TRX 的带宽给其他账户\n";
    echo "\$tx = \$tronWeb->transactionBuilder->delegateResource(\n";
    echo "    'TXYZ... receiverAddress',\n";
    echo "    100,\n";
    echo "    'BANDWIDTH',\n";
    echo "    'TABC... ownerAddress'\n";
    echo ");\n\n";

    echo "// 委托 50 TRX 的能量\n";
    echo "\$tx = \$tronWeb->transactionBuilder->delegateResource(\n";
    echo "    'TXYZ... receiverAddress',\n";
    echo "    50,\n";
    echo "    'ENERGY',\n";
    echo "    'TABC... ownerAddress'\n";
    echo ");\n\n";

    echo "⚠️  注意事项:\n";
    echo "- 委托的 TRX 会被锁定，不能转账\n";
    echo "- 委托的资源接收者可以免费使用\n";
    echo "- 可以随时取消委托，但有3天解押期\n";
    echo "- 使用 API KEY 可以避免频率限制\n";
    echo "- 确保地址有足够的 TRX 余额\n";
    echo "- 先在测试网测试，确认无误后再在主网操作\n\n";

    echo "📚 相关方法:\n";
    echo "- delegateResource(): 委托资源\n";
    echo "- undelegateResource(): 取消委托\n";
    echo "- getDelegatedResourceV2(): 查询委托记录\n";
    echo "- getAvailableUnfreezeBalance(): 查询可解押金额\n\n";

} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}
?>