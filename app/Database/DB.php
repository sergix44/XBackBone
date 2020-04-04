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
    protected static $dsn = 'sqlite:database.db';

    /** @var string */
    protected $currentDriver;

    public function __construct(string $dsn, string $username = null, string $password = null)
    {
        self::setDsn($dsn, $username, $password);

        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        $this->currentDriver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($this->currentDriver === 'sqlite') {
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    public function query(string $query, $parameters = [])
    {
        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }
        $query = $this->pdo->prepare($query);

        foreach ($parameters as $index => $parameter) {
            $query->bindValue($index + 1, $parameter, is_int($parameter) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $query->execute();
        return $query;
    }

    /**
     * Get the PDO instance
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get the current PDO driver
     * @return string
     */
    public function getCurrentDriver(): string
    {
        return $this->currentDriver;
    }

    public static function getInstance(): DB
    {
        if (self::$instance === null) {
            self::$instance = new self(self::$dsn, self::$username, self::$password);
        }

        return self::$instance;
    }

    /**
     * Perform a query
     * @param string $query
     * @param array $parameters
     * @return bool|\PDOStatement|string
     */
    public static function doQuery(string $query, $parameters = [])
    {
        return self::getInstance()->query($query, $parameters);
    }

    /**
     * Static method to get the current driver name
     * @return string
     */
    public static function driver(): string
    {
        return self::getInstance()->getCurrentDriver();
    }

    /**
     * Get directly the PDO instance
     * @return PDO
     */
    public static function raw(): PDO
    {
        return self::getInstance()->getPdo();
    }

    /**
     * Set the PDO connection string
     * @param string $dsn
     * @param string|null $username
     * @param string|null $password
     */
    public static function setDsn(string $dsn, string $username = null, string $password = null)
    {
        self::$dsn = $dsn;
        self::$username = $username;
        self::$password = $password;
    }
}
