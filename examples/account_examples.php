<?php
/**
 * Account 模块使用示例
 * 展示账户管理相关功能：地址生成、验证、助记词等
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

echo "=== Account 模块使用示例 ===\n\n";

try {
    // 初始化 TronWeb 实例
    $tronWeb = new TronWeb(
        new HttpProvider('https://api.trongrid.io')
    );

    // 1. 生成新账户
    echo "1. 生成新账户:\n";
    $newAccount = $tronWeb->account->create();

    echo "   地址(Hex): " . $newAccount->getAddress(false) . "\n";
    echo "   地址(Base58): " . $newAccount->getAddress(true) . "\n";
    echo "   私钥: " . $newAccount->getPrivateKey() . "\n";
    echo "   公钥: " . $newAccount->getPublicKey() . "\n\n";

    // 2. 通过私钥创建账户
    echo "2. 通过私钥创建账户:\n";
    $accountFromKey = $tronWeb->account->createWithPrivateKey($newAccount->getPrivateKey());
    echo "   从私钥恢复的地址: " . $accountFromKey->getAddress(true) . "\n\n";

    // 3. 助记词功能
    echo "3. 助记词相关功能:\n";

    // 生成助记词
    $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon about';
    echo "   测试助记词: $mnemonic\n";

    // 从助记词生成账户
    $mnemonicAccount = $tronWeb->account->generateAccountWithMnemonic($mnemonic);
    echo "   从助记词生成的地址: " . $mnemonicAccount['address_base58'] . "\n";
    echo "   衍生路径: " . $mnemonicAccount['derivation_path'] . "\n\n";

    // 4. 查询账户信息
    echo "4. 查询账户信息:\n";
    try {
        $testAddress = $newAccount->getAddress(true);
        $accountInfo = $tronWeb->account->getAccount($testAddress);

        echo "   账户查询: " . (empty($accountInfo) ? '空' : '成功') . "\n";

        if (!empty($accountInfo)) {
            echo "   账户余额: " . ($accountInfo['balance'] ?? '0') . " SUN\n";
            echo "   账户名称: " . ($accountInfo['account_name'] ?? '未设置') . "\n";
        }
    } catch (TronException $e) {
        echo "   账户信息查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. 查询账户资源
    echo "5. 查询账户资源:\n";
    try {
        $testAddress = $newAccount->getAddress(true);
        $accountResources = $tronWeb->account->getAccountresource($testAddress);

        echo "   资源查询: " . (empty($accountResources) ? '空' : '成功') . "\n";

        if (!empty($accountResources)) {
            echo "   能量上限: " . ($accountResources['EnergyLimit'] ?? '0') . "\n";
            echo "   已用能量: " . ($accountResources['EnergyUsed'] ?? '0') . "\n";
        }
    } catch (TronException $e) {
        echo "   资源查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 6. 查询代币余额
    echo "6. 查询代币余额:\n";
    try {
        $testAddress = $newAccount->getAddress(true);
        $tokenId = 1000001; // 示例代币ID

        $tokenBalance = $tronWeb->account->getTokenBalance($tokenId, $testAddress, true);
        echo "   代币ID $tokenId 余额: " . $tokenBalance . "\n";
    } catch (TronException $e) {
        echo "   代币余额查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 7. 查询交易历史
    echo "7. 查询交易历史:\n";
    try {
        $testAddress = $newAccount->getAddress(true);

        // 获取最近的交易
        $transactions = $tronWeb->account->getTransactions($testAddress, 'all', 5, 0);
        echo "   交易查询: " . (empty($transactions) ? '空' : '成功') . "\n";

        if (!empty($transactions)) {
            echo "   最近交易数: " . count($transactions) . "\n";
        }
    } catch (TronException $e) {
        echo "   交易历史查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 8. 批量余额查询
    echo "8. 批量余额查询:\n";
    try {
        $addresses = [
            $newAccount->getAddress(true),
            $tronWeb->fromPrivateKey($newAccount->getPrivateKey())
        ];

        $balances = $tronWeb->account->getBalances($addresses, true, true);
        echo "   批量查询成功\n";

        foreach ($balances as $item) {
            echo "   地址: {$item['address']} - 余额: {$item['balance']} TRX\n";
        }
    } catch (TronException $e) {
        echo "   批量查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    echo "=== Account 模块示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron 异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Account 模块主要方法:\n";
echo "- create(): 生成新随机账户\n";
echo "- createWithPrivateKey(): 通过私钥创建账户\n";
echo "- generateAccountWithMnemonic(): 从助记词生成账户(BIP39/BIP44)\n";
echo "- getAccount(): 查询账户信息\n";
echo "- getAccountresource(): 查询账户资源信息\n";
echo "- getTokenBalance(): 查询指定代币的余额\n";
echo "- getTransactions(): 查询账户的交易历史\n";
echo "- getBalances(): 批量查询账户余额\n";
echo "- fromPrivateKey(): 从私钥获取地址\n";
echo "- changeName(): 更新账户名称\n";
echo "- register(): 创建新账户\n";

echo "\n💡 使用提示:\n";
echo "- 助记词和私钥必须安全存储\n";
echo "- 生产环境使用 HD 钱包更安全\n";
echo "- 批量查询可提高效率\n";
echo "- getAccount 返回账户余额信息\n";
echo "- getAccountresource 返回能量和带宽资源信息\n";
echo "- getTokenBalance 用于查询 TRC10 代币余额\n";
echo "- getTransactions 支持按交易方向过滤(to/from/all)\n";
?>
