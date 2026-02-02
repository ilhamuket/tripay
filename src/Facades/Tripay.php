<?php

namespace ilhamuket\Tripay\Facades;

use Illuminate\Support\Facades\Facade;
use ilhamuket\Tripay\Tripay as TripayClient;

/**
 * @method static \ilhamuket\Tripay\Response\PaymentChannelResponse getPaymentChannels(?string $code = null)
 * @method static \ilhamuket\Tripay\Response\FeeCalculatorResponse calculateFee(int $amount, ?string $code = null)
 * @method static \ilhamuket\Tripay\Response\TransactionResponse getTransactions(array $params = [])
 * @method static \ilhamuket\Tripay\Response\CreateTransactionResponse createTransaction(\ilhamuket\Tripay\Data\TransactionData $data)
 * @method static \ilhamuket\Tripay\Response\TransactionDetailResponse getTransactionDetail(string $reference)
 * @method static \ilhamuket\Tripay\Response\CheckStatusResponse checkTransactionStatus(string $reference)
 * @method static \ilhamuket\Tripay\Response\InstructionResponse getPaymentInstruction(string $code, ?string $payCode = null, ?int $amount = null, bool $allowHtml = true)
 * @method static bool validateCallback(string $callbackSignature, string $jsonBody)
 * @method static string generateSignature(string $merchantRef, int $amount)
 * @method static \ilhamuket\Tripay\Tripay sandbox()
 * @method static \ilhamuket\Tripay\Tripay production()
 * 
 * @see \ilhamuket\Tripay\Tripay
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
