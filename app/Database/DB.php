<?php

namespace App\Database;

use PDO;

class DB
{
    /** @var DB */
    protected static $instance;

    /** @var PDO */
    protected $pdo;

    /** @var string */
    protected $currentDriver;

    public function __construct(string $dsn, string $username = null, string $password = null)
    {
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
     * Get the PDO instance.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get the current PDO driver.
     *
     * @return string
     */
    public function getCurrentDriver(): string
    {
        return $this->currentDriver;
    }
}
