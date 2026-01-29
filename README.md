# Dsdcr\TronWeb - TRON 区块链 PHP API 库

一个用于与 TRON 区块链网络交互的现代化 PHP SDK，支持完整的 TRX 转账、智能合约、代币管理和资源操作等功能。

[![Latest Stable Version](https://poser.pugx.org/dsdcr/tronweb/version)](https://packagist.org/packages/dsdcr/tronweb)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/dsdcr/tronweb.svg?style=flat-square)](https://packagist.org/packages/dsdcr/tronweb)

## 📦 安装

使用 Composer 安装：

```bash
composer require dsdcr/tronweb --ignore-platform-reqs
```

## 🧰 环境要求

当前版本支持的 PHP 版本：

- **PHP 8.0** 或更高版本

必需的 PHP 扩展：
- `bcmath` - 精确数学运算
- `gmp` - GNU多精度运算
- `mbstring` - 多字节字符串处理
- `openssl` - OpenSSL 加密支持
- `json` - JSON 数据处理

## 🚀 快速开始

### 基本用法

```php
<?php
require_once 'vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

try {
    // 初始化 TronWeb 实例
    // 只需提供私钥，地址会自动从私钥推导出
    $tronWeb = new TronWeb([
        'fullNode' => new HttpProvider('https://api.trongrid.io'),
        'solidityNode' => new HttpProvider('https://api.trongrid.io'),
        'eventServer' => new HttpProvider('https://api.trongrid.io'),
        'privateKey' => '您的私钥'  // 设置私钥后，地址会自动从私钥推导出
    ]);

    // 获取自动推导出的地址
    $myAddress = $tronWeb->getDefaultAddress()['base58'];
    echo "我的地址: " . $myAddress . "\n";

    // 查询余额（使用默认地址）
    $balance = $tronWeb->trx->getBalance(null, true);
    echo "余额: " . $balance . " TRX\n";

    // 发送 TRX
    $result = $tronWeb->trx->send('接收地址', 100.5);
    var_dump($result);

    // 生成新地址
    $newAccount = $tronWeb->account->create();
    echo "新地址: " . $newAccount->getAddress(true) . "\n";
    echo "私钥: " . $newAccount->getPrivateKey() . "\n";

    // 获取最新区块
    $latestBlocks = $tronWeb->trx->getLatestBlocks(2);
    var_dump($latestBlocks);

} catch (\Dsdcr\TronWeb\Exception\TronException $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
```

## 📚 模块说明

### 核心模块

| 模块 | 方法数量 | 主要功能 |
|------|----------|----------|
| **Trx** | 40+ | 交易、区块、签名、广播、消息签名 |
| **Account** | 35+ | 地址生成、验证、助记词、密钥管理 |
| **Contract** | 5+ | 智能合约部署和调用 |
| **Token** | 13+ | TRC10/TRC20 代币创建和管理 |
| **Resource** | 15+ | 带宽、能量冻结、V2资源委托 |
| **Network** | 13+ | 网络节点、提案管理、交易所 |
| **Utils** | 22+ | 地址转换、单位换算、格式验证 |

### 账户模块示例

```php
// 生成新账户
$account = $tronWeb->account->create();
$address = $account->getAddress(true);
$privateKey = $account->getPrivateKey();

// 生成助记词账户
$mnemonic = $tronWeb->account->generateMnemonic(12);
$account = $tronWeb->account->generateAccountWithMnemonic($mnemonic);

// 地址验证
$isValid = $tronWeb->account->isValidAddress('地址');

// 批量查询余额
$balances = $tronWeb->account->getBalances(['地址1', '地址2'], true);
```

### 交易模块示例

```php
// 发送 TRX
$result = $tronWeb->trx->send('接收地址', 100);

// 批量发送
$results = $tronWeb->trx->sendToMultiple([
    ['地址1', 10.5],
    ['地址2', 5.2]
]);

// 查询交易
$transaction = $tronWeb->trx->getTransaction('交易ID');
$transactionInfo = $tronWeb->trx->getTransactionInfo('交易ID');

// 消息签名
$signature = $tronWeb->trx->signMessage('消息内容', $privateKey);
$isValid = $tronWeb->trx->verifyMessage('消息内容', $signature, $address);
```

### 智能合约示例

```php
// 创建 TRC20 合约实例
$trc20 = $tronWeb->contract->trc20('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');

// 获取代币信息
$name = $trc20->name();
$symbol = $trc20->symbol();
$decimals = $trc20->decimals();
$totalSupply = $trc20->totalSupply();

// 查询余额
$balance = $trc20->balanceOf('地址', true);

// 发送代币
$result = $trc20->transfer('接收地址', '100');
```

### 资源管理示例

```php
// 冻结资源
$result = $tronWeb->resource->freeze(100, 3, 'BANDWIDTH');
$result = $tronWeb->resource->freeze(100, 3, 'ENERGY');

// V2 资源委托
$delegatedResource = $tronWeb->resource->getDelegatedResourceV2(
    $fromAddress,
    $toAddress,
    ['confirmed' => true]
);

// 查询资源信息
$resources = $tronWeb->resource->getResources();
$frozenBalance = $tronWeb->resource->getFrozenBalance();

// 提取奖励
$result = $tronWeb->resource->withdrawRewards();
```

### 网络信息示例

```php
// 列出超级代表
$srs = $tronWeb->network->listSuperRepresentatives();

// 提案管理
$proposals = $tronWeb->network->listProposals();
$proposal = $tronWeb->network->getProposal($proposalID);
$params = $tronWeb->network->getProposalParameters();

// 交易所信息
$exchanges = $tronWeb->network->listExchanges();
$exchange = $tronWeb->network->getExchangeByID($exchangeID);
```

## 🛠️ 工具函数

```php
// 地址转换
$hex = $tronWeb->utils->addressToHex('TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL');
$base58 = $tronWeb->utils->hexToAddress($hexAddress);

// 单位换算
$sun = $tronWeb->utils->toSun(1.5);       // 1500000
$trx = $tronWeb->utils->fromSun(1500000); // 1.5

// 地址验证
$isValid = $tronWeb->utils->isValidTronAddress('地址');

// 字符串编码
$hex = $tronWeb->utils->stringToHex('hello');
$str = $tronWeb->utils->hexToString('68656c6c6f');
```

## 🧪 测试

运行单元测试：

```bash
php vendor/bin/phpunit
```

## 📁 示例代码

查看 `tests/` 目录获取更多使用示例：

- `tests/tronweb_basic.php` - 基础使用示例
- `tests/account_examples.php` - 账户管理示例
- `tests/trx_examples.php` - 交易操作示例
- `tests/contract_examples.php` - 智能合约示例
- `tests/token_examples.php` - 代币操作示例
- `tests/resource_examples.php` - 资源管理示例
- `tests/network_examples.php` - 网络信息示例
- `tests/utils_examples.php` - 工具函数示例
- `tests/run_all_examples.php` - 运行所有示例

## 🔒 安全提示

- **妥善保管私钥**：私钥是访问您账户的唯一凭证，切勿泄露
- **使用环境变量**：不要在代码中硬编码私钥，使用环境变量或配置管理
- **测试网络优先**：在生产环境使用前，请先在测试网络上充分测试
- **验证签名**：收到重要消息时，务必验证签名

## 📄 许可证

本项目采用 MIT 许可证 - 详见 [LICENSE](LICENSE) 文件

## 💰 捐赠支持

如果您觉得这个项目对您有帮助，欢迎捐赠 TRX：

**TRON (TRX)**: TRWBqiqoFZysoAeyR1J35ibuyc8EvhUAoY

## 📞 相关资源

- [官方 GitHub 仓库](https://github.com/dsdcr/tronweb)
- [TRON 官方文档](https://developers.tron.network/)
- [TRON Grid API](https://www.trongrid.io/)
- [Packagist 包信息](https://packagist.org/packages/dsdcr/tronweb)

---

*最后更新: 2026年1月*