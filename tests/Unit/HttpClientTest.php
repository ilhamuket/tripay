<?php

namespace Ilhamuket\Tripay\Tests\Unit;

use Ilhamuket\Tripay\Exceptions\TripayConnectionException;
use Ilhamuket\Tripay\HttpClient;
use PHPUnit\Framework\TestCase;

/** HttpClient yang mencatat nilai `verify` tiap percobaan kirim. */
class RecordingHttpClient extends HttpClient
{
    public array $verifyCalls = [];

    protected function send(string $method, string $endpoint, array $options, bool|string $verify): array
    {
        $this->verifyCalls[] = $verify;
        throw new TripayConnectionException('Failed to connect to Tripay API: cURL error 60: SSL certificate problem');
    }
}

class HttpClientTest extends TestCase
{
    public function test_ssl_error_is_not_retried_without_verification(): void
    {
        $client = new RecordingHttpClient('api-key', 'https://tripay.co.id/api-sandbox');

        try {
            $client->get('merchant/payment-channel');
            $this->fail('Harusnya melempar TripayConnectionException');
        } catch (TripayConnectionException $e) {
            $this->assertStringContainsString('SSL certificate problem', $e->getMessage());
        }

        $this->assertSame([true], $client->verifyCalls);
    }

    public function test_ca_bundle_is_used_as_verify_value(): void
    {
        $client = new RecordingHttpClient('api-key', 'https://tripay.co.id/api-sandbox', '/etc/ssl/cacert.pem');

        try {
            $client->post('transaction/create', []);
        } catch (TripayConnectionException) {
        }

        $this->assertSame(['/etc/ssl/cacert.pem'], $client->verifyCalls);
    }

    public function test_empty_ca_bundle_falls_back_to_system_ca(): void
    {
        $client = new RecordingHttpClient('api-key', 'https://tripay.co.id/api-sandbox', '');

        try {
            $client->get('merchant/payment-channel');
        } catch (TripayConnectionException) {
        }

        $this->assertSame([true], $client->verifyCalls);
    }
}
