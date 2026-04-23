<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Factory;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use \App\Traits\AuthenticatesUsers;
 
    private const ACCESS_TTL_MINUTES = 15;
    private const REFRESH_TTL_MINUTES = 10080;

    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();
 
            $user = User::create([
                'full_name' => $validated['full_name'],
                'email'     => $validated['email'],
                'password'  => Hash::make($validated['password']),
                'phone'     => $validated['phone'] ?? null,
                'status'    => User::STATUS_ACTIVE,
            ]);
 
            $customerRole = Role::where('name', 'customer')->first();
            if ($customerRole) {
                $user->roles()->attach($customerRole);
            }
 
            return $this->created([
                'id'        => $user->id,
                'full_name' => $user->full_name
            ], 'Đăng ký tài khoản thành công.');
 
        } catch (\Exception $e) {
            Log::error('Lỗi đăng ký: ' . $e->getMessage());
            return $this->error('Đăng ký thất bại. Vui lòng thử lại sau.', 500);
        }
    }
 
    public function login(LoginRequest $request)
    {
        try {
            $validated = $request->validated();
 
            $user = User::where('email', $validated['email'])->first();
            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return $this->unauthorized('Email hoặc mật khẩu không chính xác.');
            }
 
            if ($user->status !== User::STATUS_ACTIVE) {
                return $this->forbidden('Tài khoản của bạn hiện đang bị khóa.');
            }
 
            $device = $request->device_name ?? $request->header('User-Agent', 'Unknown Device');
            $tokens = $this->issueJwtTokens($user, $device);
 
            $response = $this->ok([
                'user' => [
                    'id'        => $user->id,
                    'full_name' => $user->full_name,
                    'roles'     => $user->roles()->pluck('name'),
                ],
                'token_type'   => 'Bearer',
                'expires_in'   => $tokens['expires_in'],
            ], 'Đăng nhập thành công.');
 
            return $this->setAuthCookies($response, $tokens);
 
        } catch (\Exception $e) {
            Log::error('Lỗi đăng nhập JWT: ' . $e->getMessage());
            return $this->error('Có lỗi hệ thống xảy ra, vui lòng thử lại sau.', 500);
        }
    }
 
    public function logout(Request $request)
    {
        try {
            if ($token = JWTAuth::getToken()) {
                JWTAuth::setToken($token)->invalidate(true);
            }
        } catch (\Throwable $e) {
            Log::warning('Không thể invalidate access token: ' . $e->getMessage());
        }
 
        $refreshToken = $request->cookie('refresh_token');
        if ($refreshToken) {
            try {
                JWTAuth::setToken($refreshToken)->invalidate(true);
            } catch (\Throwable $e) {
                Log::warning('Không thể invalidate refresh token: ' . $e->getMessage());
            }
        }
 
        return $this->ok([], 'Đăng xuất thành công.')
            ->withoutCookie('token')
            ->withoutCookie('refresh_token', '/api/auth');
    }
 
    public function refreshToken(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) {
            return $this->unauthorized('Phiên đăng nhập đã hết hạn.');
        }
 
        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();
            if (($payload->get('token_type') ?? null) !== 'refresh') {
                return $this->unauthorized('Token không hợp lệ.');
            }
 
            $user = JWTAuth::setToken($refreshToken)->toUser();
            if (!$user) {
                return $this->unauthorized('Người dùng không tồn tại.');
            }
 
            try {
                JWTAuth::setToken($refreshToken)->invalidate(true);
            } catch (\Throwable $e) {
                Log::warning('Không thể invalidate refresh token cũ: ' . $e->getMessage());
            }
 
            $device = $request->device_name ?? $request->header('User-Agent', 'Unknown Device');
            $tokens = $this->issueJwtTokens($user, $device);
 
            $response = $this->ok([
                'token_type'   => 'Bearer',
                'expires_in'   => $tokens['expires_in'],
            ], 'Làm mới token thành công.');
 
            return $this->setAuthCookies($response, $tokens);
 
        } catch (\Throwable $e) {
            return $this->unauthorized('Token đã hết hạn hoặc không hợp lệ.');
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user(),
        ]);
    }
}
