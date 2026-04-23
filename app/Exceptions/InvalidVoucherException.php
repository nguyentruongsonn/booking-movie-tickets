<?php

namespace App\Exceptions;

use Exception;

class InvalidVoucherException extends Exception
{
    public function __construct(string $message = 'Voucher không hợp lệ hoặc đã được sử dụng.')
    {
        parent::__construct($message);
    }
}
