<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('seat_types')->insert([
            [
                'ten' => 'Ghế thường',
                'ma' => 'NORMAL',
                'them_gia' => 0, // không phụ thu
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'Ghế VIP',
                'ma' => 'VIP',
                'them_gia' => 30000, // phụ thu +30k
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'Ghế đôi (Couple)',
                'ma' => 'COUPLE',
                'them_gia' => 80000, // phụ thu +80k
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'Ghế Deluxe',
                'ma' => 'DELUXE',
                'them_gia' => 50000, // phụ thu +50k
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}