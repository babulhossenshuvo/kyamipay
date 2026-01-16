# Laravel KPay Payment Package

A complete Laravel package for integrating Kyami Pay payment gateway into your Laravel application.

## Features

- ✅ Generate payment references
- ✅ Check payment status
- ✅ Cancel payment references
- ✅ List paid references
- ✅ Payment simulation (sandbox)
- ✅ Webhook support
- ✅ Database transaction tracking
- ✅ Easy configuration
- ✅ Facade support
- ✅ Event broadcasting

## Installation

### Step 1: Install via Composer

```bash
composer require kpay/laravel-kpay-payment
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --provider="KPay\LaravelKPayment\KPayServiceProvider" --tag=kpay-config
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

Or publish migrations first:

```bash
php artisan vendor:publish --provider="KPay\LaravelKPayment\KPayServiceProvider" --tag=kpay-migrations
php artisan migrate
```

## Configuration

Add the following environment variables to your `.env` file:

```env
KPAY_BASE_URL=https://kyamiprint.kp
KPAY_SANDBOX_URL=https://private-f32974-kyamirefapiv2.apiary-mock.com
KPAY_SANDBOX_MODE=true
KPAY_ENTITY=0000
KPAY_TOKEN=your_api_token_here
KPAY_HASH=your_hash_here
KPAY_FACTORY_BAG=Content
KPAY_CURRENCY=AOA
KPAY_REFERENCE_EXPIRY_HOURS=24
KPAY_TIMEOUT=30
KPAY_LOG_REQUESTS=false

# Webhook Configuration
KPAY_WEBHOOK_ENABLED=true
KPAY_WEBHOOK_URL=/api/kpay/webhook
KPAY_WEBHOOK_SECRET=your_webhook_secret
```

## Usage

### Using the Facade

```php
use KPay\LaravelKPayment\Facades\KPay;

// Generate a payment reference
$reference = KPay::generateReference(
    price: '100.00',
    description: 'Product Payment',
    expiry: '2024-01-31 23:59:59'
);

// Check if a reference has been paid
$payment = KPay::checkPayment('000000458712369');

// Cancel a reference
KPay::cancelReference('000000458712369');

// List all paid references
$paidReferences = KPay::listPaidReferences();

// Simulate a payment (sandbox only)
KPay::simulatePayment('000000458712369', '100.00');
```

### Using Dependency Injection

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

### Using the API Endpoint

The package provides a ready-to-use API controller. Add this route to your `routes/api.php`:

```php
use KPay\LaravelKPayment\Controllers\KPayWebhookController;

Route::prefix('kpay')->group(function () {
    Route::post('/generate', [KPayWebhookController::class, 'generateReference']);
    Route::post('/check/{reference}', [KPayWebhookController::class, 'checkPayment']);
    Route::post('/cancel', [KPayWebhookController::class, 'cancelReference']);
    Route::post('/webhook', [KPayWebhookController::class, 'handle']);
});
```

### Adding Payment Functionality to Your Model

Use the `HasPayments` trait in your User model:

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

Then you can access payment data:

```php
$user = User::find(1);

// Get all transactions
$transactions = $user->kpayTransactions;

// Get pending payments
$pending = $user->pendingPayments;

// Get paid payments
$paid = $user->paidPayments;
```

## API Endpoints

### Generate Reference

**POST** `/api/kpay/generate`

```json
{
    "amount": "100.00",
    "description": "Order Payment",
    "expiry": "2024-01-31 23:59:59",
    "user_id": "123",
    "order_id": "ORD-001",
    "metadata": {
        "custom_field": "value"
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "Reference generated successfully",
    "data": {
        "reference": "000000458712",
        "entity": "0000",
        "price": "100.00",
        "description": "Order Payment",
        "status": 200,
        "expiry": "2024-01-31 23:59:59"
    },
    "transaction_id": 1
}
```

### Check Payment Status

**GET** `/api/kpay/check/{reference}`

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "reference": "000000458712",
        "entity": "0000",
        "amount": "100.00",
        "status": "paid",
        "paid_at": "2024-01-30 15:30:00",
        "metadata": {...}
    }
}
```

### Cancel Reference

**POST** `/api/kpay/cancel`

```json
{
    "reference": "000000458712"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Reference cancelled successfully"
}
```

### Webhook

**POST** `/api/kpay/webhook`

Kyami Pay will send payment confirmations to this endpoint. The package automatically:
- Verifies the payload
- Updates transaction status
- Triggers the `kpay.payment.confirmed` event

## Database Schema

The package creates a `kpay_transactions` table with the following fields:

| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| reference | string | Unique payment reference |
| entity | string | Entity code |
| amount | decimal | Payment amount |
| price | decimal | Reference price |
| description | text | Payment description |
| status | enum | Payment status (pending, paid, cancelled, failed) |
| currency | string | Currency code |
| expires_at | timestamp | Reference expiry time |
| paid_at | timestamp | Payment confirmation time |
| metadata | json | Custom metadata |
| api_response | json | Raw API response |
| user_id | string | Associated user ID |
| order_id | string | Associated order ID |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Update time |

## Events

The package fires the following event when a payment is confirmed:

```php
// Listen for payment confirmation
Event::listen('kpay.payment.confirmed', function ($transaction) {
    // Handle confirmed payment
    Log::info('Payment confirmed: ' . $transaction->reference);
});
```

## Error Handling

The package provides custom exceptions:

```php
use KPay\LaravelKPayment\Exceptions\PaymentException;
use KPay\LaravelKPayment\Exceptions\AuthenticationException;
use KPay\LaravelKPayment\Exceptions\KPayException;

try {
    $reference = KPay::generateReference('100.00');
} catch (AuthenticationException $e) {
    // Handle authentication error
    Log::error('Authentication failed: ' . $e->getMessage());
} catch (PaymentException $e) {
    // Handle payment-specific error
    Log::error('Payment error: ' . $e->getMessage());
} catch (KPayException $e) {
    // Handle general KPay error
    Log::error('KPay error: ' . $e->getMessage());
}
```

## Testing

```bash
vendor/bin/phpunit tests/
```

## Security

- All API requests are made over TLS
- Authentication headers are validated
- Webhook payloads are verified
- Sensitive data is logged only in debug mode

## Support

For issues, questions, or suggestions, please contact Kyami Pay support at support@kyamipay.com

## License

This package is licensed under the MIT License.
