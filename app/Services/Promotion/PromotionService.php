<?php

namespace App\Services\Promotion;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    /**
     * Đăng ký mã khuyến mãi vào tài khoản người dùng.
     *
     * @throws ValidationException
     */
    public function register(User $user, string $code, string $password): Promotion
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Mật khẩu xác nhận không chính xác.'],
            ]);
        }

        $promotion = Promotion::query()
            ->where('code', $code)
            ->where('status', 1)
            ->first();

        if (! $promotion) {
            throw ValidationException::withMessages([
                'code' => ['Mã khuyến mãi không tồn tại.'],
            ]);
        }

        if (now()->lt($promotion->start_date) || now()->gt($promotion->end_date)) {
            throw ValidationException::withMessages([
                'code' => ['Mã khuyến mãi đã hết hạn hoặc chưa có hiệu lực.'],
            ]);
        }

        $existing = $user->promotions()
            ->where('promotions.id', $promotion->id)
            ->first();

        if ($existing) {
            if ((int) $existing->pivot->status === 0) {
                 return $promotion; // Already registered but not used yet
            }

            throw ValidationException::withMessages([
                'code' => ['Mã này đã được sử dụng.'],
            ]);
        }

        $user->promotions()->attach($promotion->id, [
            'status'       => 1, // Available
            'usage_count'  => 0,
        ]);

        return $promotion;
    }

    /**
     * Tính toán số tiền được giảm giá và validate điều kiện.
     *
     * @throws ValidationException
     */
    public function calculateDiscount(User $user, string $code, float $totalAmount): array
    {
        $promotion = $user->promotions()
            ->where('promotions.code', $code)
            ->wherePivot('status', 1) // Available
            ->first();

        if (! $promotion) {
            throw ValidationException::withMessages([
                'code' => ['Mã không hợp lệ hoặc chưa được đăng ký.'],
            ]);
        }

        if (now()->lt($promotion->start_date) || now()->gt($promotion->end_date)) {
            throw ValidationException::withMessages([
                'code' => ['Mã khuyến mãi đã hết hạn.'],
            ]);
        }

        if ($totalAmount < (float) $promotion->min_order_value) {
            throw ValidationException::withMessages([
                'items' => ["Chưa đạt giá trị đơn hàng tối thiểu cho mã này (Yêu cầu tối thiểu: " . number_format($promotion->min_order_value) . "đ)."],
            ]);
        }

        $discountValue = $promotion->discount_type === 'percentage'
            ? ($totalAmount * $promotion->discount_value / 100)
            : (float) $promotion->discount_value;

        // Giới hạn mức giảm tối đa nếu có
        if ($promotion->max_discount_amount > 0) {
            $discountValue = min($discountValue, (float) $promotion->max_discount_amount);
        }

        $finalDiscount = min($discountValue, $totalAmount);

        return [
            'promotion_id'   => $promotion->id,
            'discount_type'  => $promotion->discount_type,
            'discount_value' => $finalDiscount,
            'discount_amount' => $finalDiscount,
            'code'           => $promotion->code,
        ];
    }
}
