<?php

namespace Database\Seeders;

use App\Models\SeatType;
use Illuminate\Database\Seeder;

class SeatTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Standard',
                'surcharge' => 0,
                'color' => '#808080',
            ],
            [
                'name' => 'VIP',
                'surcharge' => 20000,
                'color' => '#FF0000',
            ],
            [
                'name' => 'Sweetbox',
                'surcharge' => 50000,
                'color' => '#FFC0CB',
            ],
        ];

        foreach ($types as $type) {
            SeatType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}