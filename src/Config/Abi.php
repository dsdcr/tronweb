<?php
/**
 * 默认 ABI 配置类
 * 提供各种智能合约的 ABI 定义，包括 ERC20 代币、去中心化交易所路由等
 */

namespace Dsdcr\TronWeb\Config;

class Abi
{
    /**
     * ERC20 标准代币 ABI
     * 包含完整的 ERC20 代币接口定义，包括所有标准函数和事件
     *
     * @return array ERC20 ABI 定义数组
     */
    public static function erc20(): array
    {
        return [
            // 构造函数：合约部署时执行
            [
                "inputs" => [],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "constructor"
            ],
            // 查询代币名称
            [
                "constant" => true,
                "inputs" => [],
                "name" => "name",
                "outputs" => [["name" => "", "type" => "string"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 查询代币符号
            [
                "constant" => true,
                "inputs" => [],
                "name" => "symbol",
                "outputs" => [["name" => "", "type" => "string"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 查询代币小数位数
            [
                "constant" => true,
                "inputs" => [],
                "name" => "decimals",
                "outputs" => [["name" => "", "type" => "uint8"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 查询代币总供应量
            [
                "constant" => true,
                "inputs" => [],
                "name" => "totalSupply",
                "outputs" => [["name" => "", "type" => "uint256"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            [
                "constant" => true,
                "inputs" => [],
                "name" => "getAddress",
                "outputs" => [["name" => "", "type" => "address"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 查询指定账户的代币余额
            [
                "constant" => true,
                "inputs" => [["name" => "account", "type" => "address"]],
                "name" => "balanceOf",
                "outputs" => [["name" => "balance", "type" => "uint256"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 转账：从调用者账户转账给接收者
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "recipient", "type" => "address"],
                    ["name" => "amount", "type" => "uint256"]
                ],
                "name" => "transfer",
                "outputs" => [["name" => "", "type" => "bool"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 授权转账：从指定账户转账给接收者（需要授权）
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "sender", "type" => "address"],
                    ["name" => "recipient", "type" => "address"],
                    ["name" => "amount", "type" => "uint256"]
                ],
                "name" => "transferFrom",
                "outputs" => [["name" => "", "type" => "bool"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 授权：授权指定地址可以使用调用者的代币
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "spender", "type" => "address"],
                    ["name" => "value", "type" => "uint256"]
                ],
                "name" => "approve",
                "outputs" => [["name" => "", "type" => "bool"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 查询授权额度：查看指定地址可以使用的授权额度
            [
                "constant" => true,
                "inputs" => [
                    ["name" => "owner", "type" => "address"],
                    ["name" => "spender", "type" => "address"]
                ],
                "name" => "allowance",
                "outputs" => [["name" => "", "type" => "uint256"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 增加授权额度：增加指定地址的授权额度
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "spender", "type" => "address"],
                    ["name" => "addedValue", "type" => "uint256"]
                ],
                "name" => "increaseAllowance",
                "outputs" => [["name" => "", "type" => "bool"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 减少授权额度：减少指定地址的授权额度
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "spender", "type" => "address"],
                    ["name" => "subtractedValue", "type" => "uint256"]
                ],
                "name" => "decreaseAllowance",
                "outputs" => [["name" => "", "type" => "bool"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 代币销毁：销毁调用者账户中的代币
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amount", "type" => "uint256"]
                ],
                "name" => "burn",
                "outputs" => [["name" => "", "type" => "bool"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 从指定账户销毁代币（需要授权）
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "account", "type" => "address"],
                    ["name" => "amount", "type" => "uint256"]
                ],
                "name" => "burnFrom",
                "outputs" => [["name" => "", "type" => "bool"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 转账事件：转账时触发
            [
                "anonymous" => false,
                "inputs" => [
                    ["indexed" => true, "name" => "from", "type" => "address"],
                    ["indexed" => true, "name" => "to", "type" => "address"],
                    ["indexed" => false, "name" => "value", "type" => "uint256"]
                ],
                "name" => "Transfer",
                "type" => "event"
            ],
            // 代币销毁事件：销毁代币时触发
            [
                "anonymous" => false,
                "inputs" => [
                    ["indexed" => true, "name" => "from", "type" => "address"],
                    ["indexed" => false, "name" => "value", "type" => "uint256"]
                ],
                "name" => "Burn",
                "type" => "event"
            ],
            // 授权事件：授权时触发
            [
                "anonymous" => false,
                "inputs" => [
                    ["indexed" => true, "name" => "owner", "type" => "address"],
                    ["indexed" => true, "name" => "spender", "type" => "address"],
                    ["indexed" => false, "name" => "value", "type" => "uint256"]
                ],
                "name" => "Approval",
                "type" => "event"
            ]
        ];
    }

    /**
     * SunSwap V2 路由合约 ABI
     * SunSwap V2 是 Tron 网络上的去中心化交易所，支持代币交换
     *
     * @return array SunSwap V2 路由合约 ABI 定义数组
     */
    public static function sunswapV2Router(): array
    {
        return [
            // 查询交换输出金额：根据输入金额和交换路径计算能得到的输出金额
            [
                "constant" => true,
                "inputs" => [
                    ["name" => "amountIn", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"]
                ],
                "name" => "getAmountsOut",
                "outputs" => [["name" => "amounts", "type" => "uint256[]"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 查询交换输入金额：根据目标输出金额计算需要输入的金额
            [
                "constant" => true,
                "inputs" => [
                    ["name" => "amountOut", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"]
                ],
                "name" => "getAmountsIn",
                "outputs" => [["name" => "amounts", "type" => "uint256[]"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 查询单池输入金额
            [
                "constant" => true,
                "inputs" => [
                    ["name" => "amountOut", "type" => "uint256"],
                    ["name" => "reserveIn", "type" => "uint256"],
                    ["name" => "reserveOut", "type" => "uint256"]
                ],
                "name" => "getAmountIn",
                "outputs" => [["name" => "amountIn", "type" => "uint256"]],
                "payable" => false,
                "stateMutability" => "pure",
                "type" => "function"
            ],
            // 查询单池输出金额
            [
                "constant" => true,
                "inputs" => [
                    ["name" => "amountIn", "type" => "uint256"],
                    ["name" => "reserveIn", "type" => "uint256"],
                    ["name" => "reserveOut", "type" => "uint256"]
                ],
                "name" => "getAmountOut",
                "outputs" => [["name" => "amountOut", "type" => "uint256"]],
                "payable" => false,
                "stateMutability" => "pure",
                "type" => "function"
            ],
            // 计算兑换比率
            [
                "constant" => true,
                "inputs" => [
                    ["name" => "amountA", "type" => "uint256"],
                    ["name" => "reserveA", "type" => "uint256"],
                    ["name" => "reserveB", "type" => "uint256"]
                ],
                "name" => "quote",
                "outputs" => [["name" => "amountB", "type" => "uint256"]],
                "payable" => false,
                "stateMutability" => "pure",
                "type" => "function"
            ],
            // 精确输入代币交换为 ETH：用指定数量的代币交换 ETH/TRX
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountIn", "type" => "uint256"],
                    ["name" => "amountOutMin", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "swapExactTokensForETH",
                "outputs" => [["name" => "amounts", "type" => "uint256[]"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 精确输入代币交换为代币：用指定数量的代币交换其他代币
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountIn", "type" => "uint256"],
                    ["name" => "amountOutMin", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "swapExactTokensForTokens",
                "outputs" => [["name" => "amounts", "type" => "uint256[]"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 精确输入 ETH 交换为代币：用 ETH 交换指定数量的代币
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountOutMin", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "swapExactETHForTokens",
                "outputs" => [["name" => "amounts", "type" => "uint256[]"]],
                "payable" => true,
                "stateMutability" => "payable",
                "type" => "function"
            ],
            // 用 TRX 兑换精确数量的代币
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountOut", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "swapETHForExactTokens",
                "outputs" => [["name" => "amounts", "type" => "uint256[]"]],
                "payable" => true,
                "stateMutability" => "payable",
                "type" => "function"
            ],
            // 用代币兑换精确数量的 TRX
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountOut", "type" => "uint256"],
                    ["name" => "amountInMax", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "swapTokensForExactETH",
                "outputs" => [["name" => "amounts", "type" => "uint256[]"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 用代币兑换精确数量的其他代币
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountOut", "type" => "uint256"],
                    ["name" => "amountInMax", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "swapTokensForExactTokens",
                "outputs" => [["name" => "amounts", "type" => "uint256[]"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],

            // ========== 支持特殊代币（FeeOnTransfer）的方法 ==========
            // TRX → 代币（支持转账手续费代币）
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountOutMin", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "swapExactETHForTokensSupportingFeeOnTransferTokens",
                "outputs" => [],
                "payable" => true,
                "stateMutability" => "payable",
                "type" => "function"
            ],
            // 代币 → TRX（支持转账手续费代币）
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountIn", "type" => "uint256"],
                    ["name" => "amountOutMin", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "swapExactTokensForETHSupportingFeeOnTransferTokens",
                "outputs" => [],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 代币 → 代币（支持转账手续费代币）
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountIn", "type" => "uint256"],
                    ["name" => "amountOutMin", "type" => "uint256"],
                    ["name" => "path", "type" => "address[]"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "swapExactTokensForTokensSupportingFeeOnTransferTokens",
                "outputs" => [],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 移除流动性（TRX-代币对，支持转账手续费代币）
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "token", "type" => "address"],
                    ["name" => "liquidity", "type" => "uint256"],
                    ["name" => "amountTokenMin", "type" => "uint256"],
                    ["name" => "amountETHMin", "type" => "uint256"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "removeLiquidityETHSupportingFeeOnTransferTokens",
                "outputs" => [
                    ["name" => "amountETH", "type" => "uint256"]
                ],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 带签名的移除流动性（TRX-代币对，支持转账手续费代币）
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "token", "type" => "address"],
                    ["name" => "liquidity", "type" => "uint256"],
                    ["name" => "amountTokenMin", "type" => "uint256"],
                    ["name" => "amountETHMin", "type" => "uint256"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"],
                    ["name" => "approveMax", "type" => "bool"],
                    ["name" => "v", "type" => "uint8"],
                    ["name" => "r", "type" => "bytes32"],
                    ["name" => "s", "type" => "bytes32"]
                ],
                "name" => "removeLiquidityETHWithPermitSupportingFeeOnTransferTokens",
                "outputs" => [
                    ["name" => "amountETH", "type" => "uint256"]
                ],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],

            // ========== 流动性管理方法 ==========
            // 添加流动性 (代币对)
            // 添加流动性 (代币对)
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "tokenA", "type" => "address"],
                    ["name" => "tokenB", "type" => "address"],
                    ["name" => "amountADesired", "type" => "uint256"],
                    ["name" => "amountBDesired", "type" => "uint256"],
                    ["name" => "amountAMin", "type" => "uint256"],
                    ["name" => "amountBMin", "type" => "uint256"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "addLiquidity",
                "outputs" => [
                    ["name" => "amountA", "type" => "uint256"],
                    ["name" => "amountB", "type" => "uint256"],
                    ["name" => "liquidity", "type" => "uint256"]
                ],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 添加流动性 (TRX-代币对)
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "token", "type" => "address"],
                    ["name" => "amountTokenDesired", "type" => "uint256"],
                    ["name" => "amountTokenMin", "type" => "uint256"],
                    ["name" => "amountETHMin", "type" => "uint256"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "addLiquidityETH",
                "outputs" => [
                    ["name" => "amountToken", "type" => "uint256"],
                    ["name" => "amountETH", "type" => "uint256"],
                    ["name" => "liquidity", "type" => "uint256"]
                ],
                "payable" => true,
                "stateMutability" => "payable",
                "type" => "function"
            ],
            // 移除流动性 (代币对)
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "tokenA", "type" => "address"],
                    ["name" => "tokenB", "type" => "address"],
                    ["name" => "liquidity", "type" => "uint256"],
                    ["name" => "amountAMin", "type" => "uint256"],
                    ["name" => "amountBMin", "type" => "uint256"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "removeLiquidity",
                "outputs" => [
                    ["name" => "amountA", "type" => "uint256"],
                    ["name" => "amountB", "type" => "uint256"]
                ],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 移除流动性 (TRX-代币对)
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "token", "type" => "address"],
                    ["name" => "liquidity", "type" => "uint256"],
                    ["name" => "amountTokenMin", "type" => "uint256"],
                    ["name" => "amountETHMin", "type" => "uint256"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"]
                ],
                "name" => "removeLiquidityETH",
                "outputs" => [
                    ["name" => "amountToken", "type" => "uint256"],
                    ["name" => "amountETH", "type" => "uint256"]
                ],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 带授权签名的移除流动性
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "tokenA", "type" => "address"],
                    ["name" => "tokenB", "type" => "address"],
                    ["name" => "liquidity", "type" => "uint256"],
                    ["name" => "amountAMin", "type" => "uint256"],
                    ["name" => "amountBMin", "type" => "uint256"],
                    ["name" => "to", "type" => "address"],
                    ["name" => "deadline", "type" => "uint256"],
                    ["name" => "approveMax", "type" => "bool"],
                    ["name" => "v", "type" => "uint8"],
                    ["name" => "r", "type" => "bytes32"],
                    ["name" => "s", "type" => "bytes32"]
                ],
                "name" => "removeLiquidityWithPermit",
                "outputs" => [
                    ["name" => "amountA", "type" => "uint256"],
                    ["name" => "amountB", "type" => "uint256"]
                ],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],

            // ========== 实用方法 ==========
            // 查询工厂合约地址
            [
                "constant" => true,
                "inputs" => [],
                "name" => "factory",
                "outputs" => [["name" => "", "type" => "address"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 查询 WTRX 合约地址
            [
                "constant" => true,
                "inputs" => [],
                "name" => "WETH",
                "outputs" => [["name" => "", "type" => "address"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ]
        ];
    }

    /**
     * SunSwap V3 路由合约 ABI
     * SunSwap V3 提供集中流动性功能，相比 V2 有更高的资本效率
     *
     * @return array SunSwap V3 路由合约 ABI 定义数组
     */
    public static function sunswapV3Router(): array
    {
        return [
            // ========== 交换方法 ==========
            // 精确输入交换
            [
                "name" => "swapExactInput",
                "type" => "function",
                "stateMutability" => "payable",
                "inputs" => [
                    ["internalType" => "address[]", "name" => "path", "type" => "address[]"],
                    ["internalType" => "string[]", "name" => "poolVersion", "type" => "string[]"],
                    ["internalType" => "uint256[]", "name" => "versionLen", "type" => "uint256[]"],
                    ["internalType" => "uint24[]", "name" => "fees", "type" => "uint24[]"],
                    [
                        "internalType" => "struct SwapData",
                        "name" => "data",
                        "type" => "tuple",
                        "components" => [
                            ["internalType" => "uint256", "name" => "amountIn", "type" => "uint256"],
                            ["internalType" => "uint256", "name" => "amountOutMin", "type" => "uint256"],
                            ["internalType" => "address", "name" => "to", "type" => "address"],
                            ["internalType" => "uint256", "name" => "deadline", "type" => "uint256"]
                        ]
                    ]
                ],
                "outputs" => [
                    ["internalType" => "uint256[]", "name" => "amountsOut", "type" => "uint256[]"]
                ]
            ],
            // 精确输出交换
            [
                "name" => "swapExactOutput",
                "type" => "function",
                "stateMutability" => "payable",
                "inputs" => [
                    ["internalType" => "address[]", "name" => "path", "type" => "address[]"],
                    ["internalType" => "string[]", "name" => "poolVersion", "type" => "string[]"],
                    ["internalType" => "uint256[]", "name" => "versionLen", "type" => "uint256[]"],
                    ["internalType" => "uint24[]", "name" => "fees", "type" => "uint24[]"],
                    [
                        "internalType" => "struct SwapData",
                        "name" => "data",
                        "type" => "tuple",
                        "components" => [
                            ["internalType" => "uint256", "name" => "amountOut", "type" => "uint256"],
                            ["internalType" => "uint256", "name" => "amountInMax", "type" => "uint256"],
                            ["internalType" => "address", "name" => "to", "type" => "address"],
                            ["internalType" => "uint256", "name" => "deadline", "type" => "uint256"]
                        ]
                    ]
                ],
                "outputs" => [
                    ["internalType" => "uint256[]", "name" => "amountsIn", "type" => "uint256[]"]
                ]
            ],
            // 多调用方法（批量执行多个操作）
            [
                "name" => "multicall",
                "type" => "function",
                "stateMutability" => "payable",
                "inputs" => [
                    ["internalType" => "bytes[]", "name" => "data", "type" => "bytes[]"]
                ],
                "outputs" => [
                    ["internalType" => "bytes[]", "name" => "results", "type" => "bytes[]"]
                ]
            ],

            // ========== 流动性管理方法 ==========
            // 创建新仓位
            [
                "name" => "mint",
                "type" => "function",
                "stateMutability" => "payable",
                "inputs" => [
                    ["internalType" => "address", "name" => "tokenA", "type" => "address"],
                    ["internalType" => "address", "name" => "tokenB", "type" => "address"],
                    ["internalType" => "uint24", "name" => "fee", "type" => "uint24"],
                    ["internalType" => "int24", "name" => "tickLower", "type" => "int24"],
                    ["internalType" => "int24", "name" => "tickUpper", "type" => "int24"],
                    ["internalType" => "uint256", "name" => "amount", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount0Min", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount1Min", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "deadline", "type" => "uint256"]
                ],
                "outputs" => [
                    ["internalType" => "uint256", "name" => "tokenId", "type" => "uint256"],
                    ["internalType" => "uint128", "name" => "liquidity", "type" => "uint128"],
                    ["internalType" => "uint256", "name" => "amount0", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount1", "type" => "uint256"]
                ]
            ],
            // 增加仓位流动性
            [
                "name" => "increaseLiquidity",
                "type" => "function",
                "stateMutability" => "payable",
                "inputs" => [
                    ["internalType" => "uint256", "name" => "tokenId", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount0Desired", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount1Desired", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount0Min", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount1Min", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "deadline", "type" => "uint256"]
                ],
                "outputs" => [
                    ["internalType" => "uint128", "name" => "liquidity", "type" => "uint128"],
                    ["internalType" => "uint256", "name" => "amount0", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount1", "type" => "uint256"]
                ]
            ],
            // 减少仓位流动性
            [
                "name" => "decreaseLiquidity",
                "type" => "function",
                "stateMutability" => "nonpayable",
                "inputs" => [
                    ["internalType" => "uint256", "name" => "tokenId", "type" => "uint256"],
                    ["internalType" => "uint128", "name" => "liquidity", "type" => "uint128"],
                    ["internalType" => "uint256", "name" => "amount0Min", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount1Min", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "deadline", "type" => "uint256"]
                ],
                "outputs" => [
                    ["internalType" => "uint256", "name" => "amount0", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount1", "type" => "uint256"]
                ]
            ],
            // 收集手续费
            [
                "name" => "collect",
                "type" => "function",
                "stateMutability" => "nonpayable",
                "inputs" => [
                    ["internalType" => "uint256", "name" => "tokenId", "type" => "uint256"],
                    ["internalType" => "address", "name" => "recipient", "type" => "address"],
                    ["internalType" => "uint128", "name" => "amount0Max", "type" => "uint128"],
                    ["internalType" => "uint128", "name" => "amount1Max", "type" => "uint128"]
                ],
                "outputs" => [
                    ["internalType" => "uint256", "name" => "amount0", "type" => "uint256"],
                    ["internalType" => "uint256", "name" => "amount1", "type" => "uint256"]
                ]
            ],

            // ========== 实用方法 ==========
            // 查询工厂合约地址
            [
                "name" => "factory",
                "type" => "function",
                "stateMutability" => "view",
                "inputs" => [],
                "outputs" => [
                    ["internalType" => "address", "name" => "", "type" => "address"]
                ]
            ],
            // 查询 WTRX 合约地址
            [
                "name" => "WETH9",
                "type" => "function",
                "stateMutability" => "view",
                "inputs" => [],
                "outputs" => [
                    ["internalType" => "address", "name" => "", "type" => "address"]
                ]
            ]
        ];
    }

    /**
     * Fibonacci 合约 ABI
     * 示例合约，包含斐波那契数列计算方法和通知事件
     *
     * @return array Fibonacci 合约 ABI 定义数组
     */
    public static function fibonacci(): array
    {
        return [
            // 计算斐波那契数并触发通知事件
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "number", "type" => "uint256"]
                ],
                "name" => "fibonacciNotify",
                "outputs" => [["name" => "result", "type" => "uint256"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
                "type" => "function"
            ],
            // 只读查询：计算斐波那契数（不触发事件）
            [
                "constant" => true,
                "inputs" => [
                    ["name" => "number", "type" => "uint256"]
                ],
                "name" => "fibonacci",
                "outputs" => [["name" => "result", "type" => "uint256"]],
                "payable" => false,
                "stateMutability" => "view",
                "type" => "function"
            ],
            // 通知事件：当调用 fibonacciNotify 时触发
            [
                "anonymous" => false,
                "inputs" => [
                    ["indexed" => false, "name" => "input", "type" => "uint256"],
                    ["indexed" => false, "name" => "result", "type" => "uint256"]
                ],
                "name" => "Notify",
                "type" => "event"
            ]
        ];
    }

    /**
     * 组合多种类型的 ABI
     * 可以根据需要组合不同合约的 ABI 定义
     *
     * @param array $types 要包含的 ABI 类型数组，支持：'erc20', 'v2router', 'v3router', 'fibonacci'
     * @return array 组合后的 ABI 定义数组
     */
    public static function combined(array $types = ['erc20', 'v2router', 'v3router', 'fibonacci']): array
    {
        $abi = [];

        // 添加 ERC20 代币 ABI
        if (in_array('erc20', $types)) {
            $abi = array_merge($abi, self::erc20());
        }

        // 添加 SunSwap V2 路由 ABI
        if (in_array('v2router', $types)) {
            $abi = array_merge($abi, self::sunswapV2Router());
        }

        // 添加 SunSwap V3 路由 ABI
        if (in_array('v3router', $types)) {
            $abi = array_merge($abi, self::sunswapV3Router());
        }

        // 添加 Fibonacci 合约 ABI
        if (in_array('fibonacci', $types)) {
            $abi = array_merge($abi, self::fibonacci());
        }

        return $abi;
    }

    /**
     * 获取可用的 ABI 类型列表
     * 返回所有支持的 ABI 类型及其说明
     *
     * @return array 可用类型的键值对数组，键为类型名，值为说明
     */
    public static function getAvailableTypes(): array
    {
        return [
            'erc20' => 'ERC20 标准代币方法 - 完整的代币接口',
            'v2router' => 'SunSwap V2 路由方法 - 包含价格查询和各种交换方法',
            'v3router' => 'SunSwap V3 路由方法 - 支持 V3 特有的集中流动性功能',
            'fibonacci' => 'Fibonacci 示例合约 - 包含斐波那契数列计算和通知事件'
        ];
    }
}