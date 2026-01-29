<?php
/**
 * Resource 模块使用示例
 * 展示资源管理功能：带宽能量冻结、解冻、资源委托等
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

echo "=== Resource 模块使用示例 ===\n\n";

try {
    // 初始化TronWeb实例（仅查询演示）
    $tronWeb = new TronWeb([
        'fullNode' => new HttpProvider('https://api.trongrid.io')
    ]);

    // 1. 资源查询基础功能
    echo "1. 资源查询基础功能:\n";

    $testAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';

    try {
        // 获取资源信息
        $resources = $tronWeb->resource->getResources($testAddress);
        echo "   资源信息获取: " . (!empty($resources) ? '成功' : '无资源信息') . "\n";

        if (!empty($resources)) {
            echo "   带宽信息: " . ($resources['free_net_used'] ?? '0') . "/" . ($resources['free_net_limit'] ?? '0') . " 已使用\n";
            echo "   能量信息: " . ($resources['EnergyUsed'] ?? '0') . "/" . ($resources['EnergyLimit'] ?? '0') . " 已使用\n";
        }

        // 获取冻结余额
        $frozenBalance = $tronWeb->resource->getFrozenBalance($testAddress);
        echo "   冻结余额: " . ($frozenBalance['frozen_balance'] ?? '0') . " TRX\n";

    } catch (TronException $e) {
        echo "   资源查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 2. V2资源委托系统
    echo "2. V2资源委托系统:\n";

    try {
        // V2委托资源查询
        $delegatedResourceV2 = $tronWeb->resource->getDelegatedResourceV2(
            $testAddress,
            $testAddress,
            ['confirmed' => true]
        );
        echo "   V2委托资源查询: " . (!empty($delegatedResourceV2) ? '成功' : '无委托') . "\n";

        // 委托账户索引查询
        $accountIndex = $tronWeb->resource->getDelegatedResourceAccountIndexV2(
            $testAddress,
            ['confirmed' => true]
        );
        echo "   委托账户索引: " . (!empty($accountIndex) ? '获取成功' : '无索引信息') . "\n";

        // 最大可委托数量查询
        $maxDelegatedSize = $tronWeb->resource->getCanDelegatedMaxSize(
            $testAddress,
            'BANDWIDTH',
            ['confirmed' => true]
        );
        echo "   最大可委托带宽: " . ($maxDelegatedSize ?? '0') . "\n";

    } catch (TronException $e) {
        echo "   V2资源查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 3. 解冻相关功能
    echo "3. 解冻相关功能:\n";

    try {
        // 可用解冻次数查询
        $unfreezeCount = $tronWeb->resource->getAvailableUnfreezeCount(
            $testAddress,
            ['confirmed' => true]
        );
        echo "   可用解冻次数: " . ($unfreezeCount ?? '0') . "\n";

        // 可提取解冻金额查询
        $withdrawAmount = $tronWeb->resource->getCanWithdrawUnfreezeAmount(
            $testAddress,
            time() * 1000, // 当前时间戳（毫秒）
            ['confirmed' => true]
        );
        echo "   可提取解冻金额: " . ($withdrawAmount ?? '0') . " SUN\n";

    } catch (TronException $e) {
        echo "   解冻信息查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 4. 资源价格查询
    echo "4. 资源价格查询:\n";

    try {
        // 带宽价格查询
        $bandwidthPrices = $tronWeb->resource->getBandwidthPrices();
        echo "   带宽价格查询: " . (!empty($bandwidthPrices) ? '成功' : '失败') . "\n";

        // 能量价格查询
        $energyPrices = $tronWeb->resource->getEnergyPrices();
        echo "   能量价格查询: " . (!empty($energyPrices) ? '成功' : '失败') . "\n";

    } catch (TronException $e) {
        echo "   资源价格查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. 冻结和解冻功能说明
    echo "5. 冻结和解冻功能说明:\n";

    echo "   冻结资源参数:\n";
    echo "   - amount: 冻结数量（TRX）\n";
    echo "   - duration: 冻结时长（天）\n";
    echo "   - resource_type: 资源类型（BANDWIDTH/ENERGY）\n";
    echo "   - receiver_address: 接收地址（可选，用于委托）\n\n";

    echo "   冻结示例:\n";
    echo "   // 需要设置私钥\n";
    echo "   \$tronWeb->setPrivateKey('your_private_key');\n";
    echo "   \$result = \$tronWeb->resource->freeze(100, 3, 'BANDWIDTH');\n";
    echo "   // 或者使用freezeBalance别名\n";
    echo "   \$result = \$tronWeb->resource->freezeBalance(100, 3, 'BANDWIDTH');\n\n";

    echo "   解冻示例:\n";
    echo "   \$result = \$tronWeb->resource->unfreeze('BANDWIDTH');\n";
    echo "   // 或者使用unfreezeBalance别名\n";
    echo "   \$result = \$tronWeb->resource->unfreezeBalance('BANDWIDTH');\n\n";

    // 6. 奖励提取功能说明
    echo "6. 奖励提取功能说明:\n";

    echo "   奖励提取方法:\n";
    echo "   - withdrawRewards(): 提取奖励\n";
    echo "   - withdrawBlockRewards(): 提取区块奖励\n\n";

    echo "   使用示例:\n";
    echo "   \$result = \$tronWeb->resource->withdrawRewards();\n";
    echo "   \$result = \$tronWeb->resource->withdrawBlockRewards();\n\n";

    // 7. 资源类型说明
    echo "7. 资源类型说明:\n";

    echo "   BANDWIDTH（带宽）:\n";
    echo "   - 用于普通交易传输\n";
    echo "   - 每笔交易消耗带宽\n";
    echo "   - 可以通过冻结TRX获取\n\n";

    echo "   ENERGY（能量）:\n";
    echo "   - 用于智能合约执行\n";
    echo "   - 合约调用消耗能量\n";
    echo "   - 可以通过冻结TRX获取\n\n";

    echo "   资源委托:\n";
    echo "   - 可以将自己的资源委托给其他地址使用\n";
    echo "   - 支持带宽和能量委托\n";
    echo "   - V2系统提供更精细的控制\n";

    echo "\n=== Resource 模块示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Resource 模块主要方法:\n";
echo "- getResources(): 获取资源信息\n";
echo "- getFrozenBalance(): 获取冻结余额\n";
echo "- freeze()/freezeBalance(): 冻结资源\n";
echo "- unfreeze()/unfreezeBalance(): 解冻资源\n";
echo "- getDelegatedResourceV2(): V2委托资源查询\n";
echo "- getCanDelegatedMaxSize(): 最大可委托数量\n";
echo "- getAvailableUnfreezeCount(): 可用解冻次数\n";
echo "- getBandwidthPrices()/getEnergyPrices(): 资源价格查询\n";
echo "- 共15+个资源相关方法\n";

echo "\n?? 使用提示:\n";
echo "- 冻结资源可以获得带宽或能量\n";
echo("- 资源可以委托给其他地址使用\n");
echo("- V2系统提供更先进的资源管理\n");
echo "- 解冻需要等待冻结期满\n";

echo "\n💰 资源经济:\n";
echo "- 带宽: 用于交易传输，相对便宜\n";
echo("- 能量: 用于合约执行，相对昂贵\n");
echo("- 可以通过市场机制交易资源\n");

echo "\n⚠️  注意:\n";
echo "- 本示例主要展示查询功能\n";
echo("- 实际冻结操作需要真实私钥\n");
echo("- 冻结前请了解解冻规则和时间\n");
?>