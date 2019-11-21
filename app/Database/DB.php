<?php

/*
 * @copyright Copyright (c) 2019 Sergio Brighenti <sergio@brighenti.me>
 *
 * @author Sergio Brighenti <sergio@brighenti.me>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

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
