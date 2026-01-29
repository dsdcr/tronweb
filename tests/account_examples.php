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
    // 初始化TronWeb实例
    $tronWeb = new TronWeb([
        'fullNode' => new HttpProvider('https://api.trongrid.io')
    ]);

    // 1. 生成新账户
    echo "1. 生成新账户:\n";
    $newAccount = $tronWeb->account->create();

    echo "   地址(Hex): " . $newAccount['address']['hex'] . "\n";
    echo "   地址(Base58): " . $newAccount['address']['base58'] . "\n";
    echo "   私钥: " . $newAccount['privateKey'] . "\n";
    echo "   公钥: " . substr($newAccount['publicKey'], 0, 32) . "...\n\n";

    // 2. 地址验证
    echo "2. 地址验证:\n";
    $testAddress = $newAccount['address']['base58'];
    $isValid = $tronWeb->account->isValidAddress($testAddress);
    $isAddress = $tronWeb->account->isAddress($testAddress);

    echo "   地址: $testAddress\n";
    echo "   是否有效地址: " . ($isValid ? '是' : '否') . "\n";
    echo "   是否是地址: " . ($isAddress ? '是' : '否') . "\n\n";

    // 3. 地址转换
    echo "3. 地址转换:\n";
    $hexAddress = $tronWeb->account->toHex($testAddress);
    $base58Address = $tronWeb->account->toBase58($hexAddress);

    echo "   Base58 → Hex: $hexAddress\n";
    echo "   Hex → Base58: $base58Address\n";
    echo "   转换一致性: " . ($testAddress === $base58Address ? '一致' : '不一致') . "\n\n";

    // 4. 助记词生成和管理
    echo "4. 助记词相关功能:\n";

    // 生成助记词
    $mnemonic = $tronWeb->account->generateMnemonic(12);
    echo "   生成的助记词: $mnemonic\n";

    // 验证助记词
    $isValidMnemonic = $tronWeb->account->validateMnemonic($mnemonic);
    echo "   助记词有效性: " . ($isValidMnemonic ? '有效' : '无效') . "\n";

    // 从助记词生成账户
    $mnemonicAccount = $tronWeb->account->generateAccountWithMnemonic($mnemonic);
    echo "   从助记词生成的地址: " . $mnemonicAccount['address']['base58'] . "\n\n";

    // 5. 密钥验证
    echo "5. 密钥验证:\n";
    $privateKey = $newAccount['privateKey'];
    $publicKey = $newAccount['publicKey'];

    echo "   私钥有效性: " . ($tronWeb->account->isValidPrivateKey($privateKey) ? '有效' : '无效') . "\n";
    echo "   公钥有效性: " . ($tronWeb->account->isValidPublicKey($publicKey) ? '有效' : '无效') . "\n";

    // 从私钥恢复地址
    $recoveredAddress = $tronWeb->account->recoverAddressFromPrivateKey($privateKey);
    echo "   从私钥恢复的地址: " . $recoveredAddress['base58'] . "\n";
    echo "   地址一致性: " . ($recoveredAddress['base58'] === $testAddress ? '一致' : '不一致') . "\n\n";

    // 6. 批量余额查询
    echo "6. 批量余额查询演示:\n";
    $addresses = [
        'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL', // 测试地址1
        'TRWBqiqoFZysoAeyR1J35ibuyc8EvhUAoY'  // 测试地址2
    ];

    try {
        $balances = $tronWeb->account->getBalances($addresses, true);
        foreach ($balances as $index => $balance) {
            echo "   地址" . ($index + 1) . "余额: " . ($balance['balance'] ?? 'N/A') . " TRX\n";
        }
    } catch (TronException $e) {
        echo "   批量查询失败: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 7. HD钱包功能
    echo "7. HD钱包功能:\n";
    try {
        $seed = $tronWeb->account->mnemonicToSeed($mnemonic);
        echo "   种子: " . substr(bin2hex($seed), 0, 32) . "...\n";

        // 查找私钥派生路径
        $derivationPath = $tronWeb->account->findPrivateKeyDerivationPath($privateKey, $mnemonic);
        echo "   派生路径: " . ($derivationPath ?: '未找到') . "\n";

    } catch (TronException $e) {
        echo "   HD钱包功能暂不可用: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 8. 账户信息查询
    echo "8. 账户信息查询:\n";
    try {
        $accountInfo = $tronWeb->account->getAccount($testAddress);
        echo "   账户信息获取: " . (empty($accountInfo) ? '空' : '成功') . "\n";

        if (!empty($accountInfo)) {
            echo "   账户类型: " . ($accountInfo['type'] ?? '未知') . "\n";
            echo "   账户资源: " . ($accountInfo['account_resource'] ? '存在' : '无') . "\n";
        }
    } catch (TronException $e) {
        echo "   账户信息查询失败: " . $e->getMessage() . "\n";
    }

    echo "\n=== Account 模块示例完成 ===\n";

} catch (TronException $e) {
    echo "❌ Tron异常: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般异常: " . $e->getMessage() . "\n";
}

// 方法总结
echo "\n📋 Account 模块主要方法:\n";
echo "- create(): 创建新账户\n";
echo "- generateMnemonic(): 生成助记词\n";
echo "- generateAccountWithMnemonic(): 从助记词生成账户\n";
echo "- isValidAddress(): 验证地址有效性\n";
echo "- toHex() / toBase58(): 地址转换\n";
echo "- getBalances(): 批量余额查询\n";
echo "- recoverAddressFromPrivateKey(): 从私钥恢复地址\n";
echo "- validateMnemonic(): 验证助记词\n";
echo "- 共35+个账户相关方法\n";

echo "\n💡 使用提示:\n";
echo "- 助记词和私钥必须安全存储\n";
echo("- 生产环境使用HD钱包更安全\n");
echo "- 批量查询可提高效率\n";
?>