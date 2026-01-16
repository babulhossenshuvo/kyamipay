<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use KPay\LaravelKPayment\Facades\KPay;
use KPay\LaravelKPayment\Helpers\KPayHelper;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class KPayExampleController extends Controller
{
    /**
     * Create a payment
     */
    public function createPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:30',
            'order_id' => 'required|string',
        ]);

        try {
            // Generate reference from KPay API
            $response = KPay::generateReference(
                price: (string) $validated['amount'],
                description: $validated['description'],
                expiry: now()->addHours(24)->format('Y-m-d H:i:s')
            );

            // Find or create order
            $order = Order::findOrFail($validated['order_id']);

            // Update order with payment reference
            $order->update([
                'payment_reference' => $response['reference'],
                'status' => 'pending_payment',
            ]);

            Log::info('Payment created', [
                'reference' => $response['reference'],
                'order_id' => $validated['order_id'],
                'amount' => $validated['amount'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment reference created',
                'reference' => $response['reference'],
                'amount' => $response['price'],
                'expires_at' => $response['expiry'],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'order_id' => $validated['order_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus(string $reference): JsonResponse
    {
        try {
            $transaction = KPayHelper::getByReference($reference);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            // If still pending, check with API
            if ($transaction->isPending()) {
                $apiResponse = KPay::checkPayment($reference);
                
                if ($apiResponse) {
                    $transaction->markAsPaid($apiResponse);
                }
            }

            return response()->json([
                'success' => true,
                'reference' => $transaction->reference,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'paid_at' => $transaction->paid_at,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
            ], 400);
        }
    }

    /**
     * Cancel payment
     */
    public function cancelPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string|size:15',
        ]);

        try {
            KPay::cancelReference($validated['reference']);

            // Update transaction status
            $transaction = KPayHelper::getByReference($validated['reference']);
            if ($transaction) {
                $transaction->markAsCancelled();
            }

            // Update order status
            $order = Order::where('payment_reference', $validated['reference'])->first();
            if ($order) {
                $order->update(['status' => 'payment_cancelled']);
            }

            Log::info('Payment cancelled', [
                'reference' => $validated['reference'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Payment cancellation failed', [
                'reference' => $validated['reference'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment',
            ], 400);
        }
    }

    /**
     * Get user payments
     */
    public function getUserPayments(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $payments = KPayHelper::getUserTransactions($userId);

            return response()->json([
                'success' => true,
                'total' => $payments->count(),
                'payments' => $payments->map(fn($p) => [
                    'id' => $p->id,
                    'reference' => $p->reference,
                    'amount' => $p->amount,
                    'status' => $p->status,
                    'created_at' => $p->created_at,
                    'paid_at' => $p->paid_at,
                ]),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments',
            ], 400);
        }
    }

    /**
     * Test webhook manually (development only)
     */
    public function testWebhook(): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['error' => 'Not available in production'], 403);
        }

        try {
            // Create a test payment
            $transaction = KPayHelper::createPayment('100.00', [
                'description' => 'Test payment',
                'user_id' => auth()->id(),
            ]);

            // Simulate payment
            KPay::simulatePayment($transaction->reference, '100.00');

            Log::info('Webhook test completed', [
                'reference' => $transaction->reference,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook test completed',
                'reference' => $transaction->reference,
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook test failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook test failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
