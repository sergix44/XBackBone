<?php

namespace App\Web;


class Lang
{

	const DEFAULT_LANG = 'en';
	const LANG_PATH = __DIR__ . '../../resources/lang/';

	/** @var  string */
	protected static $langPath = self::LANG_PATH;

	/** @var  string */
	protected static $lang;

	/** @var  Lang */
	protected static $instance;

	/** @var  array */
	protected $cache = [];


	/**
	 * @return Lang
	 */
	public static function getInstance(): Lang
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param string $lang
	 * @param string $langPath
	 * @return Lang
	 */
	public static function build($lang = self::DEFAULT_LANG, $langPath = null): Lang
	{

		if (strlen($lang) !== 2) {
			self::$lang = strtolower(substr($lang, 0, 2));
		} else {
			self::$lang = $lang;
		}

		if ($langPath !== null) {
			self::$langPath = $langPath;
		}

		self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Recognize the current language from the request.
	 * @return bool|string
	 */
	public static function recognize()
	{
		return substr(@$_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	}


	/**
	 * @param $key
	 * @param array $args
	 * @return string
	 */
	public function get($key, $args = []): string
	{
		return $this->getString($key, self::$lang, $args);
	}

	/**
	 * @param $key
	 * @param $lang
	 * @param $args
	 * @return string
	 */
	private function getString($key, $lang, $args): string
	{

		if (array_key_exists($lang, $this->cache)) {
			$transDict = $this->cache[$lang];
		} elseif (file_exists(self::$langPath . $lang . '.lang.php')) {
			$transDict = include self::$langPath . $lang . '.lang.php';
			$this->cache[$lang] = $transDict;
		} else {
			$transDict = [];
		}

		if (array_key_exists($key, $transDict)) {
			return vsprintf($transDict[$key], $args);
		}

		if ($lang !== self::DEFAULT_LANG) {
			return $this->getString($key, self::DEFAULT_LANG, $args);
		}

		return $key;
	}
}