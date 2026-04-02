<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotions;
use App\Models\Customers; 
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo các mã khuyến mãi chung (Cho mọi người nhập tay)
        $p1 = Promotions::create([
            'ma_khuyen_mai' => 'PHIMMOI2026',
            'ten_khuyen_mai' => 'Chào mừng thành viên mới',
            'loai_khuyen_mai' => 'public',
            'mo_ta' => 'Giảm 20% cho đơn hàng đầu tiên',
            'loai_giam_gia' => 'phan_tram',
            'gia_tri_giam' => 20,
            'don_toi_thieu' => 100000,
            'ngay_bat_dau' => Carbon::now(),
            'ngay_ket_thuc' => Carbon::now()->addMonths(1),
            'so_lan_su_dung' => 1000,
            'so_lan_su_dung_moi_ngay' => 50,
            'trang_thai' => true,
        ]);

        $p2 = Promotions::create([
            'ma_khuyen_mai' => 'GIAM30K',
            'ten_khuyen_mai' => 'Ưu đãi cuối tuần',
            'loai_khuyen_mai' => 'public',
            'mo_ta' => 'Giảm trực tiếp 30.000đ',
            'loai_giam_gia' => 'so_tien',
            'gia_tri_giam' => 30000,
            'don_toi_thieu' => 150000,
            'ngay_bat_dau' => Carbon::now(),
            'ngay_ket_thuc' => Carbon::now()->addDays(7),
            'so_lan_su_dung' => 500,
            'so_lan_su_dung_moi_ngay' => 20,
            'trang_thai' => true,
        ]);

        // 2. Tạo mã khuyến mãi tặng riêng (Dành cho bảng customer_promotion)
        $p3 = Promotions::create([
            'ma_khuyen_mai' => 'TRIANVIP',
            'ten_khuyen_mai' => 'Voucher Tri Ân Khách Hàng Thân Thiết',
            'loai_khuyen_mai' => 'private',
            'mo_ta' => 'Mã giảm giá đặc biệt dành riêng cho bạn',
            'loai_giam_gia' => 'phan_tram',
            'gia_tri_giam' => 50,
            'don_toi_thieu' => 50000,
            'ngay_bat_dau' => Carbon::now(),
            'ngay_ket_thuc' => Carbon::now()->addMonths(3),
            'so_lan_su_dung' => 1, // Chỉ được dùng 1 lần
            'so_lan_su_dung_moi_ngay' => 1,
            'trang_thai' => true,
        ]);


        $customerId = 1;
        if (DB::table('customers')->where('id', $customerId)->exists()) {
            DB::table('customer_promotion')->insert([
                'customer_id' => $customerId,
                'promotion_id' => $p3->id,
                'trang_thai' => 0, // Chưa dùng
                'so_lan_da_dung' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
