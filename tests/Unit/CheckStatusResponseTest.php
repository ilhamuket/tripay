<?php

namespace Ilhamuket\Tripay\Tests\Unit;

use Ilhamuket\Tripay\Response\CheckStatusResponse;
use PHPUnit\Framework\TestCase;

class CheckStatusResponseTest extends TestCase
{
    private function response(string $message): CheckStatusResponse
    {
        return new CheckStatusResponse(['success' => true, 'message' => $message]);
    }

    public function test_unpaid_is_not_paid(): void
    {
        $res = $this->response('Status transaksi saat ini UNPAID');

        $this->assertFalse($res->isPaid());
        $this->assertTrue($res->isUnpaid());
        $this->assertFalse($res->isExpired());
    }

    public function test_paid(): void
    {
        $res = $this->response('Status transaksi saat ini PAID');

        $this->assertTrue($res->isPaid());
        $this->assertFalse($res->isUnpaid());
    }

    public function test_dibayar_is_paid(): void
    {
        // Pesan asli sandbox Tripay untuk transaksi lunas
        foreach (['Status transaksi saat ini DIBAYAR', 'Status transaksi berhasil DIBAYAR'] as $message) {
            $res = $this->response($message);
            $this->assertTrue($res->isPaid(), $message);
            $this->assertFalse($res->isUnpaid(), $message);
        }

        $this->assertFalse($this->response('Belum DIBAYARKAN')->isPaid());
    }

    public function test_case_insensitive_and_expired(): void
    {
        $res = $this->response('Status transaksi saat ini expired');

        $this->assertTrue($res->isExpired());
        $this->assertFalse($res->isPaid());
    }

    public function test_unknown_or_empty_message(): void
    {
        foreach (['', 'Transaksi tidak ditemukan', 'PREPAID voucher'] as $message) {
            $res = $this->response($message);
            $this->assertFalse($res->isPaid(), $message);
            $this->assertFalse($res->isUnpaid(), $message);
            $this->assertFalse($res->isExpired(), $message);
        }
    }
}
