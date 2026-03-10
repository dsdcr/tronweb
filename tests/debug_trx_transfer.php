<?php
/**
 * TRX 转账脚本（测试网）
 *
 * 使用库的 sendTrx() 方法直接转账 10 TRX
 */

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

// 配置参数
$privateKey = 'your_private_key_here';
$fromAddress = 'TFxaKCCGnbfLr93FjUWBKTJ2mDqJ6KT12h';
$toAddress = 'THGj1SjYL2XWYMiNYceYKSu6bzYQoAur1c';
$amount = 10; // TRX 数量

echo "=== TRX 转账脚本（测试网）===\n\n";

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
    echo "   - 转账金额: {$amount} TRX\n\n";

    // 验证地址格式
    if (!$tronWeb->isAddress($fromAddress)) {
        throw new TronException('发送地址格式无效');
    }
    if (!$tronWeb->isAddress($toAddress)) {
        throw new TronException('接收地址格式无效');
    }
    echo "   ✅ 地址格式验证通过\n\n";

    // 查询发送方TRX余额
    echo "2. 查询发送方 TRX 余额...\n";
    $balance = $tronWeb->trx->getBalance($fromAddress, true); // true 表示返回TRX单位
    echo "   - 余额: {$balance} TRX\n\n";

    // 检查余额是否足够
    if ($balance < $amount + 0.1) { // 额外留0.1 TRX作为手续费
        throw new TronException("余额不足。转账需要: {$amount} TRX + 手续费, 实际余额: {$balance} TRX");
    }

    // 使用库的 sendTrx() 方法直接转账
    echo "3. 使用库方法发送 TRX 转账交易...\n";
    $result = $tronWeb->trx->sendTrx(
        $toAddress,
        $amount
    );

    echo "   ✅ 交易广播结果:\n";
    echo "   - 成功: " . ($result['result'] ? '是' : '否') . "\n";

    if (isset($result['txid'])) {
        echo "   - 交易哈希: {$result['txid']}\n";
    }

    if (isset($result['result']) && $result['result'] === true) {
        echo "\n   🎉 TRX 转账成功！\n";
        echo "   - 金额: {$amount} TRX\n";
        echo "   - 发送方: {$fromAddress}\n";
        echo "   - 接收方: {$toAddress}\n";
        echo "   - 交易哈希: {$result['txid']}\n";
        echo "   - 浏览器查看: https://nile.tronscan.org/#/transaction/{$result['txid']}\n";
        echo "   - 使用库方法: trx->sendTrx()\n";
    } else {
        echo "\n   ❌ 转账失败\n";
        if (isset($result['message'])) {
            echo "   - 错误信息: " . ($tronWeb->utils->fromUtf8($result['message']) ?? $result['message']) . "\n";
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