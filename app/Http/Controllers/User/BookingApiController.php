<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\Promotions;
use App\Models\Seats;
use App\Models\Showtimes;
use App\Models\Tickets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BookingApiController extends Controller
{
    public function showShowtime(Showtimes $showtime)
    {
        $showtime->load(['room', 'movie']);

        $bookedSeats = Tickets::query()
            ->where('suat_chieu_id', $showtime->id)
            ->where('trang_thai', '!=', 'da_huy')
            ->pluck('ghe_id')
            ->toArray();

        $seats = Seats::query()
            ->where('room_id', $showtime->room_id)
            ->with('seat_type')
            ->orderBy('hang_ghe')
            ->orderBy('so_ghe')
            ->get();

        $seatsByRow = [];
        foreach ($seats as $seat) {
            $row = $seat->hang_ghe;

            if (!isset($seatsByRow[$row])) {
                $seatsByRow[$row] = [];
            }

            $seatsByRow[$row][] = [
                'id' => $seat->id,
                'ma' => $seat->ma,
                'hang_ghe' => $seat->hang_ghe,
                'so_ghe' => $seat->so_ghe,
                'loai_ghe' => $seat->seat_type->ten ?? null,
                'gia_ghe' => (float) ($showtime->gia ?? 0) + (float) ($seat->seat_type->them_gia ?? 0),
                'is_booked' => in_array($seat->id, $bookedSeats, true),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'showtime' => $showtime,
                'seats' => $seatsByRow,
                'booked_seats' => $bookedSeats,
            ],
        ]);
    }

    public function storeSeatHold(Request $request, Showtimes $showtime)
    {
        $request->validate([
            'ghe_id' => ['required', 'integer'],
        ]);

        $seat = Seats::query()
            ->where('id', $request->integer('ghe_id'))
            ->where('room_id', $showtime->room_id)
            ->first();

        if (!$seat) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ghe khong hop le voi suat chieu nay.',
            ], 422);
        }

        $isBooked = Tickets::query()
            ->where('suat_chieu_id', $showtime->id)
            ->where('ghe_id', $seat->id)
            ->where('trang_thai', '!=', 'da_huy')
            ->exists();

        if ($isBooked) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ghe da duoc dat.',
            ], 409);
        }

        $userId = Auth::id();
        $holdKey = "holding_showtime_{$showtime->id}_seat_{$seat->id}";
        $expiresAt = now()->addMinutes(10);

        if (Cache::has($holdKey) && Cache::get($holdKey) !== $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ghe dang duoc giu boi nguoi khac.',
            ], 409);
        }

        Cache::put($holdKey, $userId, $expiresAt);

        return response()->json([
            'status' => 'success',
            'message' => 'Giu ghe thanh cong.',
            'data' => [
                'showtime_id' => $showtime->id,
                'seat_id' => $seat->id,
                'expires_at' => $expiresAt->toISOString(),
            ],
        ]);
    }

    public function indexProducts()
    {
        return response()->json([
            'status' => 'success',
            'data' => Products::all(),
        ]);
    }

    public function indexMyVouchers()
    {
        $vouchers = Auth::user()
            ->promotions()
            ->where('promotions.trang_thai', true)
            ->where('promotions.ngay_bat_dau', '<=', now())
            ->where('promotions.ngay_ket_thuc', '>=', now())
            ->wherePivot('trang_thai', 0)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $vouchers->map(function ($voucher) {
                return [
                    'id' => $voucher->id,
                    'code' => $voucher->ma_khuyen_mai,
                    'name' => $voucher->ten_khuyen_mai,
                    'discount_type' => $voucher->loai_giam_gia,
                    'discount_value' => (float) $voucher->gia_tri_giam,
                    'minimum_order_amount' => (float) $voucher->don_toi_thieu,
                    'expires_at' => optional($voucher->ngay_ket_thuc)->toISOString(),
                ];
            }),
        ]);
    }

    public function validatePromotion(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $promotion = Promotions::query()
            ->where('ma_khuyen_mai', $request->string('code')->toString())
            ->where('trang_thai', true)
            ->first();

        if (!$promotion) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ma khuyen mai khong ton tai.',
            ], 404);
        }

        if (now()->lt($promotion->ngay_bat_dau) || now()->gt($promotion->ngay_ket_thuc)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ma khuyen mai da het han hoac chua co hieu luc.',
            ], 422);
        }

        $totalAmount = (float) $request->input('total_amount');
        if ($totalAmount < (float) $promotion->don_toi_thieu) {
            return response()->json([
                'status' => 'error',
                'message' => 'Don hang chua dat gia tri toi thieu.',
            ], 422);
        }

        $isUsed = Auth::user()
            ->promotions()
            ->where('promotions.id', $promotion->id)
            ->wherePivot('trang_thai', 1)
            ->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ban da su dung ma nay.',
            ], 422);
        }

        $discountValue = $promotion->loai_giam_gia === 'phan_tram'
            ? ($totalAmount * (float) $promotion->gia_tri_giam / 100)
            : (float) $promotion->gia_tri_giam;

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $promotion->id,
                'code' => $promotion->ma_khuyen_mai,
                'type' => $promotion->loai_giam_gia,
                'discount_value' => min($discountValue, $totalAmount),
            ],
        ]);
    }

    public function showMyLoyaltyPoints()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'points' => Auth::user()->diem_tich_luy ?? 0,
            ],
        ]);
    }
}
