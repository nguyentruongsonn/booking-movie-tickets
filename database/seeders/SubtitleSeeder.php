<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubtitleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('subtitles')->insert([
            [
                'ten' => 'Phụ đề tiếng Việt',
                'ma' => 'VI_SUB',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'Lồng tiếng Việt',
                'ma' => 'VI_DUB',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'Phụ đề tiếng Anh',
                'ma' => 'EN_SUB',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten' => 'Không phụ đề',
                'ma' => 'NO_SUB',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}