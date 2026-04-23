<?php
 
namespace App\Services\Payment;

use App\Exceptions\InsufficientStockException;
use App\Models\User;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\OrderItem;
use App\Models\Order;
use App\Services\Booking\SeatHoldService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Tính toán giá đơn hàng: ghế, sản phẩm, voucher, điểm tích lũy.
 */
class PricingService
{
    public function __construct(
        private readonly SeatHoldService $seatHoldService
    ) {}

    /**
     * Tính toán toàn bộ giá đơn hàng và trả về pricing snapshot.
     */
    public function buildSnapshot(
        User      $user,
        Showtime  $showtime,
        array     $seatRequests,
        array     $productRequests,
        ?string   $voucherCode,
        int       $pointsRequested
    ): array {
        $seatPricing    = $this->resolveSeatPricing($user, $showtime, $seatRequests);
        $productPricing = $this->resolveProductPricing($productRequests);

        $subtotal = round($seatPricing['subtotal'] + $productPricing['subtotal'], 2);

        $voucher              = $this->resolveVoucher($user, $voucherCode, $subtotal);
        $remainingAfterVoucher = max($subtotal - $voucher['discount_amount'], 0);
        $pointsUsed           = $this->resolvePointsUsage($user, $pointsRequested, $remainingAfterVoucher);

        $discountAmount = round($voucher['discount_amount'] + $pointsUsed, 2);
        $finalAmount    = round(max($subtotal - $discountAmount, 0), 2);

        if ($finalAmount < 1 && $subtotal > 0) {
            throw ValidationException::withMessages([
                'points_used' => ['Tổng thanh toán phải lớn hơn hoặc bằng 1.'],
            ]);
        }

        return [
            'subtotal'        => $subtotal,
            'discount_amount' => $discountAmount,
            'voucher_discount' => $voucher['discount_amount'],
            'point_discount'  => (float) $pointsUsed,
            'points_used'     => $pointsUsed,
            'final_amount'    => $finalAmount,
            'voucher'         => $voucher['data'],
            'seats'           => $seatPricing['items'],
            'products'        => $productPricing['items'],
        ];
    }

    /**
     * Xác định giá ghế và kiểm tra seat hold.
     */
    private function resolveSeatPricing(User $user, Showtime $showtime, array $seatRequests): array
    {
        $seatIds = collect($seatRequests)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $seats = Seat::query()
            ->where('screen_id', $showtime->screen_id)
            ->whereIn('id', $seatIds)
            ->with('seatType')
            ->get()
            ->keyBy('id');

        if ($seats->count() !== $seatIds->count()) {
            throw ValidationException::withMessages([
                'seats' => ['Danh sách ghế không hợp lệ với suất chiếu này.'],
            ]);
        }

        // Kiểm tra ghế đã bán
        $bookedSeatIds = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.showtime_id', $showtime->id)
            ->where('order_items.item_type', 'ticket')
            ->whereIn('order_items.item_id', $seatIds)
            ->whereIn('orders.status', [Order::STATUS_PAID, Order::STATUS_PENDING])
            ->where('orders.expired_at', '>', now())
            ->pluck('order_items.item_id');

        if ($bookedSeatIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'seats' => ['Có ghế đã được đặt hoặc đang giữ bởi người khác.'],
            ]);
        }

        $items    = [];
        $subtotal = 0.0;

        foreach ($seatIds as $seatId) {
            $seat = $seats->get($seatId);
            $this->seatHoldService->assertHeldBy($showtime->id, $seatId, $user->id);

            $price     = round((float) $showtime->price + (float) ($seat->seatType?->surcharge ?? 0), 2);
            $subtotal += $price;

            $items[] = [
                'id'    => $seat->id,
                'name'  => $seat->label ?? ($seat->row . $seat->number),
                'price' => $price,
            ];
        }

        return [
            'items'    => $items,
            'subtotal' => round($subtotal, 2),
        ];
    }

    /**
     * Xác định sản phẩm và kiểm tra tồn kho.
     */
    private function resolveProductPricing(array $productRequests): array
    {
        $grouped = collect($productRequests)
            ->groupBy(fn (array $item) => (int) $item['id'])
            ->map(fn (Collection $items, int $productId) => [
                'id'  => $productId,
                'qty' => $items->sum(fn (array $item) => (int) $item['quantity']),
            ])
            ->values();

        if ($grouped->isEmpty()) {
            return ['items' => [], 'subtotal' => 0.0];
        }

        $products = Product::query()
            ->whereIn('id', $grouped->pluck('id'))
            ->get()
            ->keyBy('id');

        if ($products->count() !== $grouped->count()) {
            throw ValidationException::withMessages([
                'products' => ['Danh sách sản phẩm không hợp lệ.'],
            ]);
        }

        $items    = [];
        $subtotal = 0.0;

        foreach ($grouped as $requested) {
            $product = $products->get($requested['id']);

            if ((int) $product->stock < $requested['qty']) {
                throw ValidationException::withMessages([
                    'products' => ["Sản phẩm \"{$product->name}\" không đủ số lượng trong kho."],
                ]);
            }

            $price     = round((float) $product->price, 2);
            $subtotal += $price * $requested['qty'];

            $items[] = [
                'id'    => $product->id,
                'name'  => $product->name,
                'qty'   => $requested['qty'],
                'price' => $price,
            ];
        }

        return [
            'items'    => $items,
            'subtotal' => round($subtotal, 2),
        ];
    }

    /**
     * Xác minh và tính giảm giá từ voucher.
     */
    private function resolveVoucher(User $user, ?string $voucherCode, float $subtotal): array
    {
        if (! $voucherCode) {
            return ['data' => null, 'discount_amount' => 0.0];
        }

        $voucher = $user->promotions()
            ->where('promotions.code', $voucherCode)
            ->where('promotions.status', true)
            ->where('promotions.start_date', '<=', now())
            ->where('promotions.end_date', '>=', now())
            ->wherePivot('status', 1) // 1: Available
            ->first();

        if (! $voucher instanceof Promotion) {
            throw ValidationException::withMessages([
                'voucher_code' => ['Voucher không hợp lệ hoặc đã được sử dụng.'],
            ]);
        }

        if ($subtotal < (float) $voucher->min_order_value) {
            throw ValidationException::withMessages([
                'voucher_code' => ['Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher.'],
            ]);
        }

        $discount = $voucher->discount_type === 'percentage'
            ? ($subtotal * (float) $voucher->discount_value / 100)
            : (float) $voucher->discount_value;
            
        // Nếu có giới hạn giảm tối đa
        if ($voucher->max_discount_amount > 0 && $discount > $voucher->max_discount_amount) {
            $discount = $voucher->max_discount_amount;
        }

        return [
            'data' => [
                'id'    => $voucher->id,
                'code'  => $voucher->code,
                'type'  => $voucher->discount_type,
                'value' => (float) $voucher->discount_value,
            ],
            'discount_amount' => round(min($discount, $subtotal), 2),
        ];
    }

    /**
     * Kiểm tra và xác nhận số điểm muốn sử dụng.
     */
    private function resolvePointsUsage(User $user, int $requested, float $maxDiscount): int
    {
        if ($requested === 0) {
            return 0;
        }

        if ($requested > (int) $user->loyalty_points) {
            throw ValidationException::withMessages([
                'points_used' => ['Bạn không đủ điểm tích lũy.'],
            ]);
        }

        if ($requested > (int) floor($maxDiscount)) {
            $requested = (int) floor($maxDiscount);
        }

        return $requested;
    }
}
