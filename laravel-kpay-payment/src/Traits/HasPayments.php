<?php

namespace KPay\LaravelKPayment\Traits;

use KPay\LaravelKPayment\Models\KPayTransaction;

trait HasPayments
{
    /**
     * Get user's KPay transactions.
     */
    public function kpayTransactions()
    {
        return $this->hasMany(KPayTransaction::class, 'user_id');
    }

    /**
     * Get pending payments.
     */
    public function pendingPayments()
    {
        return $this->kpayTransactions()->pending();
    }

    /**
     * Get paid payments.
     */
    public function paidPayments()
    {
        return $this->kpayTransactions()->paid();
    }
}
