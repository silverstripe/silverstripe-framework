<?php

namespace SilverStripe\Filesystem\Flysystem;

use Controller;
use Director;
use League\Flysystem\Adapter\Local;

/**
 * Adaptor for local filesystem based on assets directory
 *
 * @package framework
 * @subpackage filesystem
 */
class AssetAdapter extends Local {

	public function __construct($root = null, $writeFlags = LOCK_EX, $linkHandling = self::DISALLOW_LINKS) {
		parent::__construct($root ?: ASSETS_PATH, $writeFlags, $linkHandling);
	}

	/**
	 * Provide downloadable url
	 *
	 * @param string $path
	 * @return string|null
	 */
	public function getPublicUrl($path) {
		$rootPath = realpath(BASE_PATH);
		$filesPath = realpath($this->pathPrefix);

		if(stripos($filesPath, $rootPath) === 0) {
			$dir = substr($filesPath, strlen($rootPath));
			return Controller::join_links(Director::baseURL(), $dir, $path);
		}

		// File outside of webroot can't be used
		return null;
	}
}
