<?php
/**
 * TronWeb 基础使用示例
 * 展示如何初始化 TronWeb 实例并进行基本操作
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;

echo "=== TronWeb 基础使用示例 ===\n\n";

try {
    // 初始化 TronWeb 实例
    $tronWeb = new TronWeb(
        new HttpProvider('https://api.trongrid.io')
    );

    echo "   ✅ TronWeb 实例初始化成功\n\n";

    // 2. 基本功能演示
    echo "2. 基本功能演示:\n";

    // 获取当前区块信息
    echo "   - 获取当前区块信息:\n";
    $currentBlock = $tronWeb->trx->getCurrentBlock();
    echo "     区块高度: " . ($currentBlock['block_header']['raw_data']['number'] ?? 'N/A') . "\n";
    echo "     区块哈希: " . substr($currentBlock['blockID'] ?? '', 0, 16) . "...\n";

    // 地址转换演示
    echo "   - 地址转换演示:\n";
    $testAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';
    $hexAddress = $tronWeb->toHex($testAddress);
    $base58Address = $tronWeb->fromHex($hexAddress);

    echo "     原始地址: $testAddress\n";
    echo "     Hex 地址: $hexAddress\n";
    echo "     转换回 Base58: $base58Address\n";
    echo "     验证地址: " . ($tronWeb->utils->isAddress($testAddress) ? '有效' : '无效') . "\n\n";

    // 单位转换演示
    echo "   - 单位转换演示:\n";
    $trxAmount = 1.5;
    $sunAmount = $tronWeb->utils->toSun($trxAmount);
    $convertedBack = $tronWeb->utils->fromSun($sunAmount);

    echo "     TRX: $trxAmount\n";
    echo "     SUN: $sunAmount\n";
    echo "     转换回 TRX: $convertedBack\n";

    // 3. 模块懒加载演示
    echo "3. 模块懒加载演示:\n";
    echo "   - Trx 模块: " . get_class($tronWeb->trx) . "\n";
    echo "   - Account 模块: " . get_class($tronWeb->account) . "\n";
    echo "   - Contract 模块: " . get_class($tronWeb->contract) . "\n";
    echo "   - Token 模块: " . get_class($tronWeb->token) . "\n";
    echo "   - Resource 模块: " . get_class($tronWeb->resource) . "\n";
    echo "   - Network 模块: " . get_class($tronWeb->network) . "\n";
    echo "   - Utils 工具: " . get_class($tronWeb->utils) . "\n\n";

    // 4. 错误处理演示
    echo "4. 错误处理演示:\n";
    try {
        // 尝试查询不存在的地址余额
        $tronWeb->trx->getBalance('invalid_address_123');
    } catch (TronException $e) {
        echo "   - 错误捕获: " . $e->getMessage() . "\n";
    }

    echo "\n=== 示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron 异常: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "堆栈跟踪: " . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 方法总结
echo "\n?? 基础配置选项:\n";
echo "- fullNode: 必需，全节点提供者（如 https://api.trongrid.io）\n";
echo "- solidityNode: 可选，固态节点提供者\n";
echo "- eventServer: 可选，事件服务器提供者\n";
echo "- signServer: 可选，签名服务器提供者\n";
echo "- privateKey: 可选，用于交易签名的私钥\n";
echo "- defaultAddress: 可选，默认操作地址\n";

echo "\n💡 使用提示:\n";
echo("- 在生产环境中使用前，请在测试网络上充分测试\n");
echo("- 善善保管私钥，不要硬编码在代码中\n");
echo("- 使用环境变量或安全的配置管理系统存储敏感信息\n");
echo("- 建议使用 HttpProvider::mainnet() 或 testnet() 快捷方法\n");

echo "\n🔗 测试网络配置:\n";
echo("- 主网: HttpProvider::mainnet()\n");
echo("- 测试网: HttpProvider::testnet()\n");
echo("- Nile 测试网: HttpProvider::nile()\n");

echo "\n⚠️ 注意:\n";
echo("- 本示例主要展示查询和工具功能\n");
echo("- 实际转账操作需要真实私钥和足够余额\n");
echo("- 生产环境请验证配置的正确性\n");
echo("- 建议在生产环境中使用重试机制和超时设置\n");

echo "\n📚 相关文档:\n";
echo("- TYPE_SYSTEM_INTEGRATION.md: 完整类型系统文档\n");
echo("- account_examples.php: 账户管理示例\n");
echo("- trx_examples.php: 交易操作示例\n");
echo("- contract_examples.php: 智能合约示例\n");
echo("- token_examples.php: 代币管理示例\n");
echo("- resource_examples.php: 资源管理示例\n");
echo("- network_examples.php: 网络信息示例\n");
echo("- utils_examples.php: 工具函数示例\n");
?>
