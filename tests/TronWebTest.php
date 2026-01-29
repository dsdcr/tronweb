<?php

namespace Tests;

use Dsdcr\TronWeb\TronWeb;
use Dsdcr\TronWeb\Provider\HttpProvider;
use PHPUnit\Framework\TestCase;

class TronWebTest extends TestCase
{
    protected TronWeb $tronWeb;

    protected function setUp(): void
    {
        $fullNode = new HttpProvider('https://api.trongrid.io');

        $this->tronWeb = new TronWeb([
            'fullNode' => $fullNode,
            'defaultAddress' => 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL'
        ]);
    }

    public function testTronWebInstanceCreation()
    {
        $this->assertInstanceOf(TronWeb::class, $this->tronWeb);
    }

    public function testModulesInitialization()
    {
        $modules = ['trx', 'account', 'contract', 'token', 'resource', 'network', 'utils'];

        foreach ($modules as $module) {
            $this->assertTrue(isset($this->tronWeb->$module), "Module {$module} should be initialized");
        }
    }

    public function testAddressValidation()
    {
        $validAddress = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';
        $invalidAddress = 'invalid_address';

        $this->assertTrue($this->tronWeb->account->isValidAddress($validAddress));
        $this->assertFalse($this->tronWeb->account->isValidAddress($invalidAddress));
    }

    public function testAddressConversion()
    {
        $base58Address = 'TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL';

        $hexAddress = $this->tronWeb->account->toHex($base58Address);
        $convertedBack = $this->tronWeb->account->toBase58($hexAddress);

        $this->assertEquals($base58Address, $convertedBack);
    }

    public function testUnitConversion()
    {
        $trxAmount = 1.5;
        $sunAmount = $this->tronWeb->utils->toSun($trxAmount);
        $convertedBack = $this->tronWeb->utils->fromSun($sunAmount);

        $this->assertEquals(1500000, $sunAmount);
        $this->assertEquals($trxAmount, $convertedBack);
    }

    public function testTRC20ContractCreation()
    {
        $contract = $this->tronWeb->contract->trc20('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');

        $this->assertInstanceOf(\Dsdcr\TronWeb\TRC20Contract::class, $contract);
    }

    public function testAccountGeneration()
    {
        $account = $this->tronWeb->account->create();

        $this->assertArrayHasKey('private_key', $account->toArray());
        $this->assertArrayHasKey('public_key', $account->toArray());
        $this->assertArrayHasKey('address_hex', $account->toArray());
        $this->assertArrayHasKey('address_base58', $account->toArray());

        // 验证生成的地址格式
        $this->assertTrue($this->tronWeb->account->isValidAddress($account->address_base58));
    }
}