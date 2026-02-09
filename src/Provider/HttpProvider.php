<?php declare(strict_types=1);

namespace Dsdcr\TronWeb\Provider;

use GuzzleHttp\{Psr7\Request, Client, ClientInterface};
use Psr\Http\Message\StreamInterface;
use Dsdcr\TronWeb\Exception\{NotFoundException, TronException};
use Dsdcr\TronWeb\Support\TronUtils;

/**
 * HTTP 提供者类
 *
 * 负责与 Tron 网络节点的所有 HTTP 通信
 * 基于 Guzzle HTTP 客户端实现
 *
 * 主要功能：
 * - HTTP 请求发送和管理
 * - 连接状态检查
 * - 自动重试机制
 * - 智能节点类型处理（fullnode/solidity）
 * - 配置验证和管理
 *
 * @package Dsdcr\TronWeb\Provider
 */
class HttpProvider implements HttpProviderInterface
{
    /**
     * HTTP 客户端处理器
     *
     * 使用 Guzzle HTTP 客户端执行实际的 HTTP 请求
     *
     * @var ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * 服务器或 RPC URL
     *
     * Tron 节点的完整 URL（如 https://api.trongrid.io）
     *
     * @var string
     */
    protected string $host;

    /**
     * 请求等待时间（毫秒）
     *
     * HTTP 请求的超时时间，单位为毫秒
     * 默认值为 30000 毫秒（30 秒）
     *
     * @var int
     */
    protected int $timeout;

    /**
     * 自定义 HTTP 头部信息
     *
     * 用于附加到每个 HTTP 请求的额外头部
     * 例如 API 密钥、内容类型等
     *
     * @var array
     */
    protected array $headers;

    /**
     * 节点类型（fullnode 或 solidity）
     *
     * fullnode: 全节点，提供完整的区块链数据
     * solidity: 固态节点，提供历史状态的查询
     *
     * @var string
     */
    protected string $nodeType;

    /**
     * 连接超时时间（秒）
     *
     * 建立连接时的超时时间，单位为秒
     * 默认值为 10 秒
     *
     * @var int
     */
    protected int $connectTimeout;

    /**
     * 重试次数
     *
     * 请求失败时的自动重试次数
     * 默认值为 3 次
     *
     * @var int
     */
    protected int $retries;

    /**
     * 默认配置值
     *
     * 类中所有属性的默认值常量
     *
     * @var array
     */
    private const DEFAULT_CONFIG = [
        'timeout' => 30000,           // 默认请求超时 30 秒
        'headers' => [],              // 默认无自定义头部
        'nodeType' => 'fullnode',      // 默认为全节点
        'connectTimeout' => 10,        // 默认连接超时 10 秒
        'retries' => 3                // 默认重试 3 次
    ];

    /**
     * 有效的节点类型列表
     *
     * 用于验证节点类型参数是否合法
     *
     * @var array
     */
    private const VALID_NODE_TYPES = ['fullnode', 'solidity'];

    /**
     * 创建 HttpProvider 对象
     *
     * 支持两种构造方式以保持向后兼容：
     * 1. 旧版：通过主机字符串和配置数组
     * 2. 新版：通过配置数组（必须包含 host 键）
     *
     * @param string|array $hostOrConfig 主机 URL 或配置数组
     * @param array $config 附加配置选项
     * @throws TronException 当配置无效时抛出
     *
     * @example 旧版方式（字符串配置）
     * $provider = new HttpProvider('https://api.trongrid.io', [
     *     'timeout' => 60000
     * ]);
     *
     * @example 新版方式（数组配置）
     * $provider = new HttpProvider([
     *     'host' => 'https://api.trongrid.io',
     *     'timeout' => 60000
     * ]);
     */
    public function __construct($hostOrConfig, array $config = [])
    {
        // 处理新旧两种构造函数签名
        if (is_string($hostOrConfig)) {
            $this->initializeFromHost($hostOrConfig, $config);
        } elseif (is_array($hostOrConfig)) {
            $this->initializeFromConfig($hostOrConfig);
        } else {
            throw new TronException('Invalid constructor parameters. Expected string host or array config.');
        }

        // 验证所有配置参数
        $this->validateConfiguration();

        // 创建 HTTP 客户端实例
        $this->httpClient = $this->createHttpClient();
    }

    /**
     * 从主机字符串初始化（向后兼容）
     *
     * 旧版构造函数使用方式，通过单独的参数传递配置
     *
     * @param string $host 节点主机 URL
     * @param array $config 配置选项
     */
    private function initializeFromHost(string $host, array $config): void
    {
        $this->host = $host;
        $this->timeout = $config['timeout'] ?? self::DEFAULT_CONFIG['timeout'];
        $this->headers = $config['headers'] ?? self::DEFAULT_CONFIG['headers'];
        $this->nodeType = $config['nodeType'] ?? self::DEFAULT_CONFIG['nodeType'];
        $this->connectTimeout = $config['connectTimeout'] ?? self::DEFAULT_CONFIG['connectTimeout'];
        $this->retries = $config['retries'] ?? self::DEFAULT_CONFIG['retries'];
    }

    /**
     * 从配置数组初始化
     *
     * 新版构造函数使用方式，通过统一的配置数组传递所有参数
     *
     * @param array $config 配置数组，必须包含 'host' 键
     * @throws TronException 当配置缺少必需字段时抛出
     */
    private function initializeFromConfig(array $config): void
    {
        if (!isset($config['host'])) {
            throw new TronException('Host is required in configuration array');
        }

        $this->host = $config['host'];
        $this->timeout = $config['timeout'] ?? self::DEFAULT_CONFIG['timeout'];
        $this->headers = $config['headers'] ?? self::DEFAULT_CONFIG['headers'];
        $this->nodeType = $config['nodeType'] ?? self::DEFAULT_CONFIG['nodeType'];
        $this->connectTimeout = $config['connectTimeout'] ?? self::DEFAULT_CONFIG['connectTimeout'];
        $this->retries = $config['retries'] ?? self::DEFAULT_CONFIG['retries'];
    }

    /**
     * 验证配置参数
     *
     * 检查所有配置参数的合法性，确保可以正常工作
     *
     * @throws TronException 当配置无效时抛出
     */
    private function validateConfiguration(): void
    {
        // 验证 URL 格式
        if (!TronUtils::isValidUrl($this->host)) {
            throw new TronException('Invalid URL provided to HttpProvider');
        }

        // 验证超时时间（必须大于等于 0）
        if ($this->timeout < 0) {
            throw new TronException('Invalid timeout duration provided');
        }

        // 验证头部必须是数组
        if (!is_array($this->headers)) {
            throw new TronException('Headers must be an array');
        }

        // 验证节点类型必须是合法值
        if (!in_array($this->nodeType, self::VALID_NODE_TYPES)) {
            throw new TronException('Invalid node type. Must be "fullnode" or "solidity"');
        }

        // 验证连接超时时间
        if ($this->connectTimeout < 0) {
            throw new TronException('Invalid connect timeout duration provided');
        }

        // 验证重试次数
        if ($this->retries < 0) {
            throw new TronException('Invalid retries count provided');
        }
    }

    /**
     * 创建 HTTP 客户端实例
     *
     * 使用 Guzzle HTTP 客户端创建实例
     * 配置超时、连接超时和默认头部
     *
     * @return ClientInterface Guzzle HTTP 客户端实例
     */
    private function createHttpClient(): ClientInterface
    {
        return new Client([
            'base_uri' => $this->host,
            'timeout' => $this->timeout / 1000,  // 将毫秒转换为秒，Guzzle 使用秒
            'connect_timeout' => 10,
            'headers' => $this->headers
        ]);
    }

    /**
     * 从主机 URL 创建 HttpProvider（工厂方法）
     *
     * 快捷方法，直接通过 URL 创建实例
     *
     * @param string $host 节点主机 URL
     * @param array $options 附加选项
     * @return static 新的 HttpProvider 实例
     * @throws TronException 当配置无效时抛出
     *
     * @example
     * $provider = HttpProvider::create('https://api.trongrid.io');
     */
    public static function create(string $host, array $options = []): self
    {
        return new self($host, $options);
    }

    /**
     * 从配置数组创建 HttpProvider（工厂方法）
     *
     * 快捷方法，通过配置数组创建实例
     *
     * @param array $config 配置数组
     * @return static 新的 HttpProvider 实例
     * @throws TronException 当配置无效时抛出
     *
     * @example
     * $provider = HttpProvider::fromConfig([
     *     'host' => 'https://api.trongrid.io',
     *     'timeout' => 60000
     * ]);
     */
    public static function fromConfig(array $config): self
    {
        return new self($config);
    }

    /**
     * 创建主网 HttpProvider（工厂方法）
     *
     * 快捷方法，自动使用 Tron 主网 URL
     *
     * @param bool $useSolidity 是否使用 Solidity 节点
     * @param array $options 附加选项
     * @return static 新的 HttpProvider 实例
     * @throws TronException 当配置无效时抛出
     *
     * @example
     * // 使用全节点
     * $provider = HttpProvider::mainnet();
     *
     * @example
     * // 使用 Solidity 节点
     * $provider = HttpProvider::mainnet(true);
     */
    public static function mainnet(bool $useSolidity = false, array $options = []): self
    {
        $config = array_merge($options, [
            'host' => 'https://api.trongrid.io',
            'nodeType' => $useSolidity ? 'solidity' : 'fullnode'
        ]);

        return new self($config);
    }

    /**
     * 创建测试网 HttpProvider（工厂方法）
     *
     * 快捷方法，自动使用 Tron Shasta 测试网 URL
     *
     * @param bool $useSolidity 是否使用 Solidity 节点
     * @param array $options 附加选项
     * @return static 新的 HttpProvider 实例
     * @throws TronException 当配置无效时抛出
     *
     * @example
     * // 使用全节点
     * $provider = HttpProvider::testnet();
     *
     * @example
     * // 使用 Solidity 节点
     * $provider = HttpProvider::testnet(true);
     */
    public static function testnet(bool $useSolidity = false, array $options = []): self
    {
        $config = array_merge($options, [
            'host' => 'https://api.shasta.trongrid.io',
            'nodeType' => $useSolidity ? 'solidity' : 'fullnode'
        ]);

        return new self($config);
    }

    /**
     * 创建 Nile 测试网 HttpProvider（工厂方法）
     *
     * 快捷方法，自动使用 Tron Nile 测试网 URL
     *
     * @param bool $useSolidity 是否使用 Solidity 节点
     * @param array $options 附加选项
     * @return static 新的 HttpProvider 实例
     * @throws TronException 当配置无效时抛出
     *
     * @example
     * // 使用全节点
     * $provider = HttpProvider::nile();
     *
     * @example
     * // 使用 Solidity 节点
     * $provider = HttpProvider::nile(true);
     */
    public static function nile(bool $useSolidity = false, array $options = []): self
    {
        $config = array_merge($options, [
            'host' => 'https://nile.trongrid.io',
            'nodeType' => $useSolidity ? 'solidity' : 'fullnode'
        ]);

        return new self($config);
    }

    /**
     * 检查连接到 Tron 网络的状态
     *
     * 通过查询当前区块来验证连接是否正常
     *
     * @return bool 连接成功返回 true，失败返回 false
     *
     * @example
     * if ($provider->isConnected()) {
     *     echo "网络连接正常\n";
     * }
     */
    public function isConnected(): bool
    {
        try {
            // 尝试查询当前区块
            $response = $this->tronWeb->request('wallet/getnowblock');

            // 检查响应是否包含区块信息
            return isset($response['blockID']) || isset($response['block_header']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取主机 URL
     *
     * @return string 节点主机 URL
     *
     * @example
     * echo $provider->getHost(); // 输出: https://api.trongrid.io
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * 获取超时时间
     *
     * @return int 超时时间（毫秒）
     *
     * @example
     * echo $provider->getTimeout(); // 输出: 30000
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * 获取节点类型
     *
     * @return string 节点类型（fullnode 或 solidity）
     *
     * @example
     * echo $provider->getNodeType(); // 输出: fullnode
     */
    public function getNodeType(): string
    {
        return $this->nodeType;
    }

    /**
     * 获取头部信息
     *
     * @return array HTTP 头部数组
     *
     * @example
     * $headers = $provider->getHeaders();
     * echo $headers['Content-Type'] ?? '';
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 获取连接超时时间
     *
     * @return int 连接超时时间（秒）
     *
     * @example
     * echo $provider->getConnectTimeout(); // 输出: 10
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * 获取重试次数
     *
     * @return int 重试次数
     *
     * @example
     * echo $provider->getRetries(); // 输出: 3
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * 设置节点类型
     *
     * @param string $nodeType 节点类型（fullnode 或 solidity）
     * @throws TronException 当节点类型无效时抛出
     *
     * @example
     * $provider->setNodeType('solidity');
     */
    public function setNodeType(string $nodeType): void
    {
        if (!in_array($nodeType, self::VALID_NODE_TYPES)) {
            throw new TronException('Invalid node type. Must be one of: ' . implode(', ', self::VALID_NODE_TYPES));
        }
        $this->nodeType = $nodeType;
    }

    /**
     * 设置头部信息
     *
     * 会替换现有的所有头部，并更新 HTTP 客户端
     *
     * @param array $headers 新的头部数组
     *
     * @example
     * $provider->setHeaders([
     *     'Content-Type' => 'application/json',
     *     'TRON-PRO-API-KEY' => 'your_api_key'
     * ]);
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;

        // 使用新的头部信息更新 HTTP 客户端
        $this->httpClient = $this->createHttpClient();
    }

    /**
     * 添加单个头部信息
     *
     * 如果头部已存在则覆盖，不存在则添加
     * 会更新 HTTP 客户端
     *
     * @param string $name 头部名称
     * @param string $value 头部值
     *
     * @example
     * $provider->addHeader('TRON-PRO-API-KEY', 'your_api_key');
     */
    public function addHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;

        // 使用新的头部信息更新 HTTP 客户端
        $this->httpClient = $this->createHttpClient();
    }

    /**
     * 设置状态页面（向后兼容）
     *
     * 此方法保留用于向后兼容，但实际上不做任何操作
     * 因为 statusPage 功能已被移除
     *
     * @param string $page 状态页面路径（默认为 '/'）
     *
     * @deprecated 此方法已被弃用，将在未来版本中移除
     */
    public function setStatusPage(string $page = '/'): void
    {
        // 保持向后兼容，但实际不做任何操作
        // statusPage 功能已被移除
    }

    /**
     * 向服务器发送请求
     *
     * 核心方法，处理所有 HTTP 请求：
     * - 自动重试机制（失败时自动重试）
     * - 智能节点类型处理（自动添加 solidity/ 前缀）
     * - JSON 编码请求体
     * - 自动解码响应
     *
     * @param string $url 请求 URL（相对路径，如 'wallet/getnowblock'）
     * @param array $payload 请求负载数组
     * @param string $method 请求方法（get 或 post）
     * @return array|mixed 解码后的响应数据
     * @throws TronException 当请求失败或重试用尽时抛出
     *
     * @example GET 请求
     * $result = $provider->request('wallet/getnowblock');
     *
     * @example POST 请求
     * $result = $provider->request('wallet/getaccount', [
     *     'address' => '41...'
     * ], 'post');
     */
    public function request($url, array $payload = [], string $method = 'get'): array
    {
        // 转换为大写方法名
        $method = strtoupper($method);

        // 验证 HTTP 方法
        if (!in_array($method, ['GET', 'POST'])) {
            throw new TronException('The method is not defined');
        }

        // 根据节点类型智能处理 URL 前缀
        if ($this->nodeType === 'solidity') {
            // 对于 Solidity 节点，需要智能添加前缀
            // 规则：
            // 1. 传统 wallet API 应该已经有 walletsolidity/ 前缀
            // 2. 新的 v1 API 应该以 /v1/ 开头，不需要修改
            // 3. 其他传统 API 需要添加 solidity/ 前缀

            if (strpos($url, 'walletsolidity/') !== 0 &&
                strpos($url, 'wallet/') !== 0 &&
                strpos($url, '/v1/') !== 0 &&
                strpos($url, 'v1/') !== 0 &&
                strpos($url, 'solidity/') !== 0) {
                $url = 'solidity/' . $url;
            }
        }

        // 准备请求选项
        $options = [
            'headers'   => $this->headers,
            'body'      => json_encode($payload)
        ];

        // 创建 HTTP 请求对象
        $request = new Request($method, $url, $options['headers'], $options['body']);

        // 支持重试机制
        $maxRetries = $this->retries;
        $attempt = 0;

        // 循环重试，直到成功或达到最大重试次数
        while ($attempt <= $maxRetries) {
            try {
                // 发送 HTTP 请求
                $rawResponse = $this->httpClient->send($request, $options);

                // 解码响应并返回
                return $this->decodeBody(
                    $rawResponse->getBody(),
                    $rawResponse->getStatusCode()
                );
            } catch (\Exception $e) {
                $attempt++;

                // 如果超过最大重试次数，抛出异常
                if ($attempt > $maxRetries) {
                    throw new TronException("Request failed after {$maxRetries} attempts: " . $e->getMessage());
                }

                // 等待一段时间后重试（500ms * attempt）
                usleep(500000 * $attempt);
            }
        }

        // 不应该到达这里
        throw new TronException('Request failed unexpectedly');
    }

    /**
     * 将原始响应转换为数组
     *
     * 处理服务器返回的原始响应数据：
     * - JSON 解码
     * - 处理简单的 OK 响应
     * - 处理 null 或无效的响应
     * - 处理 404 错误
     *
     * @param StreamInterface $stream 响应流
     * @param int $status HTTP 状态码
     * @return array|mixed 解码后的响应数据
     * @throws NotFoundException 当状态码为 404 时抛出
     */
    protected function decodeBody(StreamInterface $stream, int $status): array
    {
        // 解码 JSON 响应
        $decodedBody = json_decode($stream->getContents(),true);

        // 处理简单的 OK 响应
        if((string)$stream == 'OK') {
            $decodedBody = [
                'status'    =>  1
            ];
        } elseif ($decodedBody == null or !is_array($decodedBody)) {
            // 处理 null 或无效的响应
            $decodedBody = [];
        }

        // 处理 404 错误
        if($status == 404) {
            throw new NotFoundException('Page not found');
        }

        return $decodedBody;
    }
}
