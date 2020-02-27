<?php

namespace App\Controllers;

use League\Flysystem\FileNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @throws FileNotFoundException
     */
    public function system(Request $request, Response $response): Response
    {
        $usersCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count;
        $mediasCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads`')->fetch()->count;
        $orphanFilesCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` IS NULL')->fetch()->count;
        $totalSize = $this->database->query('SELECT SUM(`current_disk_quota`) AS `sum` FROM `users`')->fetch()->sum ?? 0;
        $registerEnabled = $this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'register_enabled\'')->fetch()->value ?? 'off';
        $hideByDefault = $this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'hide_by_default\'')->fetch()->value ?? 'off';
        $copyUrl = $this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'copy_url_behavior\'')->fetch()->value ?? 'off';
        $quotaEnabled = $this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'quota_enabled\'')->fetch()->value ?? 'off';
        $defaultUserQuota = $this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'default_user_quota\'')->fetch()->value ?? '1G';

        return view()->render($response, 'dashboard/system.twig', [
            'usersCount' => $usersCount,
            'mediasCount' => $mediasCount,
            'orphanFilesCount' => $orphanFilesCount,
            'totalSize' => humanFileSize($totalSize),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'installed_lang' => $this->lang->getList(),
            'forced_lang' => $request->getAttribute('forced_lang'),
            'php_version' => phpversion(),
            'max_memory' => ini_get('memory_limit'),
            'register_enabled' => $registerEnabled,
            'hide_by_default' => $hideByDefault,
            'copy_url_behavior' => $copyUrl,
            'quota_enabled' => $quotaEnabled,
            'default_user_quota' => humanFileSize($defaultUserQuota, 0, true),
        ]);
    }

    /**
     * @param  Response  $response
     *
     * @return Response
     */
    public function deleteOrphanFiles(Response $response): Response
    {
        $orphans = $this->database->query('SELECT * FROM `uploads` WHERE `user_id` IS NULL')->fetchAll();

        $filesystem = $this->storage;
        $deleted = 0;

        foreach ($orphans as $orphan) {
            try {
                $filesystem->delete($orphan->storage_path);
                $deleted++;
            } catch (FileNotFoundException $e) {
            }
        }

        $this->database->query('DELETE FROM `uploads` WHERE `user_id` IS NULL');

        $this->session->alert(lang('deleted_orphans', [$deleted]));

        return redirect($response, route('system'));
    }

    /**
     * @param  Response  $response
     *
     * @return Response
     */
    public function getThemes(Response $response): Response
    {
        $apiJson = json_decode(file_get_contents('https://bootswatch.com/api/4.json'));

        $out = [];

        $out['Default - Bootstrap 4 default theme'] = 'https://bootswatch.com/_vendor/bootstrap/dist/css/bootstrap.min.css';
        foreach ($apiJson->themes as $theme) {
            $out["{$theme->name} - {$theme->description}"] = $theme->cssMin;
        }

        return json($response, $out);
    }

    /**
     * @param  Response  $response
     * @return Response
     */
    public function recalculateUserQuota(Response $response): Response
    {
        $uploads = $this->database->query('SELECT `id`,`user_id`, `storage_path` FROM `uploads`')->fetchAll();

        $usersQuotas = [];

        $filesystem = $this->storage;
        foreach ($uploads as $upload) {
            if (!array_key_exists($upload->user_id, $usersQuotas)) {
                $usersQuotas[$upload->user_id] = 0;
            }
            try {
                $usersQuotas[$upload->user_id] += $filesystem->getSize($upload->storage_path);
            } catch (FileNotFoundException $e) {
                $this->database->query('DELETE FROM `uploads` WHERE `id` = ?', $upload->id);
            }
        }

        foreach ($usersQuotas as $userId => $quota) {
            $this->database->query('UPDATE `users` SET `current_disk_quota`=? WHERE `id` = ?', [
                $quota,
                $userId,
            ]);
        }

        $this->session->alert(lang('quota_recalculated'));
        return redirect($response, route('system'));
    }
}
