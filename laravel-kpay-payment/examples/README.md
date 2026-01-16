# Example Usage

This directory contains example implementations for common payment scenarios.

## Files

- **KPayExampleController.php** - Complete controller with payment operations
- **HandlePaymentConfirmedListener.php** - Event listener example
- **routes.example.php** - Route configuration example
- **.env.example** - Environment configuration example

## Quick Start

### 1. Copy Files to Your Project

```bash
# Copy controller
cp examples/KPayExampleController.php app/Http/Controllers/

# Copy listener
cp examples/HandlePaymentConfirmedListener.php app/Listeners/

# Copy environment template
cp examples/.env.example .env
```

### 2. Register Routes

Add to `routes/api.php`:

```php
use App\Http\Controllers\KPayExampleController;

Route::post('/payments/create', [KPayExampleController::class, 'createPayment']);
Route::get('/payments/check/{reference}', [KPayExampleController::class, 'checkStatus']);
Route::post('/payments/cancel', [KPayExampleController::class, 'cancelPayment']);
Route::get('/payments/user', [KPayExampleController::class, 'getUserPayments'])->middleware('auth');
```

### 3. Register Event Listener

Add to `app/Providers/EventServiceProvider.php`:

```php
use KPay\LaravelKPayment\Events\PaymentConfirmed;
use App\Listeners\HandlePaymentConfirmed;

protected $listen = [
    PaymentConfirmed::class => [
        HandlePaymentConfirmed::class,
    ],
];
```

### 4. Configure Environment

Update your `.env` with KPay credentials:

```env
KPAY_SANDBOX_MODE=true
KPAY_ENTITY=0000
KPAY_TOKEN=your_token
KPAY_HASH=your_hash
```

### 5. Test

```bash
# Test configuration
php artisan kpay:test

# Make a payment request
curl -X POST http://localhost:8000/api/payments/create \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "100.00",
    "description": "Test payment",
    "order_id": "1"
  }'
```

## Common Scenarios

### Create a Payment

```php
// POST /api/payments/create
{
    "amount": "99.99",
    "description": "Product purchase",
    "order_id": "ORD-12345"
}

// Response
{
    "success": true,
    "reference": "000000458712369",
    "amount": "99.99",
    "expires_at": "2024-01-17 10:30:00"
}
```

### Check Payment Status

```php
// GET /api/payments/check/000000458712369

// Response
{
    "success": true,
    "reference": "000000458712369",
    "status": "paid",
    "amount": "99.99",
    "paid_at": "2024-01-16 15:30:00"
}
```

### Cancel Payment

```php
// POST /api/payments/cancel
{
    "reference": "000000458712369"
}

// Response
{
    "success": true,
    "message": "Payment cancelled successfully"
}
```

### Get User Payments

```php
// GET /api/payments/user
// Authorization: Bearer <token>

// Response
{
    "success": true,
    "total": 5,
    "payments": [
        {
            "id": 1,
            "reference": "000000458712369",
            "amount": "99.99",
            "status": "paid",
            "created_at": "2024-01-16 10:00:00",
            "paid_at": "2024-01-16 15:30:00"
        }
    ]
}
```

## Database Models

### Create Order Model

```php
php artisan make:model Order -m
```

Add to migration:

```php
$table->id();
$table->string('payment_reference')->nullable();
$table->enum('status', ['pending_payment', 'paid', 'payment_cancelled'])->default('pending_payment');
$table->decimal('amount', 15, 2);
$table->timestamp('payment_date')->nullable();
$table->timestamps();
```

## Event Handling

The package dispatches `PaymentConfirmed` event when payment is received:

```php
// In EventServiceProvider
protected $listen = [
    PaymentConfirmed::class => [
        HandlePaymentConfirmed::class,  // Update order status
        SendPaymentConfirmationEmail::class,  // Send email
        NotifyUser::class,  // Send notification
    ],
];
```

## Testing with Sandbox

Use the simulation endpoint in development:

```php
POST /api/payments/test-webhook

// Automatically creates a test payment and simulates payment
// Verifies webhook and event handling
```

## Production Checklist

- [ ] Set `KPAY_SANDBOX_MODE=false`
- [ ] Configure production API credentials
- [ ] Set webhook secret: `KPAY_WEBHOOK_SECRET`
- [ ] Enable request logging: `KPAY_LOG_REQUESTS=true`
- [ ] Configure email notifications
- [ ] Set up monitoring/alerts
- [ ] Test webhook deliveries
- [ ] Document payment flow for support team
- [ ] Set up database backups
- [ ] Enable SSL/HTTPS

## Support

For issues or questions:
1. Check the [README](../README.md)
2. Review [INSTALLATION.md](../INSTALLATION.md)
3. See [API_REFERENCE.md](../API_REFERENCE.md)
4. Contact Kyami Pay support
