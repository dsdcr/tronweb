<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb;

use Dsdcr\TronWeb\Provider\HttpProviderInterface;
use Dsdcr\TronWeb\Support\TronUtils;
use Dsdcr\TronWeb\Exception\TronException;

/**
 * TronWeb - Tron API 的主要入口点
 * 一个现代化的模块化SDK，用于与Tron区块链交互
 *
 * @package Dsdcr\TronWeb
 */
class TronWeb
{
    /**
     * @var array 配置数组
     */
    protected $config;

    /**
     * @var HttpProviderInterface 全节点提供者
     */
    protected $fullNode;

    /**
     * @var HttpProviderInterface|null 固态节点提供者
     */
    protected $solidityNode;

    /**
     * @var HttpProviderInterface|null 事件服务器提供者
     */
    protected $eventServer;

    /**
     * @var HttpProviderInterface|null 签名服务器提供者
     */
    protected $signServer;

    /**
     * @var string|null 私钥用于交易签名
     */
    protected $privateKey;

    /**
     * @var array|null 默认地址信息
     */
    protected $defaultAddress;

    /**
     * @var Modules\Trx TRX模块实例
     */
    public $trx;

    /**
     * @var Modules\Contract 合约模块实例
     */
    public $contract;

    /**
     * @var Modules\Account 账户模块实例
     */
    public $account;

    /**
     * @var TronUtils 工具类实例
     */
    public $utils;

    /**
     * @var Modules\Token 代币模块实例
     */
    public $token;

    /**
     * @var Modules\Resource 资源模块实例
     */
    public $resource;

    /**
     * @var Modules\Network 网络模块实例
     */
    public $network;

    /**
     * 创建新的TronWeb实例
     *
     * @param array $config 配置选项
     * @throws TronException
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'fullNode' => null,
            'solidityNode' => null,
            'eventServer' => null,
            'signServer' => null,
            'privateKey' => null,
            'defaultAddress' => null,
        ], $config);

        $this->initializeProviders();
        $this->initializeModules();
        $this->initializeUtils();

        // Set default private key and address if provided
        if ($this->config['privateKey']) {
            $this->setPrivateKey($this->config['privateKey']);
        }
    }

    /**
     * 初始化提供者实例
     *
     * @throws TronException
     */
    protected function initializeProviders(): void
    {
        $this->fullNode = $this->config['fullNode'];
        $this->solidityNode = $this->config['solidityNode'];
        $this->eventServer = $this->config['eventServer'];
        $this->signServer = $this->config['signServer'];

        if (!$this->fullNode) {
            throw new TronException('Full node provider is required');
        }
    }

    /**
     * 初始化模块
     */
    protected function initializeModules(): void
    {
        $this->trx = new Modules\Trx($this);
        $this->contract = new Modules\Contract($this);
        $this->account = new Modules\Account($this);
        $this->token = new Modules\Token($this);
        $this->resource = new Modules\Resource($this);
        $this->network = new Modules\Network($this);
    }

    /**
     * 初始化工具类
     */
    protected function initializeUtils(): void
    {
        $this->utils = new TronUtils();
    }

    /**
     * 获取全节点提供者
     *
     * @return HttpProviderInterface
     */
    public function getFullNode(): HttpProviderInterface
    {
        return $this->fullNode;
    }

    /**
     * 获取固态节点提供者
     *
     * @return HttpProviderInterface|null
     */
    public function getSolidityNode(): ?HttpProviderInterface
    {
        return $this->solidityNode;
    }

    /**
     * 获取事件服务器提供者
     *
     * @return HttpProviderInterface|null
     */
    public function getEventServer(): ?HttpProviderInterface
    {
        return $this->eventServer;
    }

    /**
     * 获取签名服务器提供者
     *
     * @return HttpProviderInterface|null
     */
    public function getSignServer(): ?HttpProviderInterface
    {
        return $this->signServer;
    }

    /**
     * 设置用于交易签名的私钥
     * 如果没有提供 defaultAddress，会自动从私钥推导出地址
     *
     * @param string $privateKey 私钥
     * @param bool $autoDeriveAddress 是否自动从私钥推导出地址
     * @return self
     * @throws TronException
     */
    public function setPrivateKey(string $privateKey, bool $autoDeriveAddress = true): self
    {
        $this->privateKey = $privateKey;

        // 如果没有设置 defaultAddress，自动从私钥推导出地址
        if ($autoDeriveAddress && $this->defaultAddress === null) {
            // 使用 Account 模块的现有方法从私钥恢复地址
            $address = $this->account->recoverAddressFromPrivateKey($privateKey);

            // 将地址转换为十六进制格式
            $addressHex = TronUtils::addressToHex($address);

            $this->defaultAddress = [
                'hex' => $addressHex,
                'base58' => $address
            ];
        }

        return $this;
    }

    /**
     * 获取私钥
     *
     * @return string|null
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * 设置操作用的默认地址
     *
     * @param string $address 地址
     * @return self
     * @throws TronException
     */
    public function setDefaultAddress(string $address): self
    {
        $addressHex = TronUtils::addressToHex($address);
        $this->defaultAddress = [
            'hex' => $addressHex,
            'base58' => TronUtils::hexToAddress($addressHex)
        ];
        return $this;
    }

    /**
     * 获取默认地址
     *
     * @return array|null
     */
    public function getDefaultAddress(): ?array
    {
        return $this->defaultAddress;
    }

    /**
     * 向Tron网络发起请求
     *
     * @param string $endpoint 端点路径
     * @param array $params 请求参数
     * @param string $providerType 提供者类型
     * @return array
     * @throws TronException
     */
    public function request(string $endpoint, array $params = [], string $providerType = 'fullNode'): array
    {
        $provider = $this->getProviderByType($providerType);

        if (!$provider) {
            throw new TronException("Provider {$providerType} not configured");
        }

        return $provider->request($endpoint, $params);
    }

    /**
     * 根据类型获取提供者
     *
     * @param string $type 提供者类型
     * @return HttpProviderInterface|null
     */
    protected function getProviderByType(string $type): ?HttpProviderInterface
    {
        return match ($type) {
            'solidityNode' => $this->solidityNode,
            'eventServer' => $this->eventServer,
            'signServer' => $this->signServer,
            default => $this->fullNode,
        };
    }

    /**
     * 检查提供者是否配置且有效
     *
     * @param string $providerType 提供者类型
     * @return bool
     */
    public function isProviderConfigured(string $providerType): bool
    {
        $provider = $this->getProviderByType($providerType);
        return $provider !== null;
    }
}