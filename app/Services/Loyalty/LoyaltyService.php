<?php

namespace App\Services\Loyalty;

use App\Models\User;

class LoyaltyService
{
    /**
     * Cộng điểm tích lũy vào tài khoản khách hàng
     */
    public function addPoints(User $user, int $points): User
    {
        if ($points <= 0) {
            return $user;
        }
        
        $user->loyalty_points += $points;
        $user->save();
        
        return $user;
    }

    /**
     * Trừ điểm tích lũy (đổi thưởng/giảm giá)
     * Trả về true nếu trừ thành công, false nếu không đủ điểm
     */
    public function redeemPoints(User $user, int $points): bool
    {
        if ($points <= 0) {
            return true;
        }

        if ($user->loyalty_points < $points) {
            return false;
        }

        $user->loyalty_points -= $points;
        $user->save();
        
        return true;
    }

    /**
     * Tính số điểm thưởng được nhận từ giá trị đơn hàng
     * Cấu hình mặc định: mỗi 1000đ = 1 điểm, hoặc lấy theo config
     */
    public function calculateEarnedPoints(float $amount, float $rate = 0.01): int
    {
        // 1% of amount as points (e.g., 100,000 VND -> 1000 VND value = 1000 points)
        // Adjust the rate logic according to your business needs
        return (int) floor($amount * $rate);
    }
}
