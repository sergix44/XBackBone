<?php

namespace App\Database;


use PDO;

class DB
{

	/** @var  DB */
	protected static $instance;

	/** @var string */
	private static $password;

	/** @var string */
	private static $username;

	/** @var PDO */
	protected $pdo;

	/** @var string */
	protected static $dsn = 'database.db';

	/** @var string */
	protected $currentDriver;

	public function __construct(string $dsn, $username, $password)
	{

		$this->pdo = new PDO($dsn, $username, $password);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

		$this->currentDriver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
		if ($this->currentDriver === 'sqlite') {
			$this->pdo->exec('PRAGMA foreign_keys = ON');
		}
	}

	public function doQuery(string $query, $parameters = [])
	{
		if (!is_array($parameters)) {
			$parameters = [$parameters];
		}
		$query = $this->pdo->prepare($query);
		$query->execute($parameters);
		return $query;
	}

	/**
	 * @return PDO
	 */
	public function getPdo(): PDO
	{
		return $this->pdo;
	}

	/**
	 * @return string
	 */
	public function getCurrentDriver(): string
	{
		return $this->currentDriver;
	}

	public static function query(string $query, $parameters = [])
	{

		if (self::$instance === null) {
			self::$instance = new self(self::$dsn, self::$username, self::$password);
		}

		return self::$instance->doQuery($query, $parameters);
	}

	public static function driver(): string
	{

		if (self::$instance === null) {
			self::$instance = new self(self::$dsn, self::$username, self::$password);
		}

		return self::$instance->getCurrentDriver();
	}

	public static function raw(): PDO
	{
		if (self::$instance === null) {
			self::$instance = new self(self::$dsn, self::$username, self::$password);
		}

		return self::$instance->getPdo();
	}

	/**
	 * @param string $dsn
	 * @param string|null $username
	 * @param string|null $password
	 */
	public static function setDsn(string $dsn, string $username = null, string $password = null): void
	{
		self::$dsn = $dsn;
		self::$username = $username;
		self::$password = $password;
	}

}