<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedOrderCancellationException extends Exception
{
    protected $message = 'You are not authorized to cancel this order';
    protected $code = 403;
}
