<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        // Danh sách phòng và số ghế
        $rooms = DB::table('rooms')->get();
        $seatTypes = DB::table('seat_types')->get();

        foreach ($rooms as $room) {
            // Ví dụ: chia theo loại ghế dựa vào tên phòng
            if (str_contains($room->loai_phong, 'VIP')) {
                $type = $seatTypes->where('ma', 'VIP')->first();
            } elseif (str_contains($room->loai_phong, 'Couple')) {
                $type = $seatTypes->where('ma', 'COUPLE')->first();
            } else {
                $type = $seatTypes->where('ma', 'NORMAL')->first();
            }

            $rows = range('A', 'J'); // 10 hàng
            $cols = range(1, $room->suc_chua / 15); // số ghế mỗi hàng tạm tính

            foreach ($rows as $row) {
                foreach ($cols as $col) {
                    $ma_ghe = $room->ma . '-' . $row . $col;
                    DB::table('seats')->insert([
                        'room_id' => $room->id,
                        'seat_type_id' => $type->id,
                        'hang_ghe' => $row,
                        'so_ghe' => $col,
                        'ma' => $ma_ghe,
                        'trang_thai' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}