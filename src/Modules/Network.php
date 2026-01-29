<?php
declare(strict_types=1);

namespace Dsdcr\TronWeb\Modules;

use Dsdcr\TronWeb\Exception\TronException;
use Dsdcr\TronWeb\Support\TronUtils;

/**
 * 网络模块 - 用于获取Tron网络信息和治理功能
 *
 * @package Dsdcr\TronWeb\Modules
 */
class Network extends BaseModule
{
    /**
     * 列出Tron网络中的所有节点
     *
     * @return array 节点列表
     * @throws TronException
     */
    public function listNodes(): array
    {
        return $this->request('wallet/listnodes');
    }

    /**
     * 列出所有超级代表
     *
     * @return array 超级代表列表
     * @throws TronException
     */
    public function listSuperRepresentatives(): array
    {
        return $this->request('wallet/listwitnesses');
    }

    /**
     * 列出所有交易所
     *
     * @return array 交易所列表
     * @throws TronException
     */
    public function listExchanges(): array
    {
        return $this->request('wallet/getexchangelist');
    }

    /**
     * 申请成为超级代表
     *
     * @param string $url 代表URL
     * @param string|null $address 申请者地址
     * @return array 申请结果
     * @throws TronException
     */
    public function applyForRepresentative(string $url, ?string $address = null): array
    {
        $addressHex = $address ? TronUtils::addressToHex($address) : $this->getDefaultAddress()['hex'];

        return $this->request('wallet/applyforsuperrepresentative', [
            'owner_address' => $addressHex,
            'url' => TronUtils::stringToHex($url)
        ]);
    }

    /**
     * applyForRepresentative的别名（向后兼容）
     *
     * @param string $address 申请者地址
     * @param string $url 代表URL
     * @return array 申请结果
     * @throws TronException
     */
    public function applyForSuperRepresentative(string $address, string $url): array
    {
        return $this->applyForRepresentative($url, $address);
    }

    /**
     * 获取距离下次投票周期的时间
     *
     * @return float 时间（秒）
     * @throws TronException
     */
    public function timeUntilNextVoteCycle(): float
    {
        $response = $this->request('wallet/getnextmaintenancetime');
        return (float)($response['num'] ?? 0);
    }

    /**
     * 获取投票奖励比例
     *
     * @return array 奖励比例信息
     * @throws TronException
     */
    public function getVoteRewardRatio(): array
    {
        return $this->request('wallet/getrewardinfo');
    }

    /**
     * 获取链参数
     *
     * @return array 链参数
     * @throws TronException
     */
    public function getChainParameters(): array
    {
        return $this->request('wallet/getchainparameters');
    }

    /**
     * 获取网络统计信息
     *
     * @return array 网络统计信息
     * @throws TronException
     */
    public function getNetworkStats(): array
    {
        $chainParams = $this->getChainParameters();
        $witnesses = $this->listSuperRepresentatives();
        $nodes = $this->listNodes();

        return [
            'total_nodes' => count($nodes),
            'super_representatives' => count($witnesses),
            'chain_parameters' => $chainParams,
            'last_updated' => time()
        ];
    }

    /**
     * 获取区块奖励信息
     *
     * @return array 区块奖励信息
     * @throws TronException
     */
    public function getBlockRewardInfo(): array
    {
        return $this->request('wallet/getBrokerage');
    }

    /**
     * 通过ID获取提案信息
     *
     * @param int $proposalID 提案ID
     * @return array 提案信息
     * @throws TronException
     */
    public function getProposal(int $proposalID): array
    {
        if ($proposalID < 0) {
            throw new TronException('Invalid proposalID provided');
        }

        return $this->request('wallet/getproposalbyid', [
            'id' => $proposalID
        ], 'post');
    }

    /**
     * 列出所有网络修改提案
     *
     * @return array 提案列表
     * @throws TronException
     */
    public function listProposals(): array
    {
        $response = $this->request('wallet/listproposals', [], 'post');
        return $response['proposals'] ?? [];
    }

    /**
     * 获取可用的网络修改提案参数
     * 列出所有可用于网络修改提案的链参数
     *
     * @return array 可用参数列表
     * @throws TronException
     */
    public function getProposalParameters(): array
    {
        $chainParams = $this->getChainParameters();

        // 过滤出可用于提案的参数
        $proposalParams = [];
        foreach ($chainParams['chainParameter'] ?? [] as $param) {
            if (isset($param['key']) && isset($param['value'])) {
                $proposalParams[] = [
                    'key' => $param['key'],
                    'value' => $param['value'],
                    'name' => $this->getParameterName($param['key'])
                ];
            }
        }

        return $proposalParams;
    }

    /**
     * 获取参数名称（内部方法）
     *
     * @param string $key 参数键
     * @return string 参数名称
     */
    private function getParameterName(string $key): string
    {
        $parameterNames = [
            'getMaintenanceTimeInterval' => '维护时间间隔',
            'getAccountUpgradeCost' => '账户升级成本',
            'getCreateAccountFee' => '创建账户费用',
            'getTransactionFee' => '交易费用',
            'getAssetIssueFee' => '资产发行费用',
            'getWitnessPayPerBlock' => '见证人每块支付',
            'getWitnessStandbyAllowance' => '见证人待机津贴',
            'getCreateNewAccountFeeInSystemContract' => '系统合约中创建新账户费用',
            'getCreateNewAccountBandwidthRate' => '创建新账户带宽率',
            'getAllowCreationOfContracts' => '允许创建合约',
            'getRemoveThePowerOfTheGr' => '移除GR权限',
            'getEnergyFee' => '能量费用',
            'getExchangeCreateFee' => '交易所创建费用',
            'getMaxCpuTimeOfOneTx' => '单笔交易最大CPU时间',
            'getAllowUpdateAccountName' => '允许更新账户名',
            'getAllowSameTokenName' => '允许相同代币名',
            'getAllowDelegateResource' => '允许委托资源',
            'getTotalEnergyLimit' => '总能量限制',
            'getAllowTvmTransferTrc10' => '允许TVM转移TRC10',
            'getTotalCurrentEnergyLimit' => '总当前能量限制',
            'getAllowMultiSign' => '允许多签',
            'getAllowAdaptiveEnergy' => '允许自适应能量',
            'getUpdateAccountPermissionFee' => '更新账户权限费用',
            'getMultiSignFee' => '多签费用',
            'getAllowProtoFilterNum' => '允许协议过滤器编号',
            'getAllowAccountStateRoot' => '允许账户状态根',
            'getAllowTvmConstantinople' => '允许TVM君士坦丁堡',
            'getAllowTvmSolidity059' => '允许TVM Solidity 0.5.9',
            'getAdjustSupportConstant' => '调整支持常数',
            'getAllowTvmIstanbul' => '允许TVM伊斯坦布尔',
            'getAllowTvmLondon' => '允许TVM伦敦',
            'getAllowTvmCompatibility' => '允许TVM兼容性'
        ];

        return $parameterNames[$key] ?? $key;
    }

    /**
     * 通过ID获取交易所信息
     *
     * @param int $exchangeID 交易所ID
     * @return array 交易所信息
     * @throws TronException
     */
    public function getExchangeByID(int $exchangeID): array
    {
        if ($exchangeID < 0) {
            throw new TronException('Invalid exchangeID provided');
        }

        return $this->request('wallet/getexchangebyid', [
            'id' => $exchangeID
        ], 'post');
    }

    /**
     * 获取分页的交易所列表
     *
     * @param int $limit 每页数量（默认10）
     * @param int $offset 偏移量（默认0）
     * @return array 分页的交易所列表
     * @throws TronException
     */
    public function listExchangesPaginated(int $limit = 10, int $offset = 0): array
    {
        if ($limit < 1 || $limit > 100) {
            throw new TronException('Limit must be between 1 and 100');
        }

        if ($offset < 0) {
            throw new TronException('Offset must be non-negative');
        }

        $response = $this->request('wallet/getpaginatedexchangelist', [
            'limit' => $limit,
            'offset' => $offset
        ], 'post');

        return $response['exchanges'] ?? [];
    }
}