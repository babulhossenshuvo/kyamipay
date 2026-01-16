# Installation & Setup Guide

## Prerequisites

- PHP 8.0 or higher
- Laravel 8.0 or higher
- Composer
- Kyami Pay API credentials

## Step 1: Install the Package

```bash
composer require kpay/laravel-kpay-payment
```

## Step 2: Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="KPay\LaravelKPayment\KPayServiceProvider" --tag=kpay-config
```

This creates `config/kpay.php` in your Laravel project.

## Step 3: Configure Environment Variables

Add these variables to your `.env` file:

```env
# KPay Configuration
KPAY_BASE_URL=https://kyamiprint.kp
KPAY_SANDBOX_URL=https://private-f32974-kyamirefapiv2.apiary-mock.com
KPAY_SANDBOX_MODE=true

# API Credentials
KPAY_ENTITY=0000
KPAY_TOKEN=your_api_token_here
KPAY_HASH=your_hash_here
KPAY_FACTORY_BAG=Content

# Webhook Configuration
KPAY_WEBHOOK_ENABLED=true
KPAY_WEBHOOK_URL=/api/kpay/webhook
KPAY_WEBHOOK_SECRET=your_webhook_secret

# Additional Settings
KPAY_CURRENCY=AOA
KPAY_REFERENCE_EXPIRY_HOURS=24
KPAY_TIMEOUT=30
KPAY_LOG_REQUESTS=false
```

## Step 4: Run Migrations

Publish and run migrations:

```bash
php artisan vendor:publish --provider="KPay\LaravelKPayment\KPayServiceProvider" --tag=kpay-migrations
php artisan migrate
```

This creates the `kpay_transactions` table.

## Step 5: Publish Routes (Optional)

To customize the routes, publish them:

```bash
php artisan vendor:publish --provider="KPay\LaravelKPayment\KPayServiceProvider" --tag=kpay-routes
```

Routes are automatically registered at:
- `POST /api/kpay/generate` - Generate payment reference
- `GET /api/kpay/check/{reference}` - Check payment status
- `POST /api/kpay/cancel` - Cancel payment
- `POST /api/kpay/webhook` - Webhook endpoint

## Step 6: Test Configuration

Test your KPay connection:

```bash
php artisan kpay:test
```

This will validate your configuration and display the current settings.

## Getting API Credentials

1. Contact Kyami Pay support
2. Obtain your:
   - API Token (KPAY_TOKEN)
   - Hash value (KPAY_HASH)
   - Entity code (KPAY_ENTITY)

## Configuration Details

### Environment Variables

| Variable | Description | Required | Default |
|----------|-------------|----------|---------|
| KPAY_BASE_URL | Production API URL | No | https://kyamiprint.kp |
| KPAY_SANDBOX_URL | Sandbox API URL | No | apiary-mock.com |
| KPAY_SANDBOX_MODE | Use sandbox for testing | No | true |
| KPAY_ENTITY | Your entity code | Yes | - |
| KPAY_TOKEN | API authentication token | Yes | - |
| KPAY_HASH | Request hash header | Yes | - |
| KPAY_FACTORY_BAG | Factory bag header | No | Content |
| KPAY_WEBHOOK_ENABLED | Enable webhooks | No | true |
| KPAY_WEBHOOK_URL | Webhook endpoint path | No | /api/kpay/webhook |
| KPAY_WEBHOOK_SECRET | Webhook signature secret | No | - |
| KPAY_CURRENCY | Currency code | No | AOA |
| KPAY_REFERENCE_EXPIRY_HOURS | Reference expiry in hours | No | 24 |
| KPAY_TIMEOUT | API request timeout | No | 30 |
| KPAY_LOG_REQUESTS | Log all API requests | No | false |

## Production Setup

For production, ensure:

1. Set `KPAY_SANDBOX_MODE=false`
2. Use production `KPAY_BASE_URL`
3. Configure valid production credentials
4. Set up proper webhook secret
5. Enable logging: `KPAY_LOG_REQUESTS=true`
6. Use HTTPS only
7. Keep secrets secure in `.env` file

## Database

The migration creates a `kpay_transactions` table to track all payments:

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

## Troubleshooting

### "Configuration incomplete" Error

Ensure `KPAY_TOKEN` and `KPAY_HASH` are set:

```bash
php artisan kpay:test
```

### Webhook not working

1. Verify `KPAY_WEBHOOK_URL` is publicly accessible
2. Check Kyami Pay configuration for webhook endpoint
3. Verify logs: `tail -f storage/logs/laravel.log`

### SSL Certificate Issues

For development, you can disable SSL verification in `config/kpay.php`:

```php
'verify_ssl' => env('APP_ENV') === 'production',
```

**Note:** Always enable SSL in production.

## Next Steps

1. [Basic Usage](USAGE.md)
2. [API Reference](API_REFERENCE.md)
3. [Event Handling](EVENTS.md)
4. [Testing Guide](TESTING.md)
