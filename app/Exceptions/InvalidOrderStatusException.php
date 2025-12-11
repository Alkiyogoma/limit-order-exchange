<?php

namespace App\Exceptions;

use Exception;

class InvalidOrderStatusException extends Exception
{
    protected $message = 'Order cannot be cancelled in its current status';
    protected $code = 400;
}
