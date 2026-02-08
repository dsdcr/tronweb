<?php
/**
 * Contract 模块使用示例
 * 展示智能合约相关功能：合约实例创建、信息查询、事件查询、链式调用等
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

echo "=== Contract 模块使用示例 ===\n\n";

try {
    // 初始化 TronWeb 实例
    $httpProvider = new HttpProvider([
        'host' => 'https://nile.trongrid.io',
        'headers' => ['TRON-PRO-API-KEY' => 'your-api-key']
    ]);

    $tronWeb = new TronWeb($httpProvider, $httpProvider, $httpProvider);

    // 设置私钥（需要交易功能时）
    // $tronWeb->setPrivateKey('your-private-key');
    // $tronWeb->setAddress('your-address');

    // USDT TRC20 合约地址
    $usdtContractAddress = 'TXYZopYRdj2D9XRtbG411XZZ3kM5VkAeBf';

    // 1. 创建合约实例
    echo "1. 创建合约实例:\n";
    echo "   方式1: \$tronWeb->contract(\$abi)->at(\$address)\n";
    echo "   方式2: \$tronWeb->contract(\$abi, \$address)\n";

    $contract = $tronWeb->contract()->at($usdtContractAddress);
    echo "   ✅ 合约实例创建成功\n";
    echo "   合约地址: " . $contract->getAddress() . "\n\n";

    // 2. 查询合约信息
    echo "2. 查询合约信息:\n";
    try {
        $contractInfo = $tronWeb->contract->getInfo($usdtContractAddress);
        echo "   ✅ 合约信息获取成功\n";
        echo "   合约名称: " . ($contractInfo['name'] ?? '未知') . "\n";
        echo "   合约字节码长度: " . strlen($contractInfo['bytecode'] ?? '') . "\n";
    } catch (TronException $e) {
        echo "   ❌ 合约信息查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 3. 合约只读方法调用
    echo "3. 合约只读方法调用（无需签名）:\n";
    echo "   用法: \$contract->methodName()\n";

    try {
        // 查询 USDT 余额（需要设置私钥后查询当前账户）
        // $balance = $contract->balanceOf($address);
        // echo "   余额: " . $tronWeb->toDecimal($balance) . " USDT\n";

        // 演示只读方法调用
        echo "   ✅ 只读方法调用示例（需私钥查询实际余额）\n";
        echo "   方法: balanceOf(address) - 查询账户余额\n";
        echo "   方法: symbol() - 查询代币符号\n";
        echo "   方法: name() - 查询代币名称\n";
        echo "   方法: decimals() - 查询小数位数\n";
        echo "   方法: totalSupply() - 查询总供应量\n\n";
    } catch (TronException $e) {
        echo "   ❌ 方法调用失败: " . $e->getMessage() . "\n\n";
    }

    // 4. 🚀 新功能：链式调用 - 合约写入方法（需要签名）
    echo "4. 🚀 链式调用 - 合约写入方法（需签名）:\n";
    echo "   用法: \$contract->method(\$param1, \$param2)->send([\$options])\n\n";

    echo "   📝 使用场景：代币转账、授权、合约交互等\n";
    echo "   ⚠️ 需要设置私钥: \$tronWeb->setPrivateKey('private-key')\n\n";

    echo "   示例代码:\n";
    echo "   // 转账 USDT\n";
    echo "   \$result = \$contract->transfer(\$toAddress, \$amount)->send([\n";
    echo "       'feeLimit' => 1000000  // 1 TRX\n";
    echo "   ]);\n";
    echo "   if (\$result['result']) {\n";
    echo "       echo '交易成功: ' . \$result['txid'];\n";
    echo "   }\n\n";

    // 5. 🔗 多种 options 传递方式
    echo "5. 🔗 多种 options 传递方式:\n\n";

    echo "   方式 A - 在 send() 中传递:\n";
    echo "   \$contract->transfer(\$to, \$amount)->send([\n";
    echo "       'feeLimit' => 1000000\n";
    echo "   ]);\n\n";

    echo "   方式 B - 在方法调用时传递:\n";
    echo "   \$contract->transfer(\$to, \$amount, [\n";
    echo "       'feeLimit' => 1000000,\n";
    echo "       'fromAddress' => \$myAddress\n";
    echo "   ])->send();\n\n";

    echo "   方式 C - 混合传递:\n";
    echo "   \$contract->transfer(\$to, \$amount, ['fromAddress' => \$addr])\n";
    echo "               ->send(['feeLimit' => 1000000]);\n\n";

    // 6. 实际交易示例
    echo "6. 📋 实际交易示例:\n";
    echo "   ⚠️ 以下代码需要有效的私钥才能运行\n\n";

    echo "   // USDT 转账示例\n";
    echo "   \$tronWeb->setPrivateKey('your-private-key');\n";
    echo "   \$tronWeb->setAddress('your-address');\n";
    echo "   \n";
    echo "   \$to = 'TRecipientAddress';\n";
    echo "   \$amount = '1000000'; // 1 USDT (最小单位)\n";
    echo "   \n";
    echo "   \$result = \$contract->transfer(\$to, \$amount)->send([\n";
    echo "       'feeLimit' => 1000000,\n";
    echo "       'fromAddress' => 'your-address'\n";
    echo "   ]);\n";
    echo "   \n";
    echo "   if (\$result['result'] ?? false) {\n";
    echo "       echo '✅ 转账成功！交易ID: ' . \$result['txid'];\n";
    echo "   } else {\n";
    echo "       echo '❌ 转账失败: ' . (\$result['message'] ?? '未知错误');\n";
    echo "   }\n\n";

    // 7. 签名但不广播
    echo "7. ✍️ 签名但不广播（高级用法）:\n";
    echo "   用法: \$contract->method(\$params)->sign()\n";
    echo "   \n";
    echo "   // 先签名，稍后广播\n";
    echo "   \$signedTx = \$contract->transfer(\$to, \$amount)->sign();\n";
    echo "   \n";
    echo "   // 稍后广播\n";
    echo "   \$result = \$tronWeb->trx->sendRawTransaction(\$signedTx);\n\n";

    // 8. 合约事件查询
    echo "8. 📊 合约事件查询:\n";
    echo "   用法: \$tronWeb->contract->getEvents(\$address, \$sinceTimestamp, \$eventName)\n\n";

    echo "   // 获取所有事件\n";
    echo "   \$events = \$tronWeb->contract->getEvents(\$usdtContractAddress);\n";
    echo "   \n";
    echo "   // 获取 Transfer 事件\n";
    echo "   \$events = \$tronWeb->contract->getEvents(\$usdtContractAddress, 0, 'Transfer');\n\n";

    // 9. 合约部署
    echo "9. 🚀 合约部署:\n";
    echo "   用法: \$tronWeb->contract->deploy(\$abi, \$bytecode, \$feeLimit, \$address, \$callValue)\n\n";

    echo "   \$result = \$tronWeb->contract->deploy(\n";
    echo "       \$abi,           // 合约 ABI\n";
    echo "       \$bytecode,      // 合约字节码\n";
    echo "       1000000000,      // feeLimit: 1000 TRX\n";
    echo "       \$deployerAddress,\n";
    echo "       0               // callValue\n";
    echo "   );\n\n";

    echo "=== Contract 模块示例完成 ===\n\n";

    // 方法总结
    echo "📋 Contract 模块主要方法:\n";
    echo "├── 创建合约实例\n";
    echo "│   └── \$tronWeb->contract(\$abi)->at(\$address)\n";
    echo "├── 只读查询\n";
    echo "│   └── \$contract->balanceOf(\$addr), symbol(), name(), decimals(), totalSupply()\n";
    echo "├── 🚀 链式调用（写入）\n";
    echo "│   └── \$contract->method(\$params)->send([\$options])\n";
    echo "├── ✍️ 签名（不广播）\n";
    echo "│   └── \$contract->method(\$params)->sign()\n";
    echo "├── 事件查询\n";
    echo "│   └── \$tronWeb->contract->getEvents(\$addr)\n";
    echo "├── 合约信息\n";
    echo "│   └── \$tronWeb->contract->getInfo(\$addr)\n";
    echo "└── 合约部署\n";
    echo "    └── \$tronWeb->contract->deploy(\$abi, \$bytecode, ...)\n\n";

    echo "💡 使用提示:\n";
    echo "• 查询操作不需要私钥\n";
    echo "• 🚀 写入操作需要私钥签名，使用 ->send() 自动签名广播\n";
    echo "• ✍️ 高级用法可使用 ->sign() 只签名不广播\n";
    echo "• getEvents 需要配置 eventServer\n";
    echo "• 支持多种 options 传递方式\n\n";

} catch (TronException $e) {
    echo "❌ Tron 异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}
?>