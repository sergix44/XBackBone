<?php

namespace App\Database\Queries;

use App\Database\DB;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListWith;

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
     * @var int
     */
    private $tagId;

    /**
     * MediaQuery constructor.
     *
     * @param  DB  $db
     * @param  bool  $isAdmin
     * @param  Filesystem  $storage
     */
    public function __construct(DB $db, Filesystem $storage, bool $isAdmin)
    {
        $this->db = $db;
        $this->isAdmin = $isAdmin;
        $this->storage = $storage;
    }

    /**
     * @param  DB  $db
     * @param  bool  $isAdmin
     * @param  Filesystem  $storage
     * @return MediaQuery
     */
    public static function make(DB $db, Filesystem $storage, bool $isAdmin)
    {
        return new self($db, $storage, $isAdmin);
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function withUserId($id)
    {
        $this->userId = $id;

        return $this;
    }

    /**
     * @param  string|null  $type
     * @param  string  $mode
     *
     * @return $this
     */
    public function orderBy(string $type = null, $mode = 'ASC')
    {
        $this->orderBy = ($type === null) ? self::ORDER_TIME : $type;
        $this->orderMode = (strtoupper($mode) === 'ASC') ? 'ASC' : 'DESC';

        return $this;
    }

    /**
     * @param  string  $text
     *
     * @return $this
     */
    public function search(?string $text)
    {
        $this->text = $text;

        return $this;
    }

    public function filterByTag($tagId)
    {
        if ($tagId !== null) {
            $this->tagId = (int) $tagId;
        }

        return $this;
    }


    public function run(int $page)
    {
        if ($this->orderBy == self::ORDER_SIZE) {
            $this->runWithFileSort($page);
        } else {
            $this->runWithDbSort($page);
        }

        return $this;
    }

    public function runWithDbSort(int $page)
    {
        if ($this->isAdmin) {
            [$queryMedia, $queryPages] = $this->buildAdminQueries();

            $params = [];
            if ($this->text !== null) {
                $params[] = '%'.htmlentities($this->text).'%';
            }

            $queryMedia .= $this->buildOrderBy().' LIMIT ? OFFSET ?';

            $this->media = $this->db->query($queryMedia, array_merge($params, [self::PER_PAGE_ADMIN, $page * self::PER_PAGE_ADMIN]))->fetchAll();
            $this->pages = $this->db->query($queryPages, $params)->fetch()->count / self::PER_PAGE_ADMIN;
        } else {
            [$queryMedia, $queryPages] = $this->buildUserQueries();

            if ($this->text !== null) {
                $params = [$this->userId, '%'.htmlentities($this->text).'%'];
            } else {
                $params = [$this->userId];
            }

            $queryMedia .= $this->buildOrderBy().' LIMIT ? OFFSET ?';

            $this->media = $this->db->query($queryMedia, array_merge($params, [self::PER_PAGE, $page * self::PER_PAGE]))->fetchAll();
            $this->pages = $this->db->query($queryPages, array_merge($params))->fetch()->count / self::PER_PAGE;
        }

        $tags = $this->getTags(array_column($this->media, 'id'));

        foreach ($this->media as $media) {
            try {
                $media->size = humanFileSize($this->storage->getSize($media->storage_path));
                $media->mimetype = $this->storage->getMimetype($media->storage_path);
            } catch (FileNotFoundException $e) {
                $media->size = null;
                $media->mimetype = null;
            }
            $media->extension = pathinfo($media->filename, PATHINFO_EXTENSION);
            if (array_key_exists($media->id, $tags)) {
                $media->tags = $tags[$media->id];
            } else {
                $media->tags = [];
            }
        }

        return $this;
    }

    public function runWithFileSort(int $page)
    {
        $this->storage->addPlugin(new ListWith());

        if ($this->isAdmin) {
            $files = $this->storage->listWith(['size', 'mimetype'], '/', true);
            $offset = $page * self::PER_PAGE_ADMIN;
            $limit = self::PER_PAGE_ADMIN;
        } else {
            $userCode = $this->db->query('SELECT `user_code` FROM `users` WHERE `id` = ?', $this->userId)->fetch()->user_code;
            $files = $this->storage->listWith(['size', 'mimetype'], $userCode);
            $offset = $page * self::PER_PAGE;
            $limit = self::PER_PAGE;
        }

        $files = array_filter($files, function ($file) {
            return $file['type'] !== 'dir';
        });

        array_multisort(array_column($files, 'size'), $this->buildOrderBy(), SORT_NUMERIC, $files);

        $params = [];
        if ($this->text !== null) {
            if ($this->isAdmin) {
                [$queryMedia,] = $this->buildAdminQueries();
            } else {
                [$queryMedia,] = $this->buildUserQueries();
                $params = [$this->userId];
            }

            $params[] = '%'.htmlentities($this->text).'%';
            $paths = array_column($files, 'path');
        } else {
            if ($this->tagId !== null) {
                $paths = array_column($files, 'path');
                $ids = $this->getMediaIdsByTagId($this->tagId);
                $queryMedia = 'SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `uploads`.`storage_path` IN ("'.implode('","', $paths).'") AND `uploads`.`id` IN ('.implode(',', $ids).')';
            } else {
                $files = array_slice($files, $offset, $limit, true);
                $paths = array_column($files, 'path');
                $queryMedia = 'SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `uploads`.`storage_path` IN ("'.implode('","', $paths).'")';
            }
        }

        $medias = $this->db->query($queryMedia, $params)->fetchAll();

        $paths = array_flip($paths);
        foreach ($medias as $media) {
            $paths[$media->storage_path] = $media;
        }

        $tags = $this->getTags(array_column($medias, 'id'));

        $this->media = [];
        foreach ($files as $file) {
            $media = $paths[$file['path']];
            if (is_object($media)) {
                $media->size = humanFileSize($file['size']);
                $media->extension = $file['extension'];
                $media->mimetype = $file['mimetype'];
                $this->media[] = $media;
                if (array_key_exists($media->id, $tags)) {
                    $media->tags = $tags[$media->id];
                } else {
                    $media->tags = [];
                }
            }
        }

        $this->pages = count($this->media) / $limit;

        if ($this->text !== null || $this->tagId !== null) {
            $this->media = array_slice($this->media, $offset, $limit, true);
        }

        return $this;
    }

    protected function buildAdminQueries()
    {
        $queryPages = 'SELECT COUNT(*) AS `count` FROM `uploads`';
        $queryMedia = 'SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id`';

        if ($this->text !== null || $this->tagId !== null) {
            $queryMedia .= ' WHERE';
            $queryPages .= ' WHERE';
        }

        if ($this->text !== null) {
            $queryMedia .= ' `uploads`.`filename` LIKE ?';
            $queryPages .= ' `filename` LIKE ?';
        }

        if ($this->tagId !== null) {
            if ($this->text !== null) {
                $queryMedia .= ' AND';
                $queryPages .= ' AND';
            }

            $ids = $this->getMediaIdsByTagId($this->tagId);
            $queryMedia .= ' `uploads`.`id` IN ('.implode(',', $ids).')';
            $queryPages .= ' `uploads`.`id` IN ('.implode(',', $ids).')';
        }

        return [$queryMedia, $queryPages];
    }

    protected function buildUserQueries()
    {
        $queryPages = 'SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` = ?';
        $queryMedia = 'SELECT `uploads`.*,`users`.`user_code`, `users`.`username` FROM `uploads` INNER JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_id` = ?';

        if ($this->text !== null) {
            $queryMedia .= ' AND `uploads`.`filename` LIKE ? ';
            $queryPages .= ' AND `filename` LIKE ?';
        }

        if ($this->tagId !== null) {
            $ids = $this->getMediaIdsByTagId($this->tagId);
            $queryMedia .= ' AND `uploads`.`id` IN ('.implode(',', $ids).')';
            $queryPages .= ' AND `uploads`.`id` IN ('.implode(',', $ids).')';
        }

        return [$queryMedia, $queryPages];
    }

    protected function buildOrderBy()
    {
        switch ($this->orderBy) {
            case self::ORDER_NAME:
                return ' ORDER BY `filename` '.$this->orderMode;
            case self::ORDER_TIME:
                return ' ORDER BY `timestamp` '.$this->orderMode;
            case self::ORDER_SIZE:
                return ($this->orderMode === 'ASC') ? SORT_ASC : SORT_DESC;
            default:
                return '';
        }
    }

    /**
     * @param  array  $mediaIds
     * @return array
     */
    protected function getTags(array $mediaIds)
    {
        $allTags = $this->db->query('SELECT `uploads_tags`.`upload_id`,`tags`.`id`, `tags`.`name` FROM `uploads_tags` INNER JOIN `tags` ON `uploads_tags`.`tag_id` = `tags`.`id` WHERE `uploads_tags`.`upload_id` IN ("'.implode('","', $mediaIds).'") ORDER BY `tags`.`timestamp`')->fetchAll();
        $tags = [];
        foreach ($allTags as $tag) {
            $tags[$tag->upload_id][$tag->id] = $tag->name;
        }
        return $tags;
    }

    /**
     * @param $tagId
     * @return array
     */
    protected function getMediaIdsByTagId($tagId)
    {
        $mediaIds = $this->db->query('SELECT `upload_id` FROM `uploads_tags` WHERE `tag_id` = ?', $tagId)->fetchAll();
        $ids = [];
        foreach ($mediaIds as $pivot) {
            $ids[] = $pivot->upload_id;
        }
        return $ids;
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
