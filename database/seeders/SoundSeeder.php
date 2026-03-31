<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SoundSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sounds')->insert([
            [
                'ten' => 'Âm thanh thường',
                'ma' => 'STANDARD',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'Dolby Digital',
                'ma' => 'DOLBY_DIGITAL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'Dolby Atmos',
                'ma' => 'DOLBY_ATMOS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'IMAX Sound',
                'ma' => 'IMAX_SOUND',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}