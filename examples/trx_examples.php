<?php
/**
 * Trx 模块使用示例
 * 展示交易相关功能：转账、查询、签名、区块操作等
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

echo "=== Trx 模块使用示例 ===\n\n";

try {
    // 初始化 TronWeb 实例
    $tronWeb = new TronWeb(
        new HttpProvider('https://api.trongrid.io')
    );

    // 1. 区块信息查询
    echo "1. 区块信息查询:\n";

    // 获取当前区块
    $currentBlock = $tronWeb->trx->getCurrentBlock();
    $blockNumber = $currentBlock['block_header']['raw_data']['number'] ?? 'N/A';
    echo "   当前区块高度: $blockNumber\n";
    echo "   区块哈希: " . substr($currentBlock['blockID'] ?? '', 0, 16) . "...\n";

    // 获取区块范围
    $blocks = $tronWeb->trx->getBlockRange($blockNumber - 2, $blockNumber);
    echo "   获取区块范围: " . count($blocks) . " 个区块\n";

    // 获取最新区块
    $latestBlocks = $tronWeb->trx->getLatestBlocks(3);
    echo "   最新3个区块: " . count($latestBlocks) . " 个区块信息\n";
    echo "\n";

    // 2. 交易查询
    echo "2. 交易查询:\n";

    // 获取交易数量
    try {
        $transactionCount = $tronWeb->request('wallet/gettotaltransaction', [], 'post');
        echo "   总交易数量: " . ($transactionCount['num'] ?? 'N/A') . "\n";
    } catch (TronException $e) {
        echo "   交易总数查询失败\n";
    }

    // 获取区块交易数量
    if ($blockNumber !== 'N/A') {
        try {
            $blockTxCount = $tronWeb->trx->getBlockTransactionCount($blockNumber);
            echo "   当前区块交易数: " . (is_count($blockTxCount) ? $blockTxCount : 'N/A') . "\n";
        } catch (TronException $e) {
            echo "   区块交易数查询失败\n";
        }
    }
    echo "\n";

    // 3. 余额查询
    echo "3. 余额查询:\n";
    $testAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';

    try {
        // getBalance() 返回 float 类型，不是数组
        $balance = $tronWeb->trx->getBalance($testAddress, true);
        echo "   地址余额: " . ($balance ?? '0') . " TRX\n";

        // 获取账户资源信息
        $resources = $tronWeb->trx->getAccountResources($testAddress);
        echo "   账户资源信息: " . (!empty($resources) ? '获取成功' : '无资源信息') . "\n";
    } catch (TronException $e) {
        echo "   余额查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 4. 账户信息查询
    echo "4. 账户信息查询:\n";

    try {
        $accountInfo = $tronWeb->trx->getAccount($testAddress);
        echo "   账户信息获取: " . (empty($accountInfo) ? '空' : '成功') . "\n";

        if (!empty($accountInfo)) {
            echo "   账户类型: " . ($accountInfo['type'] ?? '未知') . "\n";
            echo "   账户创建时间: " . ($accountInfo['create_time'] ?? '未知') . "\n";
            echo "   账户名称: " . ($accountInfo['account_name'] ?? '未设置') . "\n";
            echo "   余额: " . ($accountInfo['balance'] ?? '0') . " SUN\n";
            echo "   带宽使用: " . ($accountInfo['net_usage'] ?? '0') . "\n";
            echo "   能量使用: " . ($accountInfo['energy_usage'] ?? '0') . "\n";
        }
    } catch (TronException $e) {
        echo "   账户信息查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. 网络信息查询
    echo "5. 网络信息查询:\n";

    // 获取链参数
    try {
        $chainParams = $tronWeb->trx->getChainParameters();
        echo "   链参数数量: " . count($chainParams['chain_parameter'] ?? []) . "\n";
    } catch (TronException $e) {
        echo "   链参数查询失败\n";
    }

    // 获取节点信息
    try {
        $nodeInfo = $tronWeb->trx->getNodeInfo();
        echo "   节点信息: " . (!empty($nodeInfo) ? '获取成功' : '获取失败') . "\n";
    } catch (TronException $e) {
        echo "   节点信息查询失败\n";
    }
    echo "\n";

    // 6. 消息签名和验证（演示功能）
    echo "6. 消息签名和验证:\n";

    $message = "Hello Tron Network!";
    $privateKey = '演示用私钥'; // 实际使用时需要真实私钥

    echo "   消息签名需要真实私钥\n";
    echo "   消息: $message\n";
    echo "   签名方法: signMessage(\$message, \$privateKey)\n";
    echo "   验证方法: verifyMessage(\$message, \$signature, \$address)\n";
    echo "\n";

    // 7. 资源委托查询
    echo "7. 资源委托查询:\n";

    try {
        $delegatedResource = $tronWeb->trx->getDelegatedResource($testAddress, $testAddress);
        echo "   资源委托信息: " . (!empty($delegatedResource) ? '存在' : '无委托') . "\n";
    } catch (TronException $e) {
        echo "   资源委托查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    echo "=== Trx 模块示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron 异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Trx 模块主要方法:\n";
echo "- getTransactionBuilder(): 获取 TransactionBuilder 实例\n";
echo "- getBalance(\$address, \$fromSun): 查询余额（返回 float 类型）\n";
echo "- send(\$to, \$amount, \$options): 发送 TRX 交易\n";
echo "- signTransaction(\$transaction): 对交易进行数字签名\n";
echo "- sendRawTransaction(\$signedTransaction): 广播已签名的交易\n";
echo "- getCurrentBlock(): 获取当前最新区块\n";
echo "- getBlock(\$block): 获取指定区块\n";
echo "- getBlockByHash(\$blockHash): 通过区块哈希获取区块\n";
echo "- getBlockByNumber(\$blockID): 通过区块号获取区块\n";
echo "- getBlockRange(\$start, \$end): 获取区块范围\n";
echo "- getLatestBlocks(\$limit): 获取最新区块\n";
echo "- getTransaction(\$transactionID): 通过交易 ID 获取交易详情\n";
echo "- getTransactionInfo(\$transactionID): 获取交易的执行信息\n";
echo "- getConfirmedTransaction(\$transactionID): 获取已确认的交易信息\n";
echo "- getAccount(\$address): 查询账户信息（返回数组）\n";
echo "- getAccountResources(\$address): 查询账户资源信息\n";
echo "- getChainParameters(): 获取链参数\n";
echo "- getNodeInfo(): 获取节点信息\n";
echo "- verifyMessage(\$message, \$signature, \$address): 验证消息签名\n";
echo "- signMessage(\$message, \$privateKey): 签名消息\n";
echo "- getBandwidth(\$address): 获取带宽信息\n";
echo "- getDelegatedResource(\$fromAddress, \$toAddress): 获取委托资源信息\n";
echo "- sendToMultiple(\$recipients, \$from, \$validate): 一次性发送 TRX 给多个接收者\n";
echo "- 共 40+ 个交易相关方法\n";

echo "\n💡 使用提示:\n";
echo "- 查询操作不需要私钥\n";
echo("- 交易签名和发送需要设置私钥");
echo("- 区块查询支持多种参数格式");
echo("- 消息签名用于身份验证场景");
echo("- 带宽信息用于跟踪交易资源消耗");
echo("- getBalance() 返回 float 类型，不是数组");
echo("- getAccount() 返回数组，包含完整账户信息");

echo "\n⚠️ 注意:\n";
echo("- 本示例仅展示查询功能\n");
echo("- 实际转账操作需要真实私钥和足够余额\n");
echo("- 生产环境请使用安全的方式管理私钥");
echo("- 消息签名验证需要正确的签名格式");
echo("- 区块查询可能需要等待区块确认");
?>
