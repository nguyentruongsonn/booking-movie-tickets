<?php
 
namespace App\Services\Payment;
 
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Loyalty\LoyaltyService;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\SeatAlreadyBookedException;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Quy trình xác nhận & hoàn tất đơn hàng đã thanh toán.
 * Cập nhật trạng thái, tạo OrderItems, ghi nhận Payment, cộng điểm.
 */
class OrderFulfillmentService
{
    public function __construct(private readonly LoyaltyService $loyaltyService)
    {
    }

    /**
     * Hoàn tất xử lý đơn hàng đã thanh toán (idempotent).
     */
    public function finalize(int $gatewayOrderCode): array
    {
        return DB::transaction(function () use ($gatewayOrderCode) {
            /** @var Order|null $order */
            $order = Order::where('gateway_order_code', $gatewayOrderCode)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new Exception('Không tìm thấy đơn hàng.');
            }

            // Idempotency: đã xử lý rồi thì bỏ qua
            if ($order->status === Order::STATUS_PAID) {
                return ['already_processed' => true];
            }

            $payload        = $order->payload ?? [];
            $seatItems      = collect($payload['seats'] ?? []);
            $productItems   = collect($payload['products'] ?? []);
            $pointsUsed     = (int) ($payload['points'] ?? 0);
            $discountAmount = max(0, (float) ($payload['discount_amount'] ?? 0));
            $earnedPoints   = $this->loyaltyService->calculateEarnedPoints((float) $order->total_amount, 0.05);

            // Kiểm tra ghế còn trống (Dựa trên OrderItems hiện có hoặc bảng Tickets cũ nay là item_type='ticket')
            $this->assertSeatsAvailable($order->showtime_id, $seatItems);
            // Kiểm tra tồn kho sản phẩm
            $this->assertProductsInStock($productItems);

            // 1. Ghi nhận giao dịch thanh toán
            Payment::create([
                'order_id'         => $order->id,
                'method'           => $order->payment_provider ?? 'payos',
                'transaction_code' => (string) $gatewayOrderCode,
                'amount'           => $order->total_amount,
                'status'           => Payment::STATUS_COMPLETED,
                'payload'          => $payload,
                'paid_at'          => now(),
            ]);

            // 2. Tạo Order Items (nếu chưa có trong DB)
            // Lưu ý: Trong hệ thống hiện đại, nên tạo Items ngay khi đặt, ở đây ta tạo lúc finalize cho tương thích code cũ
            foreach ($seatItems as $seat) {
                OrderItem::create([
                    'order_id'    => $order->id,
                    'item_type'   => 'ticket',
                    'item_id'     => $seat['id'], // seat_id
                    'quantity'    => 1,
                    'unit_price'  => $seat['price'],
                    'total_price' => $seat['price'],
                    'metadata'    => [
                        'label' => $seat['name'] ?? '',
                        'ticket_code' => $this->generateTicketCode(),
                    ],
                ]);
            }

            foreach ($productItems as $item) {
                /** @var Product|null $product */
                $product = Product::query()->lockForUpdate()->find($item['id']);

                if (! $product || $product->stock < $item['qty']) {
                    throw new InsufficientStockException();
                }

                OrderItem::create([
                    'order_id'    => $order->id,
                    'item_type'   => 'product',
                    'item_id'     => $item['id'],
                    'quantity'    => $item['qty'],
                    'unit_price'  => $item['price'],
                    'total_price' => $item['price'] * $item['qty'],
                    'metadata'    => ['name' => $item['name'] ?? ''],
                ]);

                $product->decrement('stock', $item['qty']);
            }

            // 3. Cập nhật điểm tích lũy & Sổ cái (Loyalty)
            /** @var User|null $user */
            $user = User::find($order->user_id);
            if ($user) {
                if ($pointsUsed > 0) {
                    $this->loyaltyService->redeemPoints($user, $pointsUsed);
                }
                if ($earnedPoints > 0) {
                    $this->loyaltyService->addPoints($user, $earnedPoints);
                }
            }

            // 4. Đánh dấu voucher đã dùng
            $voucherId = data_get($payload, 'voucher.id');
            if ($voucherId) {
                DB::table('user_promotion')
                    ->where('user_id', $order->user_id)
                    ->where('promotion_id', $voucherId)
                    ->update([
                        'status'      => 0, // Used
                        'used_at'     => now(),
                        'order_id'    => $order->id,
                        'usage_count' => DB::raw('usage_count + 1'),
                    ]);
            }

            // 5. Cập nhật trạng thái đơn hàng
            $order->update([
                'status'         => Order::STATUS_PAID,
                'payment_status' => 'paid',
                'paid_at'        => now(),
            ]);

            return ['already_processed' => false];
        });
    }

    private function assertSeatsAvailable(int $showtimeId, Collection $seatItems): void
    {
        if ($seatItems->isEmpty()) {
            throw new Exception('Đơn hàng không có ghế hợp lệ.');
        }

        // Kiểm tra xem có bất kỳ OrderItem nào loại 'ticket' đã được thanh toán cho các ghế này chưa
        $bookedExists = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.showtime_id', $showtimeId)
            ->where('order_items.item_type', 'ticket')
            ->whereIn('order_items.item_id', $seatItems->pluck('id'))
            ->whereIn('orders.status', [Order::STATUS_PAID, Order::STATUS_PENDING])
            ->where('orders.expired_at', '>', now()) // Chỉ tính các order chưa hết hạn
            ->exists();

        if ($bookedExists) {
            throw new SeatAlreadyBookedException();
        }
    }

    private function assertProductsInStock(Collection $productItems): void
    {
        foreach ($productItems as $item) {
            $product = Product::query()->find($item['id']);
            if (! $product || $product->stock < $item['qty']) {
                throw new InsufficientStockException();
            }
        }
    }

    private function generateTicketCode(): string
    {
        return 'VE' . strtoupper(Str::random(8));
    }
}
