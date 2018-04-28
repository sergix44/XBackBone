<?php

namespace App\Exceptions;


use Exception;
use Throwable;

class AuthenticationException extends Exception
{
	public function __construct(string $message = 'Not Authorized', int $code = 401, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}