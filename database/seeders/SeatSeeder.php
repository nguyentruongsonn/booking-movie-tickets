<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        $screens = \App\Models\Screen::all();
        $seatTypeStandard = \App\Models\SeatType::where('name', 'Standard')->first();
        $seatTypeVIP      = \App\Models\SeatType::where('name', 'VIP')->first();

        foreach ($screens as $screen) {
            $rows = range('A', 'J'); // Rows A to J
            
            foreach ($rows as $rowIndex => $row) {
                // Each screen has 15 seats per row
                for ($num = 1; $num <= 15; $num++) {
                    $type = $seatTypeStandard;
                    // Hàng E đến H là ghế VIP
                    if (in_array($row, ['E', 'F', 'G', 'H'])) {
                        $type = $seatTypeVIP;
                    }

                    \App\Models\Seat::create([
                        'screen_id'    => $screen->id,
                        'seat_type_id' => $type->id,
                        'row'          => $row,
                        'number'       => (string) $num,
                        'row_index'    => $rowIndex,
                        'column_index' => $num,
                        'label'        => "{$row}{$num}",
                        'status'       => 1,
                    ]);
                }
            }
        }
    }
}