<?php

namespace Database\Seeders;

use App\Models\Subtitles;
use Illuminate\Database\Seeder;

class SubtitleSeeder extends Seeder
{
    public function run(): void
    {
        $subtitles = [
            ['name' => 'Tiếng Việt'],
            ['name' => 'Tiếng Anh'],
            ['name' => 'Lồng tiếng'],
        ];

        foreach ($subtitles as $subtitle) {
            Subtitles::firstOrCreate(['name' => $subtitle['name']], $subtitle);
        }
    }
}