<?php

namespace App\Controllers;

use App\Database\DB;
use App\Web\Lang;
use App\Web\Session;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Monolog\Logger;
use Twig\Environment;

/**
 * @property Session|null session
 * @property Environment view
 * @property DB|null database
 * @property Logger|null logger
 * @property Filesystem|null storage
 * @property Lang lang
 * @property array config
 */
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
     * @throws DependencyException
     * @throws NotFoundException
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
        $medias = $this->database->query('SELECT `uploads`.`storage_path` FROM `uploads` WHERE `user_id` = ?', $id);

        $totalSize = 0;

        $filesystem = $this->storage;
        foreach ($medias as $media) {
            try {
                $totalSize += $filesystem->getSize($media->storage_path);
            } catch (FileNotFoundException $e) {
                $this->logger->error('Error calculating file size', ['exception' => $e]);
            }
        }

        return $totalSize;
    }
}