<?php

namespace Ilhamuket\Tripay\Facades;

use Illuminate\Support\Facades\Facade;
use Ilhamuket\Tripay\Tripay as TripayClient;

/**
 * @method static \Ilhamuket\Tripay\Response\PaymentChannelResponse getPaymentChannels(?string $code = null)
 * @method static \Ilhamuket\Tripay\Response\FeeCalculatorResponse calculateFee(int $amount, ?string $code = null)
 * @method static \Ilhamuket\Tripay\Response\TransactionResponse getTransactions(array $params = [])
 * @method static \Ilhamuket\Tripay\Response\CreateTransactionResponse createTransaction(\Ilhamuket\Tripay\Data\TransactionData $data)
 * @method static \Ilhamuket\Tripay\Response\TransactionDetailResponse getTransactionDetail(string $reference)
 * @method static \Ilhamuket\Tripay\Response\CheckStatusResponse checkTransactionStatus(string $reference)
 * @method static \Ilhamuket\Tripay\Response\InstructionResponse getPaymentInstruction(string $code, ?string $payCode = null, ?int $amount = null, bool $allowHtml = true)
 * @method static bool validateCallback(string $callbackSignature, string $jsonBody)
 * @method static string generateSignature(string $merchantRef, int $amount)
 * @method static \Ilhamuket\Tripay\Tripay sandbox()
 * @method static \Ilhamuket\Tripay\Tripay production()
 * 
 * @see \Ilhamuket\Tripay\Tripay
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
