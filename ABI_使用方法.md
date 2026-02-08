# ABI 方法使用指南

本文档提供了 TronWeb 智能合约 ABI 的所有方法使用示例。

## 目录
- [ERC20 标准代币](#erc20-标准代币)
- [SunSwap V2 路由合约](#sunswap-v2-路由合约)
- [SunSwap V3 路由合约](#sunswap-v3-路由合约)
- [Fibonacci 合约](#fibonacci-合约)

---

## ERC20 标准代币

ERC20 是 TRON 网络上最常用的代币标准。

### 获取合约实例


## 工具方法

### combined() - 组合多种 ABI

```php
use Dsdcr\TronWeb\Config\DefaultAbi;

// 组合多种 ABI
$abi = DefaultAbi::combined(['erc20', 'router']);
// 支持的类型：'erc20', 'router', 'v3router', 'fibonacci'

// 使用组合后的 ABI
$contract = $tronWeb->contract($abi)->at($contractAddress);
// 使用组合后的所有 ABI
$contract = $tronWeb->contract()->at($contractAddress);
```

### 1. name() - 查询代币名称

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$name = $tokenContract->name();
// 返回：代币名称，例如 "Tether USD"
```

### 2. symbol() - 查询代币符号

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$symbol = $tokenContract->symbol();
// 返回：代币符号，例如 "USDT"
```

### 3. decimals() - 查询代币小数位数

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$decimals = $tokenContract->decimals();
// 返回：小数位数，通常为 6 或 18
```

### 4. totalSupply() - 查询代币总供应量

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$totalSupply = $tokenContract->totalSupply();
// 返回：代币总供应量（未换算小数）
```

### 5. balanceOf(account) - 查询账户余额

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$balance = $tokenContract->balanceOf($address);
// 参数：$address - 要查询的钱包地址
// 返回：该地址的代币余额（未换算小数）
```

### 6. transfer(recipient, amount) - 转账

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$result = $tokenContract->transfer($toAddress, $amount)->send();
// 参数：
//   $toAddress - 接收地址
//   $amount - 转账数量（未换算小数）
// 返回：交易收据，包含交易状态等信息
```

### 7. transferFrom(sender, recipient, amount) - 授权转账

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$result = $tokenContract->transferFrom($fromAddress, $toAddress, $amount)->send();
// 参数：
//   $fromAddress - 发送地址（需要有足够授权）
//   $toAddress - 接收地址
//   $amount - 转账数量（未换算小数）
// 返回：交易收据
```

### 8. approve(spender, value) - 授权

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$result = $tokenContract->approve($spenderAddress, $amount)->send();
// 参数：
//   $spenderAddress - 被授权地址
//   $amount - 授权数量（未换算小数）
// 返回：交易收据
```

### 9. allowance(owner, spender) - 查询授权额度

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$allowance = $tokenContract->allowance($ownerAddress, $spenderAddress);
// 参数：
//   $ownerAddress - 代币拥有者地址
//   $spenderAddress - 被授权地址
// 返回：授权额度（未换算小数）
```

### 10. increaseAllowance(spender, addedValue) - 增加授权额度

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$result = $tokenContract->increaseAllowance($spenderAddress, $addedValue)->send();
// 参数：
//   $spenderAddress - 被授权地址
//   $addedValue - 增加的授权数量（未换算小数）
// 返回：交易收据
```

### 11. decreaseAllowance(spender, subtractedValue) - 减少授权额度

```php
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$result = $tokenContract->decreaseAllowance($spenderAddress, $subtractedValue)->send();
// 参数：
//   $spenderAddress - 被授权地址
//   $subtractedValue - 减少的授权数量（未换算小数）
// 返回：交易收据
```

### 事件监听

```php
// 监听 Transfer 事件
$tokenContract = $tronWeb->contract()->at($tokenAddress);
$tokenContract->events()->Transfer()->watch(function($event) {
    echo "From: " . $event['result']['from'] . "\n";
    echo "To: " . $event['result']['to'] . "\n";
    echo "Value: " . $event['result']['value'] . "\n";
});

// 监听 Approval 事件
$tokenContract->events()->Approval()->watch(function($event) {
    echo "Owner: " . $event['result']['owner'] . "\n";
    echo "Spender: " . $event['result']['spender'] . "\n";
    echo "Value: " . $event['result']['value'] . "\n";
});
```

---

## SunSwap V2 路由合约

SunSwap V2 是 TRON 网络上的去中心化交易所，支持代币交换。

### 获取合约实例

```php
$routerContract = $tronWeb->contract()->at($routerAddress);
```

### 1. getAmountsOut(amountIn, path) - 查询交换输出金额

```php
$routerContract = $tronWeb->contract()->at($routerAddress);
$amounts = $routerContract->getAmountsOut($amountIn, $path);
// 参数：
//   $amountIn - 输入代币数量（未换算小数）
//   $path - 交换路径数组，例如 [tokenA, tokenB, tokenC]
// 返回：每一步输出的数量数组
// 示例：
$path = [$tokenA, $tokenB]; // USDT -> TRX
$amounts = $routerContract->getAmountsOut(1000000, $path);
// 输入 1 USDT，能换到多少 TRX
```

### 2. swapExactTokensForETH(amountIn, amountOutMin, path, to, deadline) - 交换代币为 ETH

```php
$routerContract = $tronWeb->contract()->at($routerAddress);
$result = $routerContract->swapExactTokensForETH(
    $amountIn,
    $amountOutMin,
    $path,
    $toAddress,
    $deadline
)->send();
// 参数：
//   $amountIn - 输入代币数量（未换算小数）
//   $amountOutMin - 最少输出数量（滑点保护，防止价格波动过大）
//   $path - 交换路径数组
//   $toAddress - 接收 ETH 的地址
//   $deadline - 交易截止时间戳
// 返回：交易收据
// 示例：
$path = [$tokenAddress, $wtrxAddress]; // Token -> WTRX
$result = $routerContract->swapExactTokensForETH(
    100000000,  // 100 Token
    99000000,   // 至少换到 99 WTRX（1% 滑点保护）
    $path,
    $myAddress,
    time() + 300 // 5 分钟后过期
)->send();
```

---

## SunSwap V3 路由合约

SunSwap V3 提供集中流动性功能，相比 V2 有更高的资本效率。

### 获取合约实例

```php
$routerContract = $tronWeb->contract()->at($routerAddress);
```

### 1. swapExactInput() - 精确输入交换

```php
$routerContract = $tronWeb->contract()->at($routerAddress);

// 构造 SwapData 结构
$swapData = [
    'amountIn' => $amountIn,
    'amountOutMin' => $amountOutMin,
    'to' => $toAddress,
    'deadline' => $deadline
];

$result = $routerContract->swapExactInput(
    $path,           // 交换路径
    $poolVersion,    // 池子版本数组，例如 ['v3']
    $versionLen,     // 版本长度数组
    $fees,           // 费率等级数组，例如 [3000]
    $swapData        // SwapData 结构
)->send();
// 参数说明：
//   $path: 交换路径，例如 [tokenA, tokenB]
//   $poolVersion: 池子版本，例如 ['v3']
//   $versionLen: 版本长度，例如 [2]
//   $fees: 费率等级，例如 [3000] 表示 0.3% 手续费
//   $swapData: 包含 amountIn, amountOutMin, to, deadline
// 返回：交易收据

// 示例：
$swapData = [
    'amountIn' => '1000000000',
    'amountOutMin' => '990000000',
    'to' => $myAddress,
    'deadline' => time() + 300
];

$result = $routerContract->swapExactInput(
    [$tokenA, $tokenB],  // A -> B
    ['v3'],
    [2],
    [3000],             // 0.3% 手续费
    $swapData
)->send();
```

---

## Fibonacci 合约

这是一个示例合约，用于演示斐波那契数列的计算和事件通知。

### 获取合约实例

```php
$fibonacciContract = $tronWeb->contract()->at($contractAddress);
```

### 1. fibonacci(number) - 计算斐波那契数（只读）

```php
$fibonacciContract = $tronWeb->contract()->at($contractAddress);
$result = $fibonacciContract->fibonacci($number);
// 参数：$number - 要计算的位置
// 返回：斐波那契数
// 示例：
$result = $fibonacciContract->fibonacci(10);
// 返回：55
```

### 2. fibonacciNotify(number) - 计算斐波那契数并触发事件

```php
$fibonacciContract = $tronWeb->contract()->at($contractAddress);
$result = $fibonacciContract->fibonacciNotify($number)->send();
// 参数：$number - 要计算的位置
// 返回：交易收据，同时触发 Notify 事件
// 示例：
$result = $fibonacciContract->fibonacciNotify(10)->send();
```

### 事件监听

```php
$fibonacciContract = $tronWeb->contract()->at($contractAddress);

// 监听 Notify 事件
$fibonacciContract->events()->Notify()->watch(function($event) {
    echo "Input: " . $event['result']['input'] . "\n";
    echo "Result: " . $event['result']['result'] . "\n";
});
```

---

## 通用注意事项

1. **只读方法 vs 写入方法**
   - 只读方法（`constant: true` 或 `stateMutability: "view"`）：直接调用，无需发送交易
   - 写入方法：需要调用 `send()` 方法发送交易

2. **金额单位**
   - 所有金额都使用最小单位（未换算小数）
   - 例如 USDT 的小数位是 6，1 USDT = 1000000
   - 例如 TRX 的小数位是 6，1 TRX = 1000000

3. **滑点保护**
   - 在交换代币时，建议设置合理的 `amountOutMin` 防止价格滑点过大
   - 通常设置预期输出金额的 95-99%

4. **交易截止时间**
   - 建议设置合理的 `deadline`，防止交易在队列中滞留过久
   - 通常设置为当前时间 + 5-10 分钟

5. **错误处理**
   ```php
   try {
       $result = $contract->transfer($to, $amount)->send();
   } catch (Exception $e) {
       echo "交易失败: " . $e->getMessage();
   }
   ```

6. **事件监听**
   - 事件监听需要合约支持事件日志
   - 监听是实时的，适合监听转账、授权等操作

---

## 相关资源

- [TronWeb 官方文档](https://developers.tron.network/docs/tron-web-introduction)
- [ERC20 标准](https://eips.ethereum.org/EIPS/eip-20)
- [SunSwap 文档](https://docs.sunswap.com/)
- [ABI 文件位置](vendor/dsdcr/tronweb/src/Config/DefaultAbi.php)