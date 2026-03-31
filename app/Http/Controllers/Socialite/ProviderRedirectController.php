<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class ProviderRedirectController extends Controller
{
    public function __invoke(Request $request, string $provider)
    {
        $allowedProviders = ['google', 'github', 'facebook'];

        if (!in_array($provider, $allowedProviders)) {
            return redirect()->route('auth.login')
                ->with('error', "Chúng tôi chưa hỗ trợ đăng nhập bằng $provider");
        }

        try {
            return Socialite::driver($provider)->redirect(); // Tạo url của Socicalite và đẩy user khỏi trang web

        }
        catch (\Exception $e) {
            Log::error("Socialite Redirect Error: " . $e->getMessage());

            return redirect()->route('auth.login')
                ->with('error', 'Không thể kết nối với ' . ucfirst($provider));
        }
    }

}
