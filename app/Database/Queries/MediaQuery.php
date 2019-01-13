<?php

namespace App\Database\Queries;


use App\Database\DB;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Plugin\ListFiles;

class MediaQuery
{
	const PER_PAGE = 21;
	const PER_PAGE_ADMIN = 25;

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

	private $pages;
	private $media;

	/**
	 * MediaQuery constructor.
	 * @param DB $db
	 * @param bool $isAdmin
	 */
	public function __construct(DB $db, bool $isAdmin)
	{
		$this->db = $db;
		$this->isAdmin = $isAdmin;
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
	public function search(string $text)
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

		switch ($this->orderBy) {
			case self::ORDER_NAME:
				$queryMedia = sprintf($queryMedia, 'ORDER BY `filename` ' . $this->orderMode);
				break;
			default:
			case self::ORDER_TIME:
				$queryMedia = sprintf($queryMedia, 'ORDER BY `timestamp` ' . $this->orderMode);
				break;
		}

		if ($this->isAdmin) {
			$this->media = $this->db->query($queryMedia, [self::PER_PAGE_ADMIN, $page * self::PER_PAGE_ADMIN])->fetchAll();
			$this->pages = $this->db->query($queryPages)->fetch()->count / self::PER_PAGE_ADMIN;
		} else {
			$this->media = $this->db->query($queryMedia, [$this->userId, self::PER_PAGE, $page * self::PER_PAGE])->fetchAll();
			$this->pages = $this->db->query($queryPages, $this->userId)->fetch()->count / self::PER_PAGE;
		}

		$filesystem = storage();

		foreach ($this->media as $media) {
			try {
				$media->size = humanFileSize($filesystem->getSize($media->storage_path));
				$media->mimetype = $filesystem->getMimetype($media->storage_path);
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
		$filesystem = storage();
		$filesystem->addPlugin(new ListFiles());

		if ($this->isAdmin) {
			$files = $filesystem->listFiles('/', true);
			$this->pages = count($files) / self::PER_PAGE_ADMIN;

			$offset = $page * self::PER_PAGE_ADMIN;
			$limit = self::PER_PAGE_ADMIN;
		} else {
			$userCode = $this->db->query('SELECT `user_code` FROM `users` WHERE `id` = ?', [$this->userId])->fetch()->user_code;
			$files = $filesystem->listFiles($userCode);
			$this->pages = count($files) / self::PER_PAGE;

			$offset = $page * self::PER_PAGE;
			$limit = self::PER_PAGE;
		}

		array_multisort(array_column($files, 'size'), ($this->orderMode === 'ASC') ? SORT_ASC : SORT_DESC, SORT_NUMERIC, $files);

		$files = array_slice($files, $offset, $limit);
		$paths = array_column($files, 'path');

		$medias = $this->db->query('SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `uploads`.`storage_path` IN ("' . implode('","', $paths) . '")')->fetchAll();

		$paths = array_flip($paths);
		foreach ($medias as $media) {
			$paths[$media->storage_path] = $media;
		}

		$this->media = [];

		foreach ($files as $file) {
			$media = $paths[$file['path']];
			$media->size = humanFileSize($file['size']);
			try {
				$media->mimetype = $filesystem->getMimetype($file['path']);
			} catch (FileNotFoundException $e) {
				$media->mimetype = null;
			}
			$media->extension = $file['extension'];
			$this->media[] = $media;
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