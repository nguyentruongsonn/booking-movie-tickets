<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('rooms')->insert([
            [
                'ten_phong' => 'Phòng 1 – IMAX',
                'ma' => 'RM_IMAX_1',
                'loai_phong' => 'IMAX',
                'suc_chua' => 200,
                'trang_thai' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_phong' => 'Phòng 2 – 2D',
                'ma' => 'RM_2D_1',
                'loai_phong' => '2D',
                'suc_chua' => 120,
                'trang_thai' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_phong' => 'Phòng 3 – 3D',
                'ma' => 'RM_3D_1',
                'loai_phong' => '3D',
                'suc_chua' => 150,
                'trang_thai' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_phong' => 'Phòng 4 – VIP',
                'ma' => 'RM_VIP_1',
                'loai_phong' => 'VIP',
                'suc_chua' => 80,
                'trang_thai' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}