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
    // 初始化TronWeb实例（仅查询，不需要私钥）
    $tronWeb = new TronWeb([
        'fullNode' => new HttpProvider('https://api.trongrid.io'),
        'solidityNode' => new HttpProvider('https://api.trongrid.io')
    ]);

    // 1. 区块信息查询
    echo "1. 区块信息查询:\n";

    // 获取当前区块
    $currentBlock = $tronWeb->trx->getCurrentBlock();
    $blockNumber = $currentBlock['block_header']['raw_data']['number'] ?? 'N/A';
    echo "   当前区块高度: $blockNumber\n";

    // 获取区块范围
    $blocks = $tronWeb->trx->getBlockRange($blockNumber - 2, $blockNumber);
    echo "   获取区块范围: " . count($blocks) . " 个区块\n";

    // 获取最新区块
    $latestBlocks = $tronWeb->trx->getLatestBlocks(3);
    echo "   最新3个区块: " . count($latestBlocks) . " 个区块信息\n\n";

    // 2. 交易查询
    echo "2. 交易查询:\n";

    // 获取交易数量
    $transactionCount = $tronWeb->trx->getTransactionCount();
    echo "   总交易数量: " . ($transactionCount['num'] ?? 'N/A') . "\n";

    // 获取区块交易数量
    if ($blockNumber !== 'N/A') {
        $blockTxCount = $tronWeb->trx->getBlockTransactionCount($blockNumber);
        echo "   当前区块交易数: " . ($blockTxCount['count'] ?? 'N/A') . "\n";
    }
    echo "\n";

    // 3. 余额查询
    echo "3. 余额查询:\n";
    $testAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';

    try {
        $balance = $tronWeb->trx->getBalance($testAddress, true);
        echo "   地址余额: " . ($balance['balance'] ?? '0') . " TRX\n";

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
        $accountInfo = $tronWeb->trx->getAccountInfo($testAddress);
        echo "   账户信息获取: " . (!empty($accountInfo) ? '成功' : '空账户') . "\n";

        if (!empty($accountInfo)) {
            echo "   账户创建时间: " . ($accountInfo['create_time'] ?? '未知') . "\n";
            echo "   账户类型: " . ($accountInfo['type'] ?? '未知') . "\n";
        }
    } catch (TronException $e) {
        echo "   账户信息查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. 网络信息查询
    echo "5. 网络信息查询:\n";

    // 获取链参数
    $chainParams = $tronWeb->trx->getChainParameters();
    echo "   链参数数量: " . count($chainParams) . "\n";

    // 获取节点信息
    try {
        $nodeInfo = $tronWeb->trx->getNodeInfo();
        echo "   节点信息: " . (!empty($nodeInfo) ? '获取成功' : '获取失败') . "\n";
    } catch (TronException $e) {
        echo "   节点信息查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 6. 消息签名和验证（演示功能）
    echo "6. 消息签名和验证:\n";

    $message = "Hello Tron Network!";
    $privateKey = '演示用私钥'; // 实际使用时需要真实私钥

    try {
        // 签名消息（需要真实私钥）
        // $signature = $tronWeb->trx->signMessage($message, $privateKey);
        echo "   消息签名: 需要真实私钥\n";

        // 验证消息签名
        // $isValid = $tronWeb->trx->verifyMessage($message, $signature, $testAddress);
        echo "   签名验证: 需要真实签名数据\n";

    } catch (TronException $e) {
        echo "   签名功能: " . $e->getMessage() . "\n";
    }
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

    // 8. 奖励信息查询
    echo "8. 奖励信息查询:\n";
    try {
        $rewardInfo = $tronWeb->trx->getRewardInfo($testAddress);
        echo "   奖励信息: " . (!empty($rewardInfo) ? '获取成功' : '无奖励信息') . "\n";
    } catch (TronException $e) {
        echo "   奖励信息查询失败: " . $e->getMessage() . "\n";
    }

    echo "\n=== Trx 模块示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Trx 模块主要方法:\n";
echo "- getBalance(): 查询余额\n";
echo "- getCurrentBlock(): 获取当前区块\n";
echo "- getBlockRange(): 获取区块范围\n";
echo "- getTransactionCount(): 获取交易数量\n";
echo "- getAccountInfo(): 获取账户信息\n";
echo "- getChainParameters(): 获取链参数\n";
echo "- signMessage()/verifyMessage(): 消息签名验证\n";
echo "- getDelegatedResource(): 资源委托查询\n";
echo "- getRewardInfo(): 奖励信息查询\n";
echo "- 共40+个交易相关方法\n";

echo "\n💡 使用提示:\n";
echo "- 查询操作不需要私钥\n";
echo("- 交易签名和发送需要设置私钥\n");
echo "- 区块查询支持多种参数格式\n";
echo "- 消息签名用于身份验证场景\n";

echo "\n⚠️  注意:\n";
echo "- 本示例仅展示查询功能\n";
echo("- 实际转账操作需要真实私钥和足够余额\n");
echo "- 生产环境请使用安全的方式管理私钥\n";
?>