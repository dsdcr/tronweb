# TronWeb 新架构完全参考指南

## 📋 目录
- [架构概述](#架构概述)
- [完整目录结构](#完整目录结构)
- [核心模块](#核心模块)
- [快速开始](#快速开始)
- [模块方法统计](#模块方法统计)
- [新增高级功能](#新增高级功能)
- [核心功能示例](#核心功能示例)
- [向后兼容性](#向后兼容性)
- [性能优势](#性能优势)
- [完整方法列表](#完整方法列表)
- [工具类详细方法](#工具类详细方法)
- [总方法统计](#总方法统计)

## 📋 架构概述

TronWeb 新架构采用模块化设计，将功能按业务域分离到不同的模块中，提高了代码的可维护性和扩展性。

## 🏗️ 完整目录结构

```
vendor/dsdcr/tronweb/
├── src/
│   ├── TronWeb.php             # 主入口类（根目录唯一文件）
│   ├── Entities/               # 实体类
│   │   └── TronAddress.php     # 地址实体对象
│   ├── Modules/                # 核心业务模块
│   │   ├── Account.php         # 账户管理 (35个方法)
│   │   ├── BaseModule.php      # 模块基类
│   │   ├── Contract.php        # 智能合约 (6个方法，含trc20工厂)
│   │   ├── Network.php         # 网络信息 (8个方法)
│   │   ├── Resource.php        # 资源管理 (8个方法)
│   │   ├── Token.php           # 代币操作 (10个方法)
│   │   ├── Trx.php             # 交易区块 (25+方法，含合约触发)
│   │   └── TRC20/              # TRC20专用模块
│   │       ├── TRC20Contract.php  # TRC20合约处理
│   │       └── trc20.json         # TRC20标准ABI
│   ├── Concerns/               # 功能特性
│   │   └── ManagesTronscan.php # Tronscan API 集成
│   ├── Exception/              # 异常类
│   │   ├── TronException.php
│   │   ├── TRC20Exception.php  # TRC20专用异常
│   │   └── ErrorException.php
│   ├── Provider/               # 网络提供商
│   │   ├── HttpProvider.php
│   │   └── HttpProviderInterface.php
│   ├── Support/                # 支持类库
│   │   ├── TronUtils.php       # TRON专用工具 (22个方法)
│   │   ├── Utils.php           # 基础工具类
│   │   ├── Base58.php
│   │   ├── Base58Check.php
│   │   ├── Bip39.php           # BIP39 助记词标准
│   │   ├── HdWallet.php        # HD钱包 (BIP44)
│   │   ├── Ethabi.php          # ABI编码器
│   │   ├── Keccak.php          # Keccak-256哈希
│   │   ├── Secp.php            # 椭圆曲线加密
│   │   └── Message.php         # 消息签名
│   └── trc20.json              # TRC20标准ABI定义（已移至TRC20目录）
├── tests/                      # 测试目录
├── composer.json               # Composer配置
├── NEW_ARCHITECTURE_REFERENCE.md  # 本架构文档
└── README.md                   # 项目说明
```

## 🏗️ 核心模块结构

```
TronWeb (主入口)
├── trx        → 交易和区块操作（25个方法）
├── account    → 账户管理（35个方法）
├── contract   → 智能合约（5个方法）
├── token      → 代币操作（10个方法）
├── resource   → 资源管理（8个方法）
├── network    → 网络信息（8个方法）
└── utils      → 工具函数（20+方法）
```

## 🎯 快速开始

```php
use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

$tronWeb = new TronWeb([
    'fullNode' => new HttpProvider('https://api.trongrid.io'),
    'privateKey' => '您的私钥',
    'defaultAddress' => '您的地址'
]);

// 查询余额
$balance = $tronWeb->trx->getBalance('地址', true);

// 发送TRX
$result = $tronWeb->trx->send('接收地址', 100.5);

// 生成账户
$account = $tronWeb->account->create();
```

## 📊 模块方法统计

| 模块 | 方法数量 | 主要功能 |
|------|----------|----------|
| **Trx** | 40+ | 交易、区块、签名、广播、消息签名 |
| **Account** | 35 | 地址生成、验证、助记词 |
| **Contract** | 5 | 智能合约部署调用 |
| **Token** | 13 | 代币创建和管理、增强查询 |
| **Resource** | 15 | 带宽能量管理、V2资源委托 |
| **Network** | 13 | 网络节点信息、提案管理、交易所 |
| **Utils** | 22 | 转换和验证工具 |

## ✨ 新增高级功能

基于 TypeScript TronWeb 库移植的高级功能，为 PHP 版本带来了更强大的企业级能力。

### 🎯 V2 资源委托系统 (Resource 模块)

新增7个精细化资源管理方法，支持更灵活的资源委托和查询：

```php
// V2 资源委托查询
$delegatedResource = $tronWeb->resource->getDelegatedResourceV2(
    $fromAddress,  // 委托方地址
    $toAddress,    // 接收方地址
    ['confirmed' => true]
);

// 获取委托资源账户索引
$accountIndex = $tronWeb->resource->getDelegatedResourceAccountIndexV2(
    $address,
    ['confirmed' => true]
);

// 查询可委托的最大资源数量
$maxSize = $tronWeb->resource->getCanDelegatedMaxSize(
    $address,
    'BANDWIDTH',
    ['confirmed' => true]
);

// 获取可用解冻次数
$unfreezeCount = $tronWeb->resource->getAvailableUnfreezeCount(
    $address,
    ['confirmed' => true]
);

// 获取可提取的解冻金额
$withdrawAmount = $tronWeb->resource->getCanWithdrawUnfreezeAmount(
    $address,
    $timestamp,
    ['confirmed' => true]
);

// 查询带宽和能量价格
$bandwidthPrice = $tronWeb->resource->getBandwidthPrices();
$energyPrice = $tronWeb->resource->getEnergyPrices();
```

### 💰 奖励和佣金系统 (Trx 模块)

新增4个奖励和佣金查询方法：

```php
// 获取已确认的奖励信息
$reward = $tronWeb->trx->getReward($address, ['confirmed' => true]);

// 获取未确认的奖励信息
$unconfirmedReward = $tronWeb->trx->getUnconfirmedReward($address);

// 获取已确认的佣金信息
$brokerage = $tronWeb->trx->getBrokerage($address, ['confirmed' => true]);

// 获取未确认的佣金信息
$unconfirmedBrokerage = $tronWeb->trx->getUnconfirmedBrokerage($address);
```

### 🖥️ 节点信息监控 (Trx 模块)

新增2个节点监控方法：

```php
// 获取详细节点信息
$nodeInfo = $tronWeb->trx->getNodeInfo();

// 获取智能合约交易的区块参考参数
$refBlockParams = $tronWeb->trx->getCurrentRefBlockParams();
```

**节点信息包含:** 同步状态、连接统计、对等节点列表、节点配置、机器运行信息等。

### 🗳️ 提案管理系统 (Network 模块)

新增3个提案管理方法：

```php
// 通过ID获取提案详情
$proposal = $tronWeb->network->getProposal($proposalID);

// 列出所有网络提案
$proposals = $tronWeb->network->listProposals();

// 获取可用的提案参数
$proposalParams = $tronWeb->network->getProposalParameters();
```

### 🏦 交易所接口 (Network 模块)

新增2个交易所查询方法：

```php
// 通过ID获取交易所信息
$exchange = $tronWeb->network->getExchangeByID($exchangeID);

// 分页获取交易所列表
$exchanges = $tronWeb->network->listExchangesPaginated($limit = 10, $offset = 0);
```

### 🪙 代币增强功能 (Token 模块)

新增3个代币查询增强方法：

```php
// 根据名称列表获取代币信息
$tokens = $tronWeb->token->getTokenListByName(['USDT', 'TRX']);

// 通过ID获取代币信息
$token = $tronWeb->token->getTokenFromID($tokenId);

// 获取地址发行的所有代币详情
$issuedTokens = $tronWeb->token->getTokensIssuedByAddress($address);
```

### ✍️ 消息签名系统 (Trx 模块 + Message 支持类)

新增4个消息签名和验证方法：

```php
// 验证消息签名
$isValid = $tronWeb->trx->verifyMessage($message, $signature, $address, $useTronHeader);

// 验证签名（兼容TypeScript版本）
$isValid = $tronWeb->trx->verifySignature($messageHex, $address, $signature, $useTronHeader);

// V2版本消息验证
$recoveredAddress = $tronWeb->trx->verifyMessageV2($message, $signature);

// 签名消息
$signature = $tronWeb->trx->signMessage($message, $privateKey, $useTronHeader);
```

### 📊 交易增强功能 (Trx 模块)

新增5个交易查询增强方法：

```php
// 获取已确认的交易信息
$confirmedTx = $tronWeb->trx->getConfirmedTransaction($transactionID);

// 获取未确认的账户信息
$unconfirmedAccount = $tronWeb->trx->getUnconfirmedAccount($address);

// 获取未确认的余额
$unconfirmedBalance = $tronWeb->trx->getUnconfirmedBalance($address);

// 获取带宽详细信息
$bandwidth = $tronWeb->trx->getBandwidth($address);

// 获取账户资源信息
$resources = $tronWeb->trx->getAccountResources($address);
```

### 🔧 新增支持类

**Message.php** - TRON消息签名工具类：

```php
use Dsdcr\TronWeb\Support\Message;

$signature = Message::signMessage($message, $privateKey, $useTronHeader);
$address = Message::verifyMessage($message, $signature, $useTronHeader);
```

**Secp256k1 增强** - 新增地址生成方法：

```php
$addressHex = $secp->getAddressFromPublicKey($publicKey);
```

### 📈 移植统计

| 功能类别 | 新增方法数 | 主要模块 |
|---------|-----------|----------|
| V2资源委托系统 | 7 | Resource |
| 奖励和佣金系统 | 4 | Trx |
| 节点信息监控 | 2 | Trx |
| 提案管理系统 | 3 | Network |
| 交易所接口 | 2 | Network |
| 代币增强功能 | 3 | Token |
| 消息签名系统 | 4 | Trx + Message |
| 交易增强功能 | 5 | Trx |
| **总计** | **30+** | - |

### 🎯 使用建议

1. **V2资源委托**: 企业级应用推荐使用 V2 方法获得更精细的控制
2. **奖励查询**: 使用 `getReward()` 和 `getBrokerage()` 获取实时奖励信息
3. **节点监控**: 使用 `getNodeInfo()` 监控节点健康状态
4. **治理参与**: 使用提案管理方法参与网络治理
5. **智能合约**: 使用 `getCurrentRefBlockParams()` 构建合规的智能合约交易
6. **消息签名**: 使用消息签名系统实现安全的身份验证和授权

## 🔧 核心功能示例

### 1. 账户管理
```php
// 生成助记词账户
$mnemonic = $tronWeb->account->generateMnemonic(12);
$account = $tronWeb->account->generateAccountWithMnemonic($mnemonic);

// 批量查询余额
$balances = $tronWeb->account->getBalances(['地址1', '地址2'], true);
```

### 2. 交易操作
```php
// 批量发送
$results = $tronWeb->trx->sendToMultiple([
    ['地址1', 10.5],
    ['地址2', 5.2]
]);

// 多重签名
$signWeight = $tronWeb->trx->getSignWeight($transaction);
```

### 3. 代币管理
```php
// 创建TRC10代币
$result = $tronWeb->token->createToken([
    'name' => 'MyToken',
    'abbr' => 'MTK',
    'total_supply' => 1000000
]);

// 发送代币
$result = $tronWeb->token->send('接收地址', 100, '代币ID');
```

### 4. 资源管理
```php
// 冻结资源获取带宽
$result = $tronWeb->resource->freeze(100, 3, 'BANDWIDTH');

// 查询资源信息
$resources = $tronWeb->resource->getResources();
```

## ✅ 向后兼容性

所有旧方法都保留了别名：
```php
// 新旧方法都可使用
$tron->getBalance()        → $tronWeb->trx->getBalance()
$tron->send()              → $tronWeb->trx->send() 
$tron->getAccount()        → $tronWeb->account->getAccount()
```

## 🚀 性能优势

1. **模块懒加载** - 按需初始化模块
2. **类型安全** - 完整的类型声明
3. **错误处理** - 统一的异常机制
4. **自动加载** - 优化的PSR-4自动加载

## 📝 完整方法列表

### ??️ Utils 工具模块 (详细方法列表)

**单位转换方法:**
- `toSun(float $trx): int` - TRX转SUN (1 TRX = 1,000,000 SUN)
- `fromSun(int $sun): float` - SUN转TRX  
- `toTron(float $trx): int` - toSun的别名
- `fromTron(int $sun): float` - fromSun的别名

**地址相关方法:**
- `addressToHex(string $address): string` - Base58地址转Hex
- `hexToAddress(string $hexAddress): string` - Hex地址转Base58
- `isValidTronAddress(string $address): bool` - 验证TRON地址格式
- `getBase58CheckAddress(string $addressBin): string` - 二进制地址转Base58
- `getAddressHex(string $pubKeyBin): string` - 公钥二进制转地址Hex
- `address2HexString(string $address): string` - addressToHex的别名
- `hexString2Address(string $hexAddress): string` - hexToAddress的别名

**格式验证方法:**
- `isHex(string $hex): bool` - 验证是否为十六进制字符串
- `isValidBlockIdentifier($block): bool` - 验证区块标识符
- `isNegative(string $value): bool` - 检查是否为负数

**字符串编码方法:**
- `stringToHex(string $str): string` - 字符串转十六进制
- `hexToString(string $hex): string` - 十六进制转字符串
- `toUtf8(string $str): string` - 字符串转UTF-8十六进制
- `fromUtf8(string $hex): string` - UTF-8十六进制转字符串

**数值处理方法:**
- `formatTrx(float $amount, int $decimals = 6): string` - 格式化TRX金额
- `microToSeconds(int $microTimestamp): int` - 微秒转秒
- `secondsToMicro(int $secondsTimestamp): int` - 秒转微秒

**加密安全方法:**
- `randomHex(int $length = 64): string` - 生成加密安全随机十六进制

**使用示例:**
```php
// 单位转换
$sun = $tronWeb->utils->toSun(1.5);       // 1500000
$trx = $tronWeb->utils->fromSun(1500000); // 1.5

// 地址转换
$hex = $tronWeb->utils->addressToHex('TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL');
$base58 = $tronWeb->utils->hexToAddress('4142c5f56c5d9f6dc935776d86e5e28c71a34d1dbe');

// 字符串编码
$hex = $tronWeb->utils->stringToHex('hello'); // "68656c6c6f"
$str = $tronWeb->utils->hexToString('68656c6c6f'); // "hello"

// 格式验证
$isValid = $tronWeb->utils->isValidTronAddress('TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL');
$isHex = $tronWeb->utils->isHex('4142c5f5');
```

### Trx 模块 (25个方法)
- `getBalance()`, `send()`, `signTransaction()`, `sendRawTransaction()`
- `getCurrentBlock()`, `getBlock()`, `getBlockByHash()`, `getBlockByNumber()`
- `getTransaction()`, `getTransactionInfo()`, `getTransactionCount()`
- `getTransactionFromBlock()`, `getBlockRange()`, `getLatestBlocks()`
- `sendTransaction()`, `sendTrx()`, `getBlockTransactionCount()`
- `getAccountInfo()`, `multiSign()`, `getSignWeight()`, `getApprovedList()`
- `getDelegatedResource()`, `getChainParameters()`, `getRewardInfo()`
- `sendToMultiple()` - 批量发送

### Account 模块 (35个方法)
- `create()`, `generateAddress()`, `getInfo()`, `validate()`, `isValidAddress()`
- `toHex()`, `toBase58()`, `getResources()`, `getBandwidth()`, `getTokenBalance()`
- `getTransactions()`, `getAccount()`, `validateAddress()`, `changeName()`
- `register()`, `changeAccountName()`, `registerAccount()`, `isAddress()`
- `createWithPrivateKey()`, `privateKeyToPublicKey()`, `publicKeyToAddress()`
- `isValidPrivateKey()`, `isValidPublicKey()`, `recoverAddressFromPrivateKey()`
- `generateRandom()`, `generateAccount()`, `generateMnemonic()`
- `generateAccountWithMnemonic()`, `validateMnemonic()`, `mnemonicToSeed()`
- `isPrivateKeyFromMnemonic()`, `findPrivateKeyDerivationPath()`
- `getBalances()` - 批量查询余额

### Contract 模块 (5个方法)
- `trc20()`, `deploy()`, `getEvents()`, `getEventsByTransaction()`, `getInfo()`

### Token 模块 (10个方法)  
- `send()`, `sendToken()`, `sendTransaction()`, `createToken()`, `purchaseToken()`
- `updateToken()`, `getIssuedByAddress()`, `getFromName()`, `getById()`, `list()`

### Resource 模块 (8个方法)
- `freeze()`, `freezeBalance()`, `unfreeze()`, `unfreezeBalance()`
- `withdrawRewards()`, `withdrawBlockRewards()`, `getResources()`, `getFrozenBalance()`

### Network 模块 (8个方法)
- `listNodes()`, `listSuperRepresentatives()`, `listExchanges()`
- `applyForRepresentative()`, `applyForSuperRepresentative()`
- `timeUntilNextVoteCycle()`, `getVoteRewardRatio()`, `getChainParameters()`

### Utils 模块 (20+方法)
- 单位转换: `toSun()`, `fromSun()`, `toTron()`, `fromTron()`
- 地址转换: `addressToHex()`, `hexToAddress()`
- 格式验证: `isValidTronAddress()`, `isValidBlockIdentifier()`
- 字符串处理: `stringToHex()`, `hexToString()`, `toUtf8()`, `fromUtf8()`

### TRC20Contract 模块 (TRC20专用模块)

TRC20Contract位于`src/Modules/TRC20/TRC20Contract.php`，用于与TRC20代币智能合约进行交互。

```php
// 通过Contract模块创建TRC20合约实例
$trc20 = $tronWeb->contract->trc20('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');

// 获取代币信息
$name = $trc20->name();
$symbol = $trc20->symbol();
$decimals = $trc20->decimals();
$totalSupply = $trc20->totalSupply();

// 查询余额
$balance = $trc20->balanceOf('地址', true);  // true返回缩放后的值

// 发送TRC20代币
$result = $trc20->transfer('接收地址', '100');

// 获取交易记录
$transactions = $trc20->getTransactions('地址', 100);

// 获取合约交易信息
$txInfo = $trc20->getTransactionInfoByContract(['limit' => 10]);

// 设置费用限制
$trc20->setFeeLimit(100);  // TRX单位
```

**TRC20Contract方法列表：**
- `name()` - 获取代币名称
- `symbol()` - 获取代币符号
- `decimals()` - 获取小数位数
- `totalSupply()` - 获取总供应量
- `balanceOf()` - 查询地址余额
- `transfer()` - 发送代币
- `getTransactions()` - 获取交易记录
- `getTransactionInfoByContract()` - 获取合约交易信息
- `getTRC20TokenHolderBalance()` - 获取代币持有者余额
- `getTransaction()` - 查找交易信息
- `setFeeLimit()` - 设置费用限制
- `getFeeLimit()` - 获取当前费用限制
- `clearCached()` - 清除缓存数据
- `array()` - 获取所有代币数据
- `__debugInfo()` - 调试信息

## 🎯 总方法统计

### 📊 模块方法汇总表

| 模块 | 方法数量 | 文件位置 | 主要功能 |
|------|----------|----------|----------|
| **Trx** | 40+ | `src/Modules/Trx.php` | 交易、区块、签名、广播、消息签名 |
| **Account** | 35 | `src/Modules/Account.php` | 地址、助记词、密钥管理 |
| **Contract** | 5 | `src/Modules/Contract.php` | 智能合约部署调用 |
| **Token** | 13 | `src/Modules/Token.php` | 代币创建和管理、增强查询 |
| **Resource** | 15 | `src/Modules/Resource.php` | 带宽能量冻结解冻、V2资源委托 |
| **Network** | 13 | `src/Modules/Network.php` | 网络节点、提案管理、交易所 |
| **Utils** | 22 | `src/Support/TronUtils.php` | 转换验证工具函数 |
| **Support** | 25+ | `src/Support/` | 加密、编码、签名工具 |
| **总计** | **140+** | - | **完整TRON功能覆盖** |

### 🔧 支持类库说明

**加密相关:**
- `Bip39.php` - BIP39助记词标准实现
- `HdWallet.php` - HD钱包 (BIP32/BIP44) 派生
- `Secp.php` - 椭圆曲线secp256k1加密
- `Keccak.php` - Keccak-256哈希算法

**编码相关:**
- `Base58.php` - Base58编码/解码
- `Base58Check.php` - 带校验的Base58
- `BytesHelper.php` - 字节数组操作

**异常处理:**
- `TronException.php` - TRON专用异常
- `ErrorException.php` - 错误异常

## 🚀 部署和生产就绪

### 环境要求
- PHP >= 8.0
- 扩展: bcmath, gmp, mbstring, openssl
- Composer 依赖管理

### 安装使用
```bash
composer require dsdcr/tronweb
```


新架构提供了超过110个完整的方法，覆盖了TRON区块链的所有核心功能，同时保持了优秀的性能和可维护性。
