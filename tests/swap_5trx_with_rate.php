<?php
/**
 * SunSwap V2 USDT -> TRX
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

// ==================== 配置参数 ====================
$privateKey = 'your_private_key_here';
$userAddress = 'TFxaKCCGnbfLr93FjUWBKTJ2mDqJ6KT12h';
$apiKey = 'cf66cd8a-1378-4890-af19-f6c484fda20e';

$routerAddress = 'TKzxdSv2FZKQrEqkKVgp5DcwEXBEKMg2Ax'; // SunSwap V2 Router
$usdtAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';   // USDT (TRC20)
$wtrxAddress = 'TNUC9Qb1rRpS5CbWLmNMxXBjyFoydXjWFR';   // WTRX

$targetTRX = 5;      // 目标获得 5 TRX
$slippage = 2.0;     // 2% 滑点
$feeLimit = 100000000; // 设置 100 TRX 作为手续费上限 (防止能量不足失败)

try {
    echo "🚀 开始 USDT 兑换 {$targetTRX} TRX 流程\n";
    echo "----------------------------------------\n";

    // 1. 初始化 TronWeb
    $nodeConfig = [
        'host' => 'https://api.trongrid.io',
        'headers' => ['TRON-PRO-API-KEY' => $apiKey]
    ];
    $tronWeb = new TronWeb(new HttpProvider($nodeConfig), new HttpProvider($nodeConfig), new HttpProvider($nodeConfig));
    $tronWeb->setPrivateKey($privateKey);
    $tronWeb->setAddress($userAddress);

    // 2. 检查基础余额
    $trxBalance = $tronWeb->trx->getBalance($userAddress, true);
    if ($trxBalance < 20) { // 建议至少保留20TRX处理手续费
        throw new Exception("TRX 余额太低 ($trxBalance TRX)，可能无法支付手续费。");
    }

    $usdtContract = $tronWeb->contract()->at($usdtAddress);
    $usdtBalanceData = $usdtContract->balanceOf($userAddress);
    // 处理返回值为数组或大数的情况
    $usdtBalance = $tronWeb->toDecimal($usdtBalanceData);
    echo "💰 账户 USDT 余额: " . ($usdtBalance / 1000000) . "\n";

    // 3. 计算兑换参数 (使用 getAmountsIn 确保获得 5 TRX 需要多少 USDT)
    $routerContract = $tronWeb->contract()->at($routerAddress);
    $path = [$usdtAddress, $wtrxAddress];
    $targetTRXMicro = $targetTRX * 1000000;

    // 获取需要支付的 USDT 数量
    $amountsIn = $routerContract->getAmountsIn($targetTRXMicro, $path);
    $exactInputUSDT = is_array($amountsIn) ? (string)$amountsIn['amounts'][0] : (string)$amountsIn;

    // 加上滑点后的实际输入
    $inputWithSlippage = (int)($exactInputUSDT * (1 + $slippage / 100));

    echo "📊 交易详情:\n";
    echo "   - 目标产出: {$targetTRX} TRX\n";
    echo "   - 预计消耗: " . ($inputWithSlippage / 1000000) . " USDT\n";

    if ($usdtBalance < $inputWithSlippage) {
        throw new Exception("USDT 余额不足，无法完成本次兑换。");
    }

    // 4. 智能检查授权 (Allowance)
    echo "🔐 检查授权额度...";
    $allowanceData = $usdtContract->allowance($userAddress, $routerAddress);
    $currentAllowance = $tronWeb->toDecimal($allowanceData);
    if ($currentAllowance < $inputWithSlippage) { // 如果授权少于 1 USDT，则重新授权
        echo " 额度不足，正在执行授权...\n";
        $res = $usdtContract->approve($routerAddress, "115792089237316195423570985008687907853269984665640564039457584007913129639935")->send();
        if (isset($res['result']) && $res['result']) {
            echo "✅ 授权成功，TXID: " . $res['txid'] . "\n";
            echo "⏱️ 等待区块链确认 (10s)...\n";
            sleep(10);
        }
    } else {
        echo " OK (额度充足)\n";
    }

    // 5. 执行兑换 (swapExactTokensForETH)
    // 注意：在 SunSwap V2 合约中，TRX 对应 ETH 接口
    echo "🔄 正在发起兑换交易...\n";

    $deadline = time() + 600; // 10分钟有效期

    // 设置 Fee Limit 以确保复杂合约调用不超时/中断
    $options = ['feeLimit' => $feeLimit];

    // 构建交易
    $swapResult = $routerContract->swapExactTokensForETH(
        $inputWithSlippage,
        $targetTRXMicro, // 最小输出为目标值（因为我们已经反推了输入）
        $path,
        $userAddress,
        $deadline
    )->send($options);

    if (isset($swapResult['txid'])) {
        echo "\n🎉 兑换申请已提交成功!\n";
        echo "🔗 交易哈希: " . $swapResult['txid'] . "\n";
        echo "🌐 查询地址: https://tronscan.org" . $swapResult['txid'] . "\n";
    } else {
        echo "❌ 交易广播失败: " . json_encode($swapResult) . "\n";
    }

} catch (Exception $e) {
    echo "\n❌ 错误: " . $e->getMessage() . "\n";
}

echo "\n✨ 脚本执行结束\n";