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
            'full_name'             => ['required', 'string', 'max:100'],
            'email'                => ['required', 'email:rfc', 'unique:users,email'],
            'password'             => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required'],
            'birthday'             => ['nullable', 'date', 'before:today'],
            'gender'               => ['nullable', 'string', 'in:Male,Female,Other,Nam,Nữ,Khác'],
            'phone'                => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'email.required'     => 'Vui lòng nhập email.',
            'email.email'        => 'Email không hợp lệ.',
            'email.unique'       => 'Email đã tồn tại.',
            'password.required'  => 'Vui lòng nhập mật khẩu.',
            'password.min'       => 'Mật khẩu phải ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password_confirmation.required' => 'Vui lòng xác nhận mật khẩu.',
            'birthday.date'      => 'Ngày sinh không hợp lệ.',
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
        $genderMap = [
            'Nam'  => 'Male',
            'Nữ'   => 'Female',
            'Khác' => 'Other',
        ];
 
        $rawGender = $this->gender ? trim($this->gender) : null;
        $mappedGender = $genderMap[$rawGender] ?? $rawGender;
 
        $this->merge([
            'email'     => $this->cleanEmail($this->email),
            'full_name'   => $this->cleanName($this->full_name),
            'gender'    => $mappedGender ? \Illuminate\Support\Str::title($mappedGender) : null,
        ]);
    }
}
