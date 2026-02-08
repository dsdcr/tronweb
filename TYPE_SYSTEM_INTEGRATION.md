# TronWeb 类型系统完整文档

本文档详细介绍了 TronWeb 中集成的类型系统，包括完整的文件结构、所有类的方法列表、使用方法和最佳实践。

## 目录
- [1. 概述](#1-概述)
- [2. 完整文件结构](#2-完整文件结构)
- [3. 核心模块与方法](#3-核心模块与方法)
  - [3.1 TronWeb 主类](#31-tronweb-主类)
  - [3.2 Account 模块](#32-account-模块)
  - [3.3 Trx 模块](#33-trx-模块)
  - [3.4 Token 模块](#34-token-模块)
  - [3.5 Contract 模块](#35-contract-模块)
  - [3.6 Resource 模块](#36-resource-模块)
  - [3.7 Network 模块](#37-network-模块)
  - [3.8 TransactionBuilder 模块](#38-transactionbuilder-模块)
- [4. 合约系统](#4-合约系统)
  - [4.1 ContractInstance](#41-contractinstance)
  - [4.2 ContractMethod](#42-contractmethod)
- [5. Provider 系统](#5-provider-系统)
  - [5.1 HttpProvider](#51-httpprovider)
  - [5.2 TronManager](#52-tronmanager)
- [6. 实体类](#6-实体类)
  - [6.1 TronAddress](#61-tronaddress)
- [7. 支持工具类](#7-支持工具类)
  - [7.1 TronUtils](#71-tronutils)
  - [7.2 加密相关](#72-加密相关)
  - [7.3 ABI 编码](#73-abi-编码)
  - [7.4 交易助手](#74-交易助手)
  - [7.5 助记词钱包](#75-助记词钱包)
- [8. 使用示例](#8-使用示例)

---

## 1. 概述

TronWeb 类型系统是一套为提高代码健壮性和开发效率而设计的类型化解决方案。它基于 PHP 的面向对象特性，实现了类似于 TypeScript 的类型安全机制，使开发者能够更清晰地管理和操作 Tron 网络中的各种实体（如地址、账户、交易等）。

### 1.1 设计理念

#### 类型安全
通过为不同的数据结构创建专用类，确保在运行时不会出现类型错误。例如，地址对象明确区分 hex 和 base58 格式，并提供验证方法。

#### 向后兼容
新引入的类型系统完全兼容现有代码，允许开发者逐步迁移至类型化接口，而无需重写整个项目。

#### TypeScript 风格
提供与 TypeScript 兼容的方法命名和调用模式，降低前端开发者学习成本。

---

## 2. 完整文件结构

```
vendor/dsdcr/tronweb/
├── src/
│   ├── TronWeb.php                          # 主入口类
│   ├── Concerns/
│   │   └── ManagesTronscan.php             # Tronscan 管理功能
│   ├── Entities/
│   │   └── TronAddress.php                  # 地址实体类
│   ├── Exception/
│   │   ├── ErrorException.php               # 错误异常
│   │   ├── NotFoundException.php             # 未找到异常
│   │   └── TronException.php               # Tron 异常基类
│   ├── Modules/
│   │   ├── Account.php                     # 账户管理模块
│   │   ├── BaseModule.php                  # 模块基类
│   │   ├── Contract/
│   │   │   ├── ContractInstance.php          # 合约实例
│   │   │   └── ContractMethod.php            # 合约方法
│   │   ├── Contract.php                   # 合约操作模块
│   │   ├── Network.php                    # 网络信息模块
│   │   ├── Resource.php                   # 资源管理模块
│   │   ├── Token.php                      # 代币管理模块
│   │   ├── TransactionBuilder.php           # 交易构建器
│   │   └── Trx.php                       # TRX 操作模块
│   ├── Provider/
│   │   ├── HttpProvider.php                # HTTP 提供者
│   │   ├── HttpProviderInterface.php        # HTTP 提供者接口
│   │   └── TronManager.php                # Provider 管理器
│   └── Support/
│       ├── AbiEncoder.php                  # ABI 编码器
│       ├── Base58.php                      # Base58 编码
│       ├── Base58Check.php                 # Base58Check 编码
│       ├── BigInteger.php                  # 大整数处理
│       ├── Bip39.php                       # BIP39 助记词
│       ├── Crypto.php                      # 加密工具
│       ├── Ethabi.php                      # 以太坊 ABI
│       ├── Hash.php                        # 哈希函数
│       ├── HdWallet.php                    # HD 钱包
│       ├── Keccak.php                      # Keccak 哈希
│       ├── Message.php                     # 消息签名
│       ├── Secp.php                        # SECP 签名（兼容）
│       ├── Secp256k1.php                   # SECP256k1 签名
│       ├── Signature.php                   # 签名对象
│       ├── TronUtils.php                   # Tron 工具类
│       └── TransactionHelper.php            # 交易助手
├── examples/
│   ├── account_examples.php               # 账户示例
│   ├── contract_examples.php              # 合约示例
│   ├── dsdcr_tronweb_introduction.md      # 介绍文档
│   ├── network_examples.php                # 网络示例
│   ├── resource_examples.php              # 资源示例
│   ├── run_all_examples.php               # 运行所有示例
│   ├── token_examples.php                 # 代币示例
│   ├── tronweb_basic.php                 # 基础示例
│   ├── TronWebTest.php                   # 测试文件
│   ├── trx_examples.php                   # TRX 示例
│   └── utils_examples.php                 # 工具示例
└── tests/
    ├── AccountBalanceTest.php               # 账户余额测试
    ├── account_examples.php                # 账户示例
    ├── contract_examples.php                # 合约示例
    ├── network_examples.php                 # 网络示例
    ├── README.md                           # 测试说明
    ├── resource_examples.php                # 资源示例
    ├── token_examples.php                   # 代币示例
    ├── trx_examples.php                     # TRX 示例
    └── utils_examples.php                   # 工具示例
```

---

## 3. 核心模块与方法

### 3.1 TronWeb 主类

**文件**: `src/TronWeb.php`  
**行数**: 302 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `HttpProviderInterface $fullNode`, `?HttpProviderInterface $solidityNode`, `?HttpProviderInterface $eventServer`, `?HttpProviderInterface $signServer`, `?HttpProviderInterface $explorer`, `?string $privateKey` | - | 创建新的 TronWeb 实例 |
| `setManager` | `TronManager $manager` | `void` | 设置 Provider 管理器 |
| `getManager` | - | `TronManager` | 获取 Provider 管理器 |
| `contract` | `string $contractAddress`, `?string $abi` | `ContractInstance` | 创建合约实例 |
| `setPrivateKey` | `string $privateKey` | `void` | 设置用于交易签名的私钥 |
| `getPrivateKey` | - | `?string` | 获取私钥 |
| `setAddress` | `string $address` | `void` | 设置操作用的默认地址 |
| `getAddress` | - | `?array` | 获取默认地址 |
| `providers` | - | `array` | 获取 Provider 列表 |
| `isConnected` | - | `array` | 检查连接状态 |
| `request` | `string $endpoint`, `array $params`, `string $method` | `array` | 基础查询方法，自动路由到正确的节点 |
| `toHex` | `string $address` | `string` | 将地址转换为十六进制 |
| `fromHex` | `string $addressHex` | `string` | 将十六进制转换为地址 |

---

### 3.2 Account 模块

**文件**: `src/Modules/Account.php`  
**行数**: 671 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `create` | - | `TronAddress` | 生成新的随机账户 |
| `getAccount` | `?string $address` | `array` | 查询账户详细信息 |
| `validateAddress` | `string $address`, `bool $hex` | `array` | 验证 Tron 地址的有效性 |
| `getAccountresource` | `?string $address` | `array` | 查询账户资源详情 |
| `getTokenBalance` | `int $tokenId`, `?string $address`, `bool $fromSun` | `float` | 查询指定代币的余额 |
| `getTransactions` | `string $address`, `string $direction`, `int $limit`, `int $offset` | `array` | 查询账户的交易历史 |
| `changeName` | `string $accountName`, `?string $address` | `array` | 更新账户名称 |
| `register` | `string $newAccountAddress`, `?string $address` | `array` | 创建新账户 |
| `createWithPrivateKey` | `string $privateKey` | `TronAddress` | 通过私钥创建账户对象 |
| `fromPrivateKey` | `string $privateKey`, `string $format` | `string` | 从私钥获取地址 |
| `generateAccountWithMnemonic` | `?string $mnemonic`, `string $path`, `string $passphrase`, `int $wordCount` | `array` | 通过助记词生成账户（BIP39/BIP44标准） |
| `getBalances` | `array $accounts`, `bool $fromSun`, `bool $validate` | `array` | 批量查询账户余额 |

---

### 3.3 Trx 模块

**文件**: `src/Modules/Trx.php`  
**行数**: 1339 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `getTransactionBuilder` | - | `TransactionBuilder` | 获取 TransactionBuilder 实例 |
| `getBalance` | `?string $address`, `bool $fromSun` | `float` | 查询指定地址的 TRX 余额 |
| `send` | `string $to`, `float $amount`, `array $options` | `array` | 发送 TRX 交易到指定地址 |
| `signTransaction` | `array $transaction` | `array` | 对交易进行数字签名 |
| `sendRawTransaction` | `array $signedTransaction` | `array` | 广播已签名的交易到 Tron 网络 |
| `getCurrentBlock` | - | `array` | 获取当前最新区块的完整信息 |
| `getBlock` | `mixed $block` | `array` | 获取指定区块的完整信息 |
| `getBlockByHash` | `string $blockHash` | `array` | 通过区块哈希获取区块 |
| `getBlockByNumber` | `int $blockID` | `array` | 通过区块号获取区块 |
| `getTransaction` | `string $transactionID` | `array` | 通过交易哈希 ID 获取交易详情 |
| `getTransactionInfo` | `string $transactionID` | `array` | 获取交易的执行信息和状态详情 |
| `getConfirmedTransaction` | `string $transactionID` | `array` | 获取已确认的交易信息 |
| `getUnconfirmedAccount` | `?string $address` | `array` | 获取未确认的账户信息 |
| `getUnconfirmedBalance` | `?string $address` | `float` | 获取未确认的余额 |
| `getBandwidth` | `?string $address` | `array` | 获取带宽信息 |
| `getTransactionsFromBlock` | `mixed $block` | `array` | 获取区块中的交易 |
| `getTransactionFromBlock` | `mixed $block`, `int $index` | `array` | 获取区块中的指定交易 |
| `getBlockRange` | `int $start`, `int $end` | `array` | 获取区块范围内的交易 |
| `getLatestBlocks` | `int $limit` | `array` | 获取最新区块 |
| `sendTrx` | `string $to`, `float $amount`, `array $options` | `array` | sendTrx 方法（send 的别名） |
| `getBlockTransactionCount` | `mixed $block` | `int` | 获取区块交易数量 |
| `getAccountInfo` | `string $addressHex` | `array` | 通过十六进制地址获取账户信息 |
| `multiSign` | `array $transaction`, `?string $privateKey`, `int $permissionId` | `array` | 多签交易方法 |
| `getSignWeight` | `array $transaction`, `array $options` | `array` | 获取交易的签名权重 |
| `getApprovedList` | `array $transaction`, `array $options` | `array` | 获取交易的批准列表 |
| `getDelegatedResource` | `?string $fromAddress`, `?string $toAddress`, `array $options` | `array` | 获取委托资源信息 |
| `getChainParameters` | - | `array` | 获取链参数 |
| `getRewardInfo` | `?string $address` | `array` | 获取奖励信息 |
| `sendToMultiple` | `array $recipients`, `?string $from`, `bool $validate` | `array` | 一次性发送 TRX 给多个接收者 |
| `getReward` | `?string $address`, `array $options` | `array` | 获取奖励信息（已确认） |
| `getUnconfirmedReward` | `?string $address`, `array $options` | `array` | 获取未确认的奖励信息 |
| `getBrokerage` | `?string $address`, `array $options` | `array` | 获取佣金信息（已确认） |
| `getUnconfirmedBrokerage` | `?string $address`, `array $options` | `array` | 获取未确认的佣金信息 |
| `_getReward` | `?string $address`, `array $options` | `array` | 内部方法：获取奖励信息 |
| `_getBrokerage` | `?string $address`, `array $options` | `array` | 内部方法：获取佣金信息 |
| `getNodeInfo` | - | `array` | 获取节点信息 |
| `verifyMessage` | `array|string $message`, `string $signature`, `?string $address`, `bool $useTronHeader` | `bool` | 验证消息签名的有效性 |
| `verifySignature` | `string $messageHex`, `string $address`, `string $signature`, `bool $useTronHeader` | `bool` | 验证消息签名 |
| `verifyMessageV2` | `array|string $message`, `string $signature` | `string` | 验证消息签名并恢复签名者地址（V2版本） |
| `signMessage` | `string $message`, `?string $privateKey`, `bool $useTronHeader` | `string` | 签名消息方法 |
| `getAccountResources` | `?string $address` | `array` | getAccountResources 方法 |
| `freezeBalance` | `int $amount`, `int $duration`, `string $resource`, `array $options`, `?string $receiverAddress` | `array` | freezeBalance 方法 |
| `unfreezeBalance` | `string $resource`, `array $options`, `?string $receiverAddress` | `array` | unfreezeBalance 方法 |
| `getTokenFromID` | `mixed $tokenID` | `array` | getTokenFromID 方法 |
| `getCurrentRefBlockParams` | - | `array` | 获取当前区块参考参数 |

---

### 3.4 Token 模块

**文件**: `src/Modules/Token.php`  
**行数**: 599 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `send` | `string $to`, `float $amount`, `string $tokenId`, `?string $from` | `array` | 发送代币（TRC10 标准） |
| `sendToken` | `string $to`, `float $amount`, `string $tokenID`, `array $options` | `array` | 发送代币（选项模式） |
| `sendTransaction` | `string $to`, `float $amount`, `?string $tokenID`, `?string $from` | `array` | 发送代币交易（SUN 单位） |
| `createToken` | `array $tokenOptions` | `array` | 创建 TRC10 代币 |
| `purchaseToken` | `string $issuerAddress`, `string $tokenID`, `float $amount`, `?string $buyer` | `array` | 购买代币（参与发行） |
| `updateToken` | `string $description`, `string $url`, `int $freeBandwidth`, `int $freeBandwidthLimit`, `?string $ownerAddress` | `array` | 更新代币信息 |
| `getIssuedByAddress` | `?string $address` | `array` | 查询地址发行的代币 |
| `getFromName` | `string $tokenName` | `array` | 通过代币名称查询 |
| `getById` | `string $tokenId` | `array` | 通过代币 ID 查询 |
| `list` | `int $limit`, `int $offset` | `array` | 获取代币列表 |
| `getTokenListByName` | `array|string $tokenNames` | `array` | 批量查询代币信息 |
| `getTokenFromID` | `mixed $tokenId` | `array` | 通过 ID 获取代币信息 |
| `getTokensIssuedByAddress` | `?string $address` | `array` | 查询地址发行的代币详情 |

---

### 3.5 Contract 模块

**文件**: `src/Modules/Contract.php`  
**行数**: 343 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `contract` | `$abi`, `?string $address` | `ContractInstance` | 创建智能合约实例 |
| `deploy` | `string $abi`, `string $bytecode`, `int $feeLimit`, `string $address`, `int $callValue`, `int $bandwidthLimit` | `array` | 部署智能合约到 Tron 网络 |
| `isPayableContract` | `string $abi` | `bool` | 检查合约构造函数是否可接收 TRX 支付（内部方法） |
| `getEvents` | `string $contractAddress`, `int $sinceTimestamp`, `?string $eventName`, `int $blockNumber` | `array` | 查询智能合约的事件日志 |
| `getEventsByTransaction` | `string $transactionID` | `array` | 通过交易 ID 查询该交易触发的事件日志 |
| `getInfo` | `string $contractAddress` | `array` | 查询已部署合约的详细信息 |

---

### 3.6 Resource 模块

**文件**: `src/Modules/Resource.php`  
**行数**: 596 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `freeze` | `float $amount`, `int $duration`, `string $resource`, `?string $ownerAddress` | `array` | 冻结 TRX 获取带宽或能量资源 |
| `unfreeze` | `string $resource`, `?string $ownerAddress` | `array` | 解冻已冻结的资源 |
| `withdrawRewards` | `?string $ownerAddress` | `array` | 提取超级代表区块奖励 |
| `getResources` | `?string $address` | `array` | 查询账户完整资源信息 |
| `getFrozenBalance` | `?string $address` | `array` | 查询账户冻结资源详情 |
| `getDelegatedResourceV2` | `?string $fromAddress`, `?string $toAddress`, `array $options` | `array` | 查询资源委托详情（V2 版本） |
| `getDelegatedResourceAccountIndexV2` | `?string $address`, `array $options` | `array` | 查询资源委托账户索引（V2 版本） |
| `getCanDelegatedMaxSize` | `?string $address`, `string $resource`, `array $options` | `array` | 查询可委托的最大资源量 |
| `getAvailableUnfreezeCount` | `?string $address`, `array $options` | `array` | 查询可用解冻次数 |
| `getCanWithdrawUnfreezeAmount` | `?string $address`, `int $timestamp`, `array $options` | `array` | 查询可提取的解冻金额 |
| `getBandwidthPrices` | - | `string` | 查询网络带宽价格 |
| `getEnergyPrices` | - | `string` | 查询网络能量价格 |

---

### 3.7 Network 模块

**文件**: `src/Modules/Network.php`  
**行数**: 280 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `listNodes` | - | `array` | 获取 Tron 网络中的所有节点列表 |
| `listwitnesses` | - | `array` | 获取 Tron 网络中所有超级代表（SR）列表 |
| `getexchangelist` | - | `array` | 获取所有已注册的交易对信息 |
| `applyForSuperRepresentative` | `string $url`, `?string $address` | `array` | 申请成为超级代表（SR 候选人） |
| `getnextmaintenancetime` | - | `float` | 获取距离下次维护周期的时间 |
| `getrewardinfo` | - | `array` | 获取当前投票奖励分配比例信息 |
| `getChainParameters` | - | `array` | 获取区块链网络参数列表 |
| `getNetworkStats` | - | `array` | 获取当前网络综合统计信息 |
| `getBlockRewardInfo` | - | `array` | 获取区块奖励相关信息 |
| `getProposal` | `int $proposalID` | `array` | 根据提案 ID 获取提案详细信息 |
| `listProposals` | - | `array` | 获取所有待投票的网络治理提案 |
| `getProposalParameters` | - | `array` | 获取可用于提案的网络参数列表 |
| `getParameterName` | `string $key` | `string` | 获取参数的中文名称（内部方法） |
| `getExchangeByID` | `int $exchangeID` | `array` | 根据交易所 ID 获取交易对详细信息 |
| `listExchangesPaginated` | `int $limit`, `int $offset` | `array` | 分页获取交易对列表 |

---

### 3.8 TransactionBuilder 模块

**文件**: `src/Modules/TransactionBuilder.php`  
**行数**: 1110 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `TronWeb $tronWeb` | - | 创建 TransactionBuilder 实例 |
| `sendTrx` | `string $to`, `float $amount`, `string $from`, `array $options` | `array` | 构建 TRX 转账交易 |
| `sendToken` | `string $to`, `string $tokenId`, `float|int $amount`, `string $from`, `array $options` | `array` | 构建代币转账交易（TRC10 标准） |
| `purchaseToken` | `string $issuerAddress`, `string $tokenID`, `int $amount`, `string $buyer` | `array` | 构建代币购买交易（参与代币发行） |
| `createToken` | `$options`, `$issuerAddress` | `array` | 创建新的 TRC10 代币 |
| `freezeBalance` | `int $amount`, `int $duration`, `string $resource`, `string $ownerAddress`, `string $receiverAddress`, `array $options` | `array` | 冻结 TRX 获取资源 |
| `unfreezeBalance` | `string $resource`, `string $ownerAddress`, `string $receiverAddress`, `array $options` | `array` | 解冻已冻结的资源 |
| `freezeBalanceV2` | `float|int $amount`, `string $resource`, `string $address`, `array $options` | `array` | 冻结 TRX 获取资源 V2 版本 |
| `unfreezeBalanceV2` | `float|int $amount`, `string $resource`, `string $address`, `array $options` | `array` | 解冻已冻结的资源 V2 版本 |
| `cancelUnfreezeBalanceV2` | `string $address`, `array $options` | `array` | 取消待解冻的 TRX（V2 版本） |
| `delegateResource` | `string $receiverAddress`, `float $amount`, `string $resource`, `string $address`, `bool $lock`, `?int $lockPeriod`, `array $options` | `array` | 委托资源给其他账户 |
| `undelegateResource` | `string $receiverAddress`, `float|int $amount`, `string $resource`, `string $address`, `array $options` | `array` | 取消委托资源 |
| `withdrawExpireUnfreeze` | `string|null $address`, `array $options` | `array` | 提取已到期的解冻金额 |
| `withdrawBlockRewards` | `$owner_address` | `array` | 提取超级代表区块奖励 |
| `updateToken` | `string $description`, `string $url`, `int $freeBandwidth`, `int $freeBandwidthLimit`, `$address` | `array` | 更新已发行代币的信息 |
| `updateEnergyLimit` | `string $contractAddress`, `int $originEnergyLimit`, `string $ownerAddress` | `array` | 更新合约的能量限制 |
| `updateSetting` | `string $contractAddress`, `int $userFeePercentage`, `string $ownerAddress` | `array` | 更新合约的资源费用设置 |
| `triggerSmartContract` | `array $abi`, `string $contract`, `string $function`, `array $params`, `int $feeLimit`, `string $address`, `int $callValue`, `int $bandwidthLimit` | `array` | 触发智能合约（写入操作） |
| `triggerConstantContract` | `array $abi`, `string $contract`, `string $function`, `array $params`, `string $address` | `array` | 调用智能合约的只读方法（Constant 操作） |

---

## 4. 合约系统

### 4.1 ContractInstance

**文件**: `src/Modules/Contract/ContractInstance.php`  
**行数**: 277 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `TronWeb $tronWeb`, `$abi`, `?string $address` | - | 创建合约实例 |
| `loadAbi` | `array $abi` | `void` | 加载 ABI 接口定义（内部方法） |
| `__call` | `string $name`, `array $arguments` | `mixed` | 动态调用合约方法 |
| `trigger` | `string $function`, `array $params`, `array $options` | `mixed` | 触发合约调用 |
| `triggerConstant` | `string $function`, `array $params`, `?string $fromAddress` | `mixed` | 触发只读合约调用 |
| `at` | `string $address` | `self` | 设置合约地址 |
| `setBytecode` | `string $bytecode` | `self` | 设置合约字节码 |
| `getAddress` | - | `?string` | 获取合约地址 |
| `isDeployed` | - | `bool` | 是否已部署 |
| `getAbi` | - | `array` | 获取 ABI |
| `getBytecode` | - | `?string` | 获取字节码 |
| `decodeInput` | `string $data` | `array` | 解码交易输入数据 |

---

### 4.2 ContractMethod

**文件**: `src/Modules/Contract/ContractMethod.php`  
**行数**: 292 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `ContractInstance $contract`, `array $abi` | - | 创建合约方法实例 |
| `call` | `...$arguments` | `mixed` | 调用合约方法 |
| `callConstant` | `...$arguments` | `mixed` | 调用只读合约方法（内部方法） |
| `callTransaction` | `...$arguments` | `mixed` | 调用交易合约方法（内部方法） |
| `validateAndPrepareParams` | `array $arguments` | `array` | 验证并准备参数（内部方法） |
| `convertParam` | `$value`, `string $type` | `mixed` | 转换参数到指定类型（内部方法） |
| `getName` | - | `string` | 获取方法名称 |
| `getType` | - | `string` | 获取方法类型 |
| `getInputs` | - | `array` | 获取输入参数定义 |
| `getOutputs` | - | `array` | 获取输出参数定义 |
| `getAbi` | - | `array` | 获取完整的 ABI 定义 |
| `getSignature` | - | `string` | 获取方法签名 |
| `getSelector` | - | `string` | 获取方法选择器 |

---

## 5. Provider 系统

### 5.1 HttpProvider

**文件**: `src/Provider/HttpProvider.php`  
**行数**: 480 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `$hostOrConfig`, `array $config` | - | 创建 HttpProvider 对象 |
| `initializeFromHost` | `string $host`, `array $config` | `void` | 从主机字符串初始化（向后兼容） |
| `initializeFromConfig` | `array $config` | `void` | 从配置数组初始化 |
| `validateConfiguration` | - | `void` | 验证配置参数（内部方法） |
| `createHttpClient` | - | `ClientInterface` | 创建 HTTP 客户端实例（内部方法） |
| `create` | `string $host`, `array $options` | `self` | 从主机 URL 创建 HttpProvider（工厂方法） |
| `fromConfig` | `array $config` | `self` | 从配置数组创建 HttpProvider（工厂方法） |
| `mainnet` | `bool $useSolidity`, `array $options` | `self` | 创建主网 HttpProvider |
| `testnet` | `bool $useSolidity`, `array $options` | `self` | 创建测试网 HttpProvider |
| `nile` | `bool $useSolidity`, `array $options` | `self` | 创建 Nile 测试网 HttpProvider |
| `isConnected` | - | `bool` | 检查连接到 Tron 网络的状态 |
| `getHost` | - | `string` | 获取主机 URL |
| `getTimeout` | - | `int` | 获取超时时间 |
| `getNodeType` | - | `string` | 获取节点类型 |
| `getHeaders` | - | `array` | 获取头部信息 |
| `getConnectTimeout` | - | `int` | 获取连接超时时间 |
| `getRetries` | - | `int` | 获取重试次数 |
| `setNodeType` | `string $nodeType` | `void` | 设置节点类型 |
| `setHeaders` | `array $headers` | `void` | 设置头部信息 |
| `addHeader` | `string $name`, `string $value` | `void` | 添加单个头部信息 |
| `setStatusPage` | `string $page` | `void` | 设置状态页面（向后兼容） |
| `request` | `string $url`, `array $payload`, `string $method` | `array` | 向服务器发送请求 |
| `decodeBody` | `StreamInterface $stream`, `int $status` | `array` | 将原始响应转换为数组（内部方法） |

---

### 5.2 TronManager

**文件**: `src/Provider/TronManager.php`  
**行数**: 271 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `TronWeb $tron`, `array $providers` | - | 初始化 TronManager，配置所有 Provider |
| `getProviders` | - | `array` | 获取所有 Providers |
| `fullNode` | - | `HttpProviderInterface` | 获取全节点 Provider |
| `getFullNode` | - | `HttpProviderInterface` | 获取全节点 Provider（兼容方法） |
| `solidityNode` | - | `HttpProviderInterface` | 获取固态节点 Provider |
| `signServer` | - | `HttpProviderInterface` | 获取签名服务器 Provider |
| `explorer` | - | `HttpProviderInterface` | 获取浏览器 Explorer Provider |
| `eventServer` | - | `HttpProviderInterface` | 获取事件服务器 Provider |
| `request` | `string $url`, `array $params`, `string $method` | `array` | 基础查询方法，自动路由到正确的节点 |
| `isConnected` | - | `array` | 检查所有节点的连接状态 |

---

## 6. 实体类

### 6.1 TronAddress

**文件**: `src/Entities/TronAddress.php`  
**行数**: 100 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `array $data` | - | 构造函数 |
| `getAddress` | `bool $is_base58` | `string` | 获取地址 |
| `getPublicKey` | - | `string` | 获取公钥 |
| `getPrivateKey` | - | `string` | 获取私钥 |
| `getRawData` | - | `array` | 获取结果数组 |
| `array_keys_exist` | `array $array`, `array $keys` | `bool` | 检查多个键（内部方法） |

---

## 7. 支持工具类

### 7.1 TronUtils

**文件**: `src/Support/TronUtils.php`  
**行数**: 347 行

#### 方法列表

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `isValidUrl` | `$url` | `bool` | 链接验证 |
| `isHex` | `$str` | `bool` | 检查字符串是否为十六进制表示 |
| `isArray` | `$array` | `bool` | 检查传递的参数是否是数组 |
| `toSun` | `float $trx` | `int` | 将 TRX 转换为 SUN（最小单位） |
| `fromSun` | `int $sun` | `float` | 将 SUN 转换为 TRX |
| `toSun` | `float $trx` | `int` | toSun 的别名（向后兼容） |
| `fromSun` | `int $sun` | `float` | fromSun 的别名（向后兼容） |
| `toHex` | `string $address` | `string` | 将 Tron 地址转换为十六进制格式 |
| `fromHex` | `string $hexAddress` | `string` | 将十六进制地址转换为 Tron base58 格式 |
| `isAddress` | `string $address` | `bool` | 验证 Tron 地址格式 |
| `getBase58CheckAddress` | `string $addressBin` | `string` | 从二进制地址数据获取 base58 校验地址 |
| `getAddressHex` | `string $pubKeyBin` | `string` | 从公钥二进制数据生成地址十六进制 |
| `formatTrx` | `float $amount`, `int $decimals` | `string` | 格式化 TRX 金额，指定精度 |
| `microToSeconds` | `int $microTimestamp` | `int` | 将微秒时间戳转换为秒 |
| `secondsToMicro` | `int $secondsTimestamp` | `int` | 将秒时间戳转换为微秒 |
| `randomHex` | `int $length` | `string` | 生成加密安全的随机十六进制字符串 |
| `isValidBlockIdentifier` | `$block` | `bool` | 检查值是否为有效的区块标识符 |
| `toHex` | `string $address` | `string` | toHex 的别名（向后兼容） |
| `fromHex` | `string $hexAddress` | `string` | fromHex 的别名（向后兼容） |
| `toUtf8` | `string $str` | `string` | 将字符串转换为 UTF-8 十六进制（别名） |
| `fromUtf8` | `string $hex` | `string` | 将十六进制转换为 UTF-8 字符串（别名） |
| `stringToHex` | `string $str` | `string` | 将字符串转换为十六进制表示 |
| `hexToString` | `string $hex` | `string` | 将十六进制表示转换为字符串 |

---

### 7.2 加密相关

#### Base58

**文件**: `src/Support/Base58.php`  
**行数**: 32 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `encode` | `$num`, `int $length` | `string` | Encodes passed whole string to base58 |
| `decode` | `string $addr`, `int $length` | `string` | Base58 decodes a large integer to a string |

#### Base58Check

**文件**: `src/Support/Base58Check.php`  
**行数**: 70 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `encode` | `string $string`, `int $prefix`, `bool $compressed` | `string` | Encode Base58Check |
| `decode` | `string $string`, `int $removeLeadingBytes`, `int $removeTrailingBytes`, `bool $removeCompression` | `bool|string` | Decoding from Base58Check |

#### Crypto

**文件**: `src/Support/Crypto.php`  
**行数**: 122 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `bc2bin` | `$num` | - | 将大数字转换为二进制 |
| `dec2base` | `string $dec`, `int $base`, `$digits` | `string` | 将十进制数字转换为指定进制 |
| `base2dec` | `string $value`, `int $base`, `$digits` | `string` | 将指定进制数字转换为十进制 |
| `digits` | `int $base` | `string` | 获取指定进制的字符集 |
| `bin2bc` | `$num` | `string` | 将二进制转换为大数字 |

#### Hash

**文件**: `src/Support/Hash.php`  
**行数**: 49 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `SHA256` | `$data`, `bool $raw` | `string` | 计算 SHA-256 哈希 |
| `sha256d` | `$data` | `string` | 计算双重 SHA-256 哈希（用于比特币地址生成） |
| `RIPEMD160` | `$data`, `bool $raw` | `string` | 计算 RIPEMD-160 哈希 |

#### Keccak

**文件**: `src/Support/Keccak.php`  
**行数**: 311 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `keccakf64` | `&$st`, `$rounds` | `void` | Keccak-f64 位运算（内部方法） |
| `keccakf32` | `&$st`, `$rounds` | `void` | Keccak-f32 位运算（内部方法） |
| `keccak64` | `$in_raw`, `int $capacity`, `int $outputlength`, `$suffix`, `bool $raw_output` | `string` | Keccak64 哈希计算（内部方法） |
| `keccak32` | `$in_raw`, `int $capacity`, `int $outputlength`, `$suffix`, `bool $raw_output` | `string` | Keccak32 哈希计算（内部方法） |
| `keccak` | `$in_raw`, `int $capacity`, `int $outputlength`, `$suffix`, `bool $raw_output` | `string` | Keccak 哈希计算（内部方法） |
| `hash` | `$in`, `int $mdlen`, `bool $raw_output` | `string` | 计算 Keccak 哈希 |
| `shake` | `$in`, `int $security_level`, `int $outlen`, `bool $raw_output` | `string` | 计算 Keccak Shake 哈希 |

#### Message

**文件**: `src/Support/Message.php`  
**行数**: 165 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `hashMessage` | `$message`, `bool $useTronHeader` | `string` | 对消息进行哈希处理 |
| `signMessage` | `$message`, `string $privateKey`, `bool $useTronHeader` | `string` | 签名消息 |
| `verifyMessage` | `$message`, `string $signature`, `bool $useTronHeader` | `string` | 验证消息签名 |
| `verifyMessageWithAddress` | `$message`, `string $signature`, `string $expectedAddress`, `bool $useTronHeader` | `bool` | 验证消息签名并返回布尔结果 |
| `verifySignature` | `string $messageHex`, `string $address`, `string $signature`, `bool $useTronHeader` | `bool` | 使用 TRON 特定的方法验证签名 |

#### Secp

**文件**: `src/Support/Secp.php`  
**行数**: 20 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `sign` | `string $message`, `string $privateKey` | `string` | 签名方法（兼容层） |

#### Secp256k1

**文件**: `src/Support/Secp256k1.php`  
**行数**: 178 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `sign` | `string $message`, `string $privateKey`, `array $options` | `Signature` | 用私钥签名消息 |
| `verify` | `string $message`, `Signature $signature`, `string $publicKey` | `bool` | 验证签名 |
| `recoverPublicKey` | `string $message`, `Signature $signature` | `?string` | 从签名恢复公钥 |
| `generatePrivateKey` | - | `string` | 生成新私钥 |
| `getPublicKey` | `string $privateKey` | `string` | 从私钥获取公钥 |
| `getRecoveryParam` | `string $messageHash`, `$signature`, `$publicKey` | `int` | 计算恢复参数（内部方法） |

#### Signature

**文件**: `src/Support/Signature.php`  
**行数**: 159 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `string $r`, `string $s`, `int $recoveryParam` | - | 构造函数 |
| `getR` | - | `string` | Get R component |
| `getS` | - | `string` | Get S component |
| `getRecoveryParam` | - | `int` | Get recovery parameter |
| `toHex` | - | `string` | Convert signature to hex string |
| `toDER` | - | `string` | Convert signature to DER format |
| `fromHex` | `string $hex`, `int $recoveryParam` | `self` | Create signature from hex string |
| `fromDER` | `string $der`, `int $recoveryParam` | `self` | Create signature from DER format |
| `toArray` | - | `array` | Convert to array |
| `__toString` | - | `string` | Convert to string representation |

#### Bip39

**文件**: `src/Support/Bip39.php`  
**行数**: 277 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `validateMnemonic` | `string $mnemonic` | `bool` | 验证助记词有效性 |
| `mnemonicToSeed` | `string $mnemonic`, `string $passphrase` | `string` | 从助记词生成种子 (PBKDF2 - BIP39标准) |
| `generateMnemonic` | `int $wordCount` | `string` | 生成随机助记词 |
| `getBip39WordList` | - | `array` | 获取 BIP39 完整词库（2048个单词） |

---

### 7.3 ABI 编码

#### Ethabi

**文件**: `src/Support/Ethabi.php`  
**行数**: 103 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `array $types` | - | 构造函数 |
| `encodeParameters` | `array $functionAbi`, `array $params` | `string` | Encode parameters for function call |
| `decodeParameters` | `array $functionAbi`, `string $data` | `array` | Decode parameters from function result |
| `encodeFunctionSignature` | `string $functionName`, `array $inputs` | `string` | Encode function signature |
| `getFunctionSelector` | `array $functionAbi` | `string` | Get function selector (first 4 bytes of function signature hash) |

---

### 7.4 交易助手

#### TransactionHelper

**文件**: `src/Support/TransactionHelper.php`  
**行数**: 186 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `__construct` | `TronWeb $tronWeb` | - | 构造函数 |
| `createTransaction` | `string $contractType`, `array $contractData`, `?int $permissionId`, `array $transactionOptions` | `array` | 统一的交易创建方法 |
| `getTransactionOptions` | `array $options` | `array` | 获取交易选项 |
| `checkBlockHeader` | `?array $blockHeader` | `bool` | 验证区块头参数 |
| `validateParameter` | `$value`, `string $type`, `string $paramName` | `void` | 参数验证 |
| `toBigInt` | `$value` | `string` | 大整数处理 |

---

### 7.5 助记词钱包

#### HdWallet

**文件**: `src/Support/HdWallet.php`  
**行数**: 178 行

| 方法名 | 参数 | 返回值 | 说明 |
|--------|------|--------|------|
| `hdMasterFromSeed` | `string $seed` | `array` | 从种子生成主密钥 (BIP32) |
| `derivePath` | `array $parent`, `string $path` | `array` | 派生 BIP44 路径 |
| `deriveChild` | `array $parent`, `int $index` | `array` | 派生子密钥 |
| `addPrivateKeys` | `string $key1`, `string $key2` | `string` | 私钥相加（椭圆曲线模运算） |
| `fromMnemonic` | `string $mnemonic`, `string $passphrase`, `string $path` | `array` | 从助记词生成完整账户信息 (BIP39/BIP32/BIP44) |
| `createAccount` | `int $wordCount`, `string $passphrase`, `string $path` | `array` | 生成新账户（随机助记词） |

---

## 8. 使用示例

### 8.1 基础使用

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

// 创建 TronWeb 实例
$tronWeb = new TronWeb([
    'fullNode' => new HttpProvider('https://api.trongrid.io'),
    'solidityNode' => new HttpProvider('https://api.trongrid.io'),
    'eventServer' => new HttpProvider('https://api.trongrid.io'),
    'privateKey' => 'your_private_key_here'
]);

// 设置默认地址
$tronWeb->setAddress('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');

// 查询余额
$balance = $tronWeb->trx->getBalance(null, true);
echo "余额: {$balance} TRX\n";

// 发送 TRX
$result = $tronWeb->trx->sendTrx('TTX...', 1.0);
echo "交易ID: {$result['txid']}\n";
```

### 8.2 合约交互

```php
// 使用 ContractInstance
$contract = $tronWeb->contract(
    json_decode($abi, true),
    'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
);

// 调用只读方法
$balance = $contract->balanceOf('TTX...');

// 调用写入方法
$tx = $contract->transfer('TTX...', 100);
echo "交易ID: {$tx['txid']}\n";
```

### 8.3 资源管理

```php
// 冻结 TRX 获取能量
$freezeResult = $tronWeb->resource->freeze(100, 30, 'ENERGY');

// 查询资源信息
$resources = $tronWeb->resource->getResources();
echo "能量: {$resources['energy']}\n";

// 解冻资源
$unfreezeResult = $tronWeb->resource->unfreeze('ENERGY');
```

### 8.4 批量操作

```php
// 批量查询余额
$balances = $tronWeb->account->getBalances([
    'TTX...',
    'TXX...',
    'TYY...'
], true, true);

foreach ($balances as $item) {
    echo "{$item['address']}: {$item['balance']} TRX\n";
}
```

---

## 9. 优势总结

### 9.1 提高代码质量
- 类型安全减少运行时错误
- 清晰的接口定义便于理解
- 自动补全和 IDE 支持提升开发效率

### 9.2 易于维护
- 统一的对象模型简化了代码结构
- 强类型检查帮助快速定位问题
- 便于单元测试和集成测试

### 9.3 开发体验优化
- TypeScript 风格的 API 降低学习门槛
- 链式调用提升代码可读性
- 完整的类型提示支持现代 IDE

---

## 10. 最佳实践

### 10.1 逐步迁移
建议从简单场景开始，逐步将现有代码迁移到类型化接口。

### 10.2 使用类型提示
在函数参数和返回值中使用类型提示，增强代码文档性。

### 10.3 集成测试
利用类型系统的强约束特性，编写更严格的测试用例。

---

## 11. 结论

TronWeb 的类型系统是一套成熟且实用的解决方案，它不仅提高了代码的安全性和可维护性，还通过提供 TypeScript 风格的 API，显著改善了开发体验。随着 Tron 生态的发展，这套类型系统将成为构建可靠、高效应用的重要基石。

---

**文档版本**: 1.0  
**最后更新**: 2026-02-03  
**维护者**: TronWeb 开发团队
