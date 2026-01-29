<?php declare(strict_types=1);

namespace Dsdcr\TronWeb\Provider;

use GuzzleHttp\{Psr7\Request, Client, ClientInterface};
use Psr\Http\Message\StreamInterface;
use Dsdcr\TronWeb\Exception\{NotFoundException, TronException};
use Dsdcr\TronWeb\Support\Utils;

class HttpProvider implements HttpProviderInterface
{
    /**
     * HTTP客户端处理器
     *
     * @var ClientInterface.
     */
    protected $httpClient;

    /**
     * 服务器或RPC URL
     *
     * @var string
     */
    protected $host;

    /**
     * 等待时间（毫秒）
     *
     * @var int
     */
    protected $timeout = 30000;

    /**
     * 获取自定义头部信息
     *
     * @var array
     */
    protected $headers = [];

    /**
     * 状态页面路径
     *
     * @var string
     */
    protected $statusPage = '/';

    /**
     * 创建HttpProvider对象
     *
     * @param string $host 主机URL
     * @param int $timeout 超时时间（毫秒）
     * @param mixed $user 用户名
     * @param mixed $password 密码
     * @param array $headers 自定义头部信息
     * @param string $statusPage 状态页面路径
     * @throws TronException
     */
    public function __construct(string $host, int $timeout = 30000, $user = false, $password = false, array $headers = [], string $statusPage = '/')
    {
        if(!Utils::isValidUrl($host)) {
            throw new TronException('Invalid URL provided to HttpProvider');
        }

        if(is_nan($timeout) || $timeout < 0) {
            throw new TronException('Invalid timeout duration provided');
        }

        if(!Utils::isArray($headers)) {
            throw new TronException('Invalid headers array provided');
        }

        $this->host = $host;
        $this->timeout = $timeout;
        $this->statusPage = $statusPage;
        $this->headers = $headers;

        $this->httpClient = new Client([
            'base_uri'  =>  $host,
            'timeout'   =>  $timeout,
            'auth'      =>  $user && [$user, $password]
        ]);
    }

    /**
     * 设置状态页面路径
     *
     * @param string $page 页面路径
     */
    public function setStatusPage(string $page = '/'): void
    {
        $this->statusPage = $page;
    }

    /**
     * 检查连接状态
     *
     * @return bool 是否已连接
     * @throws TronException
     */
    public function isConnected() : bool
    {
        $response = $this->request($this->statusPage);

        if(array_key_exists('blockID', $response)) {
            return true;
        } elseif(array_key_exists('status', $response)) {
            return true;
        }
        return false;
    }

    /**
     * 获取主机URL
     *
     * @return string 主机URL
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * 获取超时时间
     *
     * @return int 超时时间（毫秒）
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * 向服务器发送请求
     *
     * @param string $url 请求URL
     * @param array $payload 请求负载数据
     * @param string $method 请求方法（get/post）
     * @return array|mixed 响应结果
     * @throws TronException
     */
    public function request($url, array $payload = [], string $method = 'get'): array
    {
        $method = strtoupper($method);

        if(!in_array($method, ['GET', 'POST'])) {
            throw new TronException('The method is not defined');
        }

        $options = [
            'headers'   => $this->headers,
            'body'      => json_encode($payload)
        ];

        $request = new Request($method, $url, $options['headers'], $options['body']);
        $rawResponse = $this->httpClient->send($request, $options);

        return $this->decodeBody(
            $rawResponse->getBody(),
            $rawResponse->getStatusCode()
        );
    }

    /**
     * 将原始响应转换为数组
     *
     * @param StreamInterface $stream 响应流
     * @param int $status 状态码
     * @return array|mixed 解码后的响应
     */
    protected function decodeBody(StreamInterface $stream, int $status): array
    {
        $decodedBody = json_decode($stream->getContents(),true);

        if((string)$stream == 'OK') {
            $decodedBody = [
                'status'    =>  1
            ];
        }elseif ($decodedBody == null or !is_array($decodedBody)) {
            $decodedBody = [];
        }

        if($status == 404) {
            throw new NotFoundException('Page not found');
        }

        return $decodedBody;
    }
}
