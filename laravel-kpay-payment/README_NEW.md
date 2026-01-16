# Laravel KPay Payment Package

A complete, production-ready Laravel package for integrating **Kyami Pay** payment gateway into any Laravel application.

## 🚀 Features

- ✅ Generate payment references
- ✅ Check payment status
- ✅ Cancel payment references
- ✅ List paid references
- ✅ Payment simulation (sandbox)
- ✅ Webhook support with signature verification
- ✅ Database transaction tracking
- ✅ Event broadcasting for payment lifecycle
- ✅ Helper functions and utilities
- ✅ Facade support
- ✅ Form request validation
- ✅ Comprehensive error handling
- ✅ Built-in logging and debugging
- ✅ Ready-to-use API endpoints
- ✅ User model trait for payments
- ✅ Console commands for testing

## 📋 Requirements

- PHP 8.0+
- Laravel 8.0+
- Composer
- Kyami Pay API credentials

## 📦 Installation

### 1. Install via Composer

```bash
composer require kpay/laravel-kpay-payment
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="KPay\LaravelKPayment\KPayServiceProvider" --tag=kpay-config
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Configure Environment

Add to `.env`:

```env
KPAY_SANDBOX_MODE=true
KPAY_ENTITY=0000
KPAY_TOKEN=your_api_token
KPAY_HASH=your_hash
KPAY_WEBHOOK_SECRET=your_webhook_secret
```

### 5. Test Configuration

```bash
php artisan kpay:test
```

## 🎯 Quick Start

### Using the Facade

```php
use KPay\LaravelKPayment\Facades\KPay;

// Generate a payment reference
$reference = KPay::generateReference(
    price: '100.00',
    description: 'Product Payment'
);

// Check payment status
$payment = KPay::checkPayment('000000458712369');

// Cancel a reference
KPay::cancelReference('000000458712369');
```

### Using Dependency Injection

```php
use KPay\LaravelKPayment\Services\KPayService;

class PaymentController extends Controller
{
    public function pay(KPayService $kpay)
    {
        $reference = $kpay->generateReference('100.00');
        return response()->json($reference);
    }
}
```

### Using Helper Functions

```php
use KPay\LaravelKPayment\Helpers\KPayHelper;

// Create payment
$transaction = KPayHelper::createPayment('100.00', [
    'user_id' => auth()->id(),
    'order_id' => 'ORD-001'
]);

// Get user transactions
$payments = KPayHelper::getUserTransactions('user-123');
```

### Using Your User Model

```php
use KPay\LaravelKPayment\Traits\HasPayments;

class User extends Model
{
    use HasPayments;
}

// Access payments
$user = User::find(1);
$user->kpayTransactions;  // All transactions
$user->pendingPayments;   // Pending payments
$user->paidPayments;      // Paid payments
```

## 🔌 API Endpoints

The package provides automatic routes:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/kpay/generate` | Generate payment reference |
| GET | `/api/kpay/check/{reference}` | Check payment status |
| POST | `/api/kpay/cancel` | Cancel payment reference |
| POST | `/api/kpay/webhook` | Receive webhooks |

### Generate Payment

```bash
curl -X POST http://localhost:8000/api/kpay/generate \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "100.00",
    "description": "Order payment",
    "user_id": "123",
    "order_id": "ORD-001"
  }'
```

### Check Status

```bash
curl http://localhost:8000/api/kpay/check/000000458712369
```

### Cancel Payment

```bash
curl -X POST http://localhost:8000/api/kpay/cancel \
  -H "Content-Type: application/json" \
  -d '{"reference": "000000458712369"}'
```

## 🎧 Events

The package dispatches events for payment lifecycle:

```php
use KPay\LaravelKPayment\Events\PaymentConfirmed;

// Listen for payment confirmation
Event::listen(PaymentConfirmed::class, function ($event) {
    Log::info('Payment confirmed: ' . $event->transaction->reference);
    
    // Update order
    Order::find($event->transaction->order_id)?->markAsPaid();
});
```

## 🔒 Webhook Configuration

Kyami Pay will POST to your webhook URL:

```
https://yourapp.com/api/kpay/webhook
```

The package automatically:
- Verifies webhook signatures
- Updates transaction status
- Dispatches `PaymentConfirmed` event
- Logs all webhooks

## 📊 Database

The package creates a `kpay_transactions` table with:

| Column | Type | Description |
|--------|------|-------------|
| reference | string | Unique payment reference |
| amount | decimal | Payment amount |
| status | enum | pending, paid, cancelled, failed |
| currency | string | Currency code |
| user_id | string | Associated user |
| order_id | string | Associated order |
| metadata | json | Custom data |
| paid_at | timestamp | Payment confirmation time |

## 🛠 Configuration

See [INSTALLATION.md](INSTALLATION.md) for complete configuration details.

## 📖 Documentation

- [Installation Guide](INSTALLATION.md) - Setup and configuration
- [Usage Guide](USAGE.md) - Basic and advanced usage
- [API Reference](API_REFERENCE.md) - Complete API documentation
- [Events & Listeners](EVENTS.md) - Event-driven programming
- [Webhook Configuration](WEBHOOK.md) - Webhook setup and testing
- [Examples](examples/) - Complete implementation examples

## 🧪 Testing

### Run Tests

```bash
vendor/bin/phpunit tests/
```

### Test Connection

```bash
php artisan kpay:test
```

### Simulate Payment (Sandbox)

```php
KPay::simulatePayment('000000458712369', '100.00');
```

## 🚨 Error Handling

```php
use KPay\LaravelKPayment\Exceptions\PaymentException;
use KPay\LaravelKPayment\Exceptions\AuthenticationException;

try {
    $reference = KPay::generateReference('100.00');
} catch (AuthenticationException $e) {
    // Handle auth error
} catch (PaymentException $e) {
    // Handle payment error
} catch (\Exception $e) {
    // Handle other errors
}
```

## 📝 Common Use Cases

### E-Commerce Checkout

```php
// Generate payment for order
$reference = KPay::generateReference(
    price: $order->total,
    description: 'Order #' . $order->id,
    expiry: now()->addHours(24)->format('Y-m-d H:i:s')
);

// Store reference
$order->update(['payment_reference' => $reference['reference']]);

// Return to frontend
return response()->json(['reference' => $reference['reference']]);
```

### Event-Driven Updates

```php
// When webhook is received, automatically:
// 1. Confirm payment
// 2. Update order status
// 3. Send confirmation email
// 4. Trigger fulfillment

Event::listen(PaymentConfirmed::class, function ($event) {
    Order::find($event->transaction->order_id)
        ->markAsPaid()
        ->dispatch();
});
```

### User Payment History

```php
class User extends Model
{
    use HasPayments;
}

// Get all user payments
$user->kpayTransactions;

// Get pending payments
$user->pendingPayments()->get();

// Get paid payments
$user->paidPayments()->get();
```

## 🔐 Security

- ✅ All API requests use TLS/HTTPS
- ✅ Webhook signatures verified
- ✅ Sensitive data stored in `.env`
- ✅ SQL injection prevention
- ✅ CSRF protection
- ✅ Rate limiting ready
- ✅ Error logging for debugging

## 📋 Production Checklist

- [ ] Set `KPAY_SANDBOX_MODE=false`
- [ ] Configure production credentials
- [ ] Set webhook secret
- [ ] Enable logging
- [ ] Configure email notifications
- [ ] Set up monitoring/alerts
- [ ] Test webhook deliveries
- [ ] Document payment flow
- [ ] Set up database backups
- [ ] Enable HTTPS/SSL

## 🆘 Troubleshooting

### "Configuration incomplete" Error

Ensure `KPAY_TOKEN` and `KPAY_HASH` are set in `.env`:

```bash
php artisan kpay:test
```

### Webhook Not Working

1. Verify webhook URL is publicly accessible
2. Check Kyami Pay dashboard configuration
3. Review logs: `tail -f storage/logs/laravel.log | grep kpay`

### Payment Not Updating

1. Check database migrations ran
2. Verify transaction reference exists
3. Check webhook payload validation
4. Review error logs

## 📞 Support

For issues and questions:

1. Check the [documentation](./docs)
2. Review [examples](./examples)
3. Contact Kyami Pay: support@kyamipay.com

## 📄 License

MIT License - see [LICENSE](LICENSE) file

## 🤝 Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md)

## 📌 Version History

- **1.0.0** - Initial release with full KPay integration
  - Payment reference generation
  - Webhook support
  - Database tracking
  - Event broadcasting
  - Complete API endpoints

---

**Made with ❤️ for Laravel developers**
