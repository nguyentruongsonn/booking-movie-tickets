<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Factory;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private const CUSTOMER_GUARD = 'customer';
    private const ACCESS_TTL_MINUTES = 15;
    private const REFRESH_TTL_MINUTES = 10080;

    public function register(RegisterRequest $request)
    {
        try {
            Auth::shouldUse(self::CUSTOMER_GUARD);
            $validated = $request->validated();
            $validated['mat_khau'] = Hash::make($validated['mat_khau']);
            Customers::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Dang ky thanh cong',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Loi dang ky: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Dang ky that bai',
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            Auth::shouldUse(self::CUSTOMER_GUARD);
            $validated = $request->validated();

            $customer = Customers::where('email', $validated['email'])->first();
            if (!$customer || !Hash::check($validated['mat_khau'], $customer->mat_khau)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email hoac mat khau khong chinh xac',
                ], 401);
            }

            if (!$customer->trang_thai) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tai khoan cua ban hien dang bi khoa',
                ], 403);
            }

            $device = $request->device_name ?? $request->header('User-Agent', 'Unknown Device');
            $tokens = $this->issueJwtTokens($customer, $device);

            return response()->json([
                'status' => 'success',
                'message' => 'Dang nhap thanh cong',
                'token' => $tokens['access_token'],
                'access_token' => $tokens['access_token'],
                'token_type' => 'Bearer',
                'expires_in' => self::ACCESS_TTL_MINUTES * 60,
                'user' => [
                    'id' => $customer->id,
                    'ho_ten' => $customer->ho_ten,
                ],
            ], 200)->withCookie($this->makeRefreshCookie($tokens['refresh_token']));
        } catch (\Exception $e) {
            Log::error('Loi dang nhap JWT: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Co loi he thong xay ra, vui long thu lai sau!',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        Auth::shouldUse(self::CUSTOMER_GUARD);

        try {
            if ($token = JWTAuth::getToken()) {
                JWTAuth::setToken($token)->invalidate(true);
            }
        } catch (\Throwable $e) {
            Log::warning('Khong the invalidate access token: ' . $e->getMessage());
        }

        $refreshToken = $request->cookie('refresh_token');
        if ($refreshToken) {
            try {
                JWTAuth::setToken($refreshToken)->invalidate(true);
            } catch (\Throwable $e) {
                Log::warning('Khong the invalidate refresh token: ' . $e->getMessage());
            }
        }

        return response()->json(['status' => 'success'])
            ->withoutCookie('refresh_token', '/api/auth', config('session.domain'));
    }

    public function refreshToken(Request $request)
    {
        Auth::shouldUse(self::CUSTOMER_GUARD);

        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay refresh token',
            ], 401);
        }

        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();
            if (($payload->get('token_type') ?? null) !== 'refresh') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Refresh token khong hop le',
                ], 401);
            }

            $user = JWTAuth::setToken($refreshToken)->toUser();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nguoi dung khong ton tai',
                ], 401);
            }

            try {
                JWTAuth::setToken($refreshToken)->invalidate(true);
            } catch (\Throwable $e) {
                Log::warning('Khong the invalidate refresh token cu: ' . $e->getMessage());
            }

            $device = $request->device_name ?? $request->header('User-Agent', 'Unknown Device');
            $tokens = $this->issueJwtTokens($user, $device);

            return response()->json([
                'status' => 'success',
                'message' => 'Refresh token thanh cong',
                'token' => $tokens['access_token'],
                'access_token' => $tokens['access_token'],
                'token_type' => 'Bearer',
                'expires_in' => self::ACCESS_TTL_MINUTES * 60,
            ], 200)->withCookie($this->makeRefreshCookie($tokens['refresh_token']));
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token khong hop le hoac da het han',
            ], 401);
        }
    }

    public function me(Request $request)
    {
        Auth::shouldUse(self::CUSTOMER_GUARD);

        return response()->json([
            'status' => 'success',
            'data' => $request->user(self::CUSTOMER_GUARD),
        ]);
    }

    private function issueJwtTokens(Customers $customer, string $device): array
    {
        Auth::shouldUse(self::CUSTOMER_GUARD);

        app(Factory::class)->setTTL(self::ACCESS_TTL_MINUTES);
        $accessToken = JWTAuth::claims([
            'token_type' => 'access',
            'device_name' => $device,
        ])->fromUser($customer);

        app(Factory::class)->setTTL(self::REFRESH_TTL_MINUTES);
        $refreshToken = JWTAuth::claims([
            'token_type' => 'refresh',
            'device_name' => $device,
        ])->fromUser($customer);

        app(Factory::class)->setTTL(self::ACCESS_TTL_MINUTES);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    private function makeRefreshCookie(string $refreshToken)
    {
        $isSecureCookie = app()->environment('production') || request()->isSecure();

        return cookie(
            'refresh_token',
            $refreshToken,
            self::REFRESH_TTL_MINUTES,
            '/api/auth',
            config('session.domain'),
            $isSecureCookie,
            true,
            false,
            'Lax'
        );
    }
}
