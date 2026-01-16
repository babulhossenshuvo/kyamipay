# API Reference

Complete API reference for the Laravel KPay Payment package.

## Service Methods

### KPayService

The main service class for interacting with Kyami Pay API.

#### generateReference()

Generate a payment reference.

```php
public function generateReference(
    string $price,
    ?string $description = null,
    ?string $expiry = null
): array
```

**Parameters:**
- `$price` (string): Payment amount
- `$description` (string, optional): Up to 30 characters
- `$expiry` (string, optional): Expiry datetime (Y-m-d H:i:s format)

**Returns:** Array with reference details

**Example:**
```php
$response = KPay::generateReference('100.00', 'Order #001');
// [
//     'success' => true,
//     'reference' => '000000458712369',
//     'entity' => '0000',
//     'price' => '100.00',
//     'description' => 'Order #001',
//     'status' => 200,
//     'expiry' => '2024-01-31 23:59:59'
// ]
```

#### checkPayment()

Check if a reference has been paid.

```php
public function checkPayment(string $reference): ?array
```

**Parameters:**
- `$reference` (string): Payment reference

**Returns:** Array with payment details or null if not paid

**Example:**
```php
$payment = KPay::checkPayment('000000458712369');
// [
//     'EntityID' => 0,
//     'ID' => 0,
//     'accounting_period' => '000',
//     'amount' => '00000000010.00',
//     'reference' => '000000458712369',
//     'data' => '20220719',
//     ...
// ]
```

#### cancelReference()

Cancel a payment reference.

```php
public function cancelReference(string $reference): bool
```

**Parameters:**
- `$reference` (string): Payment reference to cancel

**Returns:** Boolean indicating success

**Example:**
```php
$success = KPay::cancelReference('000000458712369');
// true
```

#### listPaidReferences()

List all paid references.

```php
public function listPaidReferences(): array
```

**Returns:** Array of paid references

**Example:**
```php
$paid = KPay::listPaidReferences();
// [
//     [
//         'EntityID' => 0,
//         'reference' => '000000458712369',
//         'amount' => '00000000010.00',
//         ...
//     ],
//     ...
// ]
```

#### simulatePayment()

Simulate a payment (sandbox only).

```php
public function simulatePayment(string $reference, string $amount): bool
```

**Parameters:**
- `$reference` (string): Payment reference
- `$amount` (string): Payment amount

**Returns:** Boolean indicating success

**Example:**
```php
$success = KPay::simulatePayment('000000458712369', '100.00');
// true
```

## HTTP Endpoints

### Generate Reference

```
POST /api/kpay/generate
```

**Request Body:**
```json
{
    "amount": "100.00",
    "description": "Order payment",
    "expiry": "2024-12-31 23:59:59",
    "user_id": "123",
    "order_id": "ORD-001",
    "metadata": {
        "custom_field": "value"
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
        "description": "Order payment",
        "status": 200
    },
    "transaction_id": 1
}
```

**Validation Errors (422):**
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "amount": ["The amount must be at least 0.01"],
        "description": ["The description may not be greater than 30 characters"]
    }
}
```

### Check Payment Status

```
GET /api/kpay/check/{reference}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "reference": "000000458712369",
        "amount": "100.00",
        "status": "paid",
        "user_id": "123",
        "order_id": "ORD-001",
        "paid_at": "2024-01-15 10:30:00"
    }
}
```

**Not Found (404):**
```json
{
    "success": false,
    "message": "Transaction not found"
}
```

### Cancel Reference

```
POST /api/kpay/cancel
```

**Request Body:**
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

**Validation Errors (422):**
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "reference": ["Reference must be exactly 15 characters"]
    }
}
```

### Webhook Endpoint

```
POST /api/kpay/webhook
```

**Payload from Kyami Pay:**
```json
{
    "reference": "000000458712369",
    "amount": "00000000010.00",
    "entity": "11111",
    "data": "20221030",
    "movement_time": "220652",
    "terminal_type": "M",
    "id_terminal_type": "0000000000",
    "accounting_period": "000",
    "id_local_transaction": "00000",
    "terminal_location": "Luanda"
}
```

**Response (200):**
```json
{
    "code": 200
}
```

## Model Methods

### KPayTransaction Model

#### Query Methods

```php
// Get all pending transactions
KPayTransaction::pending()->get();

// Get all paid transactions
KPayTransaction::paid()->get();

// Get by reference
KPayTransaction::byReference('000000458712369')->first();

// Get by user ID
KPayTransaction::byUser('user-123')->get();

// Get by order ID
KPayTransaction::byOrder('order-456')->first();
```

#### Instance Methods

```php
$transaction = KPayTransaction::find(1);

// Check status
$transaction->isPaid();        // boolean
$transaction->isPending();     // boolean

// Update status
$transaction->markAsPaid($apiResponse);
$transaction->markAsCancelled();
$transaction->markAsFailed();

// Access attributes
$transaction->reference;
$transaction->amount;
$transaction->status;
$transaction->currency;
$transaction->metadata;
$transaction->user_id;
$transaction->order_id;
$transaction->paid_at;
$transaction->created_at;
```

## Helper Methods

### KPayHelper

```php
use KPay\LaravelKPayment\Helpers\KPayHelper;

// Create payment
$transaction = KPayHelper::createPayment('100.00', [
    'description' => 'Product payment',
    'user_id' => '123',
    'metadata' => ['product_id' => '456']
]);

// Get by reference
$transaction = KPayHelper::getByReference('000000458712369');

// Get user transactions
$transactions = KPayHelper::getUserTransactions('user-123');

// Get pending transactions
$pending = KPayHelper::getPendingTransactions();

// Get paid transactions
$paid = KPayHelper::getPaidTransactions();

// Format amount
$formatted = KPayHelper::formatAmount(100.50); // "100.50"

// Verify webhook
$verified = KPayHelper::verifyWebhookSignature($payload, $signature);
```

## Exception Classes

### KPayException

Base exception class for all KPay errors.

```php
use KPay\LaravelKPayment\Exceptions\KPayException;

try {
    // KPay operation
} catch (KPayException $e) {
    $message = $e->getMessage();
    $code = $e->getCode();
}
```

### PaymentException

Thrown for payment-specific errors.

```php
use KPay\LaravelKPayment\Exceptions\PaymentException;

try {
    KPay::generateReference('100.00');
} catch (PaymentException $e) {
    // Handle payment error
}
```

### AuthenticationException

Thrown for authentication failures.

```php
use KPay\LaravelKPayment\Exceptions\AuthenticationException;

try {
    KPay::generateReference('100.00');
} catch (AuthenticationException $e) {
    // Handle auth error
}
```

## Configuration

See [INSTALLATION.md](INSTALLATION.md) for configuration details.

## Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | Success | Request successful |
| 201 | Created | Resource created |
| 400 | Bad Request | Invalid input |
| 401 | Unauthorized | Authentication failed |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Server Error | Internal server error |

## Rate Limiting

Check with Kyami Pay for:
- API rate limits
- Request throttling rules
- Quota information

## Error Handling

Always handle exceptions:

```php
try {
    $reference = KPay::generateReference('100.00');
} catch (\KPay\LaravelKPayment\Exceptions\PaymentException $e) {
    Log::error('Payment error: ' . $e->getMessage());
    return error_response($e->getMessage());
} catch (\Exception $e) {
    Log::error('Unexpected error: ' . $e->getMessage());
    return error_response('Server error');
}
```

## Best Practices

1. Always validate input before sending to API
2. Handle all exception types
3. Log errors for debugging
4. Use database transactions for consistency
5. Implement proper error responses to clients
6. Test with sandbox before production
7. Secure sensitive data in environment variables
