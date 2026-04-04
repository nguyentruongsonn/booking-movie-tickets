<?php

// Đã comment hoặc xóa use App\Models\User;
// use App\Models\User;

return [

    /*
     |--------------------------------------------------------------------------
     | Authentication Defaults
     |--------------------------------------------------------------------------
     */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'customer'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'customers'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Authentication Guards
     |--------------------------------------------------------------------------
     */

    'guards' => [
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],

        'employee' => [
            'driver' => 'session',
            'provider' => 'employees',
        ],


        // API guards
        'api' => [
            'driver' => 'jwt',
            'provider' => 'customers',
        ],

        'api_employee' => [
            'driver' => 'token',
            'provider' => 'employees',
            'hash' => false,
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | User Providers
     |--------------------------------------------------------------------------
     */

    'providers' => [
        // Provider cho customers (khách hàng)
        'customers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Customers::class ,
        ],

        // Provider cho employees (nhân viên)
        'employees' => [
            'driver' => 'eloquent',
            'model' => App\Models\Employees::class ,
        ],



    ],

    /*
     |--------------------------------------------------------------------------
     | Resetting Passwords
     |--------------------------------------------------------------------------
     */

    'passwords' => [
        // Password reset cho customers
        'customers' => [
            'provider' => 'customers',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        // Password reset cho employees
        'employees' => [
            'provider' => 'employees',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

    ],

    /*
     |--------------------------------------------------------------------------
     | Password Confirmation Timeout
     |--------------------------------------------------------------------------
     */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
