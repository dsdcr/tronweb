<?php

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

echo "SunSwap Router 简单诊断\n";
echo "=======================\n";

$routerAddress = 'TKzxdSv2FZKQrEqkKVgp5DcwEXBEKMg2Ax';

try {
    $nodeConfig = [
        'host' => 'https://api.trongrid.io',
        'headers' => ['TRON-PRO-API-KEY' => 'your-api-key-here'] // 替换为您的 API Key
    ];

    $tronWeb = new TronWeb(
        new HttpProvider($nodeConfig),
        new HttpProvider($nodeConfig),
        new HttpProvider($nodeConfig)
    );

    echo "Router地址: {$routerAddress}\n\n";

    // 测试WTRX/WETH方法
    echo "1. 测试WTRX方法:\n";
    try {
        $wtrxAbi = [[
            "inputs" => [],
            "name" => "WTRX",
            "outputs" => [["name" => "", "type" => "address"]],
            "stateMutability" => "view",
            "type" => "function"
        ]];
        $contract = $tronWeb->contract($wtrxAbi)->at($routerAddress);
        $result = $contract->WTRX();
        echo "   ✅ WTRX() 返回: " . (is_array($result) ? $result[0] : $result) . "\n";
    } catch (Exception $e) {
        echo "   ❌ WTRX() 失败: " . $e->getMessage() . "\n";
    }

    echo "\n2. 测试WETH方法:\n";
    try {
        $wethAbi = [[
            "inputs" => [],
            "name" => "WETH",
            "outputs" => [["name" => "", "type" => "address"]],
            "stateMutability" => "view",
            "type" => "function"
        ]];
        $contract = $tronWeb->contract($wethAbi)->at($routerAddress);
        $result = $contract->WETH();
        echo "   ✅ WETH() 返回: " . (is_array($result) ? $result[0] : $result) . "\n";
    } catch (Exception $e) {
        echo "   ❌ WETH() 失败: " . $e->getMessage() . "\n";
    }

    echo "\n3. 检查合约信息:\n";
    try {
        // 使用 contract()->at() 获取合约实例
        $contract = $tronWeb->contract()->at($routerAddress);
        echo "   ✅ 合约地址：" . $contract->getAddress() . "\n";
        echo "   ℹ️ 注意：getContract() 方法不存在，请使用 contract()->at()\n";
    } catch (Exception $e) {
        echo "   ℹ️ 无法获取合约信息：" . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "诊断错误: " . $e->getMessage() . "\n";
}