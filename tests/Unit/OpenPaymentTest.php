<?php

namespace Ilhamuket\Tripay\Tests\Unit;

use Ilhamuket\Tripay\Exceptions\TripayApiException;
use Ilhamuket\Tripay\HttpClient;
use Ilhamuket\Tripay\Tripay;
use PHPUnit\Framework\TestCase;

/** HttpClient palsu: mencatat request dan membalas respons tetap. */
class FakeHttpClient extends HttpClient
{
    public array $calls = [];

    public function __construct(private array $reply)
    {
        parent::__construct('api-key', 'https://tripay.co.id/api');
    }

    public function get(string $endpoint, array $params = []): array
    {
        $this->calls[] = ['GET', $endpoint, $params];

        return $this->reply;
    }

    public function post(string $endpoint, array $data = []): array
    {
        $this->calls[] = ['POST', $endpoint, $data];

        return $this->reply;
    }
}

class TestableTripay extends Tripay
{
    public function useHttpClient(HttpClient $client): self
    {
        $this->httpClient = $client;

        return $this;
    }
}

class OpenPaymentTest extends TestCase
{
    private function vectors(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../../spec/test-vectors.json'), true)['openPayment'];
    }

    private function production(array $reply = ['success' => true, 'data' => []]): array
    {
        $http = new FakeHttpClient($reply);
        $tripay = (new TestableTripay('api-key', 'private-key-sandbox', 'T0001', Tripay::MODE_PRODUCTION))
            ->useHttpClient($http);

        return [$tripay, $http];
    }

    public function test_signature_matches_shared_vectors(): void
    {
        foreach ($this->vectors() as $v) {
            $tripay = new Tripay('api-key', $v['privateKey'], $v['merchantCode']);
            $this->assertSame($v['expected'], $tripay->generateOpenPaymentSignature($v['method'], $v['merchantRef']));
        }
    }

    public function test_create_sends_form_with_signature(): void
    {
        [$tripay, $http] = $this->production([
            'success' => true,
            'data' => ['uuid' => 'T0001-OP-1', 'payment_method' => 'QRIS', 'qr_string' => '000201', 'pay_code' => null],
        ]);

        $res = $tripay->createOpenPayment('QRIS', 'OP-0001', 'Budi');

        $this->assertSame('T0001-OP-1', $res->getUuid());
        $this->assertSame('000201', $res->getQrString());
        $this->assertNull($res->getPayCode());

        [$method, $endpoint, $data] = $http->calls[0];
        $this->assertSame(['POST', 'open-payment/create'], [$method, $endpoint]);
        $this->assertSame('QRIS', $data['method']);
        $this->assertSame('OP-0001', $data['merchant_ref']);
        $this->assertSame('Budi', $data['customer_name']);
        $this->assertSame($this->vectors()[0]['expected'], $data['signature']);
    }

    public function test_create_without_merchant_ref_signs_empty_string(): void
    {
        [$tripay, $http] = $this->production();

        $tripay->createOpenPayment('QRIS');

        $data = $http->calls[0][2];
        $this->assertArrayNotHasKey('merchant_ref', $data);
        $this->assertArrayNotHasKey('customer_name', $data);
        $this->assertSame($this->vectors()[2]['expected'], $data['signature']);
    }

    public function test_detail_and_transactions_encode_uuid_and_filter_params(): void
    {
        [$tripay, $http] = $this->production([
            'success' => true,
            'data' => [['reference' => 'T1', 'status' => 'PAID']],
            'pagination' => ['total' => 3, 'current_page' => 1, 'last_page' => 2, 'next_page' => 2],
        ]);

        $tripay->getOpenPaymentDetail('T0001-OP-1');
        $list = $tripay->getOpenPaymentTransactions('a/../b', [
            'start_date' => '2026-11-01 00:00:00',
            'per_page' => 50,
            'evil' => 'x',
        ]);

        $this->assertSame('open-payment/T0001-OP-1/detail', $http->calls[0][1]);
        $this->assertSame('open-payment/a%2F..%2Fb/transactions', $http->calls[1][1]);
        $this->assertSame(['start_date' => '2026-11-01 00:00:00', 'per_page' => 50], $http->calls[1][2]);
        $this->assertSame(3, $list->getTotal());
        $this->assertTrue($list->hasMorePages());
        $this->assertSame(2, $list->getNextPage());
        $this->assertCount(1, $list->getTransactions());
    }

    public function test_invalid_input_is_rejected_before_request(): void
    {
        [$tripay, $http] = $this->production();

        foreach ([fn () => $tripay->createOpenPayment(' '), fn () => $tripay->getOpenPaymentDetail(''), fn () => $tripay->getOpenPaymentTransactions(' ')] as $call) {
            try {
                $call();
                $this->fail('Harusnya InvalidArgumentException');
            } catch (\InvalidArgumentException) {
            }
        }

        $this->assertSame([], $http->calls);
    }

    public function test_sandbox_mode_is_rejected_with_clear_message(): void
    {
        $http = new FakeHttpClient([]);
        $tripay = (new TestableTripay('api-key', 'p', 'T0001', Tripay::MODE_SANDBOX))->useHttpClient($http);

        foreach (['createOpenPayment' => ['QRIS'], 'getOpenPaymentDetail' => ['x'], 'getOpenPaymentTransactions' => ['x']] as $method => $args) {
            try {
                $tripay->{$method}(...$args);
                $this->fail("$method harusnya ditolak di sandbox");
            } catch (TripayApiException $e) {
                $this->assertStringContainsString('production', $e->getMessage());
            }
        }

        $this->assertSame([], $http->calls);
    }
}
