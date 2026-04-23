<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware trích xuất JWT từ HttpOnly Cookie ('token')
 * và gán vào Header Authorization để các Guard hiện tại (JWT) 
 * có thể xử lý mượt mà mà không cần sửa code lõi.
 */
class AuthenticateFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nếu Request chưa có Authorization Header nhưng có Cookie 'token'
        if (!$request->hasHeader('Authorization') && $request->hasCookie('token')) {
            $token = $request->cookie('token');
            // Gán lại vào Header cho request hiện tại
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
