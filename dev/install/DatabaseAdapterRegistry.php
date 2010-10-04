<?php

/**
 * This class keeps track of the available database adapters
 * and provides a meaning of registering community built
 * adapters in to the installer process.
 *
 * @package installer
 * @author Tom Rix
 */
class DatabaseAdapterRegistry {
	
	/**
	 * Internal array of registered database adapters
	 */
	private static $adapters = array();
	
	/**
	 * Add new adapter to the registry
	 * @param array $config Associative array of configuration details
	 */
	static function register($config) {
		$missingExtensionText = isset($config['missingExtensionText'])
			? $config['missingExtensionText']
			: 'The PHP extension is missing, please enable or install it.';

		$moduleName = array_shift(explode('/', $config['helperPath']));
		$missingModuleText = isset($config['missingModuleText'])
			? $config['missingModuleText']
			: 'The SilverStripe module, '.$moduleName.', is missing or incomplete. Please <a href="http://silverstripe.org/modules">download it</a>.';
		
		$config['missingModuleText'] = $missingModuleText;
		$config['missingExtensionText'] = $missingExtensionText;
		
		self::$adapters[$config['class']] = $config;
	}
	
	static function unregister($class) {
		if(isset($adapters[$class])) unset($adapters[$class]);
	}
	
	static function autodiscover() {
		foreach(glob(dirname(__FILE__) . '/../../../*', GLOB_ONLYDIR) as $directory) {
			if(file_exists($directory . '/_register_database.php')) include_once($directory . '/_register_database.php');
		}
	}
	
	static function get_adapters() {
		return self::$adapters;
	}

}