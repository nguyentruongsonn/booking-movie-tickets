<?php

namespace Database\Seeders;

use App\Models\Format;
use Illuminate\Database\Seeder;

class FormatSeeder extends Seeder
{
    public function run(): void
    {
        $formats = [
            ['name' => '2D'],
            ['name' => '3D'],
            ['name' => 'IMAX'],
            ['name' => '4DX'],
        ];

        foreach ($formats as $format) {
            Format::firstOrCreate(['name' => $format['name']], $format);
        }
    }
}