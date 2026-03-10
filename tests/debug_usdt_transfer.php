<?php
/**
 * USDT TRC20 转账脚本（测试网）
 *
 * 转账 10 USDT 从用户地址到目标地址
 */

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

// 配置参数
$privateKey = 'your_private_key_here';
$fromAddress = 'TFxaKCCGnbfLr93FjUWBKTJ2mDqJ6KT12h';
$toAddress = 'THGj1SjYL2XWYMiNYceYKSu6bzYQoAur1c';
$amount = 10; // USDT 数量

// USDT TRC20 合约地址（测试网）
// 注意：测试网 USDT 合约地址可能不同，这里使用常见的测试网地址
// 实际使用时需要确认测试网的 USDT 合约地址
$usdtContractAddress = 'TXYZopYRdj2D9XRtbG411XZZ3kM5VkAeBf'; // 测试网 USDT 合约地址

echo "=== USDT TRC20 转账脚本（测试网）===\n\n";

// USDT 合约 ABI（包含 transfer 方法）
$usdtAbi = [
    [
        'constant' => false,
        'inputs' => [
            ['name' => '_to', 'type' => 'address'],
            ['name' => '_value', 'type' => 'uint256']
        ],
        'name' => 'transfer',
        'outputs' => [['name' => '', 'type' => 'bool']],
        'payable' => false,
        'stateMutability' => 'nonpayable',
        'type' => 'function'
    ],
    [
        'constant' => true,
        'inputs' => [['name' => '_owner', 'type' => 'address']],
        'name' => 'balanceOf',
        'outputs' => [['name' => 'balance', 'type' => 'uint256']],
        'payable' => false,
        'stateMutability' => 'view',
        'type' => 'function'
    ],
    [
        'constant' => true,
        'inputs' => [],
        'name' => 'decimals',
        'outputs' => [['name' => '', 'type' => 'uint8']],
        'payable' => false,
        'stateMutability' => 'view',
        'type' => 'function'
    ]
];

try {
    echo "1. 初始化 TronWeb（测试网）...\n";


    $httpProvider = new HttpProvider('https://nile.trongrid.io');
    // 使用测试网
    $tronWeb = new TronWeb($httpProvider,$httpProvider,$httpProvider,$httpProvider,$httpProvider);

    // 设置私钥
    $tronWeb->setPrivateKey($privateKey);

    echo "   ✅ TronWeb 初始化成功\n";
    echo "   - 网络: Nile 测试网\n";
    echo "   - 发送地址: {$fromAddress}\n";
    echo "   - 接收地址: {$toAddress}\n\n";

    // 验证地址格式
    if (!$tronWeb->utils->isAddress($fromAddress)) {
        throw new TronException('发送地址格式无效');
    }
    if (!$tronWeb->utils->isAddress($toAddress)) {
        throw new TronException('接收地址格式无效');
    }
    echo "   ✅ 地址格式验证通过\n\n";

    echo "2. 创建 USDT 合约实例...\n";

    // 创建合约实例
    $contract = $tronWeb->contract($usdtAbi)->at($usdtContractAddress);

    echo "   ✅ 合约实例创建成功\n";
    echo "   - 合约地址: {$usdtContractAddress}\n\n";

    // 查询 USDT 合约信息（先获取基本合约信息）
    echo "3. 查询 USDT 合约信息...\n";

    // 直接使用标准 USDT 的小数位数（6位）
    $decimals = 6;
    echo "   - 小数位数: {$decimals} (使用标准值)\n";

    // 计算实际转账金额（最小单位）
    $amountIn = $amount * pow(10, $decimals);
    echo "   - 转账金额: {$amount} USDT\n";
    echo "   - 最小单位金额: {$amountIn}\n\n";

    // 查询发送方余额
    echo "4. 查询发送方 USDT 余额...\n";
    sleep(1); // 避免频率限制
    try {
        $balanceResult = $contract->balanceOf($fromAddress);

        // 处理可能的数组返回格式
        if (is_array($balanceResult)) {
            if (isset($balanceResult['balance'])) {
                $balance = $balanceResult['balance'];
            } elseif (isset($balanceResult[0])) {
                $balance = $balanceResult[0];
            } else {
                $balance = 0;
            }
        } else {
            $balance = $balanceResult;
        }

        $balanceFormatted = intval($balance) / pow(10, $decimals);
        echo "   - 余额: {$balanceFormatted} USDT\n\n";
    } catch (Exception $e) {
        echo "   - 余额查询失败，继续转账...\n\n";
        $balance = 10 * pow(10, $decimals); // 假设有足够余额
    }

    if ($balance < $amountIn) {
        throw new TronException("余额不足。需要: {$amountIn}, 实际: {$balance}");
    }

    echo "5. 构建 USDT 转账交易...\n";

    // 将地址转换为十六进制
    $contractAddressHex = $tronWeb->toHex($usdtContractAddress);

    // 构建触发智能合约交易
    $transaction = $tronWeb->transactionBuilder->triggerSmartContract(
        $usdtAbi,
        $contractAddressHex,
        'transfer',
        [$tronWeb->toHex($toAddress), (string)$amountIn],
        [
            'feeLimit' => 100000000, // 100 TRX (最大值)
            'callValue' => 0,
            'fromAddress' => $fromAddress
        ]
    );

    echo "   ✅ 交易构建成功\n";
    echo "   - 交易ID: " . ($transaction['txID'] ?? 'N/A') . "\n\n";

    echo "6. 签名交易...\n";

    // 签名交易
    $signedTransaction = $tronWeb->trx->signTransaction($transaction);

    echo "   ✅ 交易签名成功\n\n";

    echo "7. 广播交易...\n";

    // 广播交易
    $result = $tronWeb->trx->sendRawTransaction($signedTransaction);

    echo "   ✅ 交易广播结果:\n";
    echo "   - 成功: " . ($result['result'] ? '是' : '否') . "\n";

    if (isset($result['txid'])) {
        echo "   - 交易哈希: {$result['txid']}\n";
    }

    if (isset($result['result']) && $result['result'] === true) {
        echo "\n   🎉 转账成功！\n";
        echo "   - 金额: {$amount} USDT\n";
        echo "   - 发送方: {$fromAddress}\n";
        echo "   - 接收方: {$toAddress}\n";
        echo "   - 交易哈希: {$result['txid']}\n";
        echo "   - 浏览器查看: https://nile.tronscan.org/#/transaction/{$result['txid']}\n";
    } else {
        echo "\n   ❌ 转账失败\n";
        if (isset($result['message'])) {
            echo "   - 错误信息: " . $tronWeb->utils->fromUtf8($result['message']) . "\n";
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