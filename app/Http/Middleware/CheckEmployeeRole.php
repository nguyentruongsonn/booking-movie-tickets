<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEmployeeRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$role)
    {
        if(!Auth()->guard('employee')->check())
        {
            return response()->json(['message'=>'Vui lòng đăng nhập','status'=>401],401);
        }

        $employee = Auth()->guard('employee')->user();

        if(empty($role) || in_array($employee->chuc_vu, $role))
        {
            return $next($request);
        }

        if($request->expectsJson())
        {
            return response()->json([
                'message'=>'Bạn không có quyền truy cập',
                'status'=>403,
                'required_roles'=>$role,
                'user_role'=>$employee->chuc_vu
            ],403);
        }
        abort(403, 'Bạn không có quyền truy cập chức năng này.');

    }
}
