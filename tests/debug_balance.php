<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

$httpProvider = new HttpProvider('https://nile.trongrid.io');
$tronWeb = new TronWeb($httpProvider, $httpProvider, $httpProvider);

$usdtAddress = 'TXYZopYRdj2D9XRtbG411XZZ3kM5VkAeBf';
$userAddress = 'TFxaKCCGnbfLr93FjUWBKTJ2mDqJ6KT12h';
$winTokenAddress = 'TNDSHKGBmgRx9mDYA9CnxPx55nu672yQw2';
$usdtContract = $tronWeb->contract()->at($winTokenAddress);
$balanceResult = $usdtContract->balanceOf($userAddress);
$balance=$tronWeb->toDecimal($balanceResult);
// 转换为可读格式（USDT 有6位小数）
$balanceFormatted = $tronWeb->utils->fromWei($balance,6);
echo "USDT余额: {$balanceFormatted} USDT\n";
echo "原始数值: {$balance}\n";