<?php

namespace KPay\LaravelKPayment\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ValidateKPayWebhook
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verify webhook signature if configured
        $signature = $request->header('X-Signature');
        
        if (config('kpay.webhook.secret') && $signature) {
            $payload = $request->all();
            
            $computed = hash_hmac(
                'sha256',
                json_encode($payload),
                config('kpay.webhook.secret')
            );

            if (!hash_equals($computed, $signature)) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Invalid signature',
                ], 401);
            }
        }

        return $next($request);
    }
}
