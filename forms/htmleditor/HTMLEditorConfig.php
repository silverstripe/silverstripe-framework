<?php

/**
 * A PHP version of TinyMCE's configuration, to allow various parameters to be configured on a site or section basis
 *
 * There can be multiple HTMLEditorConfig's, which should always be created / accessed using HTMLEditorConfig::get.
 * You can then set the currently active config using set_active.
 * The order of precendence for which config is used is (lowest to highest):
 *
 * - default_config config setting
 * - Active config assigned
 * - Config name assigned to HTMLEditorField
 * - Config instance assigned to HTMLEditorField
 *
 * Typically global config changes should set the active config.
 *
 * The defaut config class can be changed via dependency injection to replace HTMLEditorConfig.
 *
 * @author "Hamish Friedlander" <hamish@silverstripe.com>
 * @package forms
 * @subpackage fields-formattedinput
 */
abstract class HTMLEditorConfig extends Object {

	/**
	 * Array of registered configurations
	 *
	 * @var HTMLEditorConfig[]
	 */
	protected static $configs = array();

	/**
	 * Identifier key of current config. This will match an array key in $configs.
	 * If left blank, will fall back to value of default_config set via config.
	 *
	 * @var string
	 */
	protected static $current = null;

	/**
	 * Name of default config. This will be ignored if $current is assigned a value.
	 *
	 * @config
	 * @var string
	 */
	private static $default_config = 'default';

	/**
	 * Get the HTMLEditorConfig object for the given identifier. This is a correct way to get an HTMLEditorConfig
	 * instance - do not call 'new'
	 *
	 * @param string $identifier The identifier for the config set. If omitted, the active config is returned.
	 * @return HTMLEditorConfig The configuration object.
	 * This will be created if it does not yet exist for that identifier
	 */
	public static function get($identifier = null) {
		if(!$identifier) {
			return static::get_active();
		}
		// Create new instance if unconfigured
		if (!isset(self::$configs[$identifier])) {
			self::$configs[$identifier] = static::create();
		}
		return self::$configs[$identifier];
	}

	/**
	 * Assign a new config for the given identifier
	 *
	 * @param string $identifier A specific identifier
	 * @param HTMLEditorConfig $config
	 * @return HTMLEditorConfig The assigned config
	 */
	public static function set_config($identifier, HTMLEditorConfig $config) {
		self::$configs[$identifier] = $config;
		return $config;
	}

	/**
	 * Set the currently active configuration object. Note that the existing active
	 * config will not be renamed to the new identifier.
	 *
	 * @param string $identifier The identifier for the config set
	 */
	public static function set_active_identifier($identifier) {
		self::$current = $identifier;
	}

	/**
	 * Get the currently active configuration identifier. Will fall back to default_config
	 * if unassigned.
	 *
	 * @return string The active configuration identifier
	 */
	public static function get_active_identifier() {
		$identifier = self::$current ?: static::config()->default_config;
		return $identifier;
	}

	/**
	 * Get the currently active configuration object
	 *
	 * @return HTMLEditorConfig The active configuration object
	 */
	public static function get_active() {
		$identifier = self::get_active_identifier();
		return self::get($identifier);
	}

	/**
	 * Assigns the currently active config an explicit instance
	 *
	 * @param HTMLEditorConfig $config
	 * @return HTMLEditorConfig The given config
	 */
	public static function set_active(HTMLEditorConfig $config) {
		$identifier = static::get_active_identifier();
		return static::set_config($identifier, $config);
	}

	/**
	 * Get the available configurations as a map of friendly_name to
	 * configuration name.
	 *
	 * @return array
	 */
	public static function get_available_configs_map() {
		$configs = array();

		foreach(self::$configs as $identifier => $config) {
			$configs[$identifier] = $config->getOption('friendly_name');
		}

		return $configs;
	}

	/**
	 * Get the current value of an option
     *
	 * @param string $key The key of the option to get
	 * @return mixed The value of the specified option
	 */
	abstract public function getOption($key);

	/**
	 * Set the value of one option
	 * @param string $key The key of the option to set
	 * @param mixed $value The value of the option to set
	 * @return $this
	 */
	abstract public function setOption($key, $value);

	/**
	 * Set multiple options. This does not merge recursively, but only at the top level.
     *
	 * @param array $options The options to set, as keys and values of the array
	 * @return $this
	 */
	abstract public function setOptions($options);

	/**
	 * Associative array of data-attributes to apply to the underlying text-area
	 *
	 * @return array
	 */
	abstract public function getAttributes();

	/**
	 * Initialise the editor on the client side
	 */
	abstract public function init();

}
