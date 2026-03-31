<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$role): Response
    {
        if(!Auth()->guard('employee')->check())
        {
            return response()->json(['message'=>'Vui lòng đăng nhập','status'=>401],401);
        }

        //Lấy nhân viên hiện tại đang đăng nhập
        $employee = Auth()->guard('employee')->user();
        if($employee->trang_thai == false)
        {
            Auth()->guard('employee')->logout();
            return response()->json(['message'=>'Tài khoản của bạn đã bị khóa','status'=>403],403);
        }
        return $next($request);
    }
}
