<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormatSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('formats')->insert([
            [
                'ten' => '2D',
                'ma' => '2D',
                'gia' => 100000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => '3D',
                'ma' => '3D',
                'gia' => 140000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'IMAX',
                'ma' => 'IMAX',
                'gia' => 180000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => '4DX',
                'ma' => '4DX',
                'gia' => 220000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}