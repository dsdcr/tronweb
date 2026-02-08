<?php
/**
 * WIN TRC20 代币转账脚本（测试网）
 *
 * 转账 10 个 WIN 代币（TRC20标准）
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

// 配置参数
$privateKey = 'your_private_key_here';
$fromAddress = 'TFxaKCCGnbfLr93FjUWBKTJ2mDqJ6KT12h';
$toAddress = 'THGj1SjYL2XWYMiNYceYKSu6bzYQoAur1c';
$winTokenAddress = 'TNDSHKGBmgRx9mDYA9CnxPx55nu672yQw2'; // WIN 代币合约地址
$amount = 10; // 代币数量

echo "=== WIN TRC20 代币转账脚本（测试网）===\n\n";

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
    echo "   - WIN代币合约: {$winTokenAddress}\n";
    echo "   - 转账金额: {$amount} WIN\n\n";

    // 验证地址格式
    if (!$tronWeb->isAddress($fromAddress)) {
        throw new TronException('发送地址格式无效');
    }
    if (!$tronWeb->isAddress($toAddress)) {
        throw new TronException('接收地址格式无效');
    }
    if (!$tronWeb->isAddress($winTokenAddress)) {
        throw new TronException('WIN代币合约地址格式无效');
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

    // 创建WIN TRC20合约实例
    echo "3. 创建 WIN TRC20 合约实例...\n";

    $winContract = $tronWeb->contract()->at($winTokenAddress);
    echo "   ✅ WIN合约实例创建成功\n\n";

    // 查询WIN代币的小数位数
    echo "4. 查询WIN代币信息...\n";
    $decimalsResult = $winContract->decimals();
    $decimals = isset($decimalsResult[0]) ? (int)$decimalsResult[0] : 6; // 默认6位小数
    echo "   - 小数位数: {$decimals}\n";

    // 计算实际转账金额（考虑小数位数）
    $actualAmount = $amount * pow(10, $decimals);
    echo "   - 实际转账金额: {$actualAmount} (最小单位)\n\n";

    // 查询发送方WIN余额
    echo "5. 查询发送方 WIN 余额...\n";
    $winBalanceResult = $winContract->balanceOf($fromAddress);

    // 处理科学记数法格式
    if (is_array($winBalanceResult)) {
        if (isset($winBalanceResult['balance'])) {
            $winBalanceStr = (string)$winBalanceResult['balance'];
            // 转换为普通数字字符串
            if (stripos($winBalanceStr, 'E') !== false) {
                $winBalance = (int)preg_replace('/\.0*/', '', rtrim(sprintf('%.0f', $winBalanceStr), '.'));
            } else {
                $winBalance = (int)$winBalanceStr;
            }
        } elseif (isset($winBalanceResult[0])) {
            $winBalanceStr = (string)$winBalanceResult[0];
            if (stripos($winBalanceStr, 'E') !== false) {
                $winBalance = (int)preg_replace('/\.0*/', '', rtrim(sprintf('%.0f', $winBalanceStr), '.'));
            } else {
                $winBalance = (int)$winBalanceStr;
            }
        } else {
            $winBalance = 0;
        }
    } else {
        $winBalanceStr = (string)$winBalanceResult;
        if (stripos($winBalanceStr, 'E') !== false) {
            $winBalance = (int)preg_replace('/\.0*/', '', rtrim(sprintf('%.0f', $winBalanceStr), '.'));
        } else {
            $winBalance = (int)$winBalanceStr;
        }
    }

    $winBalanceDisplay = $winBalance / pow(10, $decimals);
    echo "   - WIN 余额: {$winBalanceDisplay} WIN\n";

    // 检查WIN余额是否足够
    if ($winBalance < $actualAmount) {
        throw new TronException("WIN余额不足。需要: {$amount} WIN, 实际: {$winBalanceDisplay} WIN");
    }

    // 执行WIN转账
    echo "6. 执行 WIN 转账交易...\n";
    $broadcastResult = $winContract->transfer($toAddress, $actualAmount, [
        'fromAddress' => $fromAddress
    ])->send();
    echo "   ✅ 交易广播结果:\n";
    echo "   - 成功: " . ($broadcastResult['result'] ? '是' : '否') . "\n";

    if (isset($broadcastResult['txid'])) {
        echo "   - 交易哈希: {$broadcastResult['txid']}\n";
    }

    if (isset($broadcastResult['result']) && $broadcastResult['result'] === true) {
        echo "\n   🎉 WIN TRC20 代币转账成功！\n";
        echo "   - 代币合约: {$winTokenAddress}\n";
        echo "   - 数量: {$amount} WIN\n";
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