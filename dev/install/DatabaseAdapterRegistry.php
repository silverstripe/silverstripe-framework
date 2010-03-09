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
	 *
	 * @param string $class classname of the adapter
	 * @param string $title friendly name for the adapter
	 * @param string $helperPath path to the DatabaseConfigurationHelper for the adapter
	 * @param boolean $supported whether or not php has the required functions
	 */
	static function register($class, $title, $helperPath, $supported, $missingModuleText = null, $missingExtensionText = null) {
		self::$adapters[$class] = array(
			'title' => $title,
			'helperPath' => $helperPath,
			'supported' => $supported
		);
		if (!$missingExtensionText) $missingExtensionText = 'The PHP extension is missing, please enable or install it.';
		if (!$missingModuleText) {
			$moduleName = array_shift(explode('/', $helperPath));
			$missingModuleText = 'The SilverStripe module, '.$moduleName.', is missing or incomplete. Please <a href="http://silverstripe.org/modules">download it</a>.';
		}
		self::$adapters[$class]['missingModuleText'] = $missingModuleText;
		self::$adapters[$class]['missingExtensionText'] = $missingExtensionText;
	}
	
	static function autodiscover() {
		foreach(glob(dirname(__FILE__).'/../../../*', GLOB_ONLYDIR) as $directory) {
			if (file_exists($directory.'/_register_database.php')) include_once($directory.'/_register_database.php');
		}
	}
	
	static function adapters() {
		return self::$adapters;
	}
}
