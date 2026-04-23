<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TheaterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $theaters = [
            [
                'name' => 'Antigravity Cinema HN',
                'address' => '123 Cầu Giấy',
                'city' => 'Hà Nội',
                'phone' => '0123456789',
                'email' => 'hn@antigravity.com',
                'status' => 1,
            ],
            [
                'name' => 'Antigravity Cinema SG',
                'address' => '456 Quận 1',
                'city' => 'TP. Hồ Chí Minh',
                'phone' => '0987654321',
                'email' => 'sg@antigravity.com',
                'status' => 1,
            ],
        ];

        foreach ($theaters as $theater) {
            \App\Models\Theater::firstOrCreate(['email' => $theater['email']], $theater);
        }
    }
}
