# Laravel KPay Payment Integration - Master Documentation

 This package provides a seamless integration with the Kyami Pay payment gateway for Laravel applications. It offers a standardized interface for generating payment references, checking payment status, handling callbacks via webhooks, and managing transactions.

## Table of Contents
1. [Features](#features)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Usage](#usage)
5. [API Reference](#api-reference)
6. [Events & Webhooks](#events--webhooks)
7. [Models & Database](#models--database)
8. [Troubleshooting](#troubleshooting)

---

## Features

- **Simple Integration:** Standardized service for Kyami Pay.
- **Reference Generation:** Easy generation of MultiCaixa payment references.
- **Status Checking:** Check payment status real-time.
- **Webhook Handling:** Automated handling of payment confirmations.
- **Transaction Management:** Built-in database logging for all transactions.
- **Sandbox Support:** Test mode for development.
- **Security:** TLS/HTTPS, Signature Verification, SQL Injection prevention.

---

## Installation

### Requirements
- PHP 8.0 or higher
- Laravel 8.0 or higher
- `guzzlehttp/guzzle`

### Steps

1.  **Install the package via Composer:**

    ```bash
    composer require kpay/laravel-kpay-payment
    ```

2.  **Publish the configuration and migrations:**

    ```bash
    php artisan vendor:publish --provider="KPay\LaravelKPayment\KPayServiceProvider"
    ```

3.  **Run Migrations:**

    ```bash
    php artisan migrate
    ```

---

## Configuration

Configure your environment variables in `.env`:

```env
# Kyami Pay Credentials
KPAY_ENTITY=0000
KPAY_TOKEN=your_token
KPAY_HASH=your_auth_hash

# API Configuration
KPAY_BASE_URL=https://kyamiprint.kp
KPAY_SANDBOX_URL=https://private-f32974-kyamirefapiv2.apiary-mock.com
KPAY_SANDBOX_MODE=true  # Set to false for production

# Webhook Configuration
KPAY_WEBHOOK_ENABLED=true
KPAY_WEBHOOK_URL=/api/kpay/webhook
KPAY_WEBHOOK_SECRET=your_webhook_secret

# Defaults
KPAY_CURRENCY=AOA
KPAY_REFERENCE_EXPIRY_HOURS=24
KPAY_TIMEOUT=30
KPAY_LOG_REQUESTS=true
```

---

## Usage

The package provides a `KPay` facade for easy access to all features.

### 1. Generating a Payment Reference

```php
use KPay\LaravelKPayment\Facades\KPay;

// Generate a reference for 1000 AOA
$response = KPay::generateReference('1000.00', 'Order #12345');

if ($response['success']) {
    $reference = $response['reference']; // e.g., 000000458712369
    $entity = $response['entity'];
    // Display to user...
}
```

### 2. Checking Payment Status

```php
$status = KPay::checkPayment('000000458712369');

if ($status) {
    // Payment confirmed
    // $status contains payment details like amount, date, etc.
} else {
    // Not paid yet
}
```

### 3. Canceling a Reference

```php
$success = KPay::cancelReference('000000458712369');
```

---

## API Reference

### KPay Facade Methods

- `generateReference(string $amount, ?string $description = null, ?string $expiry = null): array`
- `checkPayment(string $reference): ?array`
- `cancelReference(string $reference): bool`
- `listPaidReferences(): array`
- `simulatePayment(string $reference, string $amount): bool` (Sandbox only)

### HTTP Endpoints

The package automatically registers these API endpoints:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/kpay/generate` | Generate a new payment reference |
| GET | `/api/kpay/check/{reference}` | Check status of a payment |
| POST | `/api/kpay/cancel` | Cancel a payment reference |
| POST | `/api/kpay/webhook` | Receive payment notifications |

---

## Events & Webhooks

### Webhooks
Kyami Pay sends a notification to your webhook URL when a payment is successful. Enable it in your `.env` and provide the URL to Kyami Pay.

**Payload Example:**
```json
{
    "reference": "000000458712369",
    "amount": "1000.00",
    "entity": "0000",
    "data": "20220719",
    "movement_time": "121350",
    "terminal_location": "Luanda"
}
```

### Events
The package fires the `KPay\LaravelKPayment\Events\PaymentConfirmed` event when a payment is successfully processed via webhook.

**Listener Example:**
```php
// EventServiceProvider.php
protected $listen = [
    \KPay\LaravelKPayment\Events\PaymentConfirmed::class => [
        \App\Listeners\ProcessOrder::class,
    ],
];

// ProcessOrder Listener
public function handle(PaymentConfirmed $event)
{
    $transaction = $event->transaction; // KPayTransaction model
    // Update your order status, send email, etc.
}
```

---

## Models & Database

### KPayTransaction Model
Stores all payment attempts and their statuses.

**Scopes:**
- `pending()`: Get pending transactions.
- `paid()`: Get paid transactions.
- `byReference($ref)`: Find by reference.

**Attributes:**
- `reference`, `entity`, `amount`, `status` ('pending', 'paid', 'cancelled', 'failed'), `paid_at`, `meta_data`.

---

## Troubleshooting

- **Reference Not Generated:** Check your `KPAY_ENTITY` and `KPAY_TOKEN`. Ensure `KPAY_SANDBOX_MODE` is correct.
- **Webhook Not Received:** Verify that your `KPAY_WEBHOOK_URL` is publicly accessible and configured in the Kyami Pay dashboard. Check `storage/logs/laravel.log` if `KPAY_LOG_REQUESTS` is true.
- **Signature Mismatch:** Ensure `KPAY_WEBHOOK_SECRET` matches the one provided by Kyami Pay.

---

**License:** MIT
**Version:** 1.0.0
