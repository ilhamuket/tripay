<?php

namespace ilhamuket\Tripay\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ilhamuket\Tripay\PaymentMethod;

class PaymentMethodTest extends TestCase
{
    public function test_virtual_accounts_returns_array(): void
    {
        $vas = PaymentMethod::virtualAccounts();
        
        $this->assertIsArray($vas);
        $this->assertContains(PaymentMethod::BRIVA, $vas);
        $this->assertContains(PaymentMethod::BNIVA, $vas);
        $this->assertContains(PaymentMethod::BCAVA, $vas);
    }

    public function test_e_wallets_returns_array(): void
    {
        $ewallets = PaymentMethod::eWallets();
        
        $this->assertIsArray($ewallets);
        $this->assertContains(PaymentMethod::QRIS, $ewallets);
        $this->assertContains(PaymentMethod::OVO, $ewallets);
        $this->assertContains(PaymentMethod::DANA, $ewallets);
    }

    public function test_convenience_stores_returns_array(): void
    {
        $stores = PaymentMethod::convenienceStores();
        
        $this->assertIsArray($stores);
        $this->assertContains(PaymentMethod::ALFAMART, $stores);
        $this->assertContains(PaymentMethod::INDOMARET, $stores);
    }

    public function test_is_virtual_account(): void
    {
        $this->assertTrue(PaymentMethod::isVirtualAccount(PaymentMethod::BRIVA));
        $this->assertTrue(PaymentMethod::isVirtualAccount(PaymentMethod::BCAVA));
        $this->assertFalse(PaymentMethod::isVirtualAccount(PaymentMethod::QRIS));
    }

    public function test_is_e_wallet(): void
    {
        $this->assertTrue(PaymentMethod::isEWallet(PaymentMethod::QRIS));
        $this->assertTrue(PaymentMethod::isEWallet(PaymentMethod::OVO));
        $this->assertFalse(PaymentMethod::isEWallet(PaymentMethod::BRIVA));
    }

    public function test_is_qris(): void
    {
        $this->assertTrue(PaymentMethod::isQris(PaymentMethod::QRIS));
        $this->assertTrue(PaymentMethod::isQris(PaymentMethod::QRISC));
        $this->assertFalse(PaymentMethod::isQris(PaymentMethod::OVO));
    }

    public function test_all_returns_all_methods(): void
    {
        $all = PaymentMethod::all();
        
        $this->assertIsArray($all);
        $this->assertContains(PaymentMethod::BRIVA, $all);
        $this->assertContains(PaymentMethod::QRIS, $all);
        $this->assertContains(PaymentMethod::ALFAMART, $all);
    }
}
