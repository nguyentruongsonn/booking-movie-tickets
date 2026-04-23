<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vui lòng đăng nhập để tiếp tục.',
            ], 401);
        }

        if ($user->status === 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tài khoản của bạn đã bị khóa.',
            ], 403);
        }

        // Nếu không truyền role nào, chỉ cần đăng nhập là đủ
        if (empty($roles)) {
            return $next($request);
        }

        // Kiểm tra xem user có ít nhất 1 trong các role truyền vào không
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Bạn không có quyền truy cập tính năng này.',
        ], 403);
    }
}
