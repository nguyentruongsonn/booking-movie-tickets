<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScreenSeeder extends Seeder
{
    public function run(): void
    {
        $theaterId = \App\Models\Theater::first()?->id ?? 1;

        \App\Models\Screen::insert([
            [
                'theater_id' => $theaterId,
                'name'       => 'Screen 1 – IMAX',
                'code'       => 'SC_IMAX_1',
                'screen_type' => 'IMAX',
                'capacity'   => 200,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'theater_id' => $theaterId,
                'name'       => 'Screen 2 – 2D Standard',
                'code'       => 'SC_2D_1',
                'screen_type' => '2D',
                'capacity'   => 120,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'theater_id' => $theaterId,
                'name'       => 'Screen 3 – 3D Atmos',
                'code'       => 'SC_3D_1',
                'screen_type' => '3D',
                'capacity'   => 150,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'theater_id' => $theaterId,
                'name'       => 'Screen 4 – Gold Class',
                'code'       => 'SC_VIP_1',
                'screen_type' => 'VIP',
                'capacity'   => 80,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}