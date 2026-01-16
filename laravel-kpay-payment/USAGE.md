# Basic Usage Guide

## Using the Facade

The simplest way to use KPay is with the facade:

```php
use KPay\LaravelKPayment\Facades\KPay;

// Generate a payment reference
$reference = KPay::generateReference(
    price: '100.00',
    description: 'Order #001',
    expiry: '2024-12-31 23:59:59'
);

// Result:
// [
//     'success' => true,
//     'reference' => '000000458712369',
//     'entity' => '0000',
//     'price' => '100.00',
//     'description' => 'Order #001',
//     'status' => 200,
//     'expiry' => '2024-12-31 23:59:59'
// ]
```

## Using Dependency Injection

```php
<?php

namespace App\Http\Controllers;

use KPay\LaravelKPayment\Services\KPayService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function createPayment(Request $request, KPayService $kpay)
    {
        $reference = $kpay->generateReference(
            price: $request->input('amount'),
            description: $request->input('description')
        );

        return response()->json($reference);
    }
}
```

## API Endpoints

### Generate Payment Reference

**Endpoint:** `POST /api/kpay/generate`

**Request:**
```json
{
    "amount": "100.00",
    "description": "Product payment",
    "expiry": "2024-12-31 23:59:59",
    "user_id": "123",
    "order_id": "ORD-001",
    "metadata": {
        "product_id": "456"
    }
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Reference generated successfully",
    "data": {
        "success": true,
        "reference": "000000458712369",
        "entity": "0000",
        "price": "100.00",
        "description": "Product payment",
        "status": 200,
        "expiry": "2024-12-31 23:59:59"
    },
    "transaction_id": 1
}
```

### Check Payment Status

**Endpoint:** `GET /api/kpay/check/{reference}`

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "reference": "000000458712369",
        "entity": "0000",
        "amount": "100.00",
        "price": "100.00",
        "description": "Product payment",
        "status": "paid",
        "currency": "AOA",
        "expires_at": "2024-12-31 23:59:59",
        "paid_at": "2024-01-15 10:30:00",
        "metadata": {...},
        "user_id": "123",
        "order_id": "ORD-001",
        "created_at": "2024-01-15 10:00:00",
        "updated_at": "2024-01-15 10:30:00"
    }
}
```

### Cancel Payment Reference

**Endpoint:** `POST /api/kpay/cancel`

**Request:**
```json
{
    "reference": "000000458712369"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Reference cancelled successfully"
}
```

## Using Helper Functions

```php
use KPay\LaravelKPayment\Helpers\KPayHelper;

// Create a payment
$transaction = KPayHelper::createPayment('100.00', [
    'description' => 'Product payment',
    'user_id' => '123',
    'order_id' => 'ORD-001',
    'metadata' => ['product_id' => '456']
]);

// Get transaction by reference
$transaction = KPayHelper::getByReference('000000458712369');

// Get user transactions
$transactions = KPayHelper::getUserTransactions('123');

// Get pending transactions
$pending = KPayHelper::getPendingTransactions();

// Get paid transactions
$paid = KPayHelper::getPaidTransactions();

// Format amount for API
$formatted = KPayHelper::formatAmount(100.50); // "100.50"

// Verify webhook signature
$verified = KPayHelper::verifyWebhookSignature($payload, $signature);
```

## Working with Models

### Add HasPayments Trait to User Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use KPay\LaravelKPayment\Traits\HasPayments;

class User extends Model
{
    use HasPayments;

    // ...
}
```

### Access Payment Data

```php
$user = User::find(1);

// Get all transactions
$transactions = $user->kpayTransactions;

// Get pending payments
$pending = $user->pendingPayments;

// Get paid payments
$paid = $user->paidPayments;

// Check pending payments count
if ($user->pendingPayments()->count() > 0) {
    // User has pending payments
}
```

## Transaction Model Methods

```php
use KPay\LaravelKPayment\Models\KPayTransaction;

$transaction = KPayTransaction::find(1);

// Scopes
KPayTransaction::pending()->get();      // Get all pending
KPayTransaction::paid()->get();         // Get all paid
KPayTransaction::byReference('ref')->first();
KPayTransaction::byUser('user-id')->get();
KPayTransaction::byOrder('order-id')->first();

// Check status
$transaction->isPaid();      // boolean
$transaction->isPending();   // boolean

// Update status
$transaction->markAsPaid($apiResponse);
$transaction->markAsCancelled();
$transaction->markAsFailed();

// Access data
$transaction->reference;
$transaction->amount;
$transaction->status;
$transaction->metadata;
$transaction->user_id;
$transaction->order_id;
```

## Error Handling

```php
use KPay\LaravelKPayment\Facades\KPay;
use KPay\LaravelKPayment\Exceptions\PaymentException;
use KPay\LaravelKPayment\Exceptions\AuthenticationException;

try {
    $reference = KPay::generateReference('100.00');
} catch (AuthenticationException $e) {
    // Handle authentication error
    Log::error('Auth failed: ' . $e->getMessage());
    return response()->json(['error' => 'Authentication failed'], 401);
} catch (PaymentException $e) {
    // Handle payment-specific error
    Log::error('Payment failed: ' . $e->getMessage());
    return response()->json(['error' => 'Payment failed'], 400);
} catch (\Exception $e) {
    // Handle unexpected errors
    Log::error('Unexpected error: ' . $e->getMessage());
    return response()->json(['error' => 'Server error'], 500);
}
```

## Complete Example

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use KPay\LaravelKPayment\Facades\KPay;
use KPay\LaravelKPayment\Helpers\KPayHelper;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:30',
        ]);

        try {
            // Generate payment reference
            $response = KPay::generateReference(
                price: $validated['amount'],
                description: $validated['description'],
                expiry: now()->addHours(24)->format('Y-m-d H:i:s')
            );

            // Store in database
            $transaction = KPayHelper::createPayment(
                amount: $validated['amount'],
                options: [
                    'description' => $validated['description'],
                    'user_id' => auth()->id(),
                    'order_id' => 'ORD-' . uniqid(),
                ]
            );

            return response()->json([
                'success' => true,
                'reference' => $response['reference'],
                'amount' => $response['price'],
                'description' => $response['description'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function checkStatus($reference)
    {
        try {
            $transaction = KPayHelper::getByReference($reference);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'paid_at' => $transaction->paid_at,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

## Next Steps

- [Events & Listeners](EVENTS.md)
- [Webhook Configuration](WEBHOOK.md)
- [Advanced Usage](ADVANCED.md)
