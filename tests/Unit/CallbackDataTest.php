<?php

namespace Ufrfrk\Tripay\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ufrfrk\Tripay\Callback\CallbackData;

class CallbackDataTest extends TestCase
{
    private function getSampleCallbackData(string $status = 'PAID'): array
    {
        return [
            'reference' => 'T0001000023000XXXXX',
            'merchant_ref' => 'INV123456',
            'payment_method' => 'BCA Virtual Account',
            'payment_method_code' => 'BCAVA',
            'total_amount' => 200000,
            'fee_merchant' => 2000,
            'fee_customer' => 0,
            'total_fee' => 2000,
            'amount_received' => 198000,
            'is_closed_payment' => 1,
            'status' => $status,
            'paid_at' => 1608133017,
            'note' => null,
        ];
    }

    public function test_can_parse_callback_data(): void
    {
        $callback = new CallbackData($this->getSampleCallbackData());

        $this->assertEquals('T0001000023000XXXXX', $callback->getReference());
        $this->assertEquals('INV123456', $callback->getMerchantRef());
        $this->assertEquals('BCA Virtual Account', $callback->getPaymentMethod());
        $this->assertEquals('BCAVA', $callback->getPaymentMethodCode());
        $this->assertEquals(200000, $callback->getTotalAmount());
        $this->assertEquals(2000, $callback->getFeeMerchant());
        $this->assertEquals(0, $callback->getFeeCustomer());
        $this->assertEquals(2000, $callback->getTotalFee());
        $this->assertEquals(198000, $callback->getAmountReceived());
        $this->assertTrue($callback->isClosedPayment());
    }

    public function test_is_paid(): void
    {
        $callback = new CallbackData($this->getSampleCallbackData('PAID'));
        
        $this->assertTrue($callback->isPaid());
        $this->assertFalse($callback->isExpired());
        $this->assertFalse($callback->isFailed());
        $this->assertFalse($callback->isRefund());
    }

    public function test_is_expired(): void
    {
        $callback = new CallbackData($this->getSampleCallbackData('EXPIRED'));
        
        $this->assertFalse($callback->isPaid());
        $this->assertTrue($callback->isExpired());
    }

    public function test_is_failed(): void
    {
        $callback = new CallbackData($this->getSampleCallbackData('FAILED'));
        
        $this->assertFalse($callback->isPaid());
        $this->assertTrue($callback->isFailed());
    }

    public function test_is_refund(): void
    {
        $callback = new CallbackData($this->getSampleCallbackData('REFUND'));
        
        $this->assertFalse($callback->isPaid());
        $this->assertTrue($callback->isRefund());
    }

    public function test_get_paid_at_datetime(): void
    {
        $callback = new CallbackData($this->getSampleCallbackData());
        
        $datetime = $callback->getPaidAtDateTime();
        
        $this->assertInstanceOf(\DateTimeInterface::class, $datetime);
        $this->assertEquals(1608133017, $datetime->getTimestamp());
    }

    public function test_to_array(): void
    {
        $data = $this->getSampleCallbackData();
        $callback = new CallbackData($data);
        
        $this->assertEquals($data, $callback->toArray());
    }
}
