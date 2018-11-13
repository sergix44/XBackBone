<?php

require __DIR__ . '/../vendor/autoload.php';

if (!function_exists('humanFileSize')) {
	/**
	 * Generate a human readable file size
	 * @param $size
	 * @param int $precision
	 * @return string
	 */
	function humanFileSize($size, $precision = 2): string
	{
		for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
		}
		return round($size, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
	}
}

if (!function_exists('removeDirectory')) {
	/**
	 * Remove a directory and it's content
	 * @param $path
	 */
	function removeDirectory($path)
	{
		$files = glob($path . '/*');
		foreach ($files as $file) {
			is_dir($file) ? removeDirectory($file) : unlink($file);
		}
		rmdir($path);
		return;
	}
}

if (!function_exists('cleanDirectory')) {
	/**
	 * Removes all directory contents
	 * @param $path
	 */
	function cleanDirectory($path)
	{
		$directoryIterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
		$iteratorIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($iteratorIterator as $file) {
			if ($file->getFilename() !== '.gitkeep') {
				$file->isDir() ? rmdir($file) : unlink($file);
			}
		}
	}
}