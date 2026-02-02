<?php

namespace Ilhamuket\Tripay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ilhamuket\Tripay\Exceptions\TripayApiException;
use Ilhamuket\Tripay\Exceptions\TripayConnectionException;

class HttpClient
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct(string $apiKey, string $baseUrl)
    {
        $this->apiKey  = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Send GET request
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, [
            'query' => $params,
        ]);
    }

    /**
     * Send POST request
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [
            'form_params' => $data,
        ]);
    }

    /**
     * Main request handler (with SSL auto fallback)
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            // 🔐 First attempt: SSL enabled (secure default)
            return $this->send($method, $endpoint, $options, true);
        } catch (TripayConnectionException $e) {
            // 🔥 Auto fallback ONLY for SSL-related errors
            if ($this->isSslError($e)) {
                return $this->send($method, $endpoint, $options, false);
            }

            throw $e;
        }
    }

    /**
     * Low-level HTTP sender
     */
   protected function send(
        string $method,
        string $endpoint,
        array $options,
        bool $verifySsl
    ): array {
        try {
            $client = new Client([
                'base_uri'        => $this->baseUrl,
                'timeout'         => 30,
                'connect_timeout' => 10,
                'http_errors'     => false,
                'verify'          => $verifySsl,
                'headers'         => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept'        => 'application/json',
                    'User-Agent'    => 'ilhamuket/tripay-sdk/1.0.3',
                ],
            ]);

            $response = $client->request($method, $endpoint, $options);
            $status   = $response->getStatusCode();
            $body     = trim($response->getBody()->getContents());

            if ($body === '') {
                throw new TripayApiException('Empty response from Tripay API');
            }

            if (!str_starts_with($body, '{')) {
                throw new TripayApiException(
                    'Non-JSON response from Tripay API: ' . substr($body, 0, 200)
                );
            }

            $json = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new TripayApiException(
                    'Invalid JSON response from Tripay API: ' . json_last_error_msg()
                );
            }

            // ❌ Tripay DOES NOT use success flag
            // ✅ HTTP status check is enough
            if ($status >= 400) {
                throw new TripayApiException(
                    $json['message'] ?? 'Tripay API error',
                    $status
                );
            }

            return $json;

        } catch (GuzzleException $e) {
            throw new TripayConnectionException(
                'Failed to connect to Tripay API: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }



    /**
     * Detect SSL / certificate related errors
     */
    protected function isSslError(TripayConnectionException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'ssl')
            || str_contains($message, 'certificate')
            || str_contains($message, 'cainfo')
            || str_contains($message, 'curl error 77')
            || str_contains($message, 'curl error 60');
    }
}
