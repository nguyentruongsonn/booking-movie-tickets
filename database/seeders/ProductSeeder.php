<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'ten_san_pham' => 'Combo Bắp + Nước',
                'loai_san_pham' => 'combo',
                'gia_ban' => 50000,
                'so_luong_ton' => 100,
                'hinh_anh_url' => 'products/combo1.jpg',
                'mo_ta' => '1 bắp rang + 1 nước ngọt',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_san_pham' => 'Nước ngọt',
                'loai_san_pham' => 'drink',
                'gia_ban' => 20000,
                'so_luong_ton' => 200,
                'hinh_anh_url' => 'products/drink.jpg',
                'mo_ta' => 'Coca-Cola lon 330ml',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_san_pham' => 'Bắp rang bơ',
                'loai_san_pham' => 'food',
                'gia_ban' => 30000,
                'so_luong_ton' => 150,
                'hinh_anh_url' => 'products/popcorn.jpg',
                'mo_ta' => 'Bắp rang bơ size vừa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
