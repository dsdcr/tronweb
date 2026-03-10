<?php
/**
 * 资源委托示例脚本 (带宽自动计算功能)
 */

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

echo "========================================\n";
echo "    带宽资源自动计算与委托 (delegateResource)\n";
echo "========================================\n\n";

// 配置参数
$config = [
    'fullNodeUrl' => 'https://api.trongrid.io',
    'apiKey' => 'your-api-key-here', // ⚠️ 替换为您的 TRON-PRO-API-KEY
    'privateKey' => 'your_private_key_here', // ⚠️ 替换为您的私钥
    'ownerAddress' => 'YOUR_ADDRESS_HERE', // ⚠️ 替换为您的地址
    'receiverAddress' => 'RECEIVER_ADDRESS_HERE', // ⚠️ 替换为接收方地址
    'targetBandwidth' => 600, // 目标带宽数
    'resource' => 'BANDWIDTH',
    'lock' => false,
    'lockPeriod' => null
];

try {
    $httpProvider = new HttpProvider($config['fullNodeUrl'], [
        'headers' => ['TRON-PRO-API-KEY' => $config['apiKey']],
        'timeout' => 30000
    ]);
    $tronWeb = new TronWeb($httpProvider);
    $tronWeb->setPrivateKey($config['privateKey']);
    $tronWeb->setAddress($config['ownerAddress']);

    echo "1️⃣ 正在获取网络实时参数并计算汇率...\n";

    $resources = $tronWeb->trx->getAccountResources($config['ownerAddress']);
    $totalNetLimit = $resources['TotalNetLimit'] ?? 43200000000;
    $totalNetWeight = $resources['TotalNetWeight'] ?? 1;
    $finalAmount = ceil(($config['targetBandwidth'] * $totalNetWeight) / $totalNetLimit);
    $config['amount'] = $finalAmount + 1;

    echo "   [实时数据] 全网带宽上限: " . number_format($totalNetLimit) . "\n";
    echo "   [实时数据] 全网带宽总质押权重: " . number_format($totalNetWeight) . " SUN\n";
    echo "   [计算结果] 获得 {$config['targetBandwidth']} 带宽约需: {$config['amount']} TRX\n";

    echo "2️⃣ 构建委托资源交易\n";
    echo "========================================\n";
    echo "   接收方: {$config['receiverAddress']}\n";
    echo "   金额: {$config['amount']} TRX\n";
    echo "   资源类型: {$config['resource']}\n";

    $transaction = $tronWeb->transactionBuilder->delegateResource(
        $config['receiverAddress'],
        $config['amount'],         // 这里现在是自动计算后的数字
        $config['resource'],
        $config['ownerAddress'],
        $config['lock'],
        $config['lockPeriod'],
        ['permissionId' => 3]
    );

    if (!$transaction || !isset($transaction['txID'])) {
        throw new Exception("交易构建失败，请检查账户余额是否足够质押。");
    }

    echo "✅ 交易构建成功，ID: {$transaction['txID']}\n\n";

    echo "3️⃣ 签名交易 (MultiSign ID: 3)\n";
    echo "========================================\n";
    $signedTx = $tronWeb->trx->multiSign($transaction, $config['privateKey'], 3);
    echo "✅ 签名完成\n\n";

    echo "4️⃣ 广播交易\n";
    echo "========================================\n";
    $result = $tronWeb->trx->sendRawTransaction($signedTx);

    if (!empty($result['result']) || !empty($result['txid'])) {
        echo "🎉 成功！{$config['amount']} TRX 对应的资源已委托给接收方。\n";
        echo "   交易ID: " . ($result['txid'] ?? '请在链上确认') . "\n";
    } else {
        echo "❌ 广播失败\n";
        echo "返回详情: " . json_encode($result) . "\n";
    }

} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
}
