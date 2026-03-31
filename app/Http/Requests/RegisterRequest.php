<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ho_ten' => 'required|string|max:100',
            'email' => 'required|email|unique:customers,email',
            'mat_khau' => 'required|string|min:6|confirmed',
            'mat_khau_confirmation' => 'required',
            'ngay_sinh' => 'nullable|date',
            'gioi_tinh' => 'nullable|in:Nam,Nữ,Khác',
            'so_dien_thoai' => 'nullable|string|max:15',
        ];
    }
    public function messages()
    {
        return [
            'ho_ten.required' => 'Vui lòng nhập họ tên',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email đã tồn tại',
            'mat_khau.required' => 'Vui lòng nhập mật khẩu',
            'mat_khau.min' => 'Mật khẩu phải ít nhất 6 ký tự',
            'mat_khau.confirmed' => 'Xác nhận mật khẩu không khớp',
            'mat_khau_confirmation.required' => 'Vui lòng xác nhận mật khẩu',
            'ngay_sinh.date' => 'Ngày sinh không hợp lệ',
            'gioi_tinh.in' => 'Giới tính không hợp lệ',
        ];
    }
    private function cleanName($name)
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    public function cleanEmail($email)
    {
        return trim(strtolower($email));
    }

    protected function prepareforValidation()
    {
        $this->merge([
            'email' => $this->cleanEmail($this->email),
            'ho_ten' => $this->cleanName($this->ho_ten),
        ]);
    }
}
