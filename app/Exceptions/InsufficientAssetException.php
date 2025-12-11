<?php

namespace App\Exceptions;

use Exception;

class InsufficientAssetException extends Exception
{
    protected $message = 'Insufficient asset amount to place this order';
    protected $code = 400;
}
