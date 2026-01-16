<?php

namespace KPay\LaravelKPayment\Models;

use Illuminate\Database\Eloquent\Model;

class KPayTransaction extends Model
{
    protected $table = 'kpay_transactions';

    protected $fillable = [
        'reference',
        'entity',
        'amount',
        'price',
        'description',
        'status',
        'currency',
        'expires_at',
        'paid_at',
        'metadata',
        'api_response',
        'user_id',
        'order_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'api_response' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Scope: Get pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get paid transactions.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope: Get by reference.
     */
    public function scopeByReference($query, string $reference)
    {
        return $query->where('reference', $reference);
    }

    /**
     * Scope: Get by user ID.
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get by order ID.
     */
    public function scopeByOrder($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Check if transaction is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark transaction as paid.
     */
    public function markAsPaid(array $apiResponse = []): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'api_response' => $apiResponse,
        ]);
    }

    /**
     * Mark transaction as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark transaction as failed.
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
