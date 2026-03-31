<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => 'required|email',
            'mat_khau' => 'required|string|min:6',
            'device_name' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không đúng định dạng',
            'mat_khau.required' => 'Mật khẩu không được để trống',
            'mat_khau.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'device_name.string' => 'Tên thiết bị phải là chuỗi',
        ];
    }
}
