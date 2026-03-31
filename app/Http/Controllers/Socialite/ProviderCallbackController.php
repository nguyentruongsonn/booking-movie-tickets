<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Customers;
use Illuminate\Support\Facades\Auth;
class ProviderCallbackController extends Controller
{
    public function __invoke(string $provider)
    {
        if (!in_array($provider, ['google', 'github', 'facebook'])) {
            return redirect()->route('auth.login')->with('error', 'Provider không hợp lệ');
        }

        //Lấy thông tin người dùng từ socialite
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $customer = Customers::updateOrCreate([
            'email' => $socialUser->getEmail()
        ], [
            'ho_ten' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'provider_id' => $socialUser->getId(),
            'email_verified_at' => now(),
            'provider_name' => $provider,
            'provider_token' => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken,
        ]);
        $customer->tokens()->delete();

        $token = $customer->createToken('auth_token')->plainTextToken;



        return response("<script>
        window.opener.postMessage({
            status: 'success',
            token: '$token',
            user: " . json_encode($customer) . "
        }, window.location.origin);
        window.close();
    </script>");
    }
}
