<?php

/**
 * This class keeps track of the available database adapters
 * and provides a meaning of registering community built
 * adapters in to the installer process.
 *
 * @package framework
 * @author Tom Rix
 */
class DatabaseAdapterRegistry {

	/**
	 * Default database connector registration fields
	 *
	 * @var array
	 */
	private static $default_fields = array(
		'server' => array(
			'title' => 'Database server',
			'envVar' => 'SS_DATABASE_SERVER',
			'default' => 'localhost'
		),
		'username' => array(
			'title' => 'Database username',
			'envVar' => 'SS_DATABASE_USERNAME',
			'default' => 'root'
		),
		'password' => array(
			'title' => 'Database password',
			'envVar' => 'SS_DATABASE_PASSWORD',
			'default' => 'password'
		),
		'database' => array(
			'title' => 'Database name',
			'default' => 'SS_mysite',
			'attributes' => array(
				"onchange" => "this.value = this.value.replace(/[\/\\:*?&quot;<>|. \t]+/g,'');"
			)
		),
	);

	/**
	 * Internal array of registered database adapters
	 *
	 * @var array
	 */
	private static $adapters = array();

	/**
	 * Add new adapter to the registry
	 *
	 * @param array $config Associative array of configuration details
	 */
	public static function register($config) {
		$missingExtensionText = isset($config['missingExtensionText'])
			? $config['missingExtensionText']
			: 'The PHP extension is missing, please enable or install it.';

		$path = explode('/', $config['helperPath']);
		$moduleName = array_shift($path);
		$missingModuleText = isset($config['missingModuleText'])
			? $config['missingModuleText']
			: 'The SilverStripe module, '.$moduleName.', is missing or incomplete.'
				. ' Please <a href="http://silverstripe.org/modules">download it</a>.';

		$config['missingModuleText'] = $missingModuleText;
		$config['missingExtensionText'] = $missingExtensionText;

		// set default fields if none are defined already
		if(!isset($config['fields'])) $config['fields'] = self::$default_fields;

		self::$adapters[$config['class']] = $config;
	}

	/**
	 * Unregisters a database connector by classname
	 *
	 * @param string $class
	 */
	public static function unregister($class) {
		unset(self::$adapters[$class]);
	}

	/**
	 * Detects all _register_database.php files and invokes them
	 */
	public static function autodiscover() {
		foreach(glob(dirname(__FILE__) . '/../../../*', GLOB_ONLYDIR) as $directory) {
			if(file_exists($directory . '/_register_database.php')) {
				include_once($directory . '/_register_database.php');
			}
		}
	}

	/**
	 * Detects all _configure_database.php files and invokes them
	 * Called by ConfigureFromEnv.php
	 */
	public static function autoconfigure() {
		foreach(glob(dirname(__FILE__) . '/../../../*', GLOB_ONLYDIR) as $directory) {
			if(file_exists($directory . '/_configure_database.php')) {
				include_once($directory . '/_configure_database.php');
			}
		}
	}

	/**
	 * Return all registered adapters
	 *
	 * @return array
	 */
	public static function get_adapters() {
		return self::$adapters;
	}

	/**
	 * Returns registry data for a class
	 *
	 * @param string $class
	 * @return array List of adapter properties
	 */
	public static function get_adapter($class) {
		if(isset(self::$adapters[$class])) return self::$adapters[$class];
	}

	/**
	 * Retrieves default field configuration
	 *
	 * @return array
	 */
	public static function get_default_fields() {
		return self::$default_fields;
	}

}
