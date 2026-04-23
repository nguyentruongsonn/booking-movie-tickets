<?php

namespace App\Exceptions;

use Exception;

class PaymentGatewayException extends Exception
{
    public function __construct(string $message = 'Lỗi kết nối cổng thanh toán.')
    {
        parent::__construct($message);
    }
}
