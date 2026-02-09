<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules\Contract;

use Dsdcr\TronWeb\TronWeb;

/**
 * 交易包装类 - 支持链式调用 send() 方法
 *
 * 使用示例：
 * $contract = $tronWeb->contract($abi)->at($address);
 * $result = $contract->transfer($to, $amount)->send();
 */
class TransactionWrapper
{
    protected TronWeb $tronWeb;
    protected array $transaction;
    protected ?string $fromAddress;
    protected array $options;

    /**
     * 创建交易包装实例
     *
     * @param TronWeb $tronWeb TronWeb实例
     * @param array $transaction 交易对象
     * @param string|null $fromAddress 发送地址（可选）
     * @param array $options 交易选项（可选）
     */
    public function __construct(TronWeb $tronWeb, array $transaction, ?string $fromAddress = null, array $options = [])
    {
        $this->tronWeb = $tronWeb;
        $this->transaction = $transaction;
        $this->fromAddress = $fromAddress;
        $this->options = $options;
    }

    /**
     * 发送交易到区块链
     *
     * 执行以下步骤：
     * 1. 对交易进行签名
     * 2. 广播交易到网络
     * 3. 返回交易结果
     *
     * @param array $options 交易选项（可选）
     * @return array 交易结果，包含 txid 等信息
     * @throws \Exception
     *
     * @example
     * // 不传 options
     * $result = $contract->transfer($to, $amount)->send();
     *
     * // 传入 options
     * $result = $contract->transfer($to, $amount)->send(['feeLimit' => 10000000]);
     * echo "交易ID: " . $result['txid'];
     */
    public function send(array $options = []): array
    {
        // 合并默认选项和传入选项
        $mergedOptions = array_merge($this->options ?? [], $options);

        // 签名交易
        $signedTransaction = $this->tronWeb->trx->signTransaction($this->transaction, $mergedOptions);

        // 广播交易
        $broadcastResult = $this->tronWeb->trx->sendRawTransaction($signedTransaction);

        // 返回结果
        return $broadcastResult;
    }

    /**
     * 只签名交易，不广播
     *
     * 适用于需要先签名，稍后再广播的场景
     *
     * @return array 签名后的交易对象
     * @throws \Exception
     *
     * @example
     * $signed = $contract->transfer($to, $amount)->sign();
     * // 稍后广播
     * $result = $tronWeb->trx->sendRawTransaction($signed);
     */
    public function sign(): array
    {
        return $this->tronWeb->trx->signTransaction($this->transaction);
    }

    /**
     * 获取原始交易对象
     *
     * @return array
     */
    public function getTransaction(): array
    {
        return $this->transaction;
    }

    /**
     * 获取交易ID（未签名交易）
     *
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transaction['txID'] ?? $this->transaction['transaction']['txID'] ?? null;
    }
}