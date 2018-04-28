<?php

namespace App\Web;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class Log
{
	/** @var Logger */
	protected $logger;

	/** @var Log */
	protected static $instance;

	/**
	 * @return Log
	 * @throws \Exception
	 */
	public static function instance(): Log
	{
		if (static::$instance === null) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Log constructor.
	 * @throws \Exception
	 */
	public function __construct()
	{
		$this->logger = new Logger(self::class);

		$streamHandler = new RotatingFileHandler(__DIR__ . '/../../logs/log.txt', 10, Logger::DEBUG);
		$streamHandler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", "Y-m-d H:i:s", true));

		$this->logger->pushHandler($streamHandler);
	}

	public static function debug(string $message, array $context = [])
	{
		self::instance()->logger->debug($message, $context);
	}

	public static function info(string $message, array $context = [])
	{
		self::instance()->logger->info($message, $context);
	}

	public static function warning(string $message, array $context = [])
	{
		self::instance()->logger->warning($message, $context);
	}

	public static function error(string $message, array $context = [])
	{
		self::instance()->logger->error($message, $context);
	}

	public static function critical(string $message, array $context = [])
	{
		self::instance()->logger->critical($message, $context);
	}
}