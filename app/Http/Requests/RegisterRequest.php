<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ho_ten' => 'required|string|max:100',
            'email' => 'required|email|unique:customers,email',
            'mat_khau' => 'required|string|min:6|confirmed',
            'mat_khau_confirmation' => 'required',
            'ngay_sinh' => 'nullable|date',
            'gioi_tinh' => 'nullable|string|max:20',
            'so_dien_thoai' => 'nullable|string|max:15',
        ];
    }

    public function messages(): array
    {
        return [
            'ho_ten.required' => 'Vui long nhap ho ten',
            'email.required' => 'Vui long nhap email',
            'email.email' => 'Email khong hop le',
            'email.unique' => 'Email da ton tai',
            'mat_khau.required' => 'Vui long nhap mat khau',
            'mat_khau.min' => 'Mat khau phai it nhat 6 ky tu',
            'mat_khau.confirmed' => 'Xac nhan mat khau khong khop',
            'mat_khau_confirmation.required' => 'Vui long xac nhan mat khau',
            'ngay_sinh.date' => 'Ngay sinh khong hop le',
        ];
    }

    private function cleanName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $name = trim(preg_replace('/\s+/', ' ', $name));
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private function cleanEmail(?string $email): ?string
    {
        return $email ? trim(strtolower($email)) : null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->cleanEmail($this->email),
            'ho_ten' => $this->cleanName($this->ho_ten),
            'gioi_tinh' => $this->gioi_tinh ? Str::title(trim($this->gioi_tinh)) : null,
        ]);
    }
}
