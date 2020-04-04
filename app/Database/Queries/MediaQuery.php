<?php

namespace App\Database\Queries;

use App\Database\DB;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;

class MediaQuery
{
    const PER_PAGE = 21;
    const PER_PAGE_ADMIN = 27;

    const ORDER_TIME = 0;
    const ORDER_NAME = 1;
    const ORDER_SIZE = 2;


    /** @var DB */
    protected $db;

    /** @var bool */
    protected $isAdmin;

    protected $userId;

    /** @var int */
    protected $orderBy;

    /** @var string */
    protected $orderMode;

    /** @var string */
    protected $text;

    /** @var Filesystem */
    protected $storage;

    private $pages;
    private $media;

    /**
     * MediaQuery constructor.
     * @param DB $db
     * @param bool $isAdmin
     * @param Filesystem $storage
     */
    public function __construct(DB $db, bool $isAdmin, Filesystem $storage)
    {
        $this->db = $db;
        $this->isAdmin = $isAdmin;
        $this->storage = $storage;
    }

    /**
     * @param $id
     * @return $this
     */
    public function withUserId($id)
    {
        $this->userId = $id;
        return $this;
    }

    /**
     * @param string|null $type
     * @param string $mode
     * @return $this
     */
    public function orderBy(string $type = null, $mode = 'ASC')
    {
        $this->orderBy = ($type === null) ? self::ORDER_TIME : $type;
        $this->orderMode = (strtoupper($mode) === 'ASC') ? 'ASC' : 'DESC';
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function search(?string $text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @param int $page
     */
    public function run(int $page)
    {
        if ($this->orderBy == self::ORDER_SIZE) {
            $this->runWithOrderBySize($page);
            return;
        }

        $queryPages = 'SELECT COUNT(*) AS `count` FROM `uploads`';

        if ($this->isAdmin) {
            $queryMedia = 'SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` %s LIMIT ? OFFSET ?';
        } else {
            $queryMedia = 'SELECT `uploads`.*,`users`.`user_code`, `users`.`username` FROM `uploads` INNER JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_id` = ? %s LIMIT ? OFFSET ?';
            $queryPages .= ' WHERE `user_id` = ?';
        }

        $orderAndSearch = '';
        $params = [];

        if ($this->text !== null) {
            $orderAndSearch = $this->isAdmin ? 'WHERE `uploads`.`filename` LIKE ? ' : 'AND `uploads`.`filename` LIKE ? ';
            $queryPages .= $this->isAdmin ? ' WHERE `filename` LIKE ?' : ' AND `filename` LIKE ?';
            $params[] = '%' . htmlentities($this->text) . '%';
        }

        switch ($this->orderBy) {
            case self::ORDER_NAME:
                $orderAndSearch .= 'ORDER BY `filename` ' . $this->orderMode;
                break;
            default:
            case self::ORDER_TIME:
                $orderAndSearch .= 'ORDER BY `timestamp` ' . $this->orderMode;
                break;
        }

        $queryMedia = sprintf($queryMedia, $orderAndSearch);

        if ($this->isAdmin) {
            $this->media = $this->db->query($queryMedia, array_merge($params, [self::PER_PAGE_ADMIN, $page * self::PER_PAGE_ADMIN]))->fetchAll();
            $this->pages = $this->db->query($queryPages, $params)->fetch()->count / self::PER_PAGE_ADMIN;
        } else {
            $this->media = $this->db->query($queryMedia, array_merge([$this->userId], $params, [self::PER_PAGE, $page * self::PER_PAGE]))->fetchAll();
            $this->pages = $this->db->query($queryPages, array_merge([$this->userId], $params))->fetch()->count / self::PER_PAGE;
        }

        foreach ($this->media as $media) {
            try {
                $media->size = humanFileSize($this->storage->getSize($media->storage_path));
                $media->mimetype = $this->storage->getMimetype($media->storage_path);
            } catch (FileNotFoundException $e) {
                $media->size = null;
                $media->mimetype = null;
            }
            $media->extension = pathinfo($media->filename, PATHINFO_EXTENSION);
        }
    }

    /**
     * @param int $page
     */
    private function runWithOrderBySize(int $page)
    {
        $this->storage->addPlugin(new ListFiles());

        if ($this->isAdmin) {
            $files = $this->storage->listFiles('/', true);
            $this->pages = count($files) / self::PER_PAGE_ADMIN;

            $offset = $page * self::PER_PAGE_ADMIN;
            $limit = self::PER_PAGE_ADMIN;
        } else {
            $userCode = $this->db->query('SELECT `user_code` FROM `users` WHERE `id` = ?', [$this->userId])->fetch()->user_code;
            $files = $this->storage->listFiles($userCode);
            $this->pages = count($files) / self::PER_PAGE;

            $offset = $page * self::PER_PAGE;
            $limit = self::PER_PAGE;
        }

        array_multisort(array_column($files, 'size'), ($this->orderMode === 'ASC') ? SORT_ASC : SORT_DESC, SORT_NUMERIC, $files);

        if ($this->text !== null) {
            if ($this->isAdmin) {
                $medias = $this->db->query('SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `uploads`.`filename` LIKE ? ', ['%' . htmlentities($this->text) . '%'])->fetchAll();
            } else {
                $medias = $this->db->query('SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_id` = ? AND `uploads`.`filename` LIKE ? ', [$this->userId, '%' . htmlentities($this->text) . '%'])->fetchAll();
            }

            $paths = array_column($files, 'path');
        } else {
            $files = array_slice($files, $offset, $limit);
            $paths = array_column($files, 'path');

            $medias = $this->db->query('SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `uploads`.`storage_path` IN ("' . implode('","', $paths) . '")')->fetchAll();
        }

        $paths = array_flip($paths);
        foreach ($medias as $media) {
            $paths[$media->storage_path] = $media;
        }

        $this->media = [];
        foreach ($files as $file) {
            $media = $paths[$file['path']];
            if (!is_object($media)) {
                continue;
            }
            $media->size = humanFileSize($file['size']);
            try {
                $media->mimetype = $this->storage->getMimetype($file['path']);
            } catch (FileNotFoundException $e) {
                $media->mimetype = null;
            }
            $media->extension = $file['extension'];
            $this->media[] = $media;
        }

        if ($this->text !== null) {
            $this->media = array_slice($this->media, $offset, $limit);
        }
    }

    /**
     * @return mixed
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->pages;
    }
}
