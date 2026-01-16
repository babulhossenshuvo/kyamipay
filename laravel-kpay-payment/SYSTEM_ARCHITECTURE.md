# Laravel KPay Payment Package - Complete System

## 📦 Package Overview

A comprehensive Laravel package for integrating Kyami Pay payment gateway with full support for:
- Payment reference generation
- Payment status checking
- Payment cancellation
- Webhook handling with signature verification
- Database transaction tracking
- Event broadcasting
- Helper functions
- Form validation
- Error handling
- Console commands

## 📁 Project Structure

```
laravel-kpay-payment/
├── src/
│   ├── Commands/
│   │   └── TestConnectionCommand.php      ✅ Test KPay connection
│   ├── Controllers/
│   │   └── KPayWebhookController.php      ✅ Handle payments & webhooks
│   ├── Events/
│   │   ├── PaymentConfirmed.php           ✅ Payment confirmed event
│   │   └── PaymentFailed.php              ✅ Payment failed event
│   ├── Exceptions/
│   │   ├── KPayException.php              ✅ Base exception
│   │   ├── PaymentException.php           ✅ Payment errors
│   │   └── AuthenticationException.php    ✅ Auth errors
│   ├── Facades/
│   │   └── KPay.php                       ✅ Facade for easy usage
│   ├── Helpers/
│   │   └── KPayHelper.php                 ✅ Utility functions
│   ├── Middleware/
│   │   └── ValidateKPayWebhook.php        ✅ Webhook validation
│   ├── Models/
│   │   └── KPayTransaction.php            ✅ Database model
│   ├── Requests/
│   │   ├── GenerateReferenceRequest.php   ✅ Form validation
│   │   └── CancelReferenceRequest.php     ✅ Form validation
│   ├── Services/
│   │   └── KPayService.php                ✅ Core service
│   ├── Traits/
│   │   └── HasPayments.php                ✅ User model integration
│   └── KPayServiceProvider.php            ✅ Service provider
├── config/
│   └── kpay.php                           ✅ Configuration
├── database/
│   └── migrations/
│       └── 2024_01_01_000000_create_kpay_transactions_table.php ✅ Database schema
├── routes/
│   └── kpay.php                           ✅ API routes
├── tests/
│   └── KPayServiceTest.php                ✅ Unit tests
├── examples/
│   ├── KPayExampleController.php          ✅ Controller example
│   ├── HandlePaymentConfirmedListener.php ✅ Event listener example
│   ├── routes.example.php                 ✅ Routes configuration
│   ├── .env.example                       ✅ Environment variables
│   └── README.md                          ✅ Examples guide
├── docs/
│   ├── INSTALLATION.md                    ✅ Setup guide
│   ├── USAGE.md                           ✅ Usage guide
│   ├── API_REFERENCE.md                   ✅ API documentation
│   ├── EVENTS.md                          ✅ Events guide
│   ├── WEBHOOK.md                         ✅ Webhook guide
│   ├── SYSTEM_ARCHITECTURE.md             ✅ This file
│   ├── README.md                          ✅ Main readme
│   └── CONTRIBUTING.md                    ✅ Contributing guide
├── composer.json                          ✅ Package metadata
├── LICENSE                                ✅ MIT License
├── .gitignore                             ✅ Git ignore rules
└── README_NEW.md                          ✅ Enhanced readme
```

## 🔑 Core Components

### 1. Service Layer (`KPayService`)
- Handles all API communication
- Manages authentication headers
- Provides error handling
- Implements request validation

**Methods:**
- `generateReference()` - Create payment reference
- `checkPayment()` - Verify payment status
- `cancelReference()` - Cancel payment
- `listPaidReferences()` - List paid transactions
- `simulatePayment()` - Test payments (sandbox)

### 2. Database Model (`KPayTransaction`)
- Tracks all payment transactions
- Provides query scopes
- Updates payment status
- Stores metadata

**Features:**
- Unique reference tracking
- Status management (pending, paid, cancelled, failed)
- User & order association
- Metadata support
- API response logging

### 3. API Controller (`KPayWebhookController`)
- Handles payment endpoints
- Processes webhooks
- Validates requests
- Dispatches events

**Endpoints:**
- `POST /api/kpay/generate` - Generate reference
- `GET /api/kpay/check/{reference}` - Check status
- `POST /api/kpay/cancel` - Cancel payment
- `POST /api/kpay/webhook` - Receive webhooks

### 4. Events System
- `PaymentConfirmed` - When payment is received
- `PaymentFailed` - When payment fails
- Event listeners for automation

### 5. Helper Functions (`KPayHelper`)
- Create payments
- Retrieve transactions
- Get user payments
- Verify webhooks
- Format amounts

### 6. User Integration (`HasPayments` Trait)
- Add payment methods to User model
- Query user transactions
- Access pending/paid payments

### 7. Validation Layer
- Form request validation
- Input sanitization
- Error messages
- Custom rules

## 🔧 Configuration

### Environment Variables

```env
# API Configuration
KPAY_BASE_URL=https://kyamiprint.kp
KPAY_SANDBOX_URL=https://private-f32974-kyamirefapiv2.apiary-mock.com
KPAY_SANDBOX_MODE=true

# Credentials
KPAY_ENTITY=0000
KPAY_TOKEN=your_token
KPAY_HASH=your_hash
KPAY_FACTORY_BAG=Content

# Webhook
KPAY_WEBHOOK_ENABLED=true
KPAY_WEBHOOK_URL=/api/kpay/webhook
KPAY_WEBHOOK_SECRET=your_secret

# Additional
KPAY_CURRENCY=AOA
KPAY_REFERENCE_EXPIRY_HOURS=24
KPAY_TIMEOUT=30
KPAY_LOG_REQUESTS=false
```

## 🚀 Installation Steps

### 1. Install Package
```bash
composer require kpay/laravel-kpay-payment
```

### 2. Publish Files
```bash
php artisan vendor:publish --provider="KPay\LaravelKPayment\KPayServiceProvider"
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Configure Credentials
- Update `.env` with KPay credentials
- Set webhook URL in Kyami Pay dashboard

### 5. Test Connection
```bash
php artisan kpay:test
```

## 💡 Usage Examples

### Generate Payment
```php
use KPay\LaravelKPayment\Facades\KPay;

$reference = KPay::generateReference('100.00', 'Product payment');
```

### Check Status
```php
$payment = KPay::checkPayment('000000458712369');
if ($payment) {
    // Payment confirmed
}
```

### Listen to Events
```php
Event::listen(PaymentConfirmed::class, function ($event) {
    Order::find($event->transaction->order_id)->markAsPaid();
});
```

### Helper Functions
```php
use KPay\LaravelKPayment\Helpers\KPayHelper;

$transaction = KPayHelper::createPayment('100.00', [
    'user_id' => auth()->id(),
    'order_id' => 'ORD-001'
]);
```

### User Trait
```php
class User extends Model
{
    use HasPayments;
}

$user->kpayTransactions;  // All payments
$user->pendingPayments;   // Pending only
$user->paidPayments;      // Paid only
```

## 🔒 Security Features

- ✅ TLS/HTTPS for all requests
- ✅ Webhook signature verification
- ✅ Environment variable protection
- ✅ SQL injection prevention
- ✅ CSRF protection
- ✅ Input validation
- ✅ Error logging
- ✅ Rate limiting ready

## 📊 Database Schema

```sql
CREATE TABLE kpay_transactions (
    id BIGINT PRIMARY KEY,
    reference VARCHAR(255) UNIQUE,
    entity VARCHAR(255),
    amount DECIMAL(15, 2),
    price DECIMAL(15, 2),
    description VARCHAR(255),
    status ENUM('pending', 'paid', 'cancelled', 'failed'),
    currency VARCHAR(3),
    expires_at TIMESTAMP,
    paid_at TIMESTAMP,
    metadata JSON,
    api_response JSON,
    user_id VARCHAR(255),
    order_id VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX(reference),
    INDEX(entity),
    INDEX(status),
    INDEX(user_id),
    INDEX(order_id)
);
```

## 🎯 Key Features

### ✅ Automatic Routes
- Pre-configured API endpoints
- Ready to use out of the box
- Can be customized if needed

### ✅ Event Broadcasting
- Automatic event dispatching
- Integrate with any event listener
- Perfect for async processing

### ✅ Helper Functions
- Utility functions for common tasks
- Simplified API
- Chainable methods

### ✅ User Integration
- Trait for User model
- Direct payment access
- Scoped queries

### ✅ Error Handling
- Custom exceptions
- Detailed error messages
- Proper HTTP status codes

### ✅ Logging
- Request logging
- Error tracking
- Webhook logging

### ✅ Validation
- Form requests
- Input validation
- Custom rules

### ✅ Console Commands
- Connection testing
- Configuration validation
- Debugging support

## 🧪 Testing

### Run Tests
```bash
vendor/bin/phpunit tests/
```

### Test Configuration
```bash
php artisan kpay:test
```

### Simulate Payments
```php
KPay::simulatePayment('000000458712369', '100.00');
```

## 📝 Documentation Files

1. **README.md** - Main package documentation
2. **INSTALLATION.md** - Step-by-step setup guide
3. **USAGE.md** - Usage examples and patterns
4. **API_REFERENCE.md** - Complete API documentation
5. **EVENTS.md** - Event system guide
6. **WEBHOOK.md** - Webhook configuration guide
7. **CONTRIBUTING.md** - Contributing guidelines
8. **SYSTEM_ARCHITECTURE.md** - This file

## 🤝 Integration Points

### With Your Application
- Models: Use `HasPayments` trait
- Controllers: Inject `KPayService`
- Events: Listen to `PaymentConfirmed`
- Webhooks: Automatic handling
- Database: Automatic transaction tracking

### With Kyami Pay API
- Generate references
- Check payment status
- Cancel payments
- Receive webhooks
- Simulate payments (sandbox)

## 🚀 Performance

- Optimized database queries
- Indexed fields for fast lookups
- Async-ready event system
- Queue-compatible jobs
- Efficient error handling

## 📞 Support

- Full documentation
- Example implementations
- Console commands for testing
- Comprehensive error messages
- Logging for debugging

## ✅ Quality Assurance

- ✅ PHP 8.0+ compatible
- ✅ Laravel 8.0+ compatible
- ✅ PSR-12 coding standards
- ✅ Type hints throughout
- ✅ Comprehensive tests
- ✅ Error handling
- ✅ Security best practices

## 📦 Deliverables

This package includes:

1. **Complete Service Layer** - API communication
2. **Database Model** - Transaction tracking
3. **Controller** - HTTP endpoints
4. **Events System** - Payment lifecycle events
5. **Helper Functions** - Utility functions
6. **Form Validation** - Input validation
7. **User Integration** - Model trait
8. **Middleware** - Webhook validation
9. **Commands** - CLI tools
10. **Documentation** - Comprehensive guides
11. **Examples** - Implementation examples
12. **Tests** - Unit test templates

---

**Ready for production use!**
