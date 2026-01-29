<?php
/**
 * Network 模块使用示例
 * 展示网络信息功能：节点查询、提案管理、交易所接口等
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

echo "=== Network 模块使用示例 ===\n\n";

try {
    // 初始化TronWeb实例
    $tronWeb = new TronWeb([
        'fullNode' => new HttpProvider('https://api.trongrid.io')
    ]);

    // 1. 节点信息查询
    echo "1. 节点信息查询:\n";

    try {
        // 列出所有节点
        $nodes = $tronWeb->network->listNodes();
        echo "   节点数量: " . count($nodes) . "\n";

        if (!empty($nodes)) {
            $sampleNode = $nodes[0];
            echo "   示例节点地址: " . ($sampleNode['address']['host'] ?? '未知') . "\n";
            echo "   节点类型: " . ($sampleNode['address']['type'] ?? '未知') . "\n";
        }

    } catch (TronException $e) {
        echo "   节点查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 2. 超级代表查询
    echo "2. 超级代表查询:\n";

    try {
        $superRepresentatives = $tronWeb->network->listSuperRepresentatives();
        echo "   超级代表数量: " . count($superRepresentatives) . "\n";

        if (!empty($superRepresentatives)) {
            $sampleSR = $superRepresentatives[0];
            echo "   示例代表地址: " . ($sampleSR['address'] ?? '未知') . "\n";
            echo "   投票数量: " . ($sampleSR['voteCount'] ?? '0') . "\n";
        }

    } catch (TronException $e) {
        echo "   超级代表查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 3. 提案管理系统
    echo "3. 提案管理系统:\n";

    try {
        // 列出所有提案
        $proposals = $tronWeb->network->listProposals();
        echo "   提案数量: " . count($proposals) . "\n";

        if (!empty($proposals)) {
            $sampleProposal = $proposals[0];
            echo "   示例提案ID: " . ($sampleProposal['proposal_id'] ?? '未知') . "\n";
            echo "   提案状态: " . ($sampleProposal['state'] ?? '未知') . "\n";
        }

        // 获取提案参数
        $proposalParams = $tronWeb->network->getProposalParameters();
        echo "   提案参数数量: " . count($proposalParams) . "\n";

    } catch (TronException $e) {
        echo "   提案查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 4. 交易所接口
    echo "4. 交易所接口:\n";

    try {
        // 列出交易所
        $exchanges = $tronWeb->network->listExchanges();
        echo "   交易所数量: " . count($exchanges) . "\n";

        if (!empty($exchanges)) {
            $sampleExchange = $exchanges[0];
            echo "   示例交易所ID: " . ($sampleExchange['exchange_id'] ?? '未知') . "\n";
            echo "   创建者地址: " . ($sampleExchange['creator_address'] ?? '未知') . "\n";
        }

        // 分页获取交易所
        $exchangesPaginated = $tronWeb->network->listExchangesPaginated(5, 0);
        echo "   分页交易所数量: " . count($exchangesPaginated) . "\n";

    } catch (TronException $e) {
        echo "   交易所查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. 投票相关功能
    echo "5. 投票相关功能:\n";

    try {
        // 获取投票奖励比例
        $voteRewardRatio = $tronWeb->network->getVoteRewardRatio();
        echo "   投票奖励比例: " . ($voteRewardRatio ?? '未知') . "\n";

        // 距离下次投票周期时间
        $nextVoteTime = $tronWeb->network->timeUntilNextVoteCycle();
        echo "   距离下次投票周期: " . ($nextVoteTime ?? '未知') . " 毫秒\n";

    } catch (TronException $e) {
        echo "   投票信息查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 6. 申请成为代表功能说明
    echo "6. 申请成为代表功能说明:\n";

    echo "   申请成为代表参数:\n";
    echo "   - url: 官方网站\n";
    echo "   - description: 代表描述\n";
    echo "   - 需要足够的投票权和TRX抵押\n\n";

    echo "   使用示例:\n";
    echo "   // 需要设置私钥\n";
    echo "   \$tronWeb->setPrivateKey('your_private_key');\n";
    echo "   \$result = \$tronWeb->network->applyForRepresentative([\n";
    echo "       'url' => 'https://my-sr.com',\n";
    echo "       'description' => '我的超级代表节点'\n";
    echo "   ]);\n\n";

    echo "   申请超级代表:\n";
    echo "   \$result = \$tronWeb->network->applyForSuperRepresentative([\n";
    echo "       'url' => 'https://my-sr.com',\n";
    echo "       'description' => '我的超级代表节点'\n";
    echo "   ]);\n\n";

    // 7. 链参数查询
    echo "7. 链参数查询:\n";

    try {
        $chainParameters = $tronWeb->network->getChainParameters();
        echo "   链参数数量: " . count($chainParameters) . "\n";

        if (!empty($chainParameters)) {
            $sampleParam = $chainParameters[0];
            echo "   示例参数: " . ($sampleParam['key'] ?? '未知') . " = " . ($sampleParam['value'] ?? '未知') . "\n";
        }

    } catch (TronException $e) {
        echo "   链参数查询失败: " . $e->getMessage() . "\n";
    }

    echo "\n=== Network 模块示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Network 模块主要方法:\n";
echo "- listNodes(): 列出网络节点\n";
echo "- listSuperRepresentatives(): 列出超级代表\n";
echo "- listProposals(): 列出网络提案\n";
echo "- getProposalParameters(): 获取提案参数\n";
echo "- listExchanges(): 列出交易所\n";
echo "- getVoteRewardRatio(): 获取投票奖励比例\n";
echo "- applyForRepresentative(): 申请成为代表\n";
echo "- 共13+个网络相关方法\n";

echo "\n💡 使用提示:\n";
echo "- 网络信息查询不需要私钥\n";
echo("- 申请成为代表需要私钥和足够抵押\n");
echo("- 提案管理系统用于网络治理\n");
echo("- 交易所接口支持去中心化交易\n");

echo "\n🏛️  治理系统:\n";
echo "- 超级代表: 27个主要节点\n";
echo("- 提案: 网络参数修改建议\n");
echo("- 投票: 社区参与治理\n");
echo("- 交易所: 去中心化资产交换\n");

echo "\n⚠️  注意:\n";
echo "- 本示例主要展示查询功能\n";
echo("- 申请代表需要大量TRX抵押\n");
echo("- 参与治理前了解网络规则\n");
?>