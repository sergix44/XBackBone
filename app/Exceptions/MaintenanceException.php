<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class MaintenanceException extends Exception
{
    public function __construct(string $message = 'Under Maintenance', int $code = 503, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
