<?php

use Illuminate\Support\Facades\Route;
use KPay\LaravelKPayment\Controllers\KPayWebhookController;

Route::prefix('api/kpay')->middleware(['api'])->group(function () {
    // Generate payment reference
    Route::post('/generate', [KPayWebhookController::class, 'generateReference'])
        ->name('kpay.generate');

    // Check payment status
    Route::get('/check/{reference}', [KPayWebhookController::class, 'checkPayment'])
        ->name('kpay.check');

    // Cancel payment reference
    Route::post('/cancel', [KPayWebhookController::class, 'cancelReference'])
        ->name('kpay.cancel');

    // Webhook endpoint
    Route::post('/webhook', [KPayWebhookController::class, 'handle'])
        ->withoutMiddleware(['api'])
        ->name('kpay.webhook');
});
