<?php
 
namespace App\Services\Booking;
 
use App\Models\Showtime;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Quản lý giữ ghế tạm thời bằng Cache.
 * Đảm bảo chỉ 1 người dùng giữ 1 ghế tại 1 thời điểm.
 */
class SeatHoldService
{
    private const HOLD_TTL_MINUTES = 10;

    /**
     * Tạo cache key cho seat hold.
     */
    public function buildCacheKey(int $showtimeId, int $seatId): string
    {
        return "seat_hold:showtime_{$showtimeId}:seat_{$seatId}";
    }

    /**
     * Giữ ghế cho người dùng hiện tại.
     * Trả về thời gian hết hạn.
     */
    public function hold(Showtime $showtime, int $seatId, int $userId): \Carbon\Carbon
    {
        // Kiểm tra xem ghế đã có trong OrderItem nào đã thanh toán (Paid) chưa
        $isBookedInDb = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.showtime_id', $showtime->id)
            ->where('order_items.item_type', 'ticket')
            ->where('order_items.item_id', $seatId)
            ->whereIn('orders.status', [Order::STATUS_PAID, Order::STATUS_PENDING])
            ->where('orders.expired_at', '>', now())
            ->exists();

        if ($isBookedInDb) {
            throw ValidationException::withMessages([
                'seat_id' => ['Ghế đã được đặt hoặc đang trong quá trình thanh toán.'],
            ]);
        }

        $holdKey = $this->buildCacheKey($showtime->id, $seatId);
        $currentHolder = Cache::get($holdKey);

        if ($currentHolder !== null && $currentHolder !== $userId) {
            throw ValidationException::withMessages([
                'seat_id' => ['Ghế đang được giữ bởi người khác.'],
            ]);
        }

        $expiresAt = now()->addMinutes(self::HOLD_TTL_MINUTES);
        Cache::put($holdKey, $userId, $expiresAt);

        return $expiresAt;
    }

    /**
     * Kiểm tra khách hàng có đang giữ ghế không.
     *
     * @throws ValidationException
     */
    public function assertHeldBy(int $showtimeId, int $seatId, int $customerId): void
    {
        $holdKey = $this->buildCacheKey($showtimeId, $seatId);
        $holder  = Cache::get($holdKey);

        if ($holder !== $customerId) {
            throw ValidationException::withMessages([
                'seats' => ['Có ghế không còn được giữ hợp lệ. Vui lòng chọn lại.'],
            ]);
        }
    }

    /**
     * Giải phóng tất cả giữ ghế của 1 nhóm ghế.
     */
    public function releaseAll(int $showtimeId, array $seatIds): void
    {
        foreach ($seatIds as $seatId) {
            Cache::forget($this->buildCacheKey($showtimeId, (int) $seatId));
        }
    }
}
