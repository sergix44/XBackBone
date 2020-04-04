<?php

namespace App\Exceptions;


use Exception;
use Throwable;

class UnauthorizedException extends Exception
{
	public function __construct(string $message = 'Forbidden', int $code = 403, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}