<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\Customer; 
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo các mã khuyến mãi chung (Cho mọi người nhập tay)
        $p1 = Promotion::create([
            'code'              => 'PHIMMOI2026',
            'name'              => 'New Member Welcome',
            'category'          => 'public',
            'description'       => '20% discount for first order',
            'discount_type'     => 'percentage',
            'discount_value'    => 20,
            'min_order_value'   => 100000,
            'start_date'        => Carbon::now(),
            'end_date'          => Carbon::now()->addMonths(1),
            'usage_limit'       => 1000,
            'daily_usage_limit' => 50,
            'status'            => true,
        ]);

        $p2 = Promotion::create([
            'code'              => 'SAVE30K',
            'name'              => 'Weekend Offer',
            'category'          => 'public',
            'description'       => 'Direct discount of 30,000VND',
            'discount_type'     => 'fixed_amount',
            'discount_value'    => 30000,
            'min_order_value'   => 150000,
            'start_date'        => Carbon::now(),
            'end_date'          => Carbon::now()->addDays(7),
            'usage_limit'       => 500,
            'daily_usage_limit' => 20,
            'status'            => true,
        ]);

        // 2. Tạo mã khuyến mãi tặng riêng (Dành cho bảng user_promotion)
        $p3 = Promotion::create([
            'code'              => 'VIPLOYALTY',
            'name'              => 'VIP Loyalty Voucher',
            'category'          => 'private',
            'description'       => 'Special discount for loyal customers',
            'discount_type'     => 'percentage',
            'discount_value'    => 50,
            'min_order_value'   => 50000,
            'start_date'        => Carbon::now(),
            'end_date'          => Carbon::now()->addMonths(3),
            'usage_limit'       => 1, 
            'daily_usage_limit' => 1,
            'status'            => true,
        ]);

        $userId = 1;
        if (DB::table('users')->where('id', $userId)->exists()) {
            DB::table('user_promotion')->insert([
                'user_id'      => $userId,
                'promotion_id' => $p3->id,
                'status'       => 1, // Available
                'usage_count'  => 0,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
