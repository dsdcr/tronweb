<?php
/**
 * TronWeb 基础使用示例
 * 展示如何初始化TronWeb实例并进行基本操作
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

echo "=== TronWeb 基础使用示例 ===\n\n";

try {
    // 1. 初始化TronWeb实例
    echo "1. 初始化TronWeb实例...\n";

    $tronWeb = new TronWeb([
        'fullNode' => new HttpProvider('https://api.trongrid.io'),
        'solidityNode' => new HttpProvider('https://api.trongrid.io'),
        'eventServer' => new HttpProvider('https://api.trongrid.io'),
        // 'privateKey' => '您的私钥', // 可选，可以在使用时设置
        // 'defaultAddress' => '您的地址' // 可选
    ]);

    echo "   ✅ TronWeb实例初始化成功\n\n";

    // 2. 基本功能演示
    echo "2. 基本功能演示:\n";

    // 获取当前区块信息
    echo "   - 获取当前区块信息:\n";
    $currentBlock = $tronWeb->trx->getCurrentBlock();
    echo "     区块高度: " . ($currentBlock['block_header']['raw_data']['number'] ?? 'N/A') . "\n";
    echo "     区块哈希: " . substr($currentBlock['blockID'] ?? '', 0, 16) . "...\n\n";

    // 地址转换演示
    echo "   - 地址转换演示:\n";
    $testAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';
    $hexAddress = $tronWeb->utils->addressToHex($testAddress);
    $base58Address = $tronWeb->utils->hexToAddress($hexAddress);

    echo "     原始地址: $testAddress\n";
    echo "     Hex地址: $hexAddress\n";
    echo "     转换回Base58: $base58Address\n";
    echo "     验证地址: " . ($tronWeb->utils->isValidTronAddress($testAddress) ? '有效' : '无效') . "\n\n";

    // 单位转换演示
    echo "   - 单位转换演示:\n";
    $trxAmount = 1.5;
    $sunAmount = $tronWeb->utils->toSun($trxAmount);
    $convertedBack = $tronWeb->utils->fromSun($sunAmount);

    echo "     TRX: $trxAmount\n";
    echo "     SUN: $sunAmount\n";
    echo "     转换回TRX: $convertedBack\n\n";

    // 3. 模块懒加载演示
    echo "3. 模块懒加载演示:\n";
    echo "   - Trx模块: " . get_class($tronWeb->trx) . "\n";
    echo "   - Account模块: " . get_class($tronWeb->account) . "\n";
    echo "   - Contract模块: " . get_class($tronWeb->contract) . "\n";
    echo "   - Token模块: " . get_class($tronWeb->token) . "\n";
    echo "   - Resource模块: " . get_class($tronWeb->resource) . "\n";
    echo "   - Network模块: " . get_class($tronWeb->network) . "\n";
    echo "   - Utils工具: " . get_class($tronWeb->utils) . "\n\n";

    // 4. 错误处理演示
    echo "4. 错误处理演示:\n";
    try {
        // 尝试查询不存在的地址余额
        $tronWeb->trx->getBalance('无效地址', true);
    } catch (TronException $e) {
        echo "   - 错误捕获: " . $e->getMessage() . "\n";
    }

    echo "\n=== 示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron异常: " . $e->getMessage() . "\n";
    echo "堆栈跟踪: " . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 5. 配置选项说明
echo "\n5. 配置选项说明:\n";
echo "   - fullNode: 必需，全节点提供者\n";
echo "   - solidityNode: 可选，固态节点提供者\n";
echo "   - eventServer: 可选，事件服务器提供者\n";
echo "   - signServer: 可选，签名服务器提供者\n";
echo "   - privateKey: 可选，用于交易签名的私钥\n";
echo "   - defaultAddress: 可选，默认操作地址\n\n";

echo "使用提示:\n";
echo "- 在生产环境中使用前，请在测试网络上充分测试\n";
echo("- 妥善保管私钥，不要硬编码在代码中\n");
echo("- 使用环境变量或安全的配置管理系统存储敏感信息\n");
?>