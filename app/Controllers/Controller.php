<?php

namespace App\Controllers;


use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Slim\Container;
use Slim\Http\Response;

abstract class Controller
{

	/** @var Container */
	protected $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 * @throws \Interop\Container\Exception\ContainerException
	 */
	public function __get($name)
	{
		if ($this->container->has($name)) {
			return $this->container->get($name);
		}
		return null;
	}

	/**
	 * Get a filesystem instance
	 * @return Filesystem
	 */
	protected function getStorage(): Filesystem
	{
		return new Filesystem(new Local($this->settings['storage_dir']));
	}

	/**
	 * @param $id
	 * @return int
	 */
	protected function getUsedSpaceByUser($id): int
	{
		$medias = $this->database->query('SELECT `uploads`.`storage_path` FROM `uploads` WHERE `user_id` = ?', $id)->fetchAll();

		$totalSize = 0;

		$filesystem = $this->getStorage();
		foreach ($medias as $media) {
			try {
				$totalSize += $filesystem->getSize($media->storage_path);
			} catch (FileNotFoundException $e) {
			}
		}

		return $totalSize;
	}

	/**
	 * @param Response $response
	 * @param string $path
	 * @return Response
	 */
	function redirectTo(Response $response, string $path): Response
	{
		return $response->withRedirect($this->settings['base_url'] . $path);
	}
}