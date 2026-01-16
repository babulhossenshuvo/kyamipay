// Example routes for KPay payment system
// Add these to your routes/api.php

use KPay\LaravelKPayment\Controllers\KPayWebhookController;
use App\Http\Controllers\KPayExampleController;

Route::middleware('api')->group(function () {
    // Public routes
    Route::post('/kpay/generate', [KPayExampleController::class, 'createPayment']);
    Route::get('/kpay/check/{reference}', [KPayExampleController::class, 'checkStatus']);
    Route::post('/kpay/cancel', [KPayExampleController::class, 'cancelPayment']);

    // Webhook (no auth required)
    Route::post('/kpay/webhook', [KPayWebhookController::class, 'handle'])
        ->withoutMiddleware('auth:sanctum');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/kpay/payments', [KPayExampleController::class, 'getUserPayments']);
        Route::post('/kpay/test-webhook', [KPayExampleController::class, 'testWebhook']);
    });
});
