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
            if (! Auth::guard($g)->check()) {
                continue;
            }

            if (in_array($g, ['api', 'customer'], true)) {
                return response()->json([
                    'message' => 'Ban da dang nhap roi',
                    'status' => 409,
                ], 409);
            }

            if ($g === 'employee') {
                return redirect()->route('admin.dashboard');
            }

            return redirect('/');
        }

        return $next($request);
    }
}
