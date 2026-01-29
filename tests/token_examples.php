<?php
/**
 * Token 模块使用示例
 * 展示代币相关功能：TRC10代币创建、转账、查询等
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

echo "=== Token 模块使用示例 ===\n\n";

try {
    // 初始化TronWeb实例（仅查询演示）
    $tronWeb = new TronWeb([
        'fullNode' => new HttpProvider('https://api.trongrid.io')
    ]);

    // 1. 代币列表查询
    echo "1. 代币列表查询:\n";

    try {
        $tokenList = $tronWeb->token->list();
        echo "   代币数量: " . count($tokenList) . "\n";

        if (!empty($tokenList)) {
            $sampleToken = $tokenList[0];
            echo "   示例代币: " . ($sampleToken['name'] ?? '未知') . " (" . ($sampleToken['abbr'] ?? '未知') . ")\n";
            echo "   代币ID: " . ($sampleToken['id'] ?? '未知') . "\n";
        }
    } catch (TronException $e) {
        echo "   代币列表查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 2. 按名称查询代币
    echo "2. 按名称查询代币:\n";

    try {
        $tokensByName = $tronWeb->token->getTokenListByName(['USDT', 'TRX']);
        echo "   按名称查询结果数量: " . count($tokensByName) . "\n";

        foreach ($tokensByName as $token) {
            echo "   - " . ($token['name'] ?? '未知') . " (" . ($token['abbr'] ?? '未知') . ")\n";
        }
    } catch (TronException $e) {
        echo "   按名称查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 3. 按ID查询代币
    echo "3. 按ID查询代币:\n";

    try {
        // 使用一个已知的代币ID进行查询
        $tokenById = $tronWeb->token->getById('1000000'); // TRX代币ID
        if ($tokenById) {
            echo "   代币名称: " . ($tokenById['name'] ?? '未知') . "\n";
            echo "   代币缩写: " . ($tokenById['abbr'] ?? '未知') . "\n";
            echo "   总供应量: " . ($tokenById['totalSupply'] ?? '未知') . "\n";
        } else {
            echo "   未找到该ID的代币\n";
        }
    } catch (TronException $e) {
        echo "   按ID查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 4. 地址发行的代币查询
    echo "4. 地址发行的代币查询:\n";

    $testAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';

    try {
        $issuedTokens = $tronWeb->token->getIssuedByAddress($testAddress);
        echo "   发行的代币数量: " . count($issuedTokens) . "\n";

        if (!empty($issuedTokens)) {
            foreach ($issuedTokens as $token) {
                echo "   - " . ($token['name'] ?? '未知') . " (ID: " . ($token['id'] ?? '未知') . ")\n";
            }
        }
    } catch (TronException $e) {
        echo "   发行代币查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. 代币转账功能说明
    echo "5. 代币转账功能说明:\n";

    echo "   代币转账方法:\n";
    echo "   - send(): 发送代币\n";
    echo "   - sendToken(): 发送代币（别名）\n";
    echo "   - sendTransaction(): 发送交易\n\n";

    echo "   参数说明:\n";
    echo "   - 接收地址\n";
    echo "   - 转账金额\n";
    echo "   - 代币ID\n";
    echo "   - 可选参数：私钥、费用限制等\n\n";

    // 演示调用格式（需要真实参数）
    echo "   使用示例:\n";
    echo "   // 需要设置私钥\n";
    echo "   \$tronWeb->setPrivateKey('your_private_key');\n";
    echo "   \$result = \$tronWeb->token->send('接收地址', 100, '代币ID');\n\n";

    // 6. 代币创建功能说明
    echo "6. 代币创建功能说明:\n";

    echo "   创建TRC10代币参数:\n";
    echo "   - name: 代币名称\n";
    echo "   - abbr: 代币缩写\n";
    echo "   - total_supply: 总供应量\n";
    echo "   - trx_num: TRX兑换比例\n";
    echo "   - num: 代币数量\n";
    echo "   - start_time: 开始时间\n";
    echo "   - end_time: 结束时间\n";
    echo "   - description: 描述\n";
    echo "   - url: 官方网站\n";
    echo "   - free_bandwidth: 免费带宽\n";
    echo "   - free_bandwidth_limit: 免费带宽限制\n\n";

    echo "   创建示例:\n";
    echo "   \$result = \$tronWeb->token->createToken([\n";
    echo "       'name' => 'MyToken',\n";
    echo "       'abbr' => 'MTK',\n";
    echo "       'total_supply' => 1000000,\n";
    echo "       'trx_num' => 1,\n";
    echo "       'num' => 1\n";
    echo "   ]);\n\n";

    // 7. 代币购买功能说明
    echo "7. 代币购买功能说明:\n";

    echo "   购买代币参数:\n";
    echo "   - token_id: 代币ID\n";
    echo "   - token_amount: 购买数量\n";
    echo "   - buyer: 购买者地址\n\n";

    echo "   购买示例:\n";
    echo "   \$result = \$tronWeb->token->purchaseToken('代币ID', 100);\n\n";

    // 8. 代币更新功能说明
    echo "8. 代币更新功能说明:\n";

    echo "   更新代币参数:\n";
    echo "   - token_id: 代币ID\n";
    echo "   - description: 新的描述\n";
    echo "   - url: 新的网址\n\n";

    echo "   更新示例:\n";
    echo "   \$result = \$tronWeb->token->updateToken('代币ID', [\n";
    echo "       'description' => '新的代币描述',\n";
    echo "       'url' => 'https://new-website.com'\n";
    echo "   ]);\n";

    echo "\n=== Token 模块示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Token 模块主要方法:\n";
echo "- list(): 获取代币列表\n";
echo "- getTokenListByName(): 按名称查询代币\n";
echo "- getById(): 按ID查询代币\n";
echo "- getIssuedByAddress(): 查询地址发行的代币\n";
echo "- send(): 发送代币\n";
echo "- createToken(): 创建TRC10代币\n";
echo "- purchaseToken(): 购买代币\n";
echo "- updateToken(): 更新代币信息\n";
echo "- 共13+个代币相关方法\n";

echo "\n💡 使用提示:\n";
echo "- TRC10是TRON原生代币标准\n";
echo("- TRC20是智能合约代币标准\n");
echo "- 创建代币需要消耗资源和TRX\n";
echo "- 代币操作需要私钥签名\n";

echo "\n🔍 TRC10 vs TRC20:\n";
echo "- TRC10: 原生代币，创建简单，功能基础\n";
echo("- TRC20: 智能合约代币，功能丰富，支持复杂逻辑\n");
echo "- USDT在TRON上使用TRC20标准\n";

echo "\n⚠️  注意:\n";
echo "- 本示例主要展示查询功能\n";
echo("- 实际代币操作需要真实私钥\n");
echo("- 创建代币前请充分了解费用和规则\n");
?>