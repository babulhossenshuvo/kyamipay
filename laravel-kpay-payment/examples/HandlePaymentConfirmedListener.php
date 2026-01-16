<?php

namespace App\Listeners;

use KPay\LaravelKPayment\Events\PaymentConfirmed;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Example listener for payment confirmation events
 */
class HandlePaymentConfirmed
{
    public function handle(PaymentConfirmed $event): void
    {
        $transaction = $event->transaction;

        Log::info('Payment confirmed', [
            'reference' => $transaction->reference,
            'amount' => $transaction->amount,
            'user_id' => $transaction->user_id,
        ]);

        // Update order status
        if ($transaction->order_id) {
            Order::where('id', $transaction->order_id)->update([
                'status' => 'paid',
                'payment_date' => now(),
                'payment_reference' => $transaction->reference,
            ]);
        }

        // Update user if needed
        if ($transaction->user_id) {
            User::where('id', $transaction->user_id)->update([
                'last_payment_date' => now(),
            ]);
        }

        // Send email notification
        // Mail::to($transaction->user_id)->queue(
        //     new PaymentConfirmedMail($transaction)
        // );

        // Update inventory, send confirmation, etc.
        // dispatch(new ProcessPaymentJob($transaction));
    }
}
