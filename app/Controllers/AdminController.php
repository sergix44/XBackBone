<?php

namespace App\Controllers;

use League\Flysystem\FileNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController extends Controller
{
    /**
     * @param Request  $request
     * @param Response $response
     *
     * @throws FileNotFoundException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     *
     * @return Response
     */
    public function system(Request $request, Response $response): Response
    {
        $usersCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count;
        $mediasCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads`')->fetch()->count;
        $orphanFilesCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` IS NULL')->fetch()->count;

        $medias = $this->database->query('SELECT `uploads`.`storage_path` FROM `uploads`')->fetchAll();

        $totalSize = 0;

        $filesystem = $this->storage;
        foreach ($medias as $media) {
            $totalSize += $filesystem->getSize($media->storage_path);
        }

        return view()->render($response, 'dashboard/system.twig', [
            'usersCount'          => $usersCount,
            'mediasCount'         => $mediasCount,
            'orphanFilesCount'    => $orphanFilesCount,
            'totalSize'           => humanFileSize($totalSize),
            'post_max_size'       => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'installed_lang'      => $this->lang->getList(),
            'forced_lang'         => $request->getAttribute('forced_lang'),
        ]);
    }

    /**
     * @param Request  $request
     * @param Response $response
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
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function applyLang(Request $request, Response $response): Response
    {
        if (param($request, 'lang') !== 'auto') {
            if (!$this->database->query('SELECT `value` FROM `settings` WHERE `key` = \'lang\'')->fetch()) {
                $this->database->query('INSERT INTO `settings`(`key`, `value`) VALUES (\'lang\', ?)', param($request, 'lang'));
            } else {
                $this->database->query('UPDATE `settings` SET `value`=? WHERE `key` = \'lang\'', param($request, 'lang'));
            }
        } else {
            $this->database->query('DELETE FROM `settings` WHERE `key` = \'lang\'');
        }

        $this->session->alert(lang('lang_set', [param($request, 'lang')]));

        return redirect($response, route('system'));
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function applyCustomHead(Request $request, Response $response): Response
    {
        if ($request->getAttribute('custom_head_key_present')) {
            $this->database->query('UPDATE `settings` SET `value`=? WHERE `key` = \'custom_head\'', param($request, 'custom_head'));
        } else {
            $this->database->query('INSERT INTO `settings`(`key`, `value`) VALUES (\'custom_head\', ?)', param($request, 'custom_head'));
        }

        $this->session->alert(lang('custom_head_set'));

        return redirect($response, route('system'));
    }
}
