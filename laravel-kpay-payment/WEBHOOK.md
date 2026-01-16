# Webhook Configuration

Webhooks allow Kyami Pay to notify your application when payments are confirmed.

## How Webhooks Work

1. Customer makes a payment using the reference
2. Kyami Pay processes the payment
3. Kyami Pay sends a POST request to your webhook URL
4. Your application updates the transaction status
5. Kyami Pay receives a success response

## Webhook Endpoint

The package automatically registers the webhook endpoint:

```
POST /api/kpay/webhook
```

## Configuration

### Enable Webhooks

In your `.env`:

```env
KPAY_WEBHOOK_ENABLED=true
KPAY_WEBHOOK_URL=/api/kpay/webhook
KPAY_WEBHOOK_SECRET=your_secret_key_here
```

### Configure in Kyami Pay

Provide Kyami Pay with:

```
Webhook URL: https://yourapp.com/api/kpay/webhook
```

## Webhook Payload

Kyami Pay sends:

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
    "terminal_location": "Luanda       "
}
```

## Webhook Response

Your application must respond with:

```json
{
    "code": 200
}
```

The package handles this automatically.

## Webhook Security

### Signature Verification

For security, verify webhook signatures:

```php
use KPay\LaravelKPayment\Helpers\KPayHelper;

$verified = KPayHelper::verifyWebhookSignature($payload, $signature);

if (!$verified) {
    return response()->json(['code' => 401], 401);
}
```

### Custom Middleware

Create custom webhook middleware:

```bash
php artisan make:middleware ValidateKPayWebhook
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateKPayWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Signature');
        
        if (!$this->verifySignature($request->all(), $signature)) {
            return response()->json(['code' => 401], 401);
        }

        return $next($request);
    }

    private function verifySignature(array $payload, string $signature): bool
    {
        $secret = config('kpay.webhook.secret');
        $computed = hash_hmac('sha256', json_encode($payload), $secret);
        
        return hash_equals($computed, $signature);
    }
}
```

## Testing Webhooks

### Manual Testing

Use cURL:

```bash
curl -X POST https://yourapp.test/api/kpay/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "reference": "000000458712369",
    "amount": "10.00",
    "entity": "0000",
    "data": "20220719",
    "movement_time": "121350",
    "terminal_type": "A",
    "id_terminal_type": "0000000000",
    "accounting_period": "000",
    "id_local_transaction": "00000",
    "terminal_location": "NET"
  }'
```

### Using Postman

1. Import webhook payload
2. Set POST method
3. Set URL to webhook endpoint
4. Send request
5. Verify response is `{"code": 200}`

### Simulated Payments (Sandbox)

Use the simulation endpoint:

```php
use KPay\LaravelKPayment\Facades\KPay;

// Simulate a payment (sandbox only)
KPay::simulatePayment('000000458712369', '10.00');
```

This triggers a webhook with a test payment.

## Debugging Webhooks

### Enable Logging

In `.env`:

```env
KPAY_LOG_REQUESTS=true
```

### Check Logs

```bash
tail -f storage/logs/laravel.log | grep -i kpay
```

### Monitor Database

```php
// Check transaction status
$transaction = \KPay\LaravelKPayment\Models\KPayTransaction::where(
    'reference', 
    '000000458712369'
)->first();

echo $transaction->status;  // pending, paid, cancelled, failed
echo $transaction->paid_at; // payment confirmation time
```

## Webhook Troubleshooting

### Webhook Not Received

1. Verify URL is publicly accessible:
   ```bash
   curl -X GET https://yourapp.com/api/kpay/webhook
   ```

2. Check Kyami Pay configuration in your account

3. Check firewall/security rules

4. Verify SSL certificate is valid

### "Invalid Payload" Error

1. Verify payload includes `reference` and `amount`
2. Check Content-Type header is `application/json`
3. Validate JSON structure

### Signature Verification Failed

1. Verify `KPAY_WEBHOOK_SECRET` is correct
2. Check signature header name
3. Verify payload hasn't been modified

### Transaction Not Updating

1. Check transaction reference exists
2. Verify database migrations ran
3. Check error logs
4. Verify event listeners are registered

## Advanced Configuration

### Custom Webhook Handler

Create a custom webhook handler:

```php
<?php

namespace App\Services;

use KPay\LaravelKPayment\Models\KPayTransaction;

class KPayWebhookHandler
{
    public function handle(array $payload): bool
    {
        $transaction = KPayTransaction::byReference(
            $payload['reference']
        )->first();

        if (!$transaction) {
            return false;
        }

        // Perform custom logic
        $transaction->markAsPaid($payload);
        
        // Update related models
        $this->updateOrder($transaction);
        $this->notifyUser($transaction);
        
        return true;
    }

    private function updateOrder(KPayTransaction $transaction): void
    {
        // Update order
    }

    private function notifyUser(KPayTransaction $transaction): void
    {
        // Send notification
    }
}
```

### Asynchronous Processing

For async processing, dispatch a job:

```php
// In webhook controller
public function handle(Request $request): JsonResponse
{
    $payload = $request->all();
    
    // Dispatch job
    ProcessPaymentWebhook::dispatch($payload);
    
    // Immediately return success
    return response()->json(['code' => 200]);
}
```

```php
// In job
class ProcessPaymentWebhook implements ShouldQueue
{
    public function handle(array $payload): void
    {
        $transaction = KPayTransaction::byReference(
            $payload['reference']
        )->first();

        $transaction->markAsPaid($payload);
    }
}
```

## Best Practices

1. **Always verify signatures** for production
2. **Log all webhooks** for debugging
3. **Use atomic transactions** for data consistency
4. **Handle duplicates** (use unique constraints)
5. **Respond quickly** (use async jobs)
6. **Monitor webhook deliveries** in Kyami Pay dashboard
7. **Set up alerts** for failed payments
8. **Test regularly** with simulation endpoint
