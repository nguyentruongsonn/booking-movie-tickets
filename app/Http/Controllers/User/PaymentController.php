<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use App\Models\InvoiceDetails;
use App\Models\Invoices;
use App\Models\Orders;
use App\Models\Products;
use App\Models\Tickets;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PayOS\PayOS;
use Throwable;

class PaymentController extends Controller
{
    private const LOYALTY_RATE = 0.05;

    private function getPayOS(): PayOS
    {
        return new PayOS(
            env('PAYOS_CLIENT_ID'),
            env('PAYOS_API_KEY'),
            env('PAYOS_CHECKSUM_KEY'),
        );
    }

    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'suat_chieu_id' => ['required', 'integer', 'exists:showtimes,id'],
            'seats' => ['required', 'array', 'min:1'],
            'seats.*.id' => ['required', 'integer', 'exists:seats,id'],
            'seats.*.name' => ['nullable', 'string', 'max:20'],
            'seats.*.price' => ['required', 'numeric', 'min:0'],
            'products' => ['nullable', 'array'],
            'products.*.id' => ['required_with:products', 'integer', 'exists:products,id'],
            'products.*.name' => ['nullable', 'string', 'max:255'],
            'products.*.qty' => ['required_with:products', 'integer', 'min:1'],
            'products.*.price' => ['required_with:products', 'numeric', 'min:0'],
            'voucher_id' => ['nullable', 'integer', 'exists:promotions,id'],
            'point_used' => ['nullable', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $customer = $request->user();
        if (! $customer instanceof Customers) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vui long dang nhap',
            ], 401);
        }

        try {
            $order = DB::transaction(function () use ($validated, $customer) {
                return Orders::create([
                    'ma_don_hang' => $this->generateOrderNumber(),
                    'order_code' => $this->generateOrderCode(),
                    'customer_id' => $customer->id,
                    'suat_chieu_id' => $validated['suat_chieu_id'],
                    'tong_tien' => $validated['amount'],
                    'payload' => [
                        'amount' => (int) $validated['amount'],
                        'seats' => $validated['seats'],
                        'products' => $validated['products'] ?? [],
                        'voucher' => $validated['voucher_id'] ?? null,
                        'points' => $validated['point_used'] ?? 0,
                        'discount_amount' => (float) ($validated['discount_amount'] ?? 0),
                    ],
                    'trang_thai' => 'pending',
                ]);
            });

            $response = $this->getPayOS()->createPaymentLink([
                'orderCode' => $order->order_code,
                'amount' => (int) $order->tong_tien,
                'description' => substr('DH ' . $order->ma_don_hang, 0, 25),
                'cancelUrl' => url('/bookings/' . $order->suat_chieu_id . '?paymentStatus=cancelled&orderCode=' . $order->order_code),
                'returnUrl' => url('/bookings/' . $order->suat_chieu_id . '?paymentStatus=success&orderCode=' . $order->order_code),
                'items' => [[
                    'name' => 'Don hang ' . $order->ma_don_hang,
                    'quantity' => 1,
                    'price' => (int) $order->tong_tien,
                ]],
            ]);

            return response()->json([
                'status' => 'success',
                'checkoutUrl' => $response['checkoutUrl'],
                'orderCode' => $order->order_code,
                'ma_don_hang' => $order->ma_don_hang,
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loi xu ly thanh toan: ' . $exception->getMessage(),
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        try {
            $webhookData = $this->getPayOS()->verifyPaymentWebhookData($request->all());
            $orderCode = $webhookData['orderCode'] ?? null;
            $status = strtoupper((string) ($webhookData['status'] ?? ''));

            if (! $orderCode) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Thieu orderCode',
                ], 400);
            }

            if ($status !== 'PAID') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Bo qua webhook khong phai thanh toan thanh cong',
                ]);
            }

            $result = $this->finalizePaidOrder((int) $orderCode);

            return response()->json([
                'status' => 'success',
                'message' => $result['already_processed']
                    ? 'Don hang da duoc xu ly truoc do'
                    : 'Thanh toan thanh cong',
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function showOrderSummary(Request $request, int $orderCode)
    {
        $customer = $request->user();
        if (! $customer instanceof Customers) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vui long dang nhap',
            ], 401);
        }

        $order = Orders::where('order_code', $orderCode)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khong tim thay don hang',
            ], 404);
        }

        if ($order->trang_thai !== 'paid') {
            $this->syncOrderFromPayOS($order);
        }

        $order->refresh()->load([
            'showtime.movie',
            'showtime.room',
            'tickets.seat',
            'invoice.invoiceDetails.product',
        ]);

        $invoice = $order->invoice;

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_code' => $order->order_code,
                'ma_don_hang' => $order->ma_don_hang,
                'trang_thai' => $order->trang_thai,
                'tong_tien' => (float) $order->tong_tien,
                'showtime' => [
                    'id' => $order->suat_chieu_id,
                    'movie_name' => $order->showtime?->movie?->ten_phim,
                    'room_name' => $order->showtime?->room?->ten_phong,
                    'ngay_gio_chieu' => optional($order->showtime?->ngay_gio_chieu)->toIso8601String(),
                ],
                'invoice' => $invoice ? [
                    'ma_hoa_don' => $invoice->ma_hoa_don,
                    'tong_tien_goc' => (float) $invoice->tong_tien_goc,
                    'giam_gia' => (float) $invoice->giam_gia,
                    'tong_tien' => (float) $invoice->tong_tien,
                    'diem_su_dung' => (int) $invoice->diem_su_dung,
                    'diem_tich_luy' => (int) $invoice->diem_tich_luy,
                    'ngay_lap' => optional($invoice->ngay_lap)->toIso8601String(),
                ] : null,
                'tickets' => $order->tickets->map(fn (Tickets $ticket) => [
                    'ma_ve' => $ticket->ma_ve,
                    'ghe' => $ticket->seat?->hang_ghe . $ticket->seat?->so_ghe,
                    'gia_ban' => (float) $ticket->gia_ban,
                ])->values(),
                'products' => $invoice
                    ? $invoice->invoiceDetails->map(fn (InvoiceDetails $detail) => [
                        'ten_san_pham' => $detail->product?->ten_san_pham,
                        'so_luong' => (int) $detail->so_luong,
                        'don_gia' => (float) $detail->don_gia,
                    ])->values()
                    : [],
            ],
        ]);
    }

    private function syncOrderFromPayOS(Orders $order): void
    {
        try {
            $paymentInfo = $this->getPayOS()->getPaymentLinkInformation($order->order_code);
            $paymentStatus = strtoupper((string) ($paymentInfo['status'] ?? ''));

            if ($paymentStatus === 'PAID') {
                $this->finalizePaidOrder((int) $order->order_code);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function finalizePaidOrder(int $orderCode): array
    {
        return DB::transaction(function () use ($orderCode) {
            $order = Orders::where('order_code', $orderCode)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new Exception('Khong tim thay don hang');
            }

            if ($order->trang_thai === 'paid') {
                return ['already_processed' => true];
            }

            $payload = $order->payload ?? [];
            $pointsUsed = (int) ($payload['points'] ?? 0);
            $earnedPoints = $this->calculateEarnedPoints((float) $order->tong_tien);
            $discountAmount = max(0, (float) ($payload['discount_amount'] ?? 0));
            $voucherId = $payload['voucher'] ?? null;

            $invoice = Invoices::create([
                'ma_hoa_don' => $this->generateInvoiceNumber($order->order_code),
                'order_id' => $order->id,
                'khach_hang_id' => $order->customer_id,
                'nhan_vien_id' => null,
                'khuyen_mai_id' => $voucherId,
                'suat_chieu_id' => $order->suat_chieu_id,
                'ngay_lap' => now(),
                'tong_tien_goc' => $order->tong_tien + $discountAmount,
                'giam_gia' => $discountAmount,
                'tong_tien' => $order->tong_tien,
                'diem_su_dung' => $pointsUsed,
                'diem_tich_luy' => $earnedPoints,
                'phuong_thuc_thanh_toan' => 'vi_dien_tu',
                'trang_thai' => 'da_thanh_toan',
            ]);

            foreach ($payload['seats'] ?? [] as $seat) {
                Tickets::create([
                    'ma_ve' => $this->generateTicketNumber(),
                    'suat_chieu_id' => $order->suat_chieu_id,
                    'ghe_id' => $seat['id'],
                    'khach_hang_id' => $order->customer_id,
                    'order_id' => $order->id,
                    'hoa_don_id' => $invoice->id,
                    'gia_goc' => $seat['price'],
                    'gia_ban' => $seat['price'],
                    'trang_thai' => 'paid',
                    'ngay_gio_dat' => now(),
                ]);
            }

            foreach ($payload['products'] ?? [] as $item) {
                InvoiceDetails::create([
                    'hoa_don_id' => $invoice->id,
                    'san_pham_id' => $item['id'],
                    'so_luong' => $item['qty'],
                    'don_gia' => $item['price'],
                ]);

                Products::whereKey($item['id'])->decrement('so_luong_ton', $item['qty']);
            }

            $customer = Customers::find($order->customer_id);
            if ($customer) {
                if ($pointsUsed > 0) {
                    $customer->decrement('diem_tich_luy', $pointsUsed);
                }

                if ($earnedPoints > 0) {
                    $customer->increment('diem_tich_luy', $earnedPoints);
                }
            }

            if ($voucherId) {
                DB::table('customer_promotion')
                    ->where('customer_id', $order->customer_id)
                    ->where('promotion_id', $voucherId)
                    ->update([
                        'trang_thai' => 1,
                        'ngay_su_dung' => now(),
                        'so_lan_da_dung' => DB::raw('so_lan_da_dung + 1'),
                    ]);
            }

            $order->update(['trang_thai' => 'paid']);

            return ['already_processed' => false];
        });
    }

    private function calculateEarnedPoints(float $amount): int
    {
        return (int) floor($amount * self::LOYALTY_RATE);
    }

    private function generateOrderNumber(): string
    {
        do {
            $value = 'DH' . now()->format('YmdHis') . random_int(10, 99);
        } while (Orders::where('ma_don_hang', $value)->exists());

        return $value;
    }

    private function generateOrderCode(): int
    {
        do {
            $value = (int) (now()->format('ymdHis') . random_int(10, 99));
        } while (Orders::where('order_code', $value)->exists());

        return $value;
    }

    private function generateInvoiceNumber(int $orderCode): string
    {
        return 'HD' . $orderCode;
    }

    private function generateTicketNumber(): string
    {
        do {
            $value = 'VE' . strtoupper(bin2hex(random_bytes(5)));
        } while (Tickets::where('ma_ve', $value)->exists());

        return $value;
    }
}
