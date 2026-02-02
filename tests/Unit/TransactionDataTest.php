<?php

namespace Ufrfrk\Tripay\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ufrfrk\Tripay\Data\OrderItem;
use Ufrfrk\Tripay\Data\TransactionData;
use Ufrfrk\Tripay\PaymentMethod;

class TransactionDataTest extends TestCase
{
    public function test_can_create_transaction_data(): void
    {
        $data = new TransactionData();
        $data->setMethod(PaymentMethod::QRIS)
            ->setMerchantRef('INV-001')
            ->setAmount(100000)
            ->setCustomerName('John Doe')
            ->setCustomerEmail('john@example.com')
            ->addOrderItem([
                'name' => 'Test Product',
                'price' => 100000,
                'quantity' => 1,
            ]);

        $array = $data->toArray();

        $this->assertEquals('QRIS', $array['method']);
        $this->assertEquals('INV-001', $array['merchant_ref']);
        $this->assertEquals(100000, $array['amount']);
        $this->assertEquals('John Doe', $array['customer_name']);
        $this->assertEquals('john@example.com', $array['customer_email']);
        $this->assertCount(1, $array['order_items']);
    }

    public function test_can_set_expiry_hours(): void
    {
        $data = new TransactionData();
        $data->setMethod(PaymentMethod::QRIS)
            ->setMerchantRef('INV-001')
            ->setAmount(100000)
            ->setCustomerName('John Doe')
            ->setCustomerEmail('john@example.com')
            ->addOrderItem(['name' => 'Test', 'price' => 100000, 'quantity' => 1])
            ->setExpiryHours(24);

        $array = $data->toArray();

        $this->assertArrayHasKey('expired_time', $array);
        $this->assertGreaterThan(time(), $array['expired_time']);
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = new TransactionData();
        $data->setCustomerEmail('invalid-email');
    }

    public function test_throws_exception_for_invalid_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = new TransactionData();
        $data->setAmount(0);
    }

    public function test_throws_exception_without_order_items(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = new TransactionData();
        $data->setMethod(PaymentMethod::QRIS)
            ->setMerchantRef('INV-001')
            ->setAmount(100000)
            ->setCustomerName('John Doe')
            ->setCustomerEmail('john@example.com')
            ->toArray();
    }
}
