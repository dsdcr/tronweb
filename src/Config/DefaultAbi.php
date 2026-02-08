<?php
/**
 * 默认 ABI 配置类
 * 提供各种智能合约的 ABI 定义，包括 ERC20 代币、去中心化交易所路由等
 */

namespace Dsdcr\TronWeb\Config;

class DefaultAbi
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
            [
                'outputs' => [
                    ['type' => 'uint256[]', 'name' => 'amounts']
                ],
                'inputs' => [
                    ['type' => 'uint256', 'name' => 'amountOut'],
                    ['type' => 'address[]', 'name' => 'path']
                ],
                'name' => 'getAmountsIn',
                'stateMutability' => 'view',
                'type' => 'function'
            ],
            // 精确输入代币交换为 ETH：用指定数量的代币交换 ETH
            [
                "constant" => false,
                "inputs" => [
                    ["name" => "amountIn", "type" => "uint256"],          // 输入代币数量
                    ["name" => "amountOutMin", "type" => "uint256"],      // 最少输出 ETH 数量（滑点保护）
                    ["name" => "path", "type" => "address[]"],             // 交换路径
                    ["name" => "to", "type" => "address"],                 // 接收 ETH 的地址
                    ["name" => "deadline", "type" => "uint256"]           // 交易截止时间
                ],
                "name" => "swapExactTokensForETH",
                "outputs" => [["name" => "amounts", "type" => "uint256[]"]],
                "payable" => false,
                "stateMutability" => "nonpayable",
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
            // 精确输入交换：V3 交换函数，支持多个版本池子和自定义费率
            [
                "name" => "swapExactInput",
                "type" => "function",
                "stateMutability" => "payable",
                "inputs" => [
                    ["internalType" => "address[]", "name" => "path", "type" => "address[]"],           // 交换路径
                    ["internalType" => "string[]", "name" => "poolVersion", "type" => "string[]"],      // 池子版本
                    ["internalType" => "uint256[]", "name" => "versionLen", "type" => "uint256[]"],     // 版本长度
                    ["internalType" => "uint24[]", "name" => "fees", "type" => "uint24[]"],             // 费率等级
                    [
                        "internalType" => "struct SwapData",
                        "name" => "data",
                        "type" => "tuple",
                        "components" => [
                            ["internalType" => "uint256", "name" => "amountIn", "type" => "uint256"],      // 输入数量
                            ["internalType" => "uint256", "name" => "amountOutMin", "type" => "uint256"],  // 最少输出数量
                            ["internalType" => "address", "name" => "to", "type" => "address"],            // 接收地址
                            ["internalType" => "uint256", "name" => "deadline", "type" => "uint256"]       // 截止时间
                        ]
                    ]
                ],
                "outputs" => [
                    ["internalType" => "uint256[]", "name" => "amountsOut", "type" => "uint256[]"]        // 每步的输出数量
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
     * @param array $types 要包含的 ABI 类型数组，支持：'erc20', 'router', 'v3router', 'fibonacci'
     * @return array 组合后的 ABI 定义数组
     */
    public static function combined(array $types = ['erc20', 'router', 'v3router', 'fibonacci']): array
    {
        $abi = [];

        // 添加 ERC20 代币 ABI
        if (in_array('erc20', $types)) {
            $abi = array_merge($abi, self::erc20());
        }

        // 添加 SunSwap V2 路由 ABI
        if (in_array('router', $types)) {
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
            'router' => 'SunSwap V2 路由方法 - 包含价格查询和各种交换方法',
            'v3router' => 'SunSwap V3 路由方法 - 支持 V3 特有的集中流动性功能',
            'fibonacci' => 'Fibonacci 示例合约 - 包含斐波那契数列计算和通知事件'
        ];
    }
}