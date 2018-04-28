<?php

namespace App\Traits;


use App\Controllers\Controller;

trait SingletonController
{
	protected static $instance;

	public static function instance(): Controller
	{
		if (static::$instance === null) {
			static::$instance = new static();
		}

		return static::$instance;
	}
}