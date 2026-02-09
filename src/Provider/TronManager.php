<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Provider;

use Dsdcr\TronWeb\Provider\HttpProviderInterface;
use Dsdcr\TronWeb\Provider\HttpProvider;
use Dsdcr\TronWeb\Exception\TronException;

/**
 * Tron Manager
 *
 * 管理多个 HTTP providers 用于不同的 TRON 网络节点和服务
 * 这个类处理协调不同节点类型间的请求路由
 *
 * @package Dsdcr\TronWeb\Provider
 *
 * @example 使用方式
 * $manager = new TronManager($tron, [
 *     'fullNode' => $fullNodeProvider,
 *     'solidityNode' => $solidityNodeProvider,
 *     'eventServer' => $eventServerProvider
 * ]);
 */
class TronManager
{
    /**
     * 默认节点配置
     *
     * 当某个 Provider 未提供时，使用这些默认地址
     */
    protected array $defaultNodes = [
        'fullNode'      => 'https://api.trongrid.io',
        'solidityNode'  => 'https://api.trongrid.io',
        'eventServer'   => 'https://api.trongrid.io',
        'explorer'      => 'https://apilist.tronscan.org',
        'signServer'    => ''
    ];

    /**
     * Providers 数组
     *
     * 存储所有已配置的 Provider 实例
     */
    protected array $providers = [
        'fullNode'      => [],
        'solidityNode'  => [],
        'eventServer'   => [],
        'explorer'      => [],
        'signServer'    => []
    ];

    /**
     * 健康检查端点
     *
     * 每个 Provider 类型对应的健康检查端点
     * 用于检查节点连接状态
     */
    protected array $statusPage = [
        'fullNode'      => 'wallet/getnowblock',
        'solidityNode'  => 'walletsolidity/getnowblock',
        'eventServer'   => 'healthcheck',
        'explorer'      => 'api/system/status'
    ];

    /**
     * 构造函数
     *
     * 初始化 TronManager，配置所有 Provider
     * 支持自动使用默认节点
     *
     * @param array $providers Provider 配置数组
     *
     * @example 使用默认节点
     * $manager = new TronManager([]);
     *
     * @example 提供特定 Provider
     * $manager = new TronManager([
     *     'fullNode' => new HttpProvider('https://custom-node.com'),
     *     'solidityNode' => new HttpProvider('https://custom-solidity.com')
     * ]);
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;

        // 遍历所有 Provider 配置
        foreach ($providers as $key => $value) {
            // 如果值为 null，使用默认节点
            if ($value == null) {
                $this->providers[$key] = new HttpProvider(
                    $this->defaultNodes[$key]
                );
            }

            // 如果值为字符串，创建新的 HttpProvider
            if(is_string($providers[$key])) {
                $this->providers[$key] = new HttpProvider($value);
            }

            // 跳过 signServer 的状态页面设置
            if(in_array($key, ['signServer'])) {
                continue;
            }

            // 设置健康检查端点
            $this->providers[$key]->setStatusPage($this->statusPage[$key]);
        }
    }

    /**
     * 获取所有 Providers
     *
     * 返回所有已配置的 Provider 数组
     *
     * @return array
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * 获取全节点 Provider
     *
     * @return HttpProviderInterface
     * @throws TronException
     */
    public function fullNode(): HttpProviderInterface
    {
        if (!array_key_exists('fullNode', $this->providers)) {
            throw new TronException('全节点 Provider 未配置');
        }

        return $this->providers['fullNode'];
    }

    /**
     * 获取全节点 Provider（兼容方法）
     *
     * @return HttpProviderInterface
     * @throws TronException
     * @deprecated 建议使用 fullNode() 方法
     */
    public function getFullNode(): HttpProviderInterface
    {
        return $this->fullNode();
    }

    /**
     * 获取固态节点 Provider
     *
     * @return HttpProviderInterface
     * @throws TronException
     */
    public function solidityNode(): HttpProviderInterface
    {
        if (!array_key_exists('solidityNode', $this->providers)) {
            throw new TronException('固态节点 Provider 未配置');
        }

        return $this->providers['solidityNode'];
    }

    /**
     * 获取签名服务器 Provider
     *
     * @return HttpProviderInterface
     * @throws TronException
     */
    public function signServer(): HttpProviderInterface
    {
        if (!array_key_exists('signServer', $this->providers)) {
            throw new TronException('签名服务器 Provider 未配置');
        }

        return $this->providers['signServer'];
    }

    /**
     * 获取浏览器 Explorer Provider
     *
     * @return HttpProviderInterface
     * @throws TronException
     */
    public function explorer(): HttpProviderInterface
    {
        if (!array_key_exists('explorer', $this->providers)) {
            throw new TronException('浏览器 Provider 未配置');
        }

        return $this->providers['explorer'];
    }

    /**
     * 获取事件服务器 Provider
     *
     * @return HttpProviderInterface
     * @throws TronException
     */
    public function eventServer(): HttpProviderInterface
    {
        if (!array_key_exists('eventServer', $this->providers)) {
            throw new TronException('事件服务器 Provider 未配置');
        }

        return $this->providers['eventServer'];
    }

    /**
     * 基础查询方法，自动路由到正确的节点
     *
     * 根据请求的端点路径自动选择合适的 Provider：
     * - walletsolidity/ → 固态节点
     * - event/ → 事件服务器
     * - trx-sign/ → 签名服务器
     * - api/ → 浏览器
     * - 其他 → 全节点
     *
     * @param string $url API 端点路径
     * @param array $params 请求参数
     * @param string $method HTTP 方法（post/get）
     * @return array API 响应数据
     * @throws TronException
     */
    public function request($url, array $params = [], string $method = 'post')
    {
        // 分割端点路径
        $split = explode('/', $url);

        // 根据路径前缀选择合适的 Provider
        if(in_array($split[0], ['walletsolidity', 'walletextension'])) {

            // 使用固态节点
            $response = $this->solidityNode()->request($url, $params, $method);
        } elseif(in_array($split[0], ['event'])) {
            // 使用事件服务器
            $response = $this->eventServer()->request($url, $params, 'get');
        } elseif (in_array($split[0], ['trx-sign'])) {
            // 使用签名服务器
            $response = $this->signServer()->request($url, $params, 'post');
        } elseif(in_array($split[0], ['api'])) {
            // 使用浏览器
            $response = $this->explorer()->request($url, $params, 'get');
        } else {
            // 使用全节点
            $response = $this->fullNode()->request($url, $params, $method);
        }
        return $response;
    }

    /**
     * 检查所有节点的连接状态
     *
     * 通过调用每个 Provider 的健康检查端点来验证连接
     *
     * @return array 连接状态数组
     */
    public function isConnected(): array
    {
        $array = [];
        foreach ($this->providers as $key => $value) {
            $array[] = [
                $key => boolval($value->isConnected())
            ];
        }

        return $array;
    }
}