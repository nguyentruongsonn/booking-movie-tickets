<?php

    namespace App\Http\Middleware;
    use Closure;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Symfony\Component\HttpFoundation\Response;
    use Tymon\JWTAuth\Exceptions\JWTException;
    use Tymon\JWTAuth\Facades\JWTAuth;

    class CustomerMiddleware
    {
        /**
         * Handle an incoming request.
         *
         * @param  Closure(Request): (Response)  $next
         */
        public function handle(Request $request, Closure $next): Response
        {
            try {
                $customer = Auth::guard('api')->user();

                if (! $customer) {
                    $customer = JWTAuth::parseToken()->authenticate();
                }
            } catch (JWTException $exception) {
                return response()->json([
                    'message' => 'Vui long dang nhap',
                    'status' => 401,
                ], 401);
            }

            if (! $customer) {
                return response()->json([
                    'message' => 'Vui long dang nhap',
                    'status' => 401,
                ], 401);
            }

            Auth::shouldUse('api');
            $request->setUserResolver(fn () => $customer);

            if (! $customer->trang_thai) {
                try {
                    // Vô hiệu hóa token
                    JWTAuth::parseToken()->invalidate(true);
                } catch (JWTException $exception) {
                    // Token may already be invalid or missing.
                }

                return response()->json([
                    'message' => 'Tai khoan cua ban da bi khoa',
                    'status' => 403,
                ], 403);
            }

            return $next($request);
        }
    }
