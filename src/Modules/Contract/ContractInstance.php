<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules\Contract;

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\Support\Ethabi;
use Dsdcr\TronWeb\Support\Keccak;
use Dsdcr\TronWeb\Config\DefaultAbi;

class ContractInstance
{
    protected TronWeb $tronWeb;
    protected array $abi;
    protected ?string $address;
    protected ?string $bytecode;
    protected bool $deployed;
    protected array $methods = [];
    protected array $methodInstances = [];

    // 用于支持链式调用
    protected ?array $pendingCall = null;

    /**
     * 创建合约实例
     *
     * @param TronWeb $tronWeb TronWeb实例
     * @param array|string $abi ABI接口定义
     * @param string|null $address 合约地址
     * @throws TronException
     */
    public function __construct(TronWeb $tronWeb, $abi = [], ?string $address = null)
    {
        if (!$tronWeb instanceof TronWeb) {
            throw new TronException('Expected instance of TronWeb');
        }

        $this->tronWeb = $tronWeb;
        $this->address = $address;
        $this->bytecode = null;

        // 处理ABI参数
        if (is_string($abi)) {
            $abi = json_decode($abi, true) ?? [];
        }
        // 如果ABI为空，使用默认的Fibonacci合约ABI
        if (empty($abi) || (is_array($abi) && count($abi) === 0)) {
            $abi = DefaultAbi::combined();
        }
        //print_r($abi);exit;

        $this->abi = is_array($abi) ? $abi : [];

        // 检查是否已部署
        $this->deployed = $address !== null && TronUtils::isAddress($address);

        $this->loadAbi($this->abi);
    }

    /**
     * 加载ABI接口定义
     *
     * 复用 Ethabi 工具类进行ABI处理
     *
     * @param array $abi ABI数组
     */
    protected function loadAbi(array $abi): void
    {
        $this->methods = [];
        $this->methodInstances = [];

        foreach ($abi as $item) {
            // 跳过构造函数和错误函数
            if (!isset($item['type']) || in_array(strtolower($item['type']), ['constructor', 'error'])) {
                continue;
            }

            $method = new ContractMethod($this, $item);
            $methodName = $item['name'] ?? '';

            if ($methodName) {
                $this->methods[$methodName] = $method;
                $this->methodInstances[$methodName] = $method;
            }
        }
    }

    /**
     * 动态调用合约方法
     *
     * @param string $name 方法名
     * @param array $arguments 参数数组
     * @return mixed
     * @throws TronException
     */
    public function __call(string $name, array $arguments)
    {
        // 检查是否是 send() 调用
        if ($name === 'send' && isset($this->pendingCall)) {
            $method = $this->pendingCall['method'];
            $args = $this->pendingCall['arguments'];
            $options = $this->pendingCall['options'] ?? [];
            $fromAddress = $this->pendingCall['fromAddress'];

            // 检查传入的 send() 参数
            $sendOptions = $arguments[0] ?? [];
            $mergedOptions = array_merge($options, $sendOptions);

            // 清除 pending 状态
            $this->pendingCall = null;

            // 调用方法获取交易
            $result = $method->call(...$args);

            // 创建 TransactionWrapper 并立即调用 send()
            $wrapper = new TransactionWrapper(
                $this->tronWeb,
                $result,
                $fromAddress,
                $mergedOptions
            );

            return $wrapper->send();
        }

        // 检查是否是 sign() 调用
        if ($name === 'sign' && isset($this->pendingCall)) {
            $method = $this->pendingCall['method'];
            $args = $this->pendingCall['arguments'];
            $fromAddress = $this->pendingCall['fromAddress'];

            $this->pendingCall = null;
            $result = $method->call(...$args);

            return new TransactionWrapper(
                $this->tronWeb,
                $result,
                $fromAddress
            );
        }

        if (isset($this->methods[$name])) {
            $method = $this->methods[$name];

            // 如果是 view/pure 方法，直接返回结果
            $stateMutability = strtolower($method->getAbi()['stateMutability'] ?? 'nonpayable');
            if (in_array($stateMutability, ['view', 'pure'])) {
                return $method->call(...$arguments);
            }

            // 检查是否有 options 参数
            $options = [];
            $fromAddress = null;
            if (!empty($arguments) && is_array(end($arguments))) {
                $lastArg = end($arguments);
                $optionKeys = ['feeLimit', 'callValue', 'bandwidthLimit', 'fromAddress', 'permissionId'];
                $intersect = array_intersect($optionKeys, array_keys($lastArg));
                $isOptions = !empty($intersect);

                if ($isOptions) {
                    $options = array_pop($arguments);
                    $fromAddress = $options['fromAddress'] ?? null;
                }
            }

            // 保存待调用信息，返回支持链式调用的对象
            $this->pendingCall = [
                'method' => $method,
                'arguments' => $arguments,
                'fromAddress' => $fromAddress,
                'options' => $options
            ];

            return $this;
        }

        throw new TronException("Contract method {$name} not found in ABI");
    }

    /**
     * 触发合约调用
     *
     * 复用 TransactionBuilder 的 triggerSmartContract 方法
     * 支持 TypeScript 风格的选项参数
     *
     * @param string $function 函数名
     * @param array $params 参数数组
     * @param array $options 选项
     * @return mixed
     * @throws TronException
     */
    public function trigger(string $function, array $params = [], array $options = [])
    {
        if (!$this->address) {
            throw new TronException('Contract address not specified');
        }

        if (!isset($this->methods[$function])) {
            throw new TronException("Function {$function} not found in contract ABI");
        }

        return $this->tronWeb->transactionBuilder->triggerSmartContract(
            $this->abi,
            TronUtils::toHex($this->address),
            $function,
            $params,
            $options
        );
    }

    /**
     * 触发只读合约调用
     *
     * 复用 TransactionBuilder 的 triggerConstantContract 方法
     * 支持 fromAddress 选项
     *
     * @param string $function 函数名
     * @param array $params 参数数组
     * @param string|null $fromAddress 调用地址（Base58 或十六进制格式）
     * @return mixed
     * @throws TronException
     */
    public function triggerConstant(string $function, array $params = [], ?string $fromAddress = null)
    {
        if (!$this->address) {
            throw new TronException('Contract address not specified');
        }

        if (!isset($this->methods[$function])) {
            throw new TronException("Function {$function} not found in contract ABI");
        }

        // 转换地址格式
        if ($fromAddress && TronUtils::isAddress($fromAddress)) {
            $fromAddress = TronUtils::toHex($fromAddress);
        } else {
            $fromAddress = $fromAddress ?: '410000000000000000000000000000000000000000';
        }

        return $this->tronWeb->transactionBuilder->triggerConstantContract(
            $this->abi,
            TronUtils::toHex($this->address),
            $function,
            $params,
            $fromAddress
        );
    }

    /**
     * 设置合约地址
     *
     * @param string $address 合约地址
     * @return self
     * @throws TronException
     */
    public function at(string $address): self
    {
        if (!TronUtils::isAddress($address)) {
            throw new TronException('Invalid contract address');
        }

        $this->address = $address;
        $this->deployed = true;
        return $this;
    }

    /**
     * 设置合约字节码
     *
     * @param string $bytecode 字节码
     * @return self
     */
    public function setBytecode(string $bytecode): self
    {
        $this->bytecode = $bytecode;
        return $this;
    }

    /**
     * 获取合约地址
     *
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * 是否已部署
     *
     * @return bool
     */
    public function isDeployed(): bool
    {
        return $this->deployed;
    }

    /**
     * 获取ABI
     *
     * @return array
     */
    public function getAbi(): array
    {
        return $this->abi;
    }

    /**
     * 获取字节码
     *
     * @return string|null
     */
    public function getBytecode(): ?string
    {
        return $this->bytecode;
    }

    /**
     * 获取 TronWeb 实例
     *
     * @return TronWeb
     */
    public function getTronWeb(): TronWeb
    {
        return $this->tronWeb;
    }

    /**
     * 解码交易输入数据
     *
     * 复用 Keccak 和 Ethabi 工具类
     *
     * @param string $data 输入数据
     * @return array
     * @throws TronException
     */
    public function decodeInput(string $data): array
    {
        $methodId = substr($data, 0, 8);
        $inputData = substr($data, 8);

        $ethabi = new Ethabi();

        foreach ($this->abi as $item) {
            if (isset($item['name']) && $item['type'] === 'function') {
                $signature = $item['name'] . '(';
                $inputs = array_map(fn($input) => $input['type'], $item['inputs'] ?? []);
                $signature .= implode(',', $inputs) . ')';

                // 使用 Keccak 工具类计算函数选择器
                $hash = Keccak::hash($signature, 256);
                $methodHash = substr($hash, 0, 8);

                if ($methodHash === $methodId) {
                    return [
                        'method' => $item['name'],
                        'params' => $ethabi->decodeParameters($item, $inputData)
                    ];
                }
            }
        }

        throw new TronException('Method not found for input data');
    }
}