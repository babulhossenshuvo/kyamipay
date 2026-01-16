<?php

namespace KPay\LaravelKPayment\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use KPay\LaravelKPayment\Models\KPayTransaction;
use KPay\LaravelKPayment\Services\KPayService;
use KPay\LaravelKPayment\Requests\GenerateReferenceRequest;
use KPay\LaravelKPayment\Requests\CancelReferenceRequest;
use KPay\LaravelKPayment\Events\PaymentConfirmed;
use KPay\LaravelKPayment\Events\PaymentFailed;

class KPayWebhookController extends Controller
{
    /**
     * Handle webhook from Kyami Pay.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            // Validate webhook payload
            if (!isset($payload['reference']) || !isset($payload['amount'])) {
                Log::warning('KPay Webhook: Invalid payload received', $payload);
                
                return response()->json([
                    'code' => 400,
                    'message' => 'Invalid payload',
                ], 400);
            }

            // Find transaction by reference
            $transaction = KPayTransaction::byReference($payload['reference'])->first();

            if ($transaction) {
                // Update transaction status
                $transaction->markAsPaid($payload);
                
                // Dispatch event for payment confirmation
                PaymentConfirmed::dispatch($transaction, $payload);
                
                Log::info('KPay Webhook: Payment confirmed', [
                    'reference' => $payload['reference'],
                    'amount' => $payload['amount'],
                ]);
            } else {
                Log::warning('KPay Webhook: Transaction not found', [
                    'reference' => $payload['reference'],
                ]);
            }

            // Return success response as per API documentation
            return response()->json(['code' => 200]);
        } catch (\Exception $e) {
            Log::error('KPay Webhook Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            
            return response()->json([
                'code' => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate a payment reference via API.
     *
     * @param GenerateReferenceRequest $request
     * @param KPayService $service
     * @return JsonResponse
     */
    public function generateReference(GenerateReferenceRequest $request, KPayService $service): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Call KPay service to generate reference
            $response = $service->generateReference(
                (string) $validated['amount'],
                $validated['description'] ?? null,
                $validated['expiry'] ?? null
            );

            // Store transaction in database
            $transaction = KPayTransaction::create([
                'reference' => $response['reference'],
                'entity' => $response['entity'],
                'amount' => $validated['amount'],
                'price' => $response['price'],
                'description' => $validated['description'] ?? null,
                'status' => 'pending',
                'currency' => config('kpay.currency'),
                'expires_at' => $validated['expiry'] ?? now()->addHours(config('kpay.reference_expiry_hours')),
                'metadata' => $validated['metadata'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'order_id' => $validated['order_id'] ?? null,
            ]);

            Log::info('KPay: Reference generated', [
                'reference' => $response['reference'],
                'amount' => $validated['amount'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reference generated successfully',
                'data' => $response,
                'transaction_id' => $transaction->id,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('KPay: Reference generation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check payment status.
     *
     * @param string $reference
     * @param KPayService $service
     * @return JsonResponse
     */
    public function checkPayment(string $reference, KPayService $service): JsonResponse
    {
        try {
            $transaction = KPayTransaction::byReference($reference)->firstOrFail();
            
            // Check with API if still pending
            if ($transaction->isPending()) {
                $apiResponse = $service->checkPayment($reference);
                
                if ($apiResponse) {
                    $transaction->markAsPaid($apiResponse);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $transaction,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('KPay: Payment check failed', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel a payment reference.
     *
     * @param CancelReferenceRequest $request
     * @param KPayService $service
     * @return JsonResponse
     */
    public function cancelReference(CancelReferenceRequest $request, KPayService $service): JsonResponse
    {
        try {
            $validated = $request->validated();

            $service->cancelReference($validated['reference']);

            $transaction = KPayTransaction::byReference($validated['reference'])->first();
            if ($transaction) {
                $transaction->markAsCancelled();
            }

            Log::info('KPay: Reference cancelled', [
                'reference' => $validated['reference'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reference cancelled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('KPay: Reference cancellation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
