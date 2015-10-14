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

	/**
	 * Config compatible permissions configuration
	 *
	 * @config
	 * @var array
	 */
	private static $file_permissions = array(
		'file' => [
            'public' => 0744,
            'private' => 0700,
        ],
        'dir' => [
            'public' => 0755,
            'private' => 0700,
        ]
	);

	public function __construct($root = null, $writeFlags = LOCK_EX, $linkHandling = self::DISALLOW_LINKS) {
		// Override permissions with config
		$permissions = \Config::inst()->get(get_class($this), 'file_permissions');
		parent::__construct($root ?: ASSETS_PATH, $writeFlags, $linkHandling, $permissions);
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
