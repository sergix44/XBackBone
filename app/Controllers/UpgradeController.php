<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use ZipArchive;

class UpgradeController extends Controller
{
    const GITHUB_SOURCE_API = 'https://api.github.com/repos/SergiX44/XBackBone/releases';

    /**
     * @param Response $response
     *
     * @return Response
     */
    public function upgrade(Response $response): Response
    {
        if (!is_writable(BASE_DIR)) {
            $this->session->alert(lang('path_not_writable', BASE_DIR), 'warning');

            return redirect($response, route('system'));
        }

        try {
            $json = $this->getApiJson();
        } catch (RuntimeException $e) {
            $this->session->alert($e->getMessage(), 'danger');

            return redirect($response, route('system'));
        }

        if (version_compare($json[0]->tag_name, PLATFORM_VERSION, '<=')) {
            $this->session->alert(lang('already_latest_version'), 'warning');

            return redirect($response, route('system'));
        }

        $tmpFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'xbackbone_update.zip';

        if (file_put_contents($tmpFile, file_get_contents($json[0]->assets[0]->browser_download_url)) === false) {
            $this->session->alert(lang('cannot_retrieve_file'), 'danger');

            return redirect($response, route('system'));
        }

        if (filesize($tmpFile) !== $json[0]->assets[0]->size) {
            $this->session->alert(lang('file_size_no_match'), 'danger');

            return redirect($response, route('system'));
        }
        $this->logger->info('System update started.');

        $config = require BASE_DIR.'config.php';
        $config['maintenance'] = true;

        file_put_contents(BASE_DIR.'config.php', '<?php'.PHP_EOL.'return '.var_export($config, true).';');

        $currentFiles = array_merge(
            glob_recursive(BASE_DIR.'app/*'),
            glob_recursive(BASE_DIR.'bin/*'),
            glob_recursive(BASE_DIR.'bootstrap/*'),
            glob_recursive(BASE_DIR.'resources/templates/*'),
            glob_recursive(BASE_DIR.'resources/lang/*'),
            glob_recursive(BASE_DIR.'resources/schemas/*'),
            glob_recursive(BASE_DIR.'static/*')
        );

        removeDirectory(BASE_DIR.'vendor/');

        $updateZip = new ZipArchive();
        $updateZip->open($tmpFile);

        for ($i = 0; $i < $updateZip->numFiles; $i++) {
            $nameIndex = $updateZip->getNameIndex($i);

            $updateZip->extractTo(BASE_DIR, $nameIndex);

            if (($key = array_search(rtrim(BASE_DIR.$nameIndex, '/'), $currentFiles)) !== false) {
                unset($currentFiles[$key]);
            }
        }

        foreach ($currentFiles as $extraneous) {
            unlink($extraneous);
        }

        $updateZip->close();
        unlink($tmpFile);

        $this->logger->info('System update completed.');

        return redirect($response, urlFor('/install'));
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function checkForUpdates(Request $request, Response $response): Response
    {
        $jsonResponse = [
            'status'  => null,
            'message' => null,
            'upgrade' => false,
        ];

        $acceptPrerelease = param($request, 'prerelease', 'false') === 'true';

        try {
            $json = $this->getApiJson();

            $jsonResponse['status'] = 'OK';
            foreach ($json as $release) {
                if (version_compare($release->tag_name, PLATFORM_VERSION, '>') && ($release->prerelease === $acceptPrerelease)) {
                    $jsonResponse['message'] = lang('new_version_available', $release->tag_name);
                    $jsonResponse['upgrade'] = true;
                    break;
                }

                if (version_compare($release->tag_name, PLATFORM_VERSION, '<=')) {
                    $jsonResponse['message'] = lang('already_latest_version');
                    $jsonResponse['upgrade'] = false;
                    break;
                }
            }
        } catch (RuntimeException $e) {
            $jsonResponse['status'] = 'ERROR';
            $jsonResponse['message'] = $e->getMessage();
        }

        return json($response, $jsonResponse);
    }

    protected function getApiJson()
    {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: XBackBone-App',
                    'Accept: application/vnd.github.v3+json',
                ],
            ],
        ];

        $data = @file_get_contents(self::GITHUB_SOURCE_API, false, stream_context_create($opts));

        if ($data === false) {
            throw new RuntimeException('Cannot contact the Github API. Try again.');
        }

        return json_decode($data);
    }
}
