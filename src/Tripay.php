<?php

namespace Ufrfrk\Tripay;

use Ufrfrk\Tripay\Data\TransactionData;
use Ufrfrk\Tripay\Response\CheckStatusResponse;
use Ufrfrk\Tripay\Response\CreateTransactionResponse;
use Ufrfrk\Tripay\Response\FeeCalculatorResponse;
use Ufrfrk\Tripay\Response\InstructionResponse;
use Ufrfrk\Tripay\Response\PaymentChannelResponse;
use Ufrfrk\Tripay\Response\TransactionDetailResponse;
use Ufrfrk\Tripay\Response\TransactionResponse;

class Tripay
{
    protected HttpClient $httpClient;
    protected string $apiKey;
    protected string $privateKey;
    protected string $merchantCode;
    protected string $mode;
    protected string $baseUrl;

    public const MODE_SANDBOX = 'sandbox';
    public const MODE_PRODUCTION = 'production';

    public const SANDBOX_URL = 'https://tripay.co.id/api-sandbox';
    public const PRODUCTION_URL = 'https://tripay.co.id/api';

    public function __construct(
        string $apiKey,
        string $privateKey,
        string $merchantCode,
        string $mode = self::MODE_SANDBOX
    ) {
        $this->apiKey = $apiKey;
        $this->privateKey = $privateKey;
        $this->merchantCode = $merchantCode;
        $this->mode = $mode;
        $this->baseUrl = $this->resolveBaseUrl($mode);
        $this->httpClient = new HttpClient($apiKey, $this->baseUrl);
    }

    /**
     * Switch to sandbox mode
     */
    public function sandbox(): self
    {
        $this->mode = self::MODE_SANDBOX;
        $this->baseUrl = self::SANDBOX_URL;
        $this->httpClient = new HttpClient($this->apiKey, $this->baseUrl);
        
        return $this;
    }

    /**
     * Switch to production mode
     */
    public function production(): self
    {
        $this->mode = self::MODE_PRODUCTION;
        $this->baseUrl = self::PRODUCTION_URL;
        $this->httpClient = new HttpClient($this->apiKey, $this->baseUrl);
        
        return $this;
    }

    /**
     * Resolve base URL from mode
     */
    protected function resolveBaseUrl(string $mode): string
    {
        return $mode === self::MODE_PRODUCTION 
            ? self::PRODUCTION_URL 
            : self::SANDBOX_URL;
    }

    /**
     * Get available payment channels
     */
    public function getPaymentChannels(?string $code = null): PaymentChannelResponse
    {
        $params = [];
        if ($code) {
            $params['code'] = $code;
        }

        $response = $this->httpClient->get('/merchant/payment-channel', $params);
        
        return new PaymentChannelResponse($response);
    }

    /**
     * Calculate transaction fee
     */
    public function calculateFee(int $amount, ?string $code = null): FeeCalculatorResponse
    {
        $params = ['amount' => $amount];
        if ($code) {
            $params['code'] = $code;
        }

        $response = $this->httpClient->get('/merchant/fee-calculator', $params);
        
        return new FeeCalculatorResponse($response);
    }

    /**
     * Get list of transactions
     */
    public function getTransactions(array $params = []): TransactionResponse
    {
        $allowedParams = ['page', 'per_page', 'sort', 'reference', 'merchant_ref', 'method', 'status'];
        $filteredParams = array_intersect_key($params, array_flip($allowedParams));

        $response = $this->httpClient->get('/merchant/transactions', $filteredParams);
        
        return new TransactionResponse($response);
    }

    /**
     * Create a new transaction (closed payment)
     */
    public function createTransaction(TransactionData $data): CreateTransactionResponse
    {
        $payload = $data->toArray();
        $payload['signature'] = $this->generateSignature(
            $payload['merchant_ref'],
            $payload['amount']
        );

        $response = $this->httpClient->post('/transaction/create', $payload);
        
        return new CreateTransactionResponse($response);
    }

    /**
     * Get transaction detail
     */
    public function getTransactionDetail(string $reference): TransactionDetailResponse
    {
        $response = $this->httpClient->get('/transaction/detail', [
            'reference' => $reference,
        ]);
        
        return new TransactionDetailResponse($response);
    }

    /**
     * Check transaction status
     */
    public function checkTransactionStatus(string $reference): CheckStatusResponse
    {
        $response = $this->httpClient->get('/transaction/check-status', [
            'reference' => $reference,
        ]);
        
        return new CheckStatusResponse($response);
    }

    /**
     * Get payment instruction
     */
    public function getPaymentInstruction(
        string $code,
        ?string $payCode = null,
        ?int $amount = null,
        bool $allowHtml = true
    ): InstructionResponse {
        $params = [
            'code' => $code,
            'allow_html' => $allowHtml ? 1 : 0,
        ];

        if ($payCode) {
            $params['pay_code'] = $payCode;
        }

        if ($amount) {
            $params['amount'] = $amount;
        }

        $response = $this->httpClient->get('/payment/instruction', $params);
        
        return new InstructionResponse($response);
    }

    /**
     * Generate signature for transaction
     */
    public function generateSignature(string $merchantRef, int $amount): string
    {
        return hash_hmac('sha256', $this->merchantCode . $merchantRef . $amount, $this->privateKey);
    }

    /**
     * Validate callback signature from Tripay
     */
    public function validateCallback(string $callbackSignature, string $jsonBody): bool
    {
        $localSignature = hash_hmac('sha256', $jsonBody, $this->privateKey);
        
        return hash_equals($localSignature, $callbackSignature);
    }

    /**
     * Parse callback data
     */
    public function parseCallback(string $jsonBody): Callback\CallbackData
    {
        $data = json_decode($jsonBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exceptions\TripayApiException('Invalid callback JSON data');
        }
        
        return new Callback\CallbackData($data);
    }

    /**
     * Get merchant code
     */
    public function getMerchantCode(): string
    {
        return $this->merchantCode;
    }

    /**
     * Get current mode
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Check if in sandbox mode
     */
    public function isSandbox(): bool
    {
        return $this->mode === self::MODE_SANDBOX;
    }

    /**
     * Check if in production mode
     */
    public function isProduction(): bool
    {
        return $this->mode === self::MODE_PRODUCTION;
    }
}
