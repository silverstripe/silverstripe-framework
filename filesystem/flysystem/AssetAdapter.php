<?php

namespace SilverStripe\Filesystem\Flysystem;

use League\Flysystem\Adapter\Local;
use SilverStripe\ORM\ArrayList;


/**
 * Adapter for local filesystem based on assets directory
 *
 * @package framework
 * @subpackage filesystem
 */
class AssetAdapter extends Local {

	/**
	 * Server specific configuration necessary to block http traffic to a local folder
	 *
	 * @config
	 * @var array Mapping of server configurations to configuration files necessary
	 */
	private static $server_configuration = array();

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
		// Get root path
		$root = $this->findRoot($root);

		// Override permissions with config
		$permissions = \Config::inst()->get(get_class($this), 'file_permissions');
		parent::__construct($root, $writeFlags, $linkHandling, $permissions);

		// Configure server
		$this->configureServer();
	}

	/**
	 * Determine the root folder absolute system path
	 *
	 * @param string $root
	 * @return string
	 */
	protected function findRoot($root) {
		// Empty root will set the path to assets
		if (!$root) {
			throw new \InvalidArgumentException("Missing argument for root path");
		}

		// Substitute leading ./ with BASE_PATH
		if(strpos($root, './') === 0) {
			return BASE_PATH . substr($root, 1);
		}

		// Substitute leading ./ with parent of BASE_PATH, in case storage is outside of the webroot.
		if(strpos($root, '../') === 0) {
			return dirname(BASE_PATH) . substr($root, 2);
		}

		return $root;
	}

	/**
	 * Force flush and regeneration of server files
	 */
	public function flush() {
		$this->configureServer(true);
	}

	/**
	 * Configure server files for this store
	 *
	 * @param bool $forceOverwrite Force regeneration even if files already exist
	 */
	protected function configureServer($forceOverwrite = false) {
		// Get server type
		$type = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '*';
		list($type) = explode('/', strtolower($type));

		// Determine configurations to write
		$rules = \Config::inst()->get(get_class($this), 'server_configuration', \Config::FIRST_SET);
		if(empty($rules[$type])) {
			return;
		}
		$configurations = $rules[$type];

		// Apply each configuration
		$config = new \League\Flysystem\Config();
		$config->set('visibility', 'private');
		foreach($configurations as $file => $template) {
			if ($forceOverwrite || !$this->has($file)) {
				// Evaluate file
				$content = $this->renderTemplate($template);
				$success = $this->write($file, $content, $config);
				if(!$success) {
					throw new \Exception("Error writing server configuration file \"{$file}\"");
				}
			}
		}
	}

	/**
	 * Render server configuration file from a template file
	 *
	 * @param string $template
	 * @return \HTMLText Rendered results
	 */
	protected function renderTemplate($template) {
		// Build allowed extensions
		$allowedExtensions = new ArrayList();
		foreach(\File::config()->allowed_extensions as $extension) {
			if($extension) {
				$allowedExtensions->push(new \ArrayData(array(
					'Extension' => preg_quote($extension)
				)));
			}
		}

		$viewer = new \SSViewer(array($template));
		return (string)$viewer->process(new \ArrayData(array(
			'AllowedExtensions' => $allowedExtensions
		)));
	}

}
