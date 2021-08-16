<?php

namespace App\Controllers;

use App\Database\Migrator;
use App\Web\Theme;
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
     */
    public function system(Request $request, Response $response): Response
    {
        $settings = [];
        foreach ($this->database->query('SELECT `key`, `value` FROM `settings`') as $setting) {
            $settings[$setting->key] = $setting->value;
        }

        $settings['default_user_quota'] = humanFileSize(
            $this->getSetting('default_user_quota', stringToBytes('1G')),
            0,
            true
        );

        return view()->render($response, 'dashboard/system.twig', [
            'usersCount' => $usersCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count,
            'mediasCount' => $mediasCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads`')->fetch()->count,
            'orphanFilesCount' => $orphanFilesCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` IS NULL')->fetch()->count,
            'totalSize' => humanFileSize($totalSize = $this->database->query('SELECT SUM(`current_disk_quota`) AS `sum` FROM `users`')->fetch()->sum ?? 0),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'installed_lang' => $this->lang->getList(),
            'forced_lang' => $request->getAttribute('forced_lang'),
            'php_version' => phpversion(),
            'max_memory' => ini_get('memory_limit'),
            'settings' => $settings,
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
        $themes = make(Theme::class)->availableThemes();

        $out = [];

        foreach ($themes as $vendor => $list) {
            $out["-- {$vendor} --"] = null;
            foreach ($list as $name => $url) {
                $out[$name] = "{$vendor}|{$url}";
            }
        }

        return json($response, $out);
    }

    /**
     * @param  Response  $response
     * @return Response
     */
    public function recalculateUserQuota(Response $response): Response
    {
        $migrator = new Migrator($this->database, null);
        $migrator->reSyncQuotas($this->storage);
        $this->session->alert(lang('quota_recalculated'));
        return redirect($response, route('system'));
    }
}
