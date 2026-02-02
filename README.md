# Tripay SDK for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Ilhamuket/tripay.svg?style=flat-square)](https://packagist.org/packages/Ilhamuket/tripay)
[![Total Downloads](https://img.shields.io/packagist/dt/Ilhamuket/tripay.svg?style=flat-square)](https://packagist.org/packages/Ilhamuket/tripay)
[![License](https://img.shields.io/packagist/l/Ilhamuket/tripay.svg?style=flat-square)](https://packagist.org/packages/Ilhamuket/tripay)

SDK PHP untuk mengintegrasikan [Tripay Payment Gateway](https://tripay.co.id) dengan Laravel 10, 11, dan 12.

## ✨ Features

- ✅ Support Laravel 10, 11, 12
- ✅ PHP 8.2+
- ✅ Closed Payment Transaction
- ✅ Payment Channels
- ✅ Fee Calculator
- ✅ Transaction List & Detail
- ✅ Check Transaction Status
- ✅ Payment Instructions
- ✅ Callback Handling & Validation
- ✅ Facade Support
- ✅ Type-safe Response Objects

## 📦 Installation

```bash
composer require Ilhamuket/tripay
```

### Publish Config

```bash
php artisan vendor:publish --tag=tripay-config
```

### Environment Configuration

Add to your `.env` file:

```env
TRIPAY_MODE=sandbox
TRIPAY_MERCHANT_CODE=your-merchant-code
TRIPAY_API_KEY=your-api-key
TRIPAY_PRIVATE_KEY=your-private-key
```

## 🚀 Usage

### Using Facade

```php
use Ilhamuket\Tripay\Facades\Tripay;
use Ilhamuket\Tripay\Data\TransactionData;
use Ilhamuket\Tripay\Data\OrderItem;
use Ilhamuket\Tripay\PaymentMethod;

// Get Payment Channels
$channels = Tripay::getPaymentChannels();
foreach ($channels->getChannels() as $channel) {
    echo $channel->getName() . ' - ' . $channel->getCode();
}

// Get only QRIS channel
$qris = Tripay::getPaymentChannels('QRIS');

// Calculate Fee
$fee = Tripay::calculateFee(100000, 'QRIS');

// Create Transaction
$transaction = new TransactionData();
$transaction
    ->setMethod(PaymentMethod::QRIS)
    ->setMerchantRef('INV-' . time())
    ->setAmount(100000)
    ->setCustomerName('John Doe')
    ->setCustomerEmail('john@example.com')
    ->setCustomerPhone('081234567890')
    ->addOrderItem([
        'name' => 'Product Name',
        'price' => 100000,
        'quantity' => 1,
    ])
    ->setReturnUrl('https://yoursite.com/return')
    ->setExpiryHours(24);

$response = Tripay::createTransaction($transaction);

if ($response->isSuccess()) {
    echo $response->getReference();      // Tripay reference
    echo $response->getCheckoutUrl();    // Checkout URL
    echo $response->getQrUrl();          // QR Code URL (for QRIS)
    echo $response->getPayCode();        // VA Number (for VA)
}

// Get Transaction Detail
$detail = Tripay::getTransactionDetail('T0001000000000000006');
if ($detail->isPaid()) {
    echo 'Transaction is paid!';
}

// Check Transaction Status
$status = Tripay::checkTransactionStatus('T0001000000000000006');

// Get Payment Instructions
$instructions = Tripay::getPaymentInstruction('BRIVA');
```

### Using Dependency Injection

```php
use Ilhamuket\Tripay\Tripay;

class PaymentController extends Controller
{
    public function __construct(
        protected Tripay $tripay
    ) {}

    public function createPayment()
    {
        $response = $this->tripay->createTransaction(...);
    }
}
```

### Direct Instantiation (Without Laravel)

```php
use Ilhamuket\Tripay\Tripay;

$tripay = new Tripay(
    apiKey: 'your-api-key',
    privateKey: 'your-private-key',
    merchantCode: 'your-merchant-code',
    mode: 'sandbox' // or 'production'
);

// Switch mode on the fly
$tripay->production(); // Switch to production
$tripay->sandbox();    // Switch to sandbox
```

## 🔔 Handling Callback

Create a controller to handle Tripay callbacks:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ilhamuket\Tripay\Facades\Tripay;

class TripayCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // Get signature from header
        $signature = $request->server('HTTP_X_CALLBACK_SIGNATURE');
        $json = $request->getContent();

        // Validate signature
        if (!Tripay::validateCallback($signature, $json)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        // Parse callback data
        $callback = Tripay::parseCallback($json);

        // Process based on status
        if ($callback->isPaid()) {
            // Payment successful
            $merchantRef = $callback->getMerchantRef();
            $reference = $callback->getReference();
            $amount = $callback->getTotalAmount();
            
            // Update your order/invoice status
            // Order::where('invoice', $merchantRef)->update(['status' => 'paid']);
        }

        if ($callback->isExpired()) {
            // Payment expired
        }

        if ($callback->isFailed()) {
            // Payment failed
        }

        return response()->json(['success' => true]);
    }
}
```

Add route in `routes/api.php`:

```php
Route::post('/callback/tripay', [TripayCallbackController::class, 'handle']);
```

Don't forget to exclude from CSRF verification in `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'api/callback/tripay',
];
```

## 📋 Payment Methods

Use the `PaymentMethod` class for available payment methods:

```php
use Ilhamuket\Tripay\PaymentMethod;

// Virtual Account
PaymentMethod::BRIVA      // BRI Virtual Account
PaymentMethod::BNIVA      // BNI Virtual Account
PaymentMethod::BCAVA      // BCA Virtual Account
PaymentMethod::MANDIRIVA  // Mandiri Virtual Account
// ... and more

// Convenience Store
PaymentMethod::ALFAMART
PaymentMethod::INDOMARET
PaymentMethod::ALFAMIDI

// E-Wallet
PaymentMethod::QRIS
PaymentMethod::OVO
PaymentMethod::DANA
PaymentMethod::SHOPEEPAY

// Helper methods
PaymentMethod::virtualAccounts();   // Get all VA methods
PaymentMethod::eWallets();          // Get all e-wallet methods
PaymentMethod::convenienceStores(); // Get all convenience store methods
PaymentMethod::isQris('QRIS');      // Check if method is QRIS
```

## 📖 API Reference

### Tripay Class Methods

| Method | Description |
|--------|-------------|
| `getPaymentChannels(?string $code)` | Get available payment channels |
| `calculateFee(int $amount, ?string $code)` | Calculate transaction fee |
| `getTransactions(array $params)` | Get list of transactions |
| `createTransaction(TransactionData $data)` | Create new transaction |
| `getTransactionDetail(string $reference)` | Get transaction detail |
| `checkTransactionStatus(string $reference)` | Check transaction status |
| `getPaymentInstruction(string $code, ...)` | Get payment instructions |
| `validateCallback(string $signature, string $json)` | Validate callback signature |
| `parseCallback(string $json)` | Parse callback data |
| `generateSignature(string $merchantRef, int $amount)` | Generate transaction signature |
| `sandbox()` | Switch to sandbox mode |
| `production()` | Switch to production mode |

### Response Objects

All API responses are wrapped in type-safe response objects:

- `PaymentChannelResponse`
- `FeeCalculatorResponse`
- `TransactionResponse`
- `CreateTransactionResponse`
- `TransactionDetailResponse`
- `CheckStatusResponse`
- `InstructionResponse`

## 🧪 Testing

```bash
composer test
```

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 🔗 Links

- [Tripay Documentation](https://tripay.co.id/developer)
- [Tripay Sandbox](https://tripay.co.id/simulator)
