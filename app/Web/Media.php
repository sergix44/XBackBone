<?php


namespace App\Web;


use App\Database\DB;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class Media
{
    public static function recalculateQuotas(DB $db, Filesystem $filesystem)
    {
        $uploads = $db->query('SELECT `id`,`user_id`, `storage_path` FROM `uploads`')->fetchAll();

        $usersQuotas = [];

        foreach ($uploads as $upload) {
            if (!array_key_exists($upload->user_id, $usersQuotas)) {
                $usersQuotas[$upload->user_id] = 0;
            }
            try {
                $usersQuotas[$upload->user_id] += $filesystem->getSize($upload->storage_path);
            } catch (FileNotFoundException $e) {
                $db->query('DELETE FROM `uploads` WHERE `id` = ?', $upload->id);
            }
        }

        foreach ($usersQuotas as $userId => $quota) {
            $db->query('UPDATE `users` SET `current_disk_quota`=? WHERE `id` = ?', [
                $quota,
                $userId,
            ]);
        }
    }
}