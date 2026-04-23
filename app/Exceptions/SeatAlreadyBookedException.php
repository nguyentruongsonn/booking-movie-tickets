<?php

namespace App\Exceptions;

use Exception;

class SeatAlreadyBookedException extends Exception
{
    public function __construct(string $message = 'Ghế đã được đặt bởi giao dịch khác.')
    {
        parent::__construct($message);
    }
}
