<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;

/**
 * 基础模块类 - 所有模块应继承此类
 *
 * @package Dsdcr\TronWeb\Modules
 */
abstract class BaseModule
{
    /**
     * @var TronWeb TronWeb实例
     */
    protected $tronWeb;

    /**
     * BaseModule构造函数
     *
     * @param TronWeb $tronWeb TronWeb实例
     */
    public function __construct(TronWeb $tronWeb)
    {
        $this->tronWeb = $tronWeb;
    }

    /**
     * 获取TronWeb实例
     *
     * @return TronWeb
     */
    protected function getTronWeb(): TronWeb
    {
        return $this->tronWeb;
    }

    /**
     * 通过TronWeb发起请求
     *
     * @param string $endpoint API端点
     * @param array $params 请求参数
     * @param string $providerType 提供者类型
     * @return array 响应结果
     * @throws TronException
     */
    protected function request(string $endpoint, array $params = [], string $providerType = 'fullNode'): array
    {
        return $this->tronWeb->request($endpoint, $params, $providerType);
    }

    /**
     * 从TronWeb实例获取私钥
     *
     * @return string 私钥
     * @throws TronException
     */
    protected function getPrivateKey(): string
    {
        $privateKey = $this->tronWeb->getPrivateKey();
        if (!$privateKey) {
            throw new TronException('Private key is not set. Use setPrivateKey() first.');
        }
        return $privateKey;
    }

    /**
     * 从TronWeb实例获取默认地址
     *
     * @return array 默认地址信息
     * @throws TronException
     */
    protected function getDefaultAddress(): array
    {
        $address = $this->tronWeb->getDefaultAddress();
        if (!$address) {
            throw new TronException('Default address is not set. Use setDefaultAddress() first.');
        }
        return $address;
    }
}