<?php

namespace Ufrfrk\Tripay\Facades;

use Illuminate\Support\Facades\Facade;
use Ufrfrk\Tripay\Tripay as TripayClient;

/**
 * @method static \Ufrfrk\Tripay\Response\PaymentChannelResponse getPaymentChannels(?string $code = null)
 * @method static \Ufrfrk\Tripay\Response\FeeCalculatorResponse calculateFee(int $amount, ?string $code = null)
 * @method static \Ufrfrk\Tripay\Response\TransactionResponse getTransactions(array $params = [])
 * @method static \Ufrfrk\Tripay\Response\CreateTransactionResponse createTransaction(\Ufrfrk\Tripay\Data\TransactionData $data)
 * @method static \Ufrfrk\Tripay\Response\TransactionDetailResponse getTransactionDetail(string $reference)
 * @method static \Ufrfrk\Tripay\Response\CheckStatusResponse checkTransactionStatus(string $reference)
 * @method static \Ufrfrk\Tripay\Response\InstructionResponse getPaymentInstruction(string $code, ?string $payCode = null, ?int $amount = null, bool $allowHtml = true)
 * @method static bool validateCallback(string $callbackSignature, string $jsonBody)
 * @method static string generateSignature(string $merchantRef, int $amount)
 * @method static \Ufrfrk\Tripay\Tripay sandbox()
 * @method static \Ufrfrk\Tripay\Tripay production()
 * 
 * @see \Ufrfrk\Tripay\Tripay
 */
class Tripay extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return TripayClient::class;
    }
}
