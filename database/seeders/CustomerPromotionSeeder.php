<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer; // Hoặc Customer
use App\Models\Promotion;
use Carbon\Carbon;

class CustomerPromotionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Lấy danh sách User và Promotion để gán
        $user = \App\Models\User::first(); // Lấy user đầu tiên để test
        $promotions = Promotion::all();

        if (!$user || $promotions->isEmpty()) {
            return;
        }

        // 2. Gán Voucher CÓ THỂ SỬ DỤNG (Để test GET /api/v1/customer/registered-promotions)
        // Lấy mã 'VIPLOYALTY'
        $giftVoucher = $promotions->where('code', 'VIPLOYALTY')->first() ?? $promotions->first();

        // Kiểm tra xem đã tồn tại chưa để tránh lỗi duplicate khi chạy seed nhiều lần
        if (!$user->promotions()->where('promotion_id', $giftVoucher->id)->exists()) {
            $user->promotions()->attach($giftVoucher->id, [
                'status'      => 1, // 1: Available
                'usage_count' => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // 3. Gán Voucher ĐÃ SỬ DỤNG (Để test logic chống xài lại)
        $usedVoucher = $promotions->where('code', 'SAVE30K')->first();
        if ($usedVoucher && !$user->promotions()->where('promotion_id', $usedVoucher->id)->exists()) {
            $user->promotions()->attach($usedVoucher->id, [
                'status'      => 0, // 0: Used
                'used_at'     => Carbon::now()->subDays(1),
                'order_id'    => 1, // Giả định ID đơn hàng cũ là 1
                'usage_count' => 1,
                'created_at'  => now()->subDays(2),
                'updated_at'  => now()->subDays(1),
            ]);
        }
    }
}
