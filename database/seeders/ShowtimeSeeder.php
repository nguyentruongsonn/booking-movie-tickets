<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        $movies    = \App\Models\Movie::all();
        $screens   = \App\Models\Screen::all();
        $formats   = \App\Models\Format::all();
        $sounds    = \App\Models\Sound::all();
        $subtitles = \App\Models\Subtitles::all();

        foreach ($movies as $movie) {
            foreach ($screens as $screen) {
                foreach ($formats as $format) {
                    $scheduledAt = Carbon::now()->addDays(rand(1, 7))->setHour(rand(10, 22))->setMinute(0);

                    // Base price calculation
                    $basePrice = 80000; // Mặc định 80k

                    // Phụ thu theo định dạng (giả định có bảng giá hoặc hardcode mẫu)
                    if (str_contains($format->name, '3D')) $basePrice += 20000;
                    if (str_contains($screen->screen_type, 'IMAX')) $basePrice += 50000;

                    // Phụ thu cuối tuần
                    if ($scheduledAt->isWeekend()) {
                        $basePrice += 20000;
                    }

                    \App\Models\Showtime::create([
                        'movie_id'     => $movie->id,
                        'screen_id'    => $screen->id,
                        'format_id'    => $format->id,
                        'sound_id'     => $sounds->first()->id,
                        'subtitle_id'  => $subtitles->first()->id,
                        'scheduled_at' => $scheduledAt,
                        'price'        => $basePrice,
                        'status'       => \App\Models\Showtime::STATUS_AVAILABLE,
                    ]);
                }
            }
        }
    }
}