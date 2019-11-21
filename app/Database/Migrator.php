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

use PDOException;

class Migrator
{
    /**
     * @var DB
     */
    private $db;
    /**
     * @var string
     */
    private $schemaPath;
    /**
     * @var bool
     */
    private $firstMigrate;

    /**
     * Migrator constructor.
     *
     * @param DB     $db
     * @param string $schemaPath
     * @param bool   $firstMigrate
     */
    public function __construct(DB $db, string $schemaPath, bool $firstMigrate = false)
    {
        $this->db = $db;
        $this->schemaPath = $schemaPath;
        $this->firstMigrate = $firstMigrate;
    }

    public function migrate()
    {
        try {
            $this->db->query('SELECT 1 FROM `migrations` LIMIT 1');
        } catch (PDOException $exception) {
            $this->firstMigrate = true;
        }

        if ($this->firstMigrate) {
            $this->db->getPdo()->exec(file_get_contents($this->schemaPath.DIRECTORY_SEPARATOR.'migrations.sql'));
        }

        $files = glob($this->schemaPath.'/'.$this->db->getCurrentDriver().'/*.sql');

        $names = array_map(function ($path) {
            return basename($path);
        }, $files);

        $in = str_repeat('?, ', count($names) - 1).'?';

        $inMigrationsTable = $this->db->query("SELECT * FROM `migrations` WHERE `name` IN ($in)", $names)->fetchAll();

        foreach ($files as $file) {
            $continue = false;
            $exists = false;

            foreach ($inMigrationsTable as $migration) {
                if (basename($file) === $migration->name && $migration->migrated) {
                    $continue = true;
                    break;
                } else {
                    if (basename($file) === $migration->name && !$migration->migrated) {
                        $exists = true;
                        break;
                    }
                }
            }
            if ($continue) {
                continue;
            }

            $sql = file_get_contents($file);

            try {
                $this->db->getPdo()->exec($sql);
                if (!$exists) {
                    $this->db->query('INSERT INTO `migrations` VALUES (?,?)', [basename($file), 1]);
                } else {
                    $this->db->query('UPDATE `migrations` SET `migrated`=? WHERE `name`=?', [1, basename($file)]);
                }
            } catch (PDOException $exception) {
                if (!$exists) {
                    $this->db->query('INSERT INTO `migrations` VALUES (?,?)', [basename($file), 0]);
                }

                throw $exception;
            }
        }
    }
}
