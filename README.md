# Dsdcr\TronWeb - TRON 区块链 PHP API 库

功能完善的 TRON 区块链 PHP SDK，支持完整的 TRX 转账、智能合约交互、代币管理、资源操作和网络查询等功能。

[![Latest Stable Version](https://poser.pugx.org/dsdcr/tronweb/version)](https://packagist.org/packages/dsdcr/tronweb)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/dsdcr/tronweb.svg?style=flat-square)](https://packagist.org/packages/dsdcr/tronweb)))

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
- `gmp` - GNU 多精度运算
- `mbstring` - 多字节字符串处理
- `openssl` - OpenSSL 加密支持
- `json` - JSON 数据处理

## 🚀 快速开始

### 基础用法

```php
<?php
require_once 'vendor/autoload.php';

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;

try {
    // 创建 TronWeb 实例
    $tronWeb = new TronWeb(
        new HttpProvider('https://api.trongrid.io'),          // fullNode
        new HttpProvider('https://api.trongrid.io'),         // solidityNode
        new HttpProvider('https://api.trongrid.io')          // eventServer
    );

    // 设置私钥（可选）
    $tronWeb->setPrivateKey('your_private_key_here');

    // 查询余额
    $balance = $tronWeb->trx->getBalance(null, true);
    echo "余额: " . $balance . " TRX\n";

} catch (\Dsdcr\TronWeb\Exception\TronException $e) {
    echo "Tron 异常: " . $e->getMessage() . "\n";
}
```

### TransactionBuilder 详细使用

```php
// 获取 TransactionBuilder 实例
$builder = $tronWeb->trx->getTransactionBuilder();

// 1. TRX 转账
$tx = $builder->sendTrx('接收地址', 1.0);
echo "TRX 转账: " . $tx['raw_data'] . "\n";

// 2. 代币转账（TRC10）
$tx = $builder->sendToken('接收地址', '1000001', 100);
echo "代币转账: " . $tx['raw_data'] . "\n";

// 3. 资源冻结
$tx = $builder->freezeBalance(100, 3, 'BANDWIDTH');
echo "资源冻结: " . $tx['raw_data'] . "\n";

// 4. 合约调用
$contract = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
$abi = json_decode(file_get_contents('abi.json'), true);
$tx = $builder->triggerSmartContract($abi, $contract, 'transfer', ['接收地址', 100]);
echo "合约调用: " . $tx['raw_data'] . "\n";
```

## 📚 模块说明

| 模块 | 方法数量 | 主要功能 |
|------|----------|----------|
| **Trx** | 40+ | 交易、区块、签名、广播、消息签名 |
| **Account** | 12 | 地址生成、验证、助记词、密钥管理 |
| **Contract** | 5 | 合约实例、信息、事件、部署 |
| **Token** | 13 | TRC10/TRC20 代币创建和管理 |
| **Resource** | 15 | 带宽、能量冻结、解冻、委托 |
| **Network** | 13 | 节点、提案管理、交易所、治理 |
| **Utils** | 22 | 地址转换、单位换算、格式验证 |
| **TransactionBuilder** | 18 | 交易构建、多签、参数验证 |

## 💡 TransactionBuilder 详细使用

### TRX 转账交易

```php
$builder = $tronWeb->trx->getTransactionBuilder();

// 简单转账（使用默认地址）
$tx = $builder->sendTrx('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', 1.5);

// 指定发送地址
$tx = $builder->sendTrx(
    'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',  // 接收地址
    1.5,                                             // 金额
    'TTX...'                                          // 发送地址
);

// 使用权限ID（多签账户）
$tx = $builder->sendTrx(
    'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
    1.5,
    'TTX...',
    ['permissionId' => 2]  // 权限ID
);

// 签名并广播
$signed = $tronWeb->trx->signTransaction($tx);
$result = $tronWeb->trx->sendRawTransaction($signed);
echo "交易ID: " . $result['txid'] . "\n";
```

### 代币转账（TRC10）

```php
$builder = $tronWeb->trx->getTransactionBuilder();

// 代币ID转账
$tx = $builder->sendToken(
    'TTX...',              // 接收地址
    '1000001',            // 代币 ID
    100,                  // 转账数量
    'TRX...'               // 发送地址
);

// 使用代币名称
$tx = $builder->sendToken(
    'TTX...',
    'MyToken',             // 代币名称
    100
);

// 使用权限ID
$tx = $builder->sendToken(
    'TTX...',
    '1000001',
    100,
    'TRX...',
    ['permissionId' => 3]
);
```

### 资源管理

```php
$builder = $tronWeb->trx->getTransactionBuilder();

// 冻结带宽
$tx = $builder->freezeBalance(100, 3, 'BANDWIDTH');

// 冻结能量
$tx = $builder->freezeBalance(100, 3, 'ENERGY');

// 指定所有者地址
$tx = $builder->freezeBalance(
    100,
    3,
    'ENERGY',
    'TXYZ...'  // 所有者地址
);

// 委托资源给其他地址
$tx = $builder->freezeBalance(
    100,
    3,
    'ENERGY',
    'TXYZ...',      // 所有者地址
    'TABC...'       // 接收者地址
);
```

### V2 资源管理

```php
$builder = $tronWeb->trx->getTransactionBuilder();

// V2 冻结（更灵活的参数）
$tx = $builder->freezeBalanceV2(
    100,           // 金额
    'ENERGY',      // 资源类型
    'TXYZ...'      // 地址
    [
        'ownerAddress' => 'TXYZ...',    // 所有者地址
        'lock' => true,                   // 是否锁定
        'lockPeriod' => 7                 // 锁定周期（天）
    ]
);

// 取消待解冻
$tx = $builder->cancelUnfreezeBalanceV2('TXYZ...');
```

### 资源委托

```php
$builder = $tronWeb->trx->getTransactionBuilder();

// 委托资源
$tx = $builder->delegateResource(
    'TABC...',      // 接收地址
    100,           // 委托金额
    'BANDWIDTH',   // 资源类型
    'TXYZ...',      // 委托方地址
    false,         // 是否锁定
    null           // 锁定周期
);

// 锁定委托
$tx = $builder->delegateResource(
    'TABC...',
    100,
    'ENERGY',
    'TXYZ...',
    true,          // 锁定
    7              // 锁定7天
);

// 取消委托
$tx = $builder->undelegateResource(
    'TABC...',
    100,
    'ENERGY',
    'TXYZ...'
);
```

### 智能合约调用

```php
$builder = $tronWeb->trx->getTransactionBuilder();

// 写入调用（需要签名和广播）
$tx = $builder->triggerSmartContract(
    $abi,                          // ABI 数组
    'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',  // 合约地址
    'transfer',                    // 函数名
    ['0x...' => '0x123'],        // 参数
    100000000,                      // 费用限制
    'TTX...'                        // 发送地址
);

// 只读调用（不需要广播）
$result = $builder->triggerConstantContract(
    $abi,
    'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
    'balanceOf',
    ['0x...'],
    null  // 调用地址（可选）
);
echo "余额: " . $result . "\n";
```

## 💡 使用示例

### 账户管理

```php
// 生成新账户
$account = $tronWeb->account->create();
echo "地址: " . $account->getAddress(true) . "\n";
echo "私钥: " . $account->getPrivateKey() . "\n";

// 地址验证
$isValid = $tronWeb->account->validateAddress('地址', false);
echo "地址有效性: " . ($isValid['result'] ? '有效' : '无效') . "\n";
```

### 交易操作

```php
// TRX 转账
$builder = $tronWeb->trx->getTransactionBuilder();
$tx = $builder->sendTrx('接收地址', 1.5);
$signed = $tronWeb->trx->signTransaction($tx);
$result = $tronWeb->trx->sendRawTransaction($signed);
echo "交易ID: " . $result['txid'] . "\n";

// 批量发送
$results = $tronWeb->trx->sendToMultiple([
    ['address' => '接收1', 'amount' => 10.5],
    ['address' => '接收2', 'amount' => 20.0]
]);

// 消息签名
$signature = $tronWeb->trx->signMessage('消息内容', 'your_private_key');
$isValid = $tronWeb->trx->verifyMessage('消息内容', $signature, '地址');
```

### 智能合约交互

```php
// 创建合约实例
$contract = $tronWeb->contract()->at('合约地址');

// 调用只读方法
$balance = $contract->balanceOf('地址', true);

// 调用写入方法
$tx = $contract->transfer('接收地址', 100, [
    'privateKey' => 'your_private_key',
    'feeLimit' => 100000000
]);

// 查询合约信息
$contractInfo = $tronWeb->contract->getInfo('合约地址');
```

### 代币管理（TRC10）

```php
// 发送 TRC10 代币
$result = $tronWeb->token->send('接收地址', 100, '1000001', '发送地址');

// 创建新代币
$builder = $tronWeb->trx->getTransactionBuilder();
$result = $builder->createToken([
    'name' => 'MyToken',
    'abbreviation' => 'MTK',
    'totalSupply' => 1000000,
    'trxRatio' => 1,
    'tokenRatio' => 1,
    'saleStart' => time() * 1000 + 3600000,
    'saleEnd' => time() * 1000 + 86400000,
    'description' => 'My Token Description',
    'url' => 'https://mytoken.com'
]);
```

### 资源管理

```php
// 冻结 TRX 获取能量
$result = $tronWeb->resource->freeze(100, 3, 'ENERGY');

// V2 资源委托
$delegated = $tronWeb->resource->getDelegatedResourceV2(
    '发送地址',
    '接收地址',
    ['confirmed' => true]
);

// 提取奖励
$result = $tronWeb->resource->withdrawRewards();

// 查询资源价格
$bandwidthPrices = $tronWeb->resource->getBandwidthPrices();
$energyPrices = $tronWeb->resource->getEnergyPrices();
```

### 网络信息

```php
// 获取超级代表列表
$witnesses = $tronWeb->network->listwitnesses();

// 获取节点列表
$nodes = $tronWeb->network->listNodes();

// 查询提案
$proposals = $tronWeb->network->listProposals();
$proposal = $tronWeb->network->getProposal($proposalId);

// 获取链参数
$chainParams = $tronWeb->network->getChainParameters();
```

## 📁 项目结构

```
vendor/dsdcr/tronweb/
├── src/
│   ├── TronWeb.php                          # 主入口类
│   ├── Modules/
│   │   ├── Account.php                     # 账户管理模块 (671行, 12方法)
│   │   ├── BaseModule.php                  # 模块基类
│   │   ├── Contract/
│   │   │   ├── ContractInstance.php          # 合约实例 (277行, 9方法)
│   │   │   └── ContractMethod.php            # 合约方法 (292行, 10方法)
│   │   ├── Contract.php                   # 合约操作模块 (343行, 5方法)
│   │   ├── Network.php                    # 网络信息模块 (280行, 13方法)
│   │   ├── Resource.php                   # 资源管理模块 (596行, 15方法)
│   │   ├── Token.php                      # 代币管理模块 (599行, 13方法)
│   │   ├── TransactionBuilder.php           # 交易构建器 (1110行, 18方法)
│   │   └── Trx.php                       # TRX 操作模块 (1339行, 40+方法)
│   ├── Provider/
│   │   ├── HttpProvider.php                # HTTP 提供者 (421行, 21方法)
│   │   ├── HttpProviderInterface.php        # HTTP 提供者接口
│   │   └── TronManager.php                # Provider 管理器 (271行, 8方法)
│   └── Support/
│       ├── AbiEncoder.php                  # ABI 编码器
│       ├── TronUtils.php                   # Tron 工具类 (347行, 21+方法)
│       └── [其他支持类]
├── examples/
│   ├── account_examples.php               # 账户管理示例
│   ├── contract_examples.php              # 智能合约示例
│   ├── network_examples.php                # 网络信息示例
│   ├── resource_examples.php              # 资源管理示例
│   ├── token_examples.php                 # 代币管理示例
│   ├── tronweb_basic.php                 # 基础使用示例
│   ├── trx_examples.php                   # TRX 交易示例
│   └── utils_examples.php                 # 工具函数示例
├── TYPE_SYSTEM_INTEGRATION.md              # 完整类型系统文档
└── README.md                               # 本文档
```

## 📄 完整文档

详细的类型系统文档、所有方法列表和使用说明，请查看：

**[TYPE_SYSTEM_INTEGRATION.md](TYPE_SYSTEM_INTEGRATION.md)**

该文档包含：
- 完整的文件结构说明
- 所有 135+ 个公开方法的详细列表
- 参数和返回值说明
- 实际使用示例
- 最佳实践指南

## 🛠️ 工具函数

```php
// 地址转换
$hex = $tronWeb->toHex('TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL');

// 地址验证（推荐使用 TronUtils 静态类）
use Dsdcr\TronWeb\Support\TronUtils;
$isValid = TronUtils::isAddress('地址');

// 单位换算（1 TRX = 1,000,000 SUN）
$sun = $tronWeb->utils->toSun(1.5);        // 1500000
$trx = $tronWeb->utils->fromSun(1500000); // 1.5
```

## 🧪 测试

运行单元测试：

```bash
php vendor/bin/phpunit
```

查看 `examples/` 目录获取更多使用示例和测试用例。

## 📚 示例代码

### 可用示例文件

| 文件 | 说明 |
|------|------|
| `examples/tronweb_basic.php` | 基础使用和初始化 |
| `examples/account_examples.php` | 账户生成、验证、助记词 |
| `examples/trx_examples.php` | TRX 转账、查询、签名 |
| `examples/contract_examples.php` | 智能合约交互、事件查询 |
| `examples/token_examples.php` | TRC10 代币创建和管理 |
| `examples/resource_examples.php` | 资源冻结、解冻、委托 |
| `examples/network_examples.php` | 网络节点、提案、交易所 |
| `examples/utils_examples.php` | 地址转换、单位换算、格式验证 |

### 运行所有示例

```bash
php examples/run_all_examples.php
```

## 🔒 安全提示

- **妥善保管私钥**：私钥是访问您账户的唯一凭证，切勿泄露
- **使用环境变量**：不要在代码中硬编码私钥，使用环境变量或配置管理
- **测试网络优先**：在生产环境使用前，请先在测试网络上充分测试
- **验证签名**：收到重要消息时，务必验证签名
- **API 密钥保护**：使用 TronGrid API 时，请妥善保护您的 API 密钥
- **使用 HD 钱包**：生产环境建议使用 BIP39/BIP44 标准的 HD 钱包
- **检查交易对象**：在签名前仔细检查交易对象的每个字段

## 📄 许可证

本项目采用 MIT 许可证 - 详见 [LICENSE](LICENSE) 文件

## 💰 捐赠支持

如果您觉得这个项目对您有帮助，欢迎捐赠 TRX：

**TRON (TRX)**: `TRWBqiqoFZysoAeyR1J35ibuyc8EvhUAoY`

## 📞 相关资源

- **[官方 GitHub 仓库](https://github.com/dsdcr/tronweb)**
- **[完整类型系统文档](TYPE_SYSTEM_INTEGRATION.md)** - 包含所有 135+ 个方法的详细说明
- **[TRON 官方文档](https://developers.tron.network/)**
- **[TRON Grid API](https://www.trongrid.io/)**
- **[Tronscan 浏览器](https://tronscan.org/)**
- **[Packagist 包信息](https://packagist.org/packages/dsdcr/tronweb)**

## 🆕 版本历史

### v1.0.0 (2026-02-03)
- ✅ 完整的类型系统实现
- ✅ 135+ 个公开方法
- ✅ 9 个核心模块（Trx、Account、Contract、Token、Resource、Network、Utils、TransactionBuilder）
- ✅ 完整的中文文档
- ✅ 详细的示例代码
- ✅ TransactionBuilder 详细使用说明
- ✅ 支持所有 TRON 网络操作

## 👥 贡献

欢迎提交 Issue 和 Pull Request！

在提交代码前，请确保：
1. 代码符合 PSR-12 编码规范
2. 所有新功能都有相应的单元测试
3. 更新相关文档
4. 通过所有现有测试

---

**文档版本**: 1.0.0  
**最后更新**: 2026-02-08  
