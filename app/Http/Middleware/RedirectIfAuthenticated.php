<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guard ): Response
    {
        $guard = empty($guard) ? [null] : $guard;
        foreach($guard as $g)
        {
            if(Auth()->guard($g)->check())
            {
                if($guard == 'customer' && Auth()->guard($g)->check())
                {
                    return redirect('/');
                }
                if($guard == 'employee' && Auth()->guard($g)->check())
                {
                    return redirect()->route('admin.dashboard');
                }
            }
        }
        return $next($request);
    }
}
