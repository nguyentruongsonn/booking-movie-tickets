<?php
 
namespace App\Traits;
 
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Factory;
 
trait AuthenticatesUsers
{
    protected function issueJwtTokens(User $user, string $device): array
    {
        $accessTtl = 15; // Phút
        $refreshTtl = 10080; // 7 ngày
 
        JWTAuth::factory()->setTTL($accessTtl);
        $accessToken = JWTAuth::claims([
            'token_type' => 'access',
            'device_name' => $device,
        ])->fromUser($user);
 
        JWTAuth::factory()->setTTL($refreshTtl);
        $refreshToken = JWTAuth::claims([
            'token_type' => 'refresh',
            'device_name' => $device,
        ])->fromUser($user);
 
        // Trả lại TTL mặc định (thường là accessToken)
        JWTAuth::factory()->setTTL($accessTtl);
 
        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => $accessTtl * 60,
        ];
    }
 
    protected function setAuthCookies($response, array $tokens)
    {
        $isSecure = app()->environment('production') || request()->isSecure();
        $domain = config('session.domain');
 
        $accessCookie = cookie(
            'token',
            $tokens['access_token'],
            15,
            '/',
            $domain,
            $isSecure,
            true, // HttpOnly
            false,
            'Lax'
        );
 
        $refreshCookie = cookie(
            'refresh_token',
            $tokens['refresh_token'],
            10080,
            '/api/auth',
            $domain,
            $isSecure,
            true, // HttpOnly
            false,
            'Lax'
        );
 
        return $response->withCookie($accessCookie)->withCookie($refreshCookie);
    }
}
