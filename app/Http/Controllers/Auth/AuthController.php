<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\Customers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['mat_khau'] = Hash::make($validated['mat_khau']);
            $validated = Customers::create($validated);
            return response()->json([
                'status' => 'success',
                'message' => 'Đăng ký thành công',
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Đăng ký thất bại'
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $validated = $request->validated();

            $customer = Customers::where('email', $validated['email'])->first();
            if (!$customer || !Hash::check($request->mat_khau, $customer->mat_khau)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email hoặc mật khẩu không chính xác'
                ], 401);
            }
            if (!$customer->trang_thai) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tài khoản của bạn hiện đang bị khóa'
                ], 403);
            }


            $device = $request->device_name ?? $request->header('User-Agent', 'Unknown Device');
            $accessToken = $customer->createToken($device . '_access', ['*'], now()->addMinutes(15))->plainTextToken;
            $refreshToken = $customer->createToken($device . '_refresh', ['*'], now()->addDays(7))->plainTextToken;
            $cookie = cookie('refresh_token', $refreshToken, 43200, '/', null, false, true, false, 'Lax');

            return response()->json([
                'status' => 'success',
                'message' => 'Đăng nhập thành công',
                'token' => $accessToken,
                'user' => [
                    'id' => $customer->id,
                    'ho_ten' => $customer->ho_ten,
                ]
            ], 200)->withCookie($cookie);

        }
        catch (\Exception $e) {
            Log::error("Lỗi đăng nhập: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Có lỗi hệ thống xảy ra, vui lòng thử lại sau!'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->tokens()->delete();
        }
        return response()->json(['status' => 'success'])
            ->withoutCookie('refresh_token');
    }

    public function refreshToken(Request $request)
    {
        $tokenTho = $request->cookie('refresh_token');
        $tokenInstance = PersonalAccessToken::findToken($tokenTho);
        if (!$tokenInstance || !$tokenInstance->tokenable) {
            return response()->json(['status' => 'error', 'message' => 'Token không hợp lệ hoặc đã hết hạn'], 401);
        }

        $user = $tokenInstance->tokenable;
        $device = $request->device_name ?? $request->header('User-Agent', 'Unknown Device');
        $user->tokens()->delete();


        $newAccess = $user->createToken($device . '_access', ['*'], now()->addMinutes(15))->plainTextToken;
        $newRefresh = $user->createToken($device . '_refresh', ['*'], now()->addDays(7))->plainTextToken;
        $newCookie = cookie('refresh_token', $newRefresh, 10080, '/', null, false, true, false, 'Lax');

        return response()->json([
            'status' => 'success',
            'message' => 'Refresh token thành công',
            'token' => $newAccess,
        ], 200)->withCookie($newCookie); 
    }
    public function me(Request $request)
    {
        return response()->json(['status' => 'success', 'data' => $request->user()]);
    }
}
