<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guard): Response
    {
        $guards = empty($guard) ? [null] : $guard;

        foreach ($guards as $g) {
            // Chỉ kiểm tra nếu guard được định nghĩa trong config/auth.php hoặc là null (mặc định)
            if ($g && !config("auth.guards.$g")) {
                continue;
            }

            if (Auth::guard($g)->check()) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Bạn đã đăng nhập rồi.',
                    ], 409);
                }

                return redirect('/');
            }
        }

        return $next($request);
    }
}
