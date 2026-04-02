<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customers; // Hoặc Customer
use App\Models\Promotions;
use Carbon\Carbon;

class CustomerPromotionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Lấy danh sách User và Promotion để gán
        $user = Customers::first(); // Giả sử lấy user đầu tiên để test
        $promotions = Promotions::all();

        if (!$user || $promotions->isEmpty()) {
            return;
        }

        // 2. Gán Voucher CHƯA SỬ DỤNG (Để test GET /api/my-vouchers)
        // Lấy mã 'TRIANVIP' hoặc mã đầu tiên trong danh sách
        $giftVoucher = $promotions->where('ma_khuyen_mai', 'TRIANVIP')->first() ?? $promotions->first();

        $user->promotions()->attach($giftVoucher->id, [
            'trang_thai' => 0, // 0: Chưa dùng
            'so_lan_da_dung' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Gán Voucher ĐÃ SỬ DỤNG (Để test logic chống xài lại)
        $usedVoucher = $promotions->where('ma_khuyen_mai', 'GIAM30K')->first();
        if ($usedVoucher) {
            $user->promotions()->attach($usedVoucher->id, [
                'trang_thai' => 1, // 1: Đã dùng
                'ngay_su_dung' => Carbon::now()->subDays(1),
                'booking_id' => 1, // Giả định ID đơn hàng cũ là 1
                'so_lan_da_dung' => 1,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(1),
            ]);
        }
    }
}
