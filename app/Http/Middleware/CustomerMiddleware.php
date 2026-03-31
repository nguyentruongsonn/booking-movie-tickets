<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth()->guard('customer')->check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập', 'status' => 401], 401);

        }
        //Lấy khách hàng hiện tại đang đăng nhập
        $customer = Auth()->guard('customer')->user();
        if ($customer->trang_thai == false) {
            Auth()->guard('customer')->logout();
            return response()->json(['message' => 'Tài khoản của bạn đã bị khóa', 'status' => 403], 403);

        }
        return $next($request);
    }
}
