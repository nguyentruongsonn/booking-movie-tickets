<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('movies')->insert([
            [
                'ten_phim' => 'Avengers: Endgame',
                'slug' => Str::slug('Avengers: Endgame'),
                'ten_goc' => 'Avengers: Endgame',
                'gia' => 20000, // phụ phí theo phim (VD: phim đặc biệt)
                'mo_ta' => 'Các siêu anh hùng hợp lực để đánh bại Thanos.',
                'thoi_luong' => 181,
                'ngay_khoi_chieu' => '2019-04-24',
                'ngay_ket_thuc' => '2019-06-30',
                'do_tuoi' => 'C13',
                'trang_thai' => 'dang_chieu',
                'dao_dien' => 'Anthony Russo, Joe Russo',
                'dien_vien' => 'Robert Downey Jr., Chris Evans, Mark Ruffalo',
                'poster_url' => 'poster/avenger.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=TcMBFSGVi1c',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_phim' => 'The Batman',
                'slug' => Str::slug('The Batman'),
                'ten_goc' => 'The Batman',
                'gia' => 15000,
                'mo_ta' => 'Batman điều tra chuỗi tội phạm ở Gotham City.',
                'thoi_luong' => 176,
                'ngay_khoi_chieu' => '2022-03-04',
                'ngay_ket_thuc' => '2022-05-10',
                'do_tuoi' => 'C16',
                'trang_thai' => 'dang_chieu',
                'dao_dien' => 'Matt Reeves',
                'dien_vien' => 'Robert Pattinson, Zoë Kravitz',
                'poster_url' => 'poster/avenger.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=mqqft2x_Aa4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_phim' => 'Spider-Man: No Way Home',
                'slug' => Str::slug('Spider-Man: No Way Home'),
                'ten_goc' => 'Spider-Man: No Way Home',
                'gia' => 25000,
                'mo_ta' => 'Peter Parker tìm cách trở lại bình thường sau khi danh tính bị lộ.',
                'thoi_luong' => 148,
                'ngay_khoi_chieu' => '2021-12-17',
                'ngay_ket_thuc' => '2022-02-28',
                'do_tuoi' => 'C13',
                'trang_thai' => 'dang_chieu',
                'dao_dien' => 'Jon Watts',
                'dien_vien' => 'Tom Holland, Zendaya, Benedict Cumberbatch',
                'poster_url' => 'poster/avenger.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=JfVOs4VSpmA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
