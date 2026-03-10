<?php
/**
 * 资源委托示例脚本 (能量自动计算功能)
 */

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

echo "========================================\n";
echo "    能量资源自动计算与委托 (delegateResource)\n";
echo "========================================\n\n";

// 配置参数
$config = [
    'fullNodeUrl' => 'https://api.trongrid.io',
    'apiKey'      => 'your-api-key-here',
    'privateKey'  => 'your_private_key_here',
    'ownerAddress' => 'TXPi4mVoZHrTKRRA2ZNEq9bbMKZgqPQGtF',
    'receiverAddress' => 'TFxaKCCGnbfLr93FjUWBKTJ2mDqJ6KT12h',
    'targetEnergy' => 65000, // 目标能量数
    'resource'     => 'ENERGY',
    'lock'         => false,
    'lockPeriod'   => null
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

    // 获取全网资源信息 (显式传入地址防止报错)
    $resources = $tronWeb->trx->getAccountResources($config['ownerAddress']);

    $totalEnergyLimit = $resources['TotalEnergyLimit'] ?? 180000000000;
    $totalEnergyWeight = $resources['TotalEnergyWeight'] ?? 19331561945;
    $energyPerSun = $totalEnergyLimit / $totalEnergyWeight;
    $neededTrxRaw = ceil(($config['targetEnergy'] * $totalEnergyWeight) / $totalEnergyLimit);
    //建议：额外 + 1 TRX 容错，防止计算瞬间全网质押增加导致能量差一点点
    $config['amount'] = $neededTrxRaw + 2;

    echo "   [实时数据] 全网能量上限: " . number_format($totalEnergyLimit) . "\n";
    echo "   [实时数据] 全网能量总质押权重: " . number_format($totalEnergyWeight) . " SUN\n";
    echo "   [计算结果] 获得 {$config['targetEnergy']} 能量约需: $neededTrxRaw TRX\n";
    // --- 核心计算逻辑结束 ---

    echo "2️⃣ 构建委托资源交易\n";
    echo "========================================\n";
    echo "   接收方: {$config['receiverAddress']}\n";
    echo "   金额: {$config['amount']} TRX\n";
    echo "   资源类型: {$config['resource']}\n";

    // 使用计算出的 $config['amount'] 构建交易
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
