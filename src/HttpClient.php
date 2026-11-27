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
    protected ?string $caBundle;

    /**
     * @param string|null $caBundle Path file CA bundle (mis. cacert.pem) bila CA bawaan server
     *                              tidak lengkap. Verifikasi TLS tetap aktif.
     */
    public function __construct(string $apiKey, string $baseUrl, ?string $caBundle = null)
    {
        $this->apiKey   = $apiKey;
        $this->baseUrl  = rtrim($baseUrl, '/');
        $this->caBundle = $caBundle !== null && $caBundle !== '' ? $caBundle : null;
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
     * Main request handler.
     *
     * Verifikasi TLS selalu aktif. Tidak ada fallback ke verify=false: fallback itu membuka
     * celah MITM (API key bocor, respons palsu diterima). Bila server gagal memverifikasi
     * sertifikat, perbaiki CA bundle (config `tripay.ca_bundle` / TRIPAY_CA_BUNDLE).
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        return $this->send($method, $endpoint, $options, $this->caBundle ?? true);
    }

    /**
     * Low-level HTTP sender
     *
     * @param bool|string $verify true atau path CA bundle
     */
    protected function send(
        string $method,
        string $endpoint,
        array $options,
        bool|string $verify
    ): array {
        try {

       $fullUrl = $this->baseUrl . '/' . ltrim($endpoint, '/');
     


            $client = new Client([
                'base_uri'        => $this->baseUrl,
                'timeout'         => 30,
                'connect_timeout' => 10,
                'http_errors'     => false,
                'verify'          => $verify,
                'headers'         => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept'        => 'application/json',
                    'User-Agent'    => 'ilhamuket/tripay-sdk/1.1.1',
                ],
            ]);

            $response = $client->request($method, $fullUrl, $options);
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
}
