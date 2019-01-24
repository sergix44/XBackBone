<?php

namespace App\Controllers;


use Slim\Http\Request;
use Slim\Http\Response;
use ZipArchive;

class UpgradeController extends Controller
{
	const GITHUB_SOURCE_API = 'https://api.github.com/repos/SergiX44/XBackBone/releases';

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function upgrade(Request $request, Response $response): Response
	{
		try {
			$json = $this->getApiJson();
		} catch (\RuntimeException $e) {
			$jsonResponse['message'] = $e->getMessage();
			return $response->withJson($jsonResponse, 503);
		}

		if (version_compare($json[0]->tag_name, PLATFORM_VERSION, '<=')) {
			$this->session->alert(lang('already_latest_version'), 'warning');
			return redirect($response, 'system');
		}

		$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'xbackbone_update.zip';

		if (file_put_contents($tmpFile, file_get_contents($json[0]->assets[0]->browser_download_url)) === false) {
			$this->session->alert(lang('cannot_retrieve_file'), 'danger');
			return redirect($response, 'system');
		};

		if (filesize($tmpFile) !== $json[0]->assets[0]->size) {
			$this->session->alert(lang('file_size_no_match'), 'danger');
			return redirect($response, 'system');
		}

		$updateZip = new ZipArchive();
		$updateZip->open($tmpFile);


		for ($i = 0; $i < $updateZip->numFiles; $i++) {
			$nameIndex = $updateZip->getNameIndex($i);
			if (is_dir(BASE_DIR . $nameIndex)) {
				continue;
			}
			if (file_exists(BASE_DIR . $nameIndex) && md5($updateZip->getFromIndex($i)) !== md5_file(BASE_DIR . $nameIndex)) {
				$updateZip->extractTo(BASE_DIR, $nameIndex);
			} elseif (!file_exists(BASE_DIR . $nameIndex)) {
				$updateZip->extractTo(BASE_DIR, $nameIndex);
			}
			print_r($updateZip->getNameIndex($i) . '<br>');
		}

		$updateZip->close();
		unlink($tmpFile);

		return redirect($response, '/install');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function checkForUpdates(Request $request, Response $response): Response
	{
		$jsonResponse = [
			'status' => null,
			'message' => null,
			'upgrade' => false,
		];

		try {
			$json = $this->getApiJson();

			$jsonResponse['status'] = 'OK';
			if (version_compare($json[0]->tag_name, PLATFORM_VERSION, '>')) {
				$jsonResponse['message'] = lang('new_version_available', $json[0]->tag_name); //"New version {$json[0]->tag_name} available!";
				$jsonResponse['upgrade'] = true;
			} else {
				$jsonResponse['message'] = lang('already_latest_version');//'You have already the latest version.';
				$jsonResponse['upgrade'] = false;
			}
			return $response->withJson($jsonResponse, 200);
		} catch (\RuntimeException $e) {
			$jsonResponse['status'] = 'ERROR';
			$jsonResponse['message'] = $e->getMessage();
			return $response->withJson($jsonResponse, 503);
		}
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

		$data = file_get_contents(self::GITHUB_SOURCE_API, false, stream_context_create($opts));

		if ($data === false) {
			throw new \RuntimeException('Cannot contact the Github API. Try again.');
		}

		return json_decode($data);
	}

}