<?php

namespace KPay\LaravelKPayment\Helpers;

use KPay\LaravelKPayment\Models\KPayTransaction;

class KPayHelper
{
    /**
     * Create a new payment transaction.
     *
     * @param string $amount
     * @param array $options
     * @return KPayTransaction
     */
    public static function createPayment(string $amount, array $options = []): KPayTransaction
    {
        return KPayTransaction::create([
            'amount' => $amount,
            'status' => 'pending',
            'currency' => config('kpay.currency'),
            'description' => $options['description'] ?? null,
            'user_id' => $options['user_id'] ?? null,
            'order_id' => $options['order_id'] ?? null,
            'metadata' => $options['metadata'] ?? null,
            'expires_at' => $options['expires_at'] ?? now()->addHours(config('kpay.reference_expiry_hours')),
        ]);
    }

    /**
     * Get transaction by reference.
     *
     * @param string $reference
     * @return KPayTransaction|null
     */
    public static function getByReference(string $reference): ?KPayTransaction
    {
        return KPayTransaction::byReference($reference)->first();
    }

    /**
     * Get user transactions.
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserTransactions(string $userId)
    {
        return KPayTransaction::byUser($userId)->get();
    }

    /**
     * Get pending transactions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPendingTransactions()
    {
        return KPayTransaction::pending()->get();
    }

    /**
     * Get paid transactions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPaidTransactions()
    {
        return KPayTransaction::paid()->get();
    }

    /**
     * Format amount for API.
     *
     * @param float $amount
     * @return string
     */
    public static function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Verify webhook signature.
     *
     * @param array $payload
     * @param string $signature
     * @return bool
     */
    public static function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $secret = config('kpay.webhook.secret');
        
        if (!$secret) {
            return true; // No secret configured, skip verification
        }

        $computed = hash_hmac(
            'sha256',
            json_encode($payload),
            $secret
        );

        return hash_equals($computed, $signature);
    }
}
