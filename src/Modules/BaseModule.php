<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Exception\TronException;

/**
 * BaseModule - TronAPI模块系统的基类
 *
 * 提供所有功能模块的通用基础功能：
 * - TronWeb实例引用管理
 * - 统一的API请求接口
 * - 提供者类型自动路由
 *
 * 设计原则：
 * - 所有业务模块（Trx、Account、Contract等）必须继承此类
 * - 通过TronWeb实例访问配置、工具和API
 * - request()方法封装了与不同节点交互的细节
 *
 * 使用说明：
 * - 子类通过$this->tronWeb访问主实例
 * - 使用$this->request()发起API调用
 * - 自动处理提供者类型路由（全节点、固态节点等）
 *
 * @package Dsdcr\TronWeb\Modules
 * @since 1.0.0
 */
abstract class BaseModule
{
    /**
     * TronWeb主实例引用
     *
     * 提供对TronWeb所有功能的访问：
     * - 配置信息（节点地址、私钥等）
     * - 工具类（TronUtils）
     * - API请求方法
     * - 其他模块实例
     *
     * @var TronWeb
     */
    protected $tronWeb;

    /**
     * 创建基础模块实例
     *
     * 初始化模块并设置TronWeb引用。
     * 所有子模块必须通过此构造函数初始化。
     *
     * @param TronWeb $tronWeb TronWeb主实例
     *                        包含完整的配置和工具方法
     *                        不能为null
     *
     * @throws TronException 当TronWeb实例无效时抛出
     */
    public function __construct(TronWeb $tronWeb)
    {
        $this->tronWeb = $tronWeb;
    }

    /**
     * 发起API请求
     *
     * 封装TronWeb的请求方法，提供统一的API调用接口。
     * 自动根据providerType路由到相应的节点（全节点、固态节点等）。
     *
     *
     * @param string $endpoint 端点路径
     * @param array $params 请求参数
     * @param string $method 请求方法
     * @return array
     * @throws TronException
     */
    protected function request(string $endpoint, array $params = [], string $method = 'post'): array
    {
        return $this->tronWeb->request($endpoint, $params, $method);
    }
}