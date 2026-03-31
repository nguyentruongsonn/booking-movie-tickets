<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Showtimes;
use App\Models\Tickets;
use App\Models\Seats;
use Illuminate\Support\Facades\Cache;
use App\Models\Products;

class BookingApiController extends Controller
{
    public function getShowtimeInfo($showtimeID)
    {
        $showtime = Showtimes::with(['room', 'movie'])->findOrFail($showtimeID);
        $bookedSeats = Tickets::where('suat_chieu_id', $showtimeID)->where('trang_thai', '!=', 'da_huy')->pluck('ghe_id')->toArray();
        $seat = Seats::where('room_id', $showtime->room_id)->with('seat_type')->orderby('hang_ghe')->orderby('so_ghe')->get();
        $seatsByRow = [];
        foreach ($seat as $s) {
            $row = $s->hang_ghe;
            if (!isset($seatsByRow[$row])) {
                $seatsByRow[$row] = [];
            }

            $seatsByRow[$row][] = [
                'id' => $s->id,
                'ma' => $s->ma,
                'hang_ghe' => $s->hang_ghe,
                'so_ghe' => $s->so_ghe,
                'loai_ghe' => $s->seat_type,
                'gia_ghe' => $showtime->gia + ($s->seat_type->them_gia),
                'is_booked' => in_array($s->id, $bookedSeats),
            ];

        }
        return response()->json([
            'status' => 'success',
            'message' => 'Lấy thông tin suất chiếu thành công',
            'data' => [
                'showtime' => $showtime,
                'seats' => $seatsByRow,
                'booked_seats' => $bookedSeats,
            ]
        ]);
    }

    public function holdSeat(Request $request)
    {
        $request->validate([
            'suat_chieu_id' => 'required',
            'ghe_id' => 'required',
        ]);
        $userId = auth()->id();
        $key = "holding_showtime_{$request->suat_chieu_id}_seat_{$request->ghe_id}";
        if (Cache::has($key) && Cache::get($key) !== $userId) {
            return response()->json(['status' => 'error', 'message' => 'Ghế đang được giữ bởi người khác']);

        }
        Cache::put($key, $userId, now()->addMinutes(10));
        broadcast(new \App\Events\SeatStatusChanged($request->suat_chieu_id, $request->ghe_id, 'holding'))->toOthers();
        return response()->json(['status' => 'holding', 'message' => 'Giữ ghế thành công']);
    }

    public function getCombos()
    {
        $product = Products::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Lấy thông tin combo thành công',
            'data' => $product
        ]);
    }
}
