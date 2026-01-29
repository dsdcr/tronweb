<?php
/**
 * Contract 模块使用示例
 * 展示智能合约相关功能：TRC20代币交互、合约查询等
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

echo "=== Contract 模块使用示例 ===\n\n";

try {
    // 初始化TronWeb实例
    $tronWeb = new TronWeb([
        'fullNode' => new HttpProvider('https://api.trongrid.io')
    ]);

    // 1. TRC20代币合约交互
    echo "1. TRC20代币合约交互:\n";

    // USDT TRC20合约地址
    $usdtContractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';

    try {
        // 创建TRC20合约实例
        $trc20 = $tronWeb->contract->trc20($usdtContractAddress);
        echo "   TRC20合约实例创建成功\n";

        // 获取代币信息
        echo "   代币名称: " . ($trc20->name() ?? '获取中...') . "\n";
        echo "   代币符号: " . ($trc20->symbol() ?? '获取中...') . "\n";
        echo "   小数位数: " . ($trc20->decimals() ?? '获取中...') . "\n";

        // 获取总供应量
        $totalSupply = $trc20->totalSupply();
        echo "   总供应量: " . ($totalSupply ?: '获取中...') . "\n\n";

    } catch (TronException $e) {
        echo "   TRC20合约操作失败: " . $e->getMessage() . "\n\n";
    }

    // 2. 合约信息查询
    echo "2. 合约信息查询:\n";

    try {
        $contractInfo = $tronWeb->contract->getInfo($usdtContractAddress);
        echo "   合约信息获取: " . (!empty($contractInfo) ? '成功' : '失败') . "\n";

        if (!empty($contractInfo)) {
            echo "   合约创建时间: " . ($contractInfo['create_time'] ?? '未知') . "\n";
            echo "   合约状态: " . ($contractInfo['contract_state'] ?? '未知') . "\n";
        }
    } catch (TronException $e) {
        echo "   合约信息查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 3. 合约事件查询
    echo "3. 合约事件查询:\n";

    try {
        // 获取最近的事件
        $events = $tronWeb->contract->getEvents($usdtContractAddress, [
            'limit' => 3,
            'orderBy' => 'block_timestamp,desc'
        ]);

        echo "   事件数量: " . count($events) . "\n";
        if (!empty($events)) {
            echo "   最新事件类型: " . ($events[0]['event_name'] ?? '未知') . "\n";
        }
    } catch (TronException $e) {
        echo "   事件查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 4. 按交易查询合约事件
    echo "4. 按交易查询合约事件:\n";

    // 这里需要一个真实的交易ID来演示，使用一个测试交易ID
    $testTransactionId = 'abc123def456abc123def456abc123def456abc123def456abc123def456ab';

    try {
        $transactionEvents = $tronWeb->contract->getEventsByTransaction($testTransactionId);
        echo "   交易事件查询: 需要真实交易ID\n";
    } catch (TronException $e) {
        echo "   交易事件查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. TRC20余额查询演示
    echo "5. TRC20余额查询演示:\n";

    $testAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';

    try {
        if (isset($trc20)) {
            $tokenBalance = $trc20->balanceOf($testAddress, true);
            echo "   TRC20余额: " . ($tokenBalance ?: '0') . " 代币\n";
        }
    } catch (TronException $e) {
        echo "   TRC20余额查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 6. 合约部署功能说明
    echo "6. 合约部署功能说明:\n";

    try {
        // 合约部署需要字节码和ABI
        echo "   合约部署需要:\n";
        echo "   - 合约字节码\n";
        echo "   - ABI定义\n";
        echo "   - 足够的带宽和能量\n";
        echo "   - 签名私钥\n";

        // 演示部署方法（需要真实参数）
        // $deployResult = $tronWeb->contract->deploy($bytecode, $abi, $options);
        echo "   部署方法: contract->deploy(bytecode, abi, options)\n";

    } catch (TronException $e) {
        echo "   合约部署说明: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 7. 合约调用功能说明
    echo "7. 合约调用功能说明:\n";

    echo "   TRC20合约提供的方法:\n";
    echo "   - name(): 获取代币名称\n";
    echo "   - symbol(): 获取代币符号\n";
    echo "   - decimals(): 获取小数位数\n";
    echo "   - totalSupply(): 获取总供应量\n";
    echo "   - balanceOf(): 查询余额\n";
    echo "   - transfer(): 转账代币\n";
    echo "   - getTransactions(): 获取交易记录\n";
    echo "   - setFeeLimit(): 设置费用限制\n\n";

    echo "=== Contract 模块示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Contract 模块主要方法:\n";
echo "- trc20(): 创建TRC20合约实例\n";
echo "- getInfo(): 获取合约信息\n";
echo "- getEvents(): 查询合约事件\n";
echo "- getEventsByTransaction(): 按交易查询事件\n";
echo "- deploy(): 部署新合约\n";
echo "- 支持所有TRC20标准方法\n";

echo "\n💡 使用提示:\n";
echo "- TRC20合约交互需要合约地址\n";
echo("- 查询操作不需要私钥\n");
echo "- 转账和部署需要私钥签名\n";
echo "- 注意gas费用限制设置\n";

echo "\n🔗 常用TRC20合约地址:\n";
echo "- USDT: TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t\n";
echo("- USDC: TEkxiTehnzSmSe2XqrBj4w32RUN966rdz8\n");
echo "- BUSD: TMwFHYXLJaRUPeW6421aqXL4ZEzPRFGkGT\n";
echo "- JST: TCsgEptjhu3wQ6R6e8ZMi6m3KoBau6qh5L\n";

echo "\n⚠️  注意:\n";
echo "- 本示例主要展示查询功能\n";
echo("- 实际合约交互需要真实私钥\n");
echo "- 生产环境请充分测试合约调用\n";
?>