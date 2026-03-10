<?php
/**
 * TRC10 代币转账脚本（测试网）
 *
 * 转账 10 个 TRC10 代币（资产 ID: 1005416）
 */

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

// 配置参数
$privateKey = 'your_private_key_here';
$fromAddress = 'TFxaKCCGnbfLr93FjUWBKTJ2mDqJ6KT12h';
$toAddress = 'THGj1SjYL2XWYMiNYceYKSu6bzYQoAur1c';
$tokenId = '1005416'; // TRC10 代币资产ID
$amount = 10; // 代币数量

echo "=== TRC10 代币转账脚本（测试网）===\n\n";

try {
    echo "1. 初始化 TronWeb（测试网）...\n";

    $httpProvider = new HttpProvider('https://nile.trongrid.io');
    $tronWeb = new TronWeb($httpProvider, $httpProvider, $httpProvider);

    // 设置私钥
    $tronWeb->setPrivateKey($privateKey);

    echo "   ✅ TronWeb 初始化成功\n";
    echo "   - 网络: Nile 测试网\n";
    echo "   - 发送地址: {$fromAddress}\n";
    echo "   - 接收地址: {$toAddress}\n";
    echo "   - 代币资产ID: {$tokenId}\n";
    echo "   - 转账金额: {$amount} 个代币\n\n";

    // 验证地址格式
    if (!$tronWeb->utils->isAddress($fromAddress)) {
        throw new TronException('发送地址格式无效');
    }
    if (!$tronWeb->utils->isAddress($toAddress)) {
        throw new TronException('接收地址格式无效');
    }
    echo "   ✅ 地址格式验证通过\n\n";

    // 查询发送方TRX余额（用于手续费）
    echo "2. 查询发送方 TRX 余额（用于手续费）...\n";
    $balance = $tronWeb->trx->getBalance($fromAddress, true);
    echo "   - TRX 余额: {$balance} TRX\n\n";

    // 检查TRX余额是否足够支付手续费
    if ($balance < 1) { // 至少需要1 TRX作为手续费
        throw new TronException("TRX余额不足支付手续费。需要至少1 TRX, 实际: {$balance} TRX");
    }

    // 使用TransactionBuilder发送TRC10代币
    echo "3. 发送 TRC10 代币转账交易...\n";
    $result = $tronWeb->transactionBuilder->sendToken(
        $toAddress,
        $tokenId,
        $amount,
        $fromAddress
    );

    echo "   ✅ 交易构建成功\n";
    echo "   - 交易ID: " . ($result['txID'] ?? 'N/A') . "\n\n";

    // 签名交易
    echo "4. 签名交易...\n";
    $signedTransaction = $tronWeb->trx->signTransaction($result);
    echo "   ✅ 交易签名成功\n\n";

    // 广播交易
    echo "5. 广播交易...\n";
    $broadcastResult = $tronWeb->trx->sendRawTransaction($signedTransaction);

    echo "   ✅ 交易广播结果:\n";
    echo "   - 成功: " . ($broadcastResult['result'] ? '是' : '否') . "\n";

    if (isset($broadcastResult['txid'])) {
        echo "   - 交易哈希: {$broadcastResult['txid']}\n";
    }

    if (isset($broadcastResult['result']) && $broadcastResult['result'] === true) {
        echo "\n   🎉 TRC10 代币转账成功！\n";
        echo "   - 代币资产ID: {$tokenId}\n";
        echo "   - 数量: {$amount} 个代币\n";
        echo "   - 发送方: {$fromAddress}\n";
        echo "   - 接收方: {$toAddress}\n";
        echo "   - 交易哈希: {$broadcastResult['txid']}\n";
        echo "   - 浏览器查看: https://nile.tronscan.org/#/transaction/{$broadcastResult['txid']}\n";
    } else {
        echo "\n   ❌ 转账失败\n";
        if (isset($broadcastResult['message'])) {
            echo "   - 错误信息: " . ($tronWeb->utils->fromUtf8($broadcastResult['message']) ?? $broadcastResult['message']) . "\n";
        }
    }

} catch (TronException $e) {
    echo "\n❌ Tron 异常: " . $e->getMessage() . "\n";
    echo "   文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "\n❌ 一般异常: " . $e->getMessage() . "\n";
    echo "   文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== 脚本执行完成 ===\n";
?>