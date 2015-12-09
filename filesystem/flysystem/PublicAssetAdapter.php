<?php
/**
 * Created by PhpStorm.
 * User: dmooyman
 * Date: 16/12/15
 * Time: 1:54 PM
 */

namespace SilverStripe\Filesystem\Flysystem;

use Controller;
use Director;

class PublicAssetAdapter extends AssetAdapter implements PublicAdapter {

	/**
	 * Server specific configuration necessary to block http traffic to a local folder
	 *
	 * @config
	 * @var array Mapping of server configurations to configuration files necessary
	 */
	private static $server_configuration = array(
		'apache' => array(
			'.htaccess' => "Assets_HTAccess"
		),
		'microsoft-iis' => array(
			'web.config' => "Assets_WebConfig"
		)
	);

    protected function findRoot($root) {
		if ($root) {
            return parent::findRoot($root);
        }

        // Empty root will set the path to assets
        return ASSETS_PATH;
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