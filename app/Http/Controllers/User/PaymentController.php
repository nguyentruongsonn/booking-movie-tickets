<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use App\Models\InvoiceDetails;
use App\Models\Invoices;
use App\Models\Orders;
use App\Models\Products;
use App\Models\Promotions;
use App\Models\Seats;
use App\Models\Showtimes;
use App\Models\Tickets;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PayOS\PayOS;
use Throwable;

class PaymentController extends Controller
{
    private const LOYALTY_RATE = 0.05;

    public function createPayment(Request $request)
    {
        //Kiểm tra người dùng đăng nhập
        $customer = $request->user();
        if (! $customer instanceof Customers) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vui long dang nhap',
            ], 401);
        }

        $validated = $request->validate([
            'suat_chieu_id' => ['required', 'integer', 'exists:showtimes,id'],
            'seats' => ['required', 'array', 'min:1'],
            'seats.*.id' => ['required', 'integer', 'distinct'],
            'products' => ['nullable', 'array'],
            'products.*.id' => ['required_with:products', 'integer'],
            'products.*.qty' => ['required_with:products', 'integer', 'min:1'],
            'voucher_id' => ['nullable', 'integer', 'exists:promotions,id'],
            'point_used' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $showtime = Showtimes::query()
                ->with('room')
                ->findOrFail($validated['suat_chieu_id']);
            //tính toán giá đơn hàng: tổng tiền, giảm giá, số điểm sử dụng, số tiền cuối cùng
            $pricing = $this->buildPricingSnapshot($customer, $showtime, $validated);

            $order = DB::transaction(function () use ($customer, $showtime, $pricing) {
                return Orders::create([
                    'ma_don_hang' => $this->generateOrderNumber(),
                    'order_code' => $this->generateOrderCode(),
                    'payment_provider' => 'payos',
                    'customer_id' => $customer->id,
                    'suat_chieu_id' => $showtime->id,
                    'tong_tien' => $pricing['final_amount'],
                    'payload' => [
                        'subtotal' => $pricing['subtotal'],
                        'discount_amount' => $pricing['discount_amount'],
                        'voucher_discount' => $pricing['voucher_discount'],
                        'point_discount' => $pricing['point_discount'],
                        'points' => $pricing['points_used'],
                        'voucher' => $pricing['voucher'],
                        'seats' => $pricing['seats'],
                        'products' => $pricing['products'],
                    ],
                    'trang_thai' => 'pending',
                    'payment_status' => 'pending',
                ]);
            });

            //gọi payostaoj link thanh toán
            $response = $this->getPayOS()->createPaymentLink([
                'orderCode' => $order->order_code,
                'amount' => (int) round($order->tong_tien),
                'description' => substr('DH ' . $order->ma_don_hang, 0, 25),
                'cancelUrl' => url('/bookings/' . $order->suat_chieu_id . '?paymentStatus=cancelled&orderCode=' . $order->order_code),
                'returnUrl' => url('/bookings/' . $order->suat_chieu_id . '?paymentStatus=success&orderCode=' . $order->order_code),
                'items' => [[
                    'name' => 'Don hang ' . $order->ma_don_hang,
                    'quantity' => 1,
                    'price' => (int) round($order->tong_tien),
                ]],
            ]);

            //luu lại link thanh toán
            $order->forceFill([
                'checkout_url' => $response['checkoutUrl'] ?? null,
                'payment_status' => 'pending',
            ])->save();

            return response()->json([
                'status' => 'success',
                'checkoutUrl' => $response['checkoutUrl'],
                'orderCode' => $order->order_code,
                'ma_don_hang' => $order->ma_don_hang,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loi xu ly thanh toan: ' . $exception->getMessage(),
            ], 500);
        }
    }

    //Nhận thông báo từ payos sau khi giao dịch được xử lý
    public function handleWebhook(Request $request)
    {
        try {
            //kiểm tra tính hợp lệ, chống giả mạo
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
                Orders::where('order_code', (int) $orderCode)
                    ->where('trang_thai', 'pending')
                    ->update([
                        'payment_status' => strtolower($status ?: 'failed'),
                    ]);

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

    //trả về thông tin chi tiết đơn hàng dạng json
    //hiển thị đơn hàng cho UI
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

        //nếu chưa paid goi hàm lấy thông tin để đồng bộ trạng thái từ payos (cập nhật thanh thánh, hủy, hết hạn)
        if ($order->trang_thai !== 'paid') {
            $this->syncOrderFromPayOS($order);
        }

        //lấy dữ liệu mới từ database
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
                        'ten_san_pham' => $detail->ten_san_pham ?: $detail->product?->ten_san_pham,
                        'so_luong' => (int) $detail->so_luong,
                        'don_gia' => (float) $detail->don_gia,
                    ])->values()
                    : [],
            ],
        ]);
    }

    //đồng bộ trạng thái đơn hàng từ payos

    private function syncOrderFromPayOS(Orders $order): void
    {
        try {
            //gọi api payos, lấy thông tin thanh toán theo ordercode
            $paymentInfo = $this->getPayOS()->getPaymentLinkInformation($order->order_code);
            $paymentStatus = strtoupper((string) ($paymentInfo['status'] ?? ''));

            //nếu đã trả gọi hàm để xử lý đơn hàng thanh toán: tạo hóa đơn, vé trừ kho
            if ($paymentStatus === 'PAID') {
                $this->finalizePaidOrder((int) $order->order_code);

            // chwua thì cập đơn hàng hàng thành huyr
            } elseif (in_array($paymentStatus, ['CANCELLED', 'EXPIRED'], true)) {
                $order->forceFill([
                    'trang_thai' => strtolower($paymentStatus) === 'cancelled' ? 'cancelled' : 'expired',
                    'payment_status' => strtolower($paymentStatus),
                    'cancelled_at' => strtolower($paymentStatus) === 'cancelled' ? now() : $order->cancelled_at,
                    'expired_at' => strtolower($paymentStatus) === 'expired' ? now() : $order->expired_at,
                ])->save();
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
    //quy trình cuối cùng để xác nhận đơn hàng thanh toán: kiểm tra, tạo hóa đơn, tạo vé,trừ kho, cập nhật điểm
    private function finalizePaidOrder(int $orderCode): array
    {
        return DB::transaction(function () use ($orderCode) {
            $order = Orders::where('order_code', $orderCode)
                ->lockForUpdate()// khóa bản ghi để tránh xung đột khi nhiều tiến trìn xử lý
                ->first();

            if (! $order) {
                throw new Exception('Khong tim thay don hang');
            }

            if ($order->trang_thai === 'paid') {
                return ['already_processed' => true];
            }

            $payload = $order->payload ?? [];
            $seatItems = collect($payload['seats'] ?? []);
            $productItems = collect($payload['products'] ?? []);
            $pointsUsed = (int) ($payload['points'] ?? 0);
            $discountAmount = max(0, (float) ($payload['discount_amount'] ?? 0));
            $subtotal = max((float) ($payload['subtotal'] ?? ($order->tong_tien + $discountAmount)), 0);
            $voucherId = data_get($payload, 'voucher.id');
            $earnedPoints = $this->calculateEarnedPoints((float) $order->tong_tien);

            //kiểm tra ghế còn trống
            $this->assertSeatsStillAvailable($order->suat_chieu_id, $seatItems);
            //kiểm tra số lượng tồn khõ`
            $this->assertProductsInStock($productItems);

            $invoice = Invoices::create([
                'ma_hoa_don' => $this->generateInvoiceNumber($order->order_code),
                'order_id' => $order->id,
                'khach_hang_id' => $order->customer_id,
                'nhan_vien_id' => null,
                'khuyen_mai_id' => $voucherId,
                'suat_chieu_id' => $order->suat_chieu_id,
                'ngay_lap' => now(),
                'tong_tien_goc' => $subtotal,
                'giam_gia' => $discountAmount,
                'tong_tien' => $order->tong_tien,
                'diem_su_dung' => $pointsUsed,
                'diem_tich_luy' => $earnedPoints,
                'phuong_thuc_thanh_toan' => 'vi_dien_tu',
                'trang_thai' => 'da_thanh_toan',
            ]);

            foreach ($seatItems as $seat) {
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

            //duyệt qua từng sản phẩm khách hàng chọn
            foreach ($productItems as $item) {
                $product = Products::query()
                    ->lockForUpdate()
                    ->find($item['id']);

                if (! $product || $product->so_luong_ton < $item['qty']) {
                    throw new Exception('San pham khong du so luong trong kho');
                }

                InvoiceDetails::create([
                    'hoa_don_id' => $invoice->id,
                    'san_pham_id' => $item['id'],
                    'ten_san_pham' => $item['name'] ?? null,
                    'so_luong' => $item['qty'],
                    'don_gia' => $item['price'],
                ]);
                //giảm đô lượng tồn theo số lương khách hàng đã mua
                $product->decrement('so_luong_ton', $item['qty']);
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
                        'order_id' => $order->id,
                        'invoice_id' => $invoice->id,
                        'gia_tri_giam' => $discountAmount,
                        'so_lan_da_dung' => DB::raw('so_lan_da_dung + 1'),
                    ]);
            }

            $order->update([
                'trang_thai' => 'paid',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            return ['already_processed' => false];
        });
    }

    //tính điểm tich lũy dựa trên số tiền thanh toán và tỷ lệ tích điểm
    private function calculateEarnedPoints(float $amount): int
    {
        return (int) floor($amount * self::LOYALTY_RATE);
    }

    //tính toán giá đơn hàng: tổng tiền, giảm giá, số điểm sử dụng, số tiền cuối cùng
    private function buildPricingSnapshot(Customers $customer, Showtimes $showtime, array $validated): array
    {
        //xác định giá ghế và tính tổng tiền cho ghế
        $seatPricing = $this->resolveSeatPricing($customer, $showtime, $validated['seats']);
        //xác định sản phẩm và tính tổng tiền cho sản phẩm
        $productPricing = $this->resolveProductPricing($validated['products'] ?? []);

        $subtotal = round($seatPricing['subtotal'] + $productPricing['subtotal'], 2);
        //giảm giá
        $voucher = $this->resolveVoucher($customer, $validated['voucher_id'] ?? null, $subtotal);
        $remainingAfterVoucher = max($subtotal - $voucher['discount_amount'], 0);
        //kiểm tra số điểm tích lũy của khách hàng
        $pointsUsed = $this->resolvePointsUsage($customer, (int) ($validated['point_used'] ?? 0), $remainingAfterVoucher);

        $discountAmount = round($voucher['discount_amount'] + $pointsUsed, 2);
        $finalAmount = round(max($subtotal - $discountAmount, 0), 2);

        if ($finalAmount < 1) {
            throw ValidationException::withMessages([
                'point_used' => ['Tong thanh toan phai lon hon hoac bang 1.'],
            ]);
        }

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'voucher_discount' => $voucher['discount_amount'],
            'point_discount' => $pointsUsed,
            'points_used' => $pointsUsed,
            'final_amount' => $finalAmount,
            'voucher' => $voucher['data'],
            'seats' => $seatPricing['items'],
            'products' => $productPricing['items'],
        ];
    }

    //Xác dịnh giá ghế và tính tổng tiền cho ghế
    private function resolveSeatPricing(Customers $customer, Showtimes $showtime, array $seatRequests): array
    {
        //Lấy danh sách id ghế từ request
        $seatIds = collect($seatRequests)
            ->pluck('id')
            ->map(fn ($seatId) => (int) $seatId)
            ->unique()//Loại bỏ trùng lăp
            ->values();

            //Lấy danh sách ghế có trong seatIds
        $seats = Seats::query()
            ->where('room_id', $showtime->room_id)
            ->whereIn('id', $seatIds)
            ->with('seat_type')
            ->get()
            ->keyBy('id');

        if ($seats->count() !== $seatIds->count()) {
            throw ValidationException::withMessages([
                'seats' => ['Danh sach ghe khong hop le voi suat chieu nay.'],
            ]);
        }

        //Lấy danh sách ghế đã được đặt
        $bookedSeatIds = Tickets::query()
            ->where('suat_chieu_id', $showtime->id)
            ->whereIn('ghe_id', $seatIds)
            ->where('trang_thai', '!=', 'cancelled')
            ->pluck('ghe_id'); //lấy một cột


        if ($bookedSeatIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'seats' => ['Co ghe da duoc dat. Vui long chon lai.'],
            ]);
        }

        $items = [];
        $subtotal = 0.0;

        foreach ($seatIds as $seatId) {
            $seat = $seats->get($seatId);
              //Kiểm tra ghế khách hàng chọn có phải là ghế khách hàng giữ không
            $this->ensureSeatHeldByCustomer($showtime->id, $seatId, $customer->id);

            $price = round((float) $showtime->gia + (float) ($seat->seat_type?->them_gia ?? 0), 2);
            $subtotal += $price;

            $items[] = [
                'id' => $seat->id,
                'name' => $seat->hang_ghe . $seat->so_ghe,
                'price' => $price,
            ];
        }

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
        ];
    }

    //Xác dịnh sản phẩm và tính tổng tiền cho sản phẩm
    private function resolveProductPricing(array $productRequests): array
    {
        //Lấy danh sách sản phẩm theo id và tổng
        $groupedProducts = collect($productRequests) //Chuyển mảng thành collection
            ->groupBy(fn (array $item) => (int) $item['id']) //Gom nhóm các phần từ theo id sản paharm
            ->map(fn (Collection $items, int $productId) => [ //duyệt qua từng nhóm
                'id' => $productId,
                'qty' => $items->sum(fn (array $item) => (int) $item['qty']),
            ])
            ->values();

        if ($groupedProducts->isEmpty()) {
            return [
                'items' => [],
                'subtotal' => 0.0,
            ];
        }
        //Lấy thông tin sp từ dtb theo danh sách groupedProducts
        $products = Products::query()
            ->whereIn('id', $groupedProducts->pluck('id'))
            ->get()
            ->keyBy('id');

        if ($products->count() !== $groupedProducts->count()) {
            throw ValidationException::withMessages([
                'products' => ['Danh sach san pham khong hop le.'],
            ]);
        }

        $items = [];
        $subtotal = 0.0;

        foreach ($groupedProducts as $requestedProduct) {
            $product = $products->get($requestedProduct['id']);

            // Số lượng trong kho nhỏ hơn số lượng đặt hàng
            if ((int) $product->so_luong_ton < $requestedProduct['qty']) {
                throw ValidationException::withMessages([
                    'products' => ['So luong san pham trong kho khong du.'],
                ]);
            }

            $price = round((float) $product->gia_ban, 2);
            $subtotal += $price * $requestedProduct['qty'];

            $items[] = [
                'id' => $product->id,
                'name' => $product->ten_san_pham,
                'qty' => $requestedProduct['qty'],
                'price' => $price,
            ];
        }

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
        ];
    }

    //Giảm giá
    private function resolveVoucher(Customers $customer, ?int $voucherId, float $subtotal): array
    {
        if (! $voucherId) {
            return [
                'data' => null,
                'discount_amount' => 0.0,
            ];
        }

        //Trả về khuyến mãi khách hàng sở hữ
        $voucher = $customer->promotions()
            ->where('promotions.id', $voucherId)
            ->where('promotions.trang_thai', true)
            ->where('promotions.ngay_bat_dau', '<=', now())
            ->where('promotions.ngay_ket_thuc', '>=', now())
            ->wherePivot('trang_thai', 0)
            ->first();

        //Kiểm tra voucher có phải là một object của model promotions không
        if (! $voucher instanceof Promotions) {
            throw ValidationException::withMessages([
                'voucher_id' => ['Voucher khong hop le hoac da duoc su dung.'],
            ]);
        }
        //tỏng giá trị đơn hàng hiện tại <  giá trị tối thiểu mà voucher yêu cầu
        if ($subtotal < (float) $voucher->don_toi_thieu) {
            throw ValidationException::withMessages([
                'voucher_id' => ['Don hang chua dat gia tri toi thieu de ap dung voucher.'],
            ]);
        }

        $discountAmount = $voucher->loai_giam_gia === 'phan_tram'
            ? ($subtotal * (float) $voucher->gia_tri_giam / 100)
            : (float) $voucher->gia_tri_giam;

        return [
            'data' => [
                'id' => $voucher->id,
                'code' => $voucher->ma_khuyen_mai,
                'type' => $voucher->loai_giam_gia,
                'value' => (float) $voucher->gia_tri_giam,
            ],
            'discount_amount' => round(min($discountAmount, $subtotal), 2),
        ];
    }

    //kiểm tra số điểm tích lũy của khách hàng
    private function resolvePointsUsage(Customers $customer, int $requestedPoints, float $maxDiscount): int
    {
        if ($requestedPoints === 0) {
            return 0;
        }

        if ($requestedPoints > (int) $customer->diem_tich_luy) {
            throw ValidationException::withMessages([
                'point_used' => ['Ban khong du diem tich luy.'],
            ]);
        }

        if ($requestedPoints > (int) floor($maxDiscount)) {
            throw ValidationException::withMessages([
                'point_used' => ['So diem su dung vuot qua gia tri don hang con lai.'],
            ]);
        }

        return $requestedPoints;
    }

    //Kiểm tra ghế khách hàng chọn có phải là ghế khách hàng giữ không
    private function ensureSeatHeldByCustomer(int $showtimeId, int $seatId, int $customerId): void
    {
        //Sinh ra chuỗi duy nhất để lưu trạng thái giữ ghế trong cache
        $holdKey = $this->getSeatHoldCacheKey($showtimeId, $seatId);
        $holder = Cache::get($holdKey);

        //Nếu ID khách hàng đang giữ ghế khác với ID khách hàng hiện tại
        if ($holder !== $customerId) {
            throw ValidationException::withMessages([
                'seats' => ['Co ghe khong con duoc giu hop le. Vui long chon lai.'],
            ]);
        }
    }

    //Kiểm tra lại ghế còn trống hay không
    private function assertSeatsStillAvailable(int $showtimeId, Collection $seatItems): void
    {
        if ($seatItems->isEmpty()) {
            throw new Exception('Don hang khong co ghe hop le');
        }

        $seatIds = $seatItems->pluck('id');
        $hasBookedSeat = Tickets::query()
            ->where('suat_chieu_id', $showtimeId)
            ->whereIn('ghe_id', $seatIds)
            ->where('trang_thai', '!=', 'cancelled')
            ->exists();

        if ($hasBookedSeat) {
            throw new Exception('Co ghe da duoc dat boi giao dich khac');
        }
    }

    //Kiểm tra số lượng tồn kho của mỗi sản phẩm
    private function assertProductsInStock(Collection $productItems): void
    {
        foreach ($productItems as $item) {
            $product = Products::query()
                ->lockForUpdate()
                ->find($item['id']);

            if (! $product || $product->so_luong_ton < $item['qty']) {
                throw new Exception('San pham khong du so luong trong kho');
            }
        }
    }

    //Tạo khóa cache duy nhất cho cache
    private function getSeatHoldCacheKey(int $showtimeId, int $seatId): string
    {
        return "holding_showtime_{$showtimeId}_seat_{$seatId}";
    }

    private function getPayOS(): PayOS
    {
        return new PayOS(
            env('PAYOS_CLIENT_ID'),
            env('PAYOS_API_KEY'),
            env('PAYOS_CHECKSUM_KEY'),
        );
    }

    //Tạo ra mã đơn
    private function generateOrderNumber(): string
    {
        do {
            $value = 'DH' . now()->format('YmdHis') . random_int(10, 99);
        } while (Orders::where('ma_don_hang', $value)->exists());// Tồn tại-> tạo mã mới, chưa tồn tại-> thoát

        return $value;
    }

    //tạo ordercode
    private function generateOrderCode(): int
    {
        do {
            $value = (int) (now()->format('ymdHis') . random_int(10, 99));
        } while (Orders::where('order_code', $value)->exists());

        return $value;
    }

    //tạo ra hóa dựa vào mã đơn hàng
    private function generateInvoiceNumber(int $orderCode): string
    {
        return 'HD' . $orderCode;
    }

    //Tạo mã vé
    private function generateTicketNumber(): string
    {
        do {
            $value = 'VE' . strtoupper(bin2hex(random_bytes(5)));
        } while (Tickets::where('ma_ve', $value)->exists());

        return $value;
    }
}
