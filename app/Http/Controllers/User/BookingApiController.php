<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromotionResource;
use App\Http\Resources\ShowtimeResource;
use App\Traits\ApiResponse;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Seat;
use App\Models\Showtime;
use App\Services\Booking\SeatHoldService;
use App\Services\Promotion\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BookingApiController extends Controller
{
    use ApiResponse;
 
    public function __construct(
        private readonly SeatHoldService $seatHoldService,
        private readonly PromotionService $promotionService
    ) {}

    // -------------------------------------------------------------------------
    // Endpoints
    // -------------------------------------------------------------------------
 
    /**
     * Lấy thông tin suất chiếu và bản đồ ghế.
     * GET /api/showtimes/{showtime}
     */
    public function showShowtime(Showtime $showtime): JsonResponse
    {
        $showtime->load(['screen', 'movie']);
 
        // Lấy danh sách ghế đã được đặt (Paid hoặc Pending con thời hạn)
        $bookedSeatIds = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.showtime_id', $showtime->id)
            ->where('order_items.item_type', 'ticket')
            ->where(function($query) {
                $query->where('orders.status', Order::STATUS_PAID)
                      ->orWhere(function($q) {
                          $q->where('orders.status', Order::STATUS_PENDING)
                            ->where('orders.expired_at', '>', now());
                      });
            })
            ->pluck('order_items.item_id')
            ->toArray();
 
        $seats = Seat::query()
            ->where('screen_id', $showtime->screen_id)
            ->with('seatType')
            ->orderBy('row_index')
            ->orderBy('column_index')
            ->get();
 
        $seatsByRow = [];
        foreach ($seats as $seat) {
            $seatsByRow[$seat->row][] = [
                'id'           => $seat->id,
                'label'        => $seat->label,
                'row'          => $seat->row,
                'number'       => $seat->number,
                'row_index'    => $seat->row_index,
                'column_index' => $seat->column_index,
                'seat_type'    => $seat->seatType->name ?? null,
                'price'        => (float) ($showtime->price ?? 0) + (float) ($seat->seatType->surcharge ?? 0),
                'is_booked'    => in_array($seat->id, $bookedSeatIds, true),
            ];
        }
 
        return $this->ok([
            'showtime'     => new ShowtimeResource($showtime),
            'seats'        => $seatsByRow,
            'booked_seats' => $bookedSeatIds,
        ]);
    }
 
    /**
     * Giữ ghế tạm thời cho người dùng.
     * POST /api/showtimes/{showtime}/seat-holds
     */
    public function storeSeatHold(Request $request, Showtime $showtime): JsonResponse
    {
        $request->validate([
            'seat_id' => ['required', 'integer'],
        ]);
 
        $user = auth()->user();
        if (! $user) {
            return $this->unauthorized();
        }
 
        $seat = Seat::query()
            ->where('id', $request->integer('seat_id'))
            ->where('screen_id', $showtime->screen_id)
            ->first();
 
        if (! $seat) {
            return $this->error('Ghế không hợp lệ với suất chiếu này.', 422);
        }
 
        try {
            $expiresAt = $this->seatHoldService->hold($showtime, $seat->id, $user->id);
        } catch (ValidationException $e) {
            return $this->error(collect($e->errors())->flatten()->first(), 409);
        }
 
        return $this->ok([
            'showtime_id' => $showtime->id,
            'seat_id'     => $seat->id,
            'expires_at'  => $expiresAt->toISOString(),
        ], 'Giữ ghế thành công.');
    }
 
    /**
     * Lấy danh sách sản phẩm (combo/bắp nước).
     * GET /api/products
     */
    public function indexProducts(): JsonResponse
    {
        $products = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->get(['id', 'name', 'price', 'image_url', 'stock']);
 
        return $this->ok($products);
    }
 
    /**
     * Đăng ký mã khuyến mãi vào tài khoản.
     * POST /api/customer/register-promotion
     */
    public function registerPromotion(Request $request): JsonResponse
    {
        $request->validate([
            'code'     => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $user = auth()->user();
        if (! $user) {
            return $this->unauthorized();
        }

        try {
            $promotion = $this->promotionService->register(
                $user, 
                $request->string('code'), 
                $request->string('password')
            );
        } catch (ValidationException $e) {
            return $this->error(collect($e->errors())->flatten()->first(), 422);
        }

        return $this->created(new PromotionResource($promotion), 'Đăng ký mã thành công.');
    }
 
    public function validatePromotion(Request $request): JsonResponse
    {
        $request->validate([
            'code'  => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
        ]);

        $user = auth()->user();
        if (! $user) {
            return $this->unauthorized();
        }

        $totalAmount = 0;
        foreach ($request->array('items') as $item) {
            $totalAmount += (float) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 1);
        }

        try {
            $discountData = $this->promotionService->calculateDiscount($user, $request->string('code'), $totalAmount);
        } catch (ValidationException $e) {
            return $this->error(collect($e->errors())->flatten()->first(), 422);
        }

        return $this->ok($discountData);
    }
 
    public function registeredPromotions(): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return $this->unauthorized();
        }
 
        $promotions = $user->promotions()
            ->where('promotions.status', 1)
            ->where('promotions.start_date', '<=', now())
            ->where('promotions.end_date', '>=', now())
            ->wherePivot('status', 1)
            ->get();
 
        return $this->ok(PromotionResource::collection($promotions));
    }
 
    /**
     * Xem điểm tích lũy của khách hàng.
     * GET /api/customers/me/loyalty-points
     */
    public function showMyLoyaltyPoints(): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return $this->unauthorized();
        }
 
        return $this->ok([
            'points' => $user->loyalty_points ?? 0,
        ]);
    }
}
