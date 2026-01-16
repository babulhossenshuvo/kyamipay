<?php

namespace KPay\LaravelKPayment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use KPay\LaravelKPayment\Models\KPayTransaction;

class PaymentConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public KPayTransaction $transaction,
        public array $payload
    ) {}
}
