<?php

require 'vendor/autoload.php';

use Ilhamuket\Tripay\Tripay;
use Ilhamuket\Tripay\Data\TransactionData;
use Ilhamuket\Tripay\PaymentMethod;

// ✅ Ganti dengan kredensial ASLI kamu
$apiKey = 'DEV-zcBoP49eCBhFBxx5rwjNzyCQ9jxgWpEYYT8HQIg5';
$privateKey = 'tjdtm-rUovg-qvgzE-9fCvD-dsvkC';
$merchantCode = 'T48358';

// ANSI color codes untuk output yang lebih menarik
define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('BLUE', "\033[34m");
define('RESET', "\033[0m");

function printHeader($text) {
    echo "\n" . BLUE . str_repeat("=", 60) . RESET . "\n";
    echo BLUE . $text . RESET . "\n";
    echo BLUE . str_repeat("=", 60) . RESET . "\n\n";
}

function printTest($number, $name) {
    echo YELLOW . "\nTEST $number: $name" . RESET . "\n";
    echo str_repeat("-", 60) . "\n";
}

function printSuccess($message) {
    echo GREEN . "✅ SUCCESS: $message" . RESET . "\n";
}

function printError($message) {
    echo RED . "❌ FAILED: $message" . RESET . "\n";
}

function printInfo($label, $value) {
    echo "  $label: $value\n";
}

// ====================================
// MULAI TESTING
// ====================================

printHeader("TRIPAY SDK - COMPREHENSIVE API TEST");

$tripay = new Tripay($apiKey, $privateKey, $merchantCode, 'sandbox');

echo "Configuration:\n";
printInfo("Mode", $tripay->getMode());
printInfo("Is Sandbox", $tripay->isSandbox() ? 'Yes' : 'No');
printInfo("Merchant Code", $tripay->getMerchantCode());

$testsPassed = 0;
$testsFailed = 0;
$createdReference = null; // Untuk test detail & status

// ====================================
// TEST 1: Get Payment Channels (All)
// ====================================
printTest(1, "Get All Payment Channels");
try {
    $channels = $tripay->getPaymentChannels();
    $channelData = $channels->getData();
    
    printSuccess("Got " . count($channelData) . " payment channels");
    if (!empty($channelData)) {
        printInfo("First Channel", $channelData[0]['name'] ?? 'N/A');
        printInfo("Code", $channelData[0]['code'] ?? 'N/A');
    }
    
    $testsPassed++;
} catch (Exception $e) {
    printError($e->getMessage());
    $testsFailed++;
}

// ====================================
// TEST 2: Get Payment Channel (Specific)
// ====================================
printTest(2, "Get Specific Payment Channel (BRIVA)");
try {
    $channel = $tripay->getPaymentChannels('BRIVA');
    $channelData = $channel->getData();
    
    printSuccess("Got channel details");
    if (!empty($channelData)) {
        printInfo("Name", $channelData[0]['name'] ?? 'N/A');
        printInfo("Code", $channelData[0]['code'] ?? 'N/A');
        printInfo("Fee", "Rp " . number_format($channelData[0]['total_fee']['flat'] ?? 0));
    }
    
    $testsPassed++;
} catch (Exception $e) {
    printError($e->getMessage());
    $testsFailed++;
}

// ====================================
// TEST 3: Calculate Fee
// ====================================
printTest(3, "Calculate Transaction Fee");
try {
    $feeCalc = $tripay->calculateFee(100000, 'BRIVA');
    $feeData = $feeCalc->getData();
    
    printSuccess("Fee calculated");
    if (!empty($feeData)) {
        printInfo("Amount", "Rp " . number_format(100000));
        printInfo("Total Fee", "Rp " . number_format($feeData[0]['total_fee']['customer'] ?? 0));
        printInfo("Amount Received", "Rp " . number_format($feeData[0]['total_fee']['merchant'] ?? 0));
    }
    
    $testsPassed++;
} catch (Exception $e) {
    printError($e->getMessage());
    $testsFailed++;
}

// ====================================
// TEST 4: Create Transaction
// ====================================
printTest(4, "Create Closed Payment Transaction");

$merchantRef = 'TEST-' . date('YmdHis');

try {
    $data = new TransactionData();
    $data->setMethod(PaymentMethod::BRIVA)
        ->setMerchantRef($merchantRef)
        ->setAmount(100000)
        ->setCustomerName('John Doe')
        ->setCustomerEmail('johndoe@example.com')
        ->setCustomerPhone('081234567890')
        ->addOrderItem([
            'sku' => 'PROD-001',
            'name' => 'Test Product 1',
            'price' => 50000,
            'quantity' => 1,
            'product_url' => 'https://example.com/product-1',
            'image_url' => 'https://example.com/product-1.jpg',
        ])
        ->addOrderItem([
            'sku' => 'PROD-002',
            'name' => 'Test Product 2',
            'price' => 50000,
            'quantity' => 1,
            'product_url' => 'https://example.com/product-2',
            'image_url' => 'https://example.com/product-2.jpg',
        ])
        ->setReturnUrl('https://example.com/return')
        ->setCallbackUrl('https://example.com/callback')
        ->setExpiryHours(24);

    $response = $tripay->createTransaction($data);
    
    printSuccess("Transaction created");
    printInfo("Reference", $response->getReference());
    printInfo("Merchant Ref", $merchantRef);
    printInfo("Status", $response->getStatus());
    printInfo("Amount", "Rp " . number_format($response->getAmount()));
    printInfo("Pay Code", $response->getPayCode() ?? 'N/A');
    printInfo("Checkout URL", $response->getCheckoutUrl() ?? 'N/A');
    
    $createdReference = $response->getReference();
    $testsPassed++;
    
} catch (Exception $e) {
    printError($e->getMessage());
    $testsFailed++;
}

// ====================================
// TEST 5: Get Transaction Detail
// ====================================
if ($createdReference) {
    printTest(5, "Get Transaction Detail");
    try {
        $detail = $tripay->getTransactionDetail($createdReference);
        
        printSuccess("Got transaction detail");
        printInfo("Reference", $detail->getReference());
        printInfo("Status", $detail->getStatus());
        printInfo("Amount", "Rp " . number_format($detail->getAmount()));
        printInfo("Customer Name", $detail->getCustomerName());
        printInfo("Payment Method", $detail->getPaymentMethod());
        printInfo("Payment Name", $detail->getPaymentName());
        
        $testsPassed++;
    } catch (Exception $e) {
        printError($e->getMessage());
        $testsFailed++;
    }
} else {
    printTest(5, "Get Transaction Detail");
    printError("Skipped - No transaction created");
    $testsFailed++;
}

// ====================================
// TEST 6: Check Transaction Status
// ====================================
if ($createdReference) {
    printTest(6, "Check Transaction Status");
    try {
        $status = $tripay->checkTransactionStatus($createdReference);
        
        printSuccess("Got transaction status");
        printInfo("Status Message", $status->getStatusMessage());
        printInfo("Is Paid", $status->isPaid() ? 'Yes' : 'No');
        printInfo("Is Unpaid", $status->isUnpaid() ? 'Yes' : 'No');
        printInfo("Is Expired", $status->isExpired() ? 'Yes' : 'No');
        
        $testsPassed++;
    } catch (Exception $e) {
        printError($e->getMessage());
        $testsFailed++;
    }
} else {
    printTest(6, "Check Transaction Status");
    printError("Skipped - No transaction created");
    $testsFailed++;
}

// ====================================
// TEST 7: Get Payment Instructions
// ====================================
printTest(7, "Get Payment Instructions");
try {
    $instruction = $tripay->getPaymentInstruction('BRIVA');
    
    printSuccess("Got payment instructions");
    
    // Cek apakah menggunakan Laravel Collection atau array biasa
    if (method_exists($instruction, 'getInstructions')) {
        $instructions = $instruction->getInstructions();
        if (is_object($instructions) && method_exists($instructions, 'count')) {
            // Laravel Collection
            printInfo("Instructions Count", $instructions->count());
            if ($instructions->count() > 0) {
                $titles = $instruction->getTitles();
                printInfo("Titles", $titles->implode(', '));
            }
        } else {
            // Array biasa
            printInfo("Instructions Count", count($instructions));
        }
    } else {
        $data = $instruction->getData();
        printInfo("Data Count", count($data));
    }
    
    $testsPassed++;
} catch (Exception $e) {
    printError($e->getMessage());
    $testsFailed++;
}

// ====================================
// TEST 8: Get Transactions List
// ====================================
printTest(8, "Get Transactions List");
try {
    $transactions = $tripay->getTransactions([
        'per_page' => 5,
        'page' => 1,
    ]);
    
    printSuccess("Got transactions list");
    
    // Cek apakah menggunakan Laravel Collection atau array biasa
    if (method_exists($transactions, 'getTransactions')) {
        $transData = $transactions->getTransactions();
        if (is_object($transData) && method_exists($transData, 'count')) {
            // Laravel Collection
            printInfo("Total Found", $transData->count());
            if ($transData->count() > 0) {
                $first = $transData->first();
                printInfo("First Transaction", $first['reference'] ?? 'N/A');
                printInfo("Status", $first['status'] ?? 'N/A');
            }
            
            // Print pagination info
            printInfo("Current Page", $transactions->getCurrentPage());
            printInfo("Total Records", $transactions->getTotalRecords());
        } else {
            printInfo("Total Found", count($transData));
        }
    } else {
        $transData = $transactions->getData();
        printInfo("Total Found", count($transData));
        if (!empty($transData)) {
            printInfo("First Transaction", $transData[0]['reference'] ?? 'N/A');
            printInfo("Status", $transData[0]['status'] ?? 'N/A');
        }
    }
    
    $testsPassed++;
} catch (Exception $e) {
    printError($e->getMessage());
    $testsFailed++;
}

// ====================================
// TEST 9: Get Transactions by Merchant Ref
// ====================================
printTest(9, "Get Transactions by Merchant Ref");
try {
    $transactions = $tripay->getTransactions([
        'merchant_ref' => $merchantRef,
    ]);
    
    printSuccess("Got filtered transactions");
    
    if (method_exists($transactions, 'getTransactions')) {
        $transData = $transactions->getTransactions();
        if (is_object($transData) && method_exists($transData, 'count')) {
            printInfo("Total Found", $transData->count());
            if ($transData->count() > 0) {
                $first = $transData->first();
                printInfo("Merchant Ref", $first['merchant_ref'] ?? 'N/A');
            }
        } else {
            printInfo("Total Found", count($transData));
        }
    } else {
        $transData = $transactions->getData();
        printInfo("Total Found", count($transData));
        if (!empty($transData)) {
            printInfo("Merchant Ref", $transData[0]['merchant_ref'] ?? 'N/A');
        }
    }
    
    $testsPassed++;
} catch (Exception $e) {
    printError($e->getMessage());
    $testsFailed++;
}

// ====================================
// TEST 10: Signature Generation & Validation
// ====================================
printTest(10, "Signature Generation & Validation");
try {
    $testMerchantRef = 'TEST-SIGNATURE-123';
    $testAmount = 50000;
    
    $signature = $tripay->generateSignature($testMerchantRef, $testAmount);
    
    printSuccess("Signature generated");
    printInfo("Merchant Ref", $testMerchantRef);
    printInfo("Amount", "Rp " . number_format($testAmount));
    printInfo("Signature", substr($signature, 0, 20) . '...');
    
    // Test callback validation
    $callbackData = json_encode([
        'merchant_ref' => $testMerchantRef,
        'reference' => 'T1234567890',
        'status' => 'PAID',
    ]);
    
    $callbackSignature = hash_hmac('sha256', $callbackData, $privateKey);
    $isValid = $tripay->validateCallback($callbackSignature, $callbackData);
    
    printInfo("Callback Validation", $isValid ? 'Valid ✅' : 'Invalid ❌');
    
    $testsPassed++;
} catch (Exception $e) {
    printError($e->getMessage());
    $testsFailed++;
}

// ====================================
// SUMMARY
// ====================================
printHeader("TEST SUMMARY");

$totalTests = $testsPassed + $testsFailed;
$successRate = $totalTests > 0 ? round(($testsPassed / $totalTests) * 100, 2) : 0;

echo "Total Tests: " . BLUE . $totalTests . RESET . "\n";
echo "Passed: " . GREEN . $testsPassed . " ✅" . RESET . "\n";
echo "Failed: " . RED . $testsFailed . " ❌" . RESET . "\n";
echo "Success Rate: " . ($successRate >= 80 ? GREEN : RED) . $successRate . "%" . RESET . "\n\n";

if ($testsPassed === $totalTests) {
    echo GREEN . "🎉 ALL TESTS PASSED! SDK is working perfectly!" . RESET . "\n\n";
} else {
    echo YELLOW . "⚠️  Some tests failed. Please check the errors above." . RESET . "\n\n";
}

exit($testsFailed > 0 ? 1 : 0);