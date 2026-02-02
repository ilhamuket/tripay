<?php

namespace Ufrfrk\Tripay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ufrfrk\Tripay\Exceptions\TripayApiException;
use Ufrfrk\Tripay\Exceptions\TripayConnectionException;

class HttpClient
{
    protected Client $client;
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct(string $apiKey, string $baseUrl)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Send GET request
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    /**
     * Send POST request
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['form_params' => $data]);
    }

    /**
     * Send request to Tripay API
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $endpoint, $options);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new TripayApiException('Invalid JSON response from Tripay API');
            }

            if (!isset($data['success'])) {
                throw new TripayApiException('Unexpected response format from Tripay API');
            }

            if (!$data['success']) {
                throw new TripayApiException(
                    $data['message'] ?? 'Unknown error from Tripay API',
                    $response->getStatusCode()
                );
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new TripayConnectionException(
                'Failed to connect to Tripay API: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
