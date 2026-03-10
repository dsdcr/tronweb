<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules\Contract;

use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\Support\Keccak;

class ContractMethod
{
    protected ContractInstance $contract;
    protected array $abi;
    protected string $name;
    protected string $type;
    protected array $inputs;
    protected array $outputs;
    protected bool $returnWrapper = false;
    protected ?string $fromAddress = null;
    protected array $sendOptions = [];

    /**
     * 创建合约方法实例
     *
     * @param ContractInstance $contract 合约实例
     * @param array $abi ABI方法定义
     * @throws TronException
     */
    public function __construct(ContractInstance $contract, array $abi)
    {
        $this->contract = $contract;
        $this->abi = $abi;
        $this->name = $abi['name'] ?? '';
        $this->type = strtolower($abi['type'] ?? 'function');
        $this->inputs = $abi['inputs'] ?? [];
        $this->outputs = $abi['outputs'] ?? [];

        if (!$this->name) {
            throw new TronException('Contract method must have a name');
        }
    }

    /**
     * 调用合约方法
     *
     * @param mixed ...$arguments 方法参数
     * @return mixed
     * @throws TronException
     */
    public function call(...$arguments)
    {
        if ($this->type === 'function') {
            $stateMutability = strtolower($this->abi['stateMutability'] ?? 'nonpayable');

            if (in_array($stateMutability, ['view', 'pure'])) {
                return $this->callConstant(...$arguments);
            } else {
                return $this->callTransaction(...$arguments);
            }
        }

        throw new TronException("Unsupported contract method type: {$this->type}");
    }

    /**
     * 启用 send() 链式调用
     *
     * 使用后，调用写入方法将返回 TransactionWrapper 对象
     * 支持链式调用 ->send() 方法
     *
     * @param string|array|null $fromAddressOrOptions 发送地址（字符串）或交易选项（数组）
     * @return self
     *
     * @example
     * $contract = $tronWeb->contract($abi)->at($address);
     *
     * // 只传递发送地址
     * $result = $contract->transfer($to, $amount)->send('TAddress...');
     *
     * // 传递交易选项（推荐）
     * $result = $contract->transfer($to, $amount)->send(['feeLimit' => 1000000]);
     *
     * // 传递发送地址和选项
     * $result = $contract->transfer($to, $amount)->send([
     *     'fromAddress' => 'TAddress...',
     *     'feeLimit' => 1000000
     * ]);
     */
    public function send($fromAddressOrOptions = null): self
    {
        $this->returnWrapper = true;

        if (is_string($fromAddressOrOptions)) {
            // 字符串参数作为 fromAddress
            $this->fromAddress = $fromAddressOrOptions;
        } elseif (is_array($fromAddressOrOptions)) {
            // 数组参数作为 options，保存 fromAddress
            $this->fromAddress = $fromAddressOrOptions['fromAddress'] ?? null;
            // 这里需要将 options 传递给 TransactionWrapper
            // 但我们需要一个地方存储它
            $this->sendOptions = $fromAddressOrOptions;
        }

        return $this;
    }

    /**
     * 调用只读合约方法
     * 复用 triggerConstant 方法，支持 fromAddress 选项
     *
     * @param mixed ...$arguments 方法参数
     * @return mixed
     * @throws TronException
     */
    protected function callConstant(...$arguments)
    {
        // 查找 options 参数（最后一个参数如果是数组，并且包含 fromAddress）
        $fromAddress = null;
        $methodParams = $arguments;

        if (!empty($arguments) && is_array(end($arguments))) {
            $lastArg = end($arguments);
            if (isset($lastArg['fromAddress'])) {
                $fromAddress = $lastArg['fromAddress'];
                array_pop($arguments);
                $methodParams = array_slice($arguments, 0);
            }
        }

        $params = $this->validateAndPrepareParams($methodParams);

        return $this->contract->triggerConstant(
            $this->name,
            $params,
            $fromAddress
        );
    }

    /**
     * 调用交易合约方法
     * 复用 trigger 方法，支持 TypeScript 风格的 options 参数
     *
     * @param mixed ...$arguments 方法参数
     * @return mixed
     * @throws TronException
     */
    protected function callTransaction(...$arguments)
    {
        // 查找 options 参数（最后一个参数如果是数组，并且包含交易选项键名）
        $options = [];
        $methodParams = $arguments;

        if (!empty($arguments) && is_array(end($arguments))) {
            $lastArg = end($arguments);
            // 检查最后一个参数是否是 options（包含特定的选项键名）
            $optionKeys = ['feeLimit', 'callValue', 'bandwidthLimit', 'fromAddress', 'permissionId'];
            // 如果最后一个参数包含至少一个选项键，就认为是options
            $intersect = array_intersect($optionKeys, array_keys($lastArg));
            $isOptions = !empty($intersect);

            if ($isOptions) {
                $options = array_pop($arguments);
                $methodParams = array_slice($arguments, 0);
            }
        }

        $params = $this->validateAndPrepareParams($methodParams);

        // 如果启用了 wrapper 模式，需要先合并 options（send() 传递的优先级更高）
        // 这样 triggerSmartContract 在构建交易时就能使用正确的选项
        if ($this->returnWrapper) {
            // 合并 send() 传递的 options 和方法参数中的 options
            $finalOptions = array_merge($options, $this->sendOptions);

            // 用合并后的 options 调用 trigger
            $result = $this->contract->trigger(
                $this->name,
                $params,
                $finalOptions
            );

            $this->returnWrapper = false;
            $fromAddr = $this->fromAddress ?: ($finalOptions['fromAddress'] ?? null);

            return new \Dsdcr\TronWeb\Modules\Contract\TransactionWrapper(
                $this->contract->getTronWeb(),
                $result,
                $fromAddr,
                $finalOptions
            );
        } else {
            // 非 wrapper 模式，直接使用原始 options
            return $this->contract->trigger(
                $this->name,
                $params,
                $options
            );
        }
    }

    /**
     * 验证并准备参数
     *
     * 使用 TronUtils 进行地址验证
     *
     * @param array $arguments 原始参数
     * @return array 索引数组，按参数顺序排列
     * @throws TronException
     */
    protected function validateAndPrepareParams(array $arguments): array
    {
        $expectedCount = count($this->inputs);
        $actualCount = count($arguments);

        if ($actualCount < $expectedCount) {
            throw new TronException(
                "Method {$this->name} expects {$expectedCount} arguments, {$actualCount} given"
            );
        }

        // 转换参数为正确的格式，返回索引数组
        $params = [];
        foreach ($this->inputs as $index => $input) {
            $paramType = $input['type'] ?? 'string';
            $value = $arguments[$index] ?? null;

            $params[$index] = $this->convertParam($value, $paramType);
        }

        return $params;
    }

    /**
     * 转换参数到指定类型
     *
     * 复用 TronUtils 进行地址验证
     *
     * @param mixed $value 参数值
     * @param string $type 目标类型
     * @return mixed
     * @throws TronException
     */
    protected function convertParam($value, string $type)
    {
        if ($value === null) {
            throw new TronException("Parameter of type {$type} cannot be null");
        }

        switch ($type) {
            case 'address':
                if (!is_string($value)) {
                    throw new TronException("Address parameter must be a string");
                }
                // 使用新的验证服务转换地址为十六进制格式
                return \Dsdcr\TronWeb\Support\AddressValidator::validateAndNormalize($value, 'contract address parameter', 'hex');

            case 'uint256':
            case 'uint128':
            case 'uint64':
            case 'uint32':
            case 'uint16':
            case 'uint8':
            case 'int256':
            case 'int128':
            case 'int64':
            case 'int32':
            case 'int16':
            case 'int8':
                if (!is_numeric($value)) {
                    throw new TronException("{$type} parameter must be numeric");
                }
                return (string)$value;

            case 'bool':
                if (!is_bool($value)) {
                    throw new TronException("Bool parameter must be boolean");
                }
                return $value;

            case 'string':
                if (!is_string($value)) {
                    throw new TronException("String parameter must be a string");
                }
                return $value;

            case 'bytes':
            case 'bytes32':
                if (!is_string($value)) {
                    throw new TronException("Bytes parameter must be a string");
                }
                return $value;

            case 'string[]':
                if (!is_array($value)) {
                    throw new TronException("String array parameter must be an array");
                }
                return $value;

            case 'address[]':
                if (!is_array($value)) {
                    throw new TronException("Address array parameter must be an array");
                }
                // 转换地址数组中的每个地址
                return array_map(fn($addr) => $this->convertParam($addr, 'address'), $value);

            default:
                // 处理其他数组类型 uint256[] 等
                if (str_ends_with($type, '[]')) {
                    if (!is_array($value)) {
                        throw new TronException("Array parameter must be an array");
                    }

                    $baseType = substr($type, 0, -2);
                    return array_map(fn($item) => $this->convertParam($item, $baseType), $value);
                }

                return $value;
        }
    }

    /**
     * 获取方法名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取方法类型
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * 获取输入参数定义
     *
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * 获取输出参数定义
     *
     * @return array
     */
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /**
     * 获取完整的ABI定义
     *
     * @return array
     */
    public function getAbi(): array
    {
        return $this->abi;
    }

    /**
     * 获取方法签名
     *
     * @return string
     */
    public function getSignature(): string
    {
        $inputTypes = array_map(function ($input) {
            // 处理元组类型的签名生成
            if ($input['type'] === 'tuple' && isset($input['components'])) {
                // 对于元组类型，生成完整的components签名
                $components = array_map(fn($component) => $component['type'], $input['components']);
                return '(' . implode(',', $components) . ')';
            }
            return $input['type'];
        }, $this->inputs);

        return $this->name . '(' . implode(',', $inputTypes) . ')';
    }

    /**
     * 获取方法选择器（函数签名哈希的前4字节）
     *
     * 复用 Keccak 工具类
     *
     * @return string
     */
    public function getSelector(): string
    {
        $signature = $this->getSignature();
        // 使用 Keccak 工具类计算函数选择器
        $hash = Keccak::hash($signature, 256);
        return substr($hash, 0, 8);
    }

    /**
     * 获取方法实例（用于支持 send() 调用）
     *
     * @return self
     */
    public function getInstance(): self
    {
        return $this;
    }
}