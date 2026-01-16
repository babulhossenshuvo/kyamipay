# Events & Listeners

The package dispatches events for payment lifecycle events.

## Available Events

### PaymentConfirmed Event

Fired when a payment is confirmed via webhook.

```php
namespace KPay\LaravelKPayment\Events;

class PaymentConfirmed
{
    public KPayTransaction $transaction;
    public array $payload;
}
```

## Listening to Events

### Using Event Listener Class

Create a listener:

```bash
php artisan make:listener SendPaymentConfirmationEmail --event="PaymentConfirmed"
```

Update the listener:

```php
<?php

namespace App\Listeners;

use KPay\LaravelKPayment\Events\PaymentConfirmed;
use App\Mail\PaymentConfirmedMail;
use Illuminate\Support\Facades\Mail;

class SendPaymentConfirmationEmail
{
    public function handle(PaymentConfirmed $event): void
    {
        $transaction = $event->transaction;
        
        // Send email to user
        Mail::to($transaction->user_id)->send(
            new PaymentConfirmedMail($transaction)
        );
        
        // Notify admin
        notify()->admin('Payment confirmed: ' . $transaction->reference);
    }
}
```

Register in `EventServiceProvider`:

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use KPay\LaravelKPayment\Events\PaymentConfirmed;
use App\Listeners\SendPaymentConfirmationEmail;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentConfirmed::class => [
            SendPaymentConfirmationEmail::class,
        ],
    ];
}
```

### Using Closure Listeners

In `EventServiceProvider.php`:

```php
use KPay\LaravelKPayment\Events\PaymentConfirmed;
use Illuminate\Support\Facades\Log;

Event::listen(PaymentConfirmed::class, function (PaymentConfirmed $event) {
    Log::info('Payment confirmed: ' . $event->transaction->reference);
    
    // Update order status
    Order::where('id', $event->transaction->order_id)
        ->update(['status' => 'paid']);
});
```

### Using Attribute

In `EventServiceProvider.php`:

```php
use KPay\LaravelKPayment\Events\PaymentConfirmed;

#[AsListener]
class HandlePaymentConfirmed
{
    public function __invoke(PaymentConfirmed $event)
    {
        // Handle payment confirmation
    }
}
```

## Complete Example

### 1. Create Listener

```bash
php artisan make:listener HandlePaymentConfirmed
```

### 2. Update Listener

```php
<?php

namespace App\Listeners;

use KPay\LaravelKPayment\Events\PaymentConfirmed;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class HandlePaymentConfirmed
{
    public function handle(PaymentConfirmed $event): void
    {
        $transaction = $event->transaction;
        $payload = $event->payload;

        // Log the payment
        Log::info('Payment Confirmed', [
            'reference' => $transaction->reference,
            'amount' => $transaction->amount,
            'user_id' => $transaction->user_id,
            'timestamp' => now(),
        ]);

        // Update order status
        if ($transaction->order_id) {
            Order::find($transaction->order_id)?->update([
                'status' => 'paid',
                'payment_date' => now(),
                'payment_reference' => $transaction->reference,
            ]);
        }

        // Update user account
        if ($transaction->user_id) {
            User::find($transaction->user_id)?->update([
                'last_payment_date' => now(),
            ]);
        }

        // Send notifications
        if ($transaction->user_id) {
            // Send email
            Mail::to($transaction->user_id)->queue(
                new PaymentConfirmedMail($transaction)
            );

            // Send SMS
            notification()->sms($transaction->user_id, 
                "Payment confirmed. Reference: {$transaction->reference}"
            );
        }

        // Trigger other actions
        dispatch(new ProcessPaymentJob($transaction));
    }
}
```

### 3. Register in Event Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use KPay\LaravelKPayment\Events\PaymentConfirmed;
use App\Listeners\HandlePaymentConfirmed;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentConfirmed::class => [
            HandlePaymentConfirmed::class,
        ],
    ];
}
```

## Event Payload

The `PaymentConfirmed` event includes:

```php
$event->transaction; // KPayTransaction model
$event->payload;     // Raw webhook payload from KPay

// Transaction model includes:
$event->transaction->id;
$event->transaction->reference;
$event->transaction->entity;
$event->transaction->amount;
$event->transaction->price;
$event->transaction->status;
$event->transaction->currency;
$event->transaction->user_id;
$event->transaction->order_id;
$event->transaction->metadata;
$event->transaction->paid_at;
$event->transaction->created_at;
$event->transaction->updated_at;
```

## Best Practices

1. **Use Queued Listeners** for long-running tasks:

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentConfirmationEmail implements ShouldQueue
{
    // ...
}
```

2. **Log Important Events**:

```php
Log::channel('payments')->info('Payment confirmed', [
    'reference' => $event->transaction->reference,
    'amount' => $event->transaction->amount,
]);
```

3. **Handle Failures Gracefully**:

```php
try {
    // Process payment
} catch (\Exception $e) {
    Log::error('Payment processing failed: ' . $e->getMessage());
    
    // Mark transaction as failed
    $event->transaction->markAsFailed();
}
```

4. **Use Transactions** for data consistency:

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($event) {
    $event->transaction->update(['status' => 'processed']);
    Order::find($event->transaction->order_id)->markAsCompleted();
});
```
