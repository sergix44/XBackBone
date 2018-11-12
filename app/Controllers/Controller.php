<?php

namespace App\Controllers;


use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Slim\Container;
use Slim\Http\Request;
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
	 * Generate a human readable file size
	 * @param $size
	 * @param int $precision
	 * @return string
	 */
	protected function humanFilesize($size, $precision = 2): string
	{
		for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
		}
		return round($size, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
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
	 * @param $path
	 */
	protected function removeDirectory($path)
	{
		$files = glob($path . '/*');
		foreach ($files as $file) {
			is_dir($file) ? $this->removeDirectory($file) : unlink($file);
		}
		rmdir($path);
		return;
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
}