<?php

namespace App\Database;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
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
     * Migrator constructor.
     *
     * @param  DB  $db
     * @param  string|null  $schemaPath
     */
    public function __construct(DB $db, ?string $schemaPath)
    {
        $this->db = $db;
        $this->schemaPath = $schemaPath;
    }

    public function migrate(): void
    {
        $this->db->getPdo()->exec(file_get_contents($this->schemaPath.DIRECTORY_SEPARATOR.'migrations.sql'));

        $files = glob($this->schemaPath.'/'.$this->db->getCurrentDriver().'/*.sql');

        $names = array_map(static function ($path) {
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
                }

                if (basename($file) === $migration->name && !$migration->migrated) {
                    $exists = true;
                    break;
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

    /**
     * @param  Filesystem  $filesystem
     */
    public function reSyncQuotas(Filesystem $filesystem)
    {
        $uploads = $this->db->query('SELECT `id`,`user_id`, `storage_path` FROM `uploads`')->fetchAll();

        $usersQuotas = [];

        foreach ($uploads as $upload) {
            if (!array_key_exists($upload->user_id, $usersQuotas)) {
                $usersQuotas[$upload->user_id] = 0;
            }
            try {
                $usersQuotas[$upload->user_id] += $filesystem->getSize($upload->storage_path);
            } catch (FileNotFoundException $e) {
                $this->db->query('DELETE FROM `uploads` WHERE `id` = ?', $upload->id);
            }
        }

        foreach ($usersQuotas as $userId => $quota) {
            $this->db->query('UPDATE `users` SET `current_disk_quota`=? WHERE `id` = ?', [
                $quota,
                $userId,
            ]);
        }
    }
}
