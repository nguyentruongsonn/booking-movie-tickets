<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Traits\AuthenticatesUsers;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class ProviderCallbackController extends Controller
{
    use AuthenticatesUsers;

    public function __invoke(string $provider)
    {
        try {
            if (!in_array($provider, ['google', 'github', 'facebook'])) {
                return redirect()->route('home')->with('error', 'Provider không hợp lệ');
            }

            // Lấy thông tin người dùng từ socialite
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Sử dụng model User (English Schema)
            $user = User::updateOrCreate([
                'email' => $socialUser->getEmail()
            ], [
                'full_name'      => $socialUser->getName(),
                'provider_id'    => $socialUser->getId(),
                'provider_name'  => $provider,
                'status'         => User::STATUS_ACTIVE,
                'provider_token' => $socialUser->token,
            ]);

            // Gán role customer nếu chưa có
            if (!$user->hasRole('customer')) {
                $customerRole = Role::where('name', 'customer')->first();
                if ($customerRole) {
                    $user->roles()->syncWithoutDetaching([$customerRole->id]);
                }
            }

            // Cấp phát JWT và Cookie (chuẩn hiện đại)
            $tokens = $this->issueJwtTokens($user, 'Social Login');

            // Trả về script để đóng popup và thông báo cho cửa sổ cha
            $response = response("<script>
                if (window.opener) {
                    window.opener.postMessage({
                        status: 'success',
                        token: 'session_active',
                        user: " . json_encode([
                            'id' => $user->id,
                            'full_name' => $user->full_name
                        ]) . "
                    }, window.location.origin);
                    window.close();
                } else {
                    window.location.href = '/';
                }
            </script>");

            return $this->setAuthCookies($response, $tokens);

        } catch (\Exception $e) {
            Log::error("Social Login Error (\$provider): " . $e->getMessage());
            return redirect()->route('home')->with('error', 'Đăng nhập thất bại.');
        }
    }
}
