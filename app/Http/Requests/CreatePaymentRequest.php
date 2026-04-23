<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'showtime_id'     => ['required', 'integer', 'exists:showtimes,id'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.id'      => ['required', 'integer'],
            'items.*.type'    => ['required', 'string', 'in:seat,product'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'voucher_code'    => ['nullable', 'string'],
            'points_used'     => ['nullable', 'integer', 'min:0'],
            'payment_gateway' => ['nullable', 'string', 'in:vnpay,payos'],
        ];
    }

    public function messages(): array
    {
        return [
            'suat_chieu_id.required' => 'Vui lòng chọn suất chiếu.',
            'suat_chieu_id.exists'   => 'Suất chiếu không tồn tại.',
            'seats.required'         => 'Vui lòng chọn ít nhất một ghế.',
            'seats.min'              => 'Vui lòng chọn ít nhất một ghế.',
            'seats.max'              => 'Tối đa 10 ghế mỗi lần đặt.',
            'seats.*.id.required'    => 'Thông tin ghế không hợp lệ.',
            'seats.*.id.distinct'    => 'Không được chọn ghế trùng lặp.',
            'products.*.qty.min'     => 'Số lượng sản phẩm phải ít nhất là 1.',
            'point_used.min'         => 'Số điểm sử dụng không hợp lệ.',
        ];
    }
}
