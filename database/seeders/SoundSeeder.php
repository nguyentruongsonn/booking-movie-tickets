<?php

namespace Database\Seeders;

use App\Models\Sound;
use Illuminate\Database\Seeder;

class SoundSeeder extends Seeder
{
    public function run(): void
    {
        $sounds = [
            ['name' => 'Dolby Atmos'],
            ['name' => 'DTS:X'],
            ['name' => 'Auro 11.1'],
        ];

        foreach ($sounds as $sound) {
            Sound::firstOrCreate(['name' => $sound['name']], $sound);
        }
    }
}