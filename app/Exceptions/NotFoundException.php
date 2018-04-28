<?php

namespace App\Exceptions;


use Exception;
use Throwable;

class NotFoundException extends Exception
{
	public function __construct(string $message = 'Not Found', int $code = 404, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}