# Dsdcr\TronWeb - PHP TRON区块链开发库

## 📋 概述

Dsdcr\TronWeb 是一个功能完整的PHP库，用于与TRON区块链网络进行交互。它提供了模块化的API设计，支持TRON的所有核心功能，包括账户管理、交易处理、智能合约、代币操作等。

## 🏗️ 架构设计

该库采用模块化设计，主要包含以下模块：

- **TronWeb**: 主入口类，协调所有模块
- **Trx**: 交易和区块操作 (40+ 方法)
- **Account**: 账户管理 (35+ 方法)  
- **Contract**: 智能合约操作 (5+ 方法)
- **Token**: 代币管理 (13+ 方法)
- **Resource**: 资源管理 (15+ 方法)
- **Network**: 网络信息 (13+ 方法)
- **Utils**: 工具函数 (22+ 方法)

## 📦 依赖关系

```json
{
    "php": ">=8.0",
    "guzzlehttp/guzzle": "^7.2",
    "simplito/elliptic-php": "^1.0",
    "ext-json": "*",
    "ext-bcmath": "*"
}
```

## 🚀 快速开始

### 安装

```bash
composer require dsdcr/tronweb
```

### 基本用法

```php
use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

// 初始化TronWeb实例
$tronWeb = new TronWeb([
    'fullNode' => new HttpProvider('https://api.trongrid.io'),
    'privateKey' => '您的私钥',
    'defaultAddress' => '您的地址'
]);

// 查询余额
$balance = $tronWeb->trx->getBalance('地址', true);

// 发送TRX
$result = $tronWeb->trx->send('接收地址', 100.5);
```

## 🎯 核心功能

### 1. 账户管理
- 生成助记词和HD钱包
- 地址验证和转换
- 批量余额查询

### 2. 交易操作  
- TRX转账
- 多重签名
- 交易查询
- 消息签名

### 3. 智能合约
- TRC20代币交互
- 合约部署和调用
- 事件监听

### 4. 资源管理
- 带宽和能量冻结/解冻
- V2资源委托系统
- 奖励查询

### 5. 网络信息
- 节点状态监控
- 提案管理系统
- 交易所接口

## 🔧 高级功能

### V2资源委托
```php
$delegatedResource = $tronWeb->resource->getDelegatedResourceV2(
    $fromAddress,
    $toAddress,
    ['confirmed' => true]
);
```

### 消息签名系统
```php
$signature = $tronWeb->trx->signMessage('消息内容', $privateKey);
$isValid = $tronWeb->trx->verifyMessage('消息内容', $signature, $address);
```

### 提案管理
```php
$proposals = $tronWeb->network->listProposals();
$proposal = $tronWeb->network->getProposal($proposalID);
```

## 📊 功能统计

| 模块 | 方法数量 | 主要功能 |
|------|----------|----------|
| Trx | 40+ | 交易、区块、签名 |
| Account | 35+ | 地址、密钥管理 |
| Contract | 5+ | 智能合约操作 |
| Token | 13+ | 代币管理 |
| Resource | 15+ | 资源管理 |
| Network | 13+ | 网络信息 |
| Utils | 22+ | 工具函数 |
| **总计** | **140+** | **完整功能覆盖** |

## 🛡️ 安全性特性

- 加密安全的随机数生成
- 完整的地址验证
- 消息签名验证
- 异常处理机制

## 📝 示例文件

本目录包含详细的模块使用示例：
- `tronweb_basic.php` - 基础使用示例
- `account_examples.php` - 账户管理示例  
- `trx_examples.php` - 交易操作示例
- `contract_examples.php` - 智能合约示例
- `token_examples.php` - 代币操作示例
- `resource_examples.php` - 资源管理示例
- `network_examples.php` - 网络信息示例
- `utils_examples.php` - 工具函数示例

## 🔗 相关资源

- [官方GitHub仓库](https://github.com/dsdcr/tronweb)
- [TRON官方文档](https://developers.tron.network/)
- [TRON Grid API](https://www.trongrid.io/)

## 📄 许可证

MIT License

---

*注意：在使用真实私钥和地址前，请确保在测试网络上进行充分测试。*