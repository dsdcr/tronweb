<?php
namespace Dsdcr\TronWeb\Concerns;

use Dsdcr\TronWeb\Exception\TronException;

trait ManagesTronscan
{
    /**
     * Get transactions from Tronscan explorer API
     *
     * @param array $options
     * @return array
     * @throws TronException
     */
    public function getTransactionByAddress(array $options = []): array
    {
        if (empty($options)) {
            throw new TronException('Parameters must not be empty.');
        }

        // 检查是否在模块中使用
        if (method_exists($this, 'request')) {
            return $this->request('api/transaction', $options);
        }

        // 如果在 TronWeb 中使用，通过 fullNode provider
        if (property_exists($this, 'fullNode') && is_object($this->fullNode)) {
            return $this->fullNode->request('api/transaction', $options);
        }

        throw new TronException('Unable to make request - invalid context');
    }

    /**
     * Get transaction details from Tronscan
     *
     * @param string $transactionHash
     * @return array
     * @throws TronException
     */
    public function getTransactionByHash(string $transactionHash): array
    {
        return $this->getTransactionByAddress([
            'hash' => $transactionHash,
            'detail' => 'true'
        ]);
    }

    /**
     * Get account transactions from Tronscan
     *
     * @param string $address
     * @param int $limit
     * @param int $start
     * @param string $sort
     * @return array
     * @throws TronException
     */
    public function getAccountTransactions(
        string $address,
        int $limit = 20,
        int $start = 0,
        string $sort = '-timestamp'
    ): array {
        return $this->getTransactionByAddress([
            'address' => $address,
            'limit' => $limit,
            'start' => $start,
            'sort' => $sort
        ]);
    }

    /**
     * Get token transfers for an account
     *
     * @param string $address
     * @param string $token
     * @param int $limit
     * @param int $start
     * @return array
     * @throws TronException
     */
    public function getTokenTransfers(
        string $address,
        string $token = 'trx',
        int $limit = 20,
        int $start = 0
    ): array {
        return $this->getTransactionByAddress([
            'address' => $address,
            'token' => $token,
            'limit' => $limit,
            'start' => $start,
            'type' => 'transfer'
        ]);
    }
}