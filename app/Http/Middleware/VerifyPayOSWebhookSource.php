<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Whitelist IP cho PayOS webhook endpoint.
 * Chỉ cho phép các IP chính thức của PayOS gọi webhook.
 */
class VerifyPayOSWebhookSource
{
    public function handle(Request $request, Closure $next): Response
    {
        // Bỏ qua kiểm tra IP trong môi trường local/testing
        if (app()->isLocal() || app()->runningUnitTests()) {
            return $next($request);
        }

        $allowedIps = config('services.payos.webhook_ips', []);

        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps, true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}
