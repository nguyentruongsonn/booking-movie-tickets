<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $message = 'Sản phẩm không đủ số lượng trong kho.')
    {
        parent::__construct($message);
    }
}
