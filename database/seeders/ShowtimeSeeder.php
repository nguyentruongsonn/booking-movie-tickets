<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        $movies = DB::table('movies')->get();
        $rooms = DB::table('rooms')->get();
        $formats = DB::table('formats')->get();
        $sounds = DB::table('sounds')->get();
        $subtitles = DB::table('subtitles')->get();

        foreach ($movies as $movie) {
            foreach ($rooms as $room) {
                foreach ($formats as $format) {
                    // random thời gian chiếu trong 1 tuần tới
                    $ngay_gio_chieu = Carbon::now()->addDays(rand(0,7))->setHour(rand(10,22))->setMinute(0);

                    // Base price = movie + format
                    $gia_suat = $movie->gia + $format->gia;

                    // Phụ thu cuối tuần
                    $weekday = $ngay_gio_chieu->dayOfWeekIso; // 6=Sat, 7=Sun
                    if($weekday >= 6){
                        $gia_suat += 30000;
                    }

                    // Phụ thu giờ vàng
                    $hour = (int)$ngay_gio_chieu->format('H');
                    if($hour >= 18 && $hour <= 23){
                        $gia_suat += 30000;
                    }

                    DB::table('showtimes')->insert([
                        'movie_id' => $movie->id,
                        'room_id' => $room->id,
                        'format_id' => $format->id,
                        'sound_id' => $sounds->first()->id,
                        'subtitle_id' => $subtitles->first()->id,
                        'ngay_gio_chieu' => $ngay_gio_chieu,
                        'gia' => $gia_suat,
                        'trang_thai' => 'con_ve',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}