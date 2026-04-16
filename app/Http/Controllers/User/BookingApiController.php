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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BookingApiController extends Controller
{
    protected function customer()
    {
        return auth('customer')->user();
    }

    protected function unauthorizedResponse()
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Vui long dang nhap de tiep tuc.',
        ], 401);
    }

    protected function transformPromotion($promotion): array
    {
        return [
            'id' => $promotion->id,
            'code' => $promotion->ma_khuyen_mai,
            'name' => $promotion->ten_khuyen_mai,
            'discount_type' => $promotion->loai_giam_gia,
            'discount_value' => (float) $promotion->gia_tri_giam,
            'minimum_order_amount' => (float) $promotion->don_toi_thieu,
            'expires_at' => optional($promotion->ngay_ket_thuc)->toISOString(),
        ];
    }

    public function showShowtime(Showtimes $showtime)
    {
        $showtime->load(['room', 'movie']);

        $bookedSeats = Tickets::query()
            ->where('suat_chieu_id', $showtime->id)
            ->where('trang_thai', '!=', 'cancelled')
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
            ->where('trang_thai', '!=', 'cancelled')
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

    public function registerPromotion(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'password' => ['required', 'min:8', 'string'],
        ]);

        $user = $this->customer();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if (!Hash::check($request->password, $user->mat_khau)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mat khau khong dung.',
            ], 422);
        }

        $promotion = Promotions::query()
            ->where('ma_khuyen_mai', $request->string('code'))
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

        $existing = $user->promotions()
            ->where('promotions.id', $promotion->id)
            ->first();

        if ($existing) {
            if ((int) $existing->pivot->trang_thai === 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ma nay da duoc su dung.',
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Ma da co san trong tai khoan.',
                'data' => $this->transformPromotion($promotion),
            ]);
        }

        $user->promotions()->attach($promotion->id, [
            'trang_thai' => 0,
            'so_lan_da_dung' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dang ky ma thanh cong.',
            'data' => $this->transformPromotion($promotion),
        ]);
    }

    public function validatePromotion(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $this->customer();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $promotion = $user->promotions()
            ->where('promotions.ma_khuyen_mai', $request->code)
            ->wherePivot('trang_thai', 0)
            ->first();

        if (!$promotion) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ma khong hop le hoac da su dung.',
            ], 404);
        }

        if (now()->lt($promotion->ngay_bat_dau) || now()->gt($promotion->ngay_ket_thuc)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ma khuyen mai da het han.',
            ], 422);
        }

        $totalAmount = (float) $request->total_amount;

        if ($totalAmount < (float) $promotion->don_toi_thieu) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chua dat gia tri don hang toi thieu.',
            ], 422);
        }

        $discountValue = $promotion->loai_giam_gia === 'phan_tram'
            ? ($totalAmount * $promotion->gia_tri_giam / 100)
            : (float) $promotion->gia_tri_giam;

        $finalDiscountValue = min($discountValue, $totalAmount);

        return response()->json([
            'status' => 'success',
            'data' => [
                'promotion_id' => $promotion->id,
                'discount_type' => $promotion->loai_giam_gia,
                'discount_value' => $finalDiscountValue,
                'discount_amount' => $finalDiscountValue,
                'code' => $promotion->ma_khuyen_mai,
            ],
        ]);
    }

    public function registeredPromotions()
    {
        $user = $this->customer();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $promotions = $user->promotions()
            ->where('promotions.trang_thai', true)
            ->where('promotions.ngay_bat_dau', '<=', now())
            ->where('promotions.ngay_ket_thuc', '>=', now())
            ->wherePivot('trang_thai', 0)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $promotions->map(fn ($promotion) => $this->transformPromotion($promotion)),
        ]);
    }

    public function checkout(Request $request, Showtimes $showtime)
    {
        $request->validate([
            'seats' => ['required', 'array', 'min:1'],
            'seats.*' => ['integer'],
            'promotion_id' => ['nullable', 'integer'],
        ]);

        $user = $this->customer();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        return DB::transaction(function () use ($request, $user, $showtime) {
            $promotion = null;

            if ($request->promotion_id) {
                $promotion = $user->promotions()
                    ->where('promotions.id', $request->promotion_id)
                    ->lockForUpdate()
                    ->first();

                if (!$promotion || (int) $promotion->pivot->trang_thai === 1) {
                    throw new \Exception('Ma khuyen mai khong hop le hoac da su dung.');
                }
            }

            foreach ($request->seats as $seatId) {
                Tickets::create([
                    'suat_chieu_id' => $showtime->id,
                    'ghe_id' => $seatId,
                    'user_id' => $user->id,
                    'trang_thai' => 'booked',
                ]);
            }

            if ($promotion) {
                $user->promotions()->updateExistingPivot($promotion->id, [
                    'trang_thai' => 1,
                    'so_lan_da_dung' => DB::raw('so_lan_da_dung + 1'),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Dat ve thanh cong.',
            ]);
        });
    }

    public function showMyLoyaltyPoints()
    {
        $user = $this->customer();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'points' => $user->diem_tich_luy ?? 0,
            ],
        ]);
    }
}
