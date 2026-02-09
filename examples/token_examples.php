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
    // 初始化 TronWeb 实例
    $tronWeb = new TronWeb(
        new HttpProvider('https://api.trongrid.io')
    );

    // 1. 代币列表查询
    echo "1. 代币列表查询:\n";

    try {
        // 获取所有代币（有限制）
        $tokenList = $tronWeb->token->list(20, 0);
        echo "   代币数量（前20个）: " . count($tokenList) . "\n";

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
        // 按名称查询单个代币（使用 Token 模块的方法）
        $tokenByName = $tronWeb->token->getFromName('USDT');
        echo "   代币名称: " . ($tokenByName['name'] ?? '未知') . "\n";
        echo "   代币缩写: " . ($tokenByName['abbr'] ?? '未知') . "\n";
        echo "   总供应量: " . ($tokenByName['total_supply'] ?? '未知') . "\n";

        // 批量查询（使用 Token 模块的方法）
        $tokensByName = $tronWeb->token->getTokenListByName(['USDT', 'TRX', 'USDC']);
        echo "   按名称批量查询数量: " . count($tokensByName) . "\n";

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
        // TRX 的代币 ID
        $tokenById = $tronWeb->token->getById('1000000');
        if ($tokenById) {
            echo "   代币名称: " . ($tokenById['name'] ?? '未知') . "\n";
            echo "   代币缩写: " . ($tokenById['abbr'] ?? '未知') . "\n";
        echo "   总供应量: " . ($tokenById['total_supply'] ?? '未知') . "\n";
        } else {
            echo "   未找到该 ID 的代币\n";
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

    // 5. 获取地址发行的代币详情
    echo "5. 获取地址发行的代币详情:\n";

    try {
        $issuedTokensDetails = $tronWeb->token->getTokensIssuedByAddress($testAddress);
        echo "   代币详情数量: " . count($issuedTokensDetails) . "\n";

        if (!empty($issuedTokensDetails)) {
            $sampleDetail = $issuedTokensDetails[0];
            echo "   示例代币: " . ($sampleDetail['name'] ?? '未知') . "\n";
            echo "   总供应量: " . ($sampleDetail['total_supply'] ?? '未知') . "\n";
            echo "   精度: " . ($sampleDetail['precision'] ?? '未知') . "\n";
        }
    } catch (TronException $e) {
        echo "   代币详情查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 6. 代币转账功能说明
    echo "6. 代币转账功能说明:\n";

    echo "   代币转账方法:\n";
    echo "   - send(): 发送代币（TRC10）\n";
    echo "   - sendToken(): 发送代币（选项模式）\n";
    echo "   - sendTransaction(): 发送交易（SUN 单位）\n";

    echo "   参数说明:\n";
    echo "   - to: 接收地址\n";
    echo "   - amount: 转账金额\n";
    echo "   - tokenID: 代币 ID\n";
    echo "   - 可选参数：私钥、发送地址等\n";

    echo "   使用示例:\n";
    echo "   // 需要设置私钥\n";
    echo "   \$tronWeb->setPrivateKey('your_private_key');\n";
    echo "   \$result = \$tronWeb->token->send('接收地址', 100, '代币ID');\n";

    // 7. 代币创建功能说明
    echo "7. 代币创建功能说明:\n";

    echo "   创建 TRC10 代币参数:\n";
    echo "   - name: 代币名称\n";
    echo "   - abbreviation: 代币缩写\n";
    echo "   - total_supply: 总供应量\n";
    echo "   - trx_num: TRX 兑换比例\n";
    echo "   - num: 代币兑换比例\n";
    echo "   - start_time: 开始时间（毫秒时间戳）\n";
    echo "   - end_time: 结束时间（毫秒时间戳）\n";
    echo "   - description: 描述\n";
    echo "   - url: 官方网站\n";
    echo "   - 可选参数：免费带宽、精度等\n";

    echo "   创建示例:\n";
    echo "   \$result = \$tronWeb->token->createToken([\n";
    echo "       'name' => 'MyToken',\n";
    echo "       'abbreviation' => 'MTK',\n";
    echo "       'total_supply' => 1000000,\n";
    echo "       'trx_num' => 1,\n";
    echo "       'num' => 1,\n";
    echo "       'start_time' => time() * 1000 + 3600000,\n";
    echo "       'end_time' => time() * 1000 + 86400000,\n";
    echo "       'description' => '我的代币',\n";
    echo "       'url' => 'https://mytoken.com'\n";
    echo "   ]);\n";

    // 8. 代币更新功能说明
    echo "8. 代币更新功能说明:\n";

    echo "   更新代币参数:\n";
    echo "   - tokenID: 代币 ID（或从地址推断）\n";
    echo "   - description: 新描述\n";
    echo "   - url: 新网址\n";
    echo "   - freeBandwidth: 免费带宽总量\n";
    echo "   - freeBandwidthLimit: 每账户免费带宽限制\n";

    echo "   更新示例:\n";
    echo "   \$result = \$tronWeb->token->updateToken(\n";
    echo "       '新的代币描述',\n";
    echo "       'https://new-website.com',\n";
    echo "       1000000,\n";
    echo "       10000\n";
    echo "   ]);\n";

    // 9. 代币购买功能说明
    echo "9. 代币购买功能说明:\n";

    echo "   购买代币参数:\n";
    echo "   - issuerAddress: 代币发行者地址\n";
    echo "   - tokenID: 代币 ID\n";
    echo "   - amount: 购买金额（TRX）\n";
    echo "   - buyer: 购买者地址\n";

    echo "   购买示例:\n";
    echo "   \$result = \$tronWeb->token->purchaseToken(\n";
    echo "       '发行者地址',\n";
    echo "       '代币ID',\n";
    echo "       100\n";
    echo "   ]);\n";

    echo "\n=== Token 模块示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron 异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Token 模块主要方法:\n";
echo "- list(): 获取代币列表\n";
echo "- getFromName(): 按名称查询代币\n";
echo "- getById(): 按 ID 查询代币\n";
echo "- getIssuedByAddress(): 查询地址发行的代币\n";
echo "- getTokensIssuedByAddress(): 获取地址发行的代币详情\n";
echo "- send(): 发送代币（TRC10）\n";
echo "- sendToken(): 发送代币（选项模式）\n";
echo "- sendTransaction(): 发送交易（SUN 单位）\n";
echo "- createToken(): 创建 TRC10 代币\n";
echo "- purchaseToken(): 购买代币\n";
echo "- updateToken(): 更新代币信息\n";
echo "- getTokenListByName(): 批量查询代币\n";
echo "- getTokenFromID(): 通过 ID 获取代币信息\n";
echo "- 共 13+ 个代币相关方法\n";

echo "\n💡 使用提示:\n";
echo("- TRC10 是 TRON 原生代币标准，使用代币 ID");
echo("- TRC20 是智能合约代币标准，使用合约地址");
echo("- 创建代币需要消耗资源和 TRX");
echo("- 代币操作需要私钥签名");
echo("- 代币名称、ID、缩写都可以用于查询");
echo("- getIssuedByAddress 返回基本代币列表");
echo("- getTokensIssuedByAddress 返回完整的代币详情");

echo "\n🔍 常见 TRC10 代币:\n";
echo("- TRX: 1000000 (主网原生代币)\n");
echo("- USDT: 1000212 (测试网 USDT)\n");
echo("- BTT: 1000050 (测试网 BTT)\n");
echo("- USDC: 1000060 (测试网 USDC)\n");
echo("- WIN: 1000200 (测试网 WIN)\n");

echo "\n⚠️ 注意:\n";
echo("- 本示例主要展示查询功能\n");
echo("- 实际代币操作需要真实私钥\n");
echo("- 创建代币前请充分了解费用和规则\n");
echo("- 代币名称和 ID 在全网必须唯一\n");
echo("- 发行时间必须大于当前时间\n");
echo("- 代币发行后无法修改基本参数（名称、ID、缩写）");
?>
