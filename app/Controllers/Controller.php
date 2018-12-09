<?php

namespace App\Controllers;


use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Slim\Container;

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
	 * @param $id
	 * @return int
	 */
	protected function getUsedSpaceByUser($id): int
	{
		$medias = $this->database->query('SELECT `uploads`.`storage_path` FROM `uploads` WHERE `user_id` = ?', $id)->fetchAll();

		$totalSize = 0;

		$filesystem = storage();
		foreach ($medias as $media) {
			try {
				$totalSize += $filesystem->getSize($media->storage_path);
			} catch (FileNotFoundException $e) {
				$this->logger->error('Error calculating file size', [$e->getTraceAsString()]);
			}
		}

		return $totalSize;
	}
}