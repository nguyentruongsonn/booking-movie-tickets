<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $movies = [
            [
                'title' => 'Lật Mặt 7: Một Điều Ước',
                'original_title' => 'Lat Mat 7',
                'description' => 'Một bộ phim gia đình cảm động của Lý Hải.',
                'duration' => 110,
                'release_date' => '2026-04-26',
                'end_date' => '2026-06-26',
                'age_rating' => 'T13',
                'status' => Movie::STATUS_SHOWING,
                'director' => 'Lý Hải',
                'cast' => 'Thanh Hiền, Trương Minh Cường',
                'poster_url' => 'https://example.com/poster7.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'is_hot' => true,
            ],
            [
                'title' => 'Hành Tinh Khỉ: Vương Quốc Mới',
                'original_title' => 'Kingdom of the Planet of the Apes',
                'description' => 'Cuộc chiến mới trên hành tinh khỉ.',
                'duration' => 145,
                'release_date' => '2024-05-10',
                'end_date' => '2024-07-10',
                'age_rating' => 'T13',
                'status' => Movie::STATUS_SHOWING,
                'director' => 'Wes Ball',
                'cast' => 'Owen Teague, Freya Allan',
                'poster_url' => 'https://example.com/monkey.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'is_hot' => false,
            ],
        ];

        foreach ($movies as $movie) {
            Movie::firstOrCreate(['title' => $movie['title']], $movie);
        }
    }
}
