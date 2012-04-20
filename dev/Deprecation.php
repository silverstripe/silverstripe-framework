<?php

/**
 * Handles raising an notice when accessing a deprecated method
 *
 * A pattern used in SilverStripe when deprecating a method is to add something like
 *   user_error('This method is deprecated', E_USER_NOTICE);
 * to the method
 *
 * However sometimes we want to mark that a method will be deprecated in some future version and shouldn't be used in
 * new code, but not forbid in the current version - for instance when that method is still heavily used in framework
 * or cms.
 *
 * This class abstracts the above pattern and adds a way to do that.
 *
 * Each call to notice passes a version that the notice will be valid from. Additionally this class has a notion of the
 * version it should use when deciding whether to raise the notice. If that version is equal to or greater than the
 * notices version (and SilverStripe is in dev mode) a deprecation message will be raised.
 *
 * Normally the checking version will be the release version of SilverStripe, but a developer can choose to set it to a
 * future version, to see how their code will behave in future versions.
 *
 * Modules can also set the version for calls they make - either setting it to a future version in order to ensure
 * forwards compatibility or setting it backwards if a module has not yet removed references to deprecated methods.
 *
 * When set per-module, only direct calls to deprecated methods from those modules are considered - if the module
 * calls a non-module method which then calls a deprecated method, that call will use the global check version, not
 * the module specific check version.
 *
 * @package framework
 * @subpackage dev
 */
class Deprecation {

	/**
	 *
	 * @var string
	 */
	protected static $version;

	/**
	 *
	 * @var array
	 */
	protected static $module_version_overrides = array();

	/**
	 * @var int - the notice level to raise on a deprecation notice. Defaults to E_USER_DEPRECATED if that exists,
	 * E_USER_NOTICE if not
	 */
	public static $notice_level = null;

	/**
	 * Set the version that is used to check against the version passed to notice. If the ::notice version is
	 * greater than or equal to this version, a message will be raised
	 *
	 * @static
	 * @param $ver string -
	 *     A php standard version string, see http://php.net/manual/en/function.version-compare.php for details.
	 * @param null $forModule string -
	 *    The name of a module. The passed version will be used as the check value for
	 *    calls directly from this module rather than the global value
	 * @return void
	 */
	public static function notification_version($ver, $forModule = null) {
		if ($forModule) {
			self::$module_version_overrides[$forModule] = $ver;
		}
		else {
			self::$version = $ver;
		}
	}

	/**
	 * Given a backtrace, get the module name from the caller two removed (the caller of the method that called #notice)
	 *
	 * @static
	 * @param $backtrace array - a backtrace as returned from debug_backtrace
	 * @return string - the name of the module the call came from, or null if we can't determine
	 */
	protected static function get_calling_module_from_trace($backtrace) {
		if (!isset($backtrace[1]['file'])) return;

		$callingfile = $backtrace[1]['file'];

		global $manifest;
		foreach ($manifest->getModules() as $name => $path) {
			if (strpos($callingfile, $path) === 0) {
				return $name;
			}
		}
	}

	/**
	 * Given a backtrace, get the method name from the immediate parent caller (the caller of #notice)
	 *
	 * @static
	 * @param $backtrace array - a backtrace as returned from debug_backtrace
	 * @param $level - 1 (default) will return immediate caller, 2 will return caller's caller, etc.
	 * @return string - the name of the method
	 */
	protected static function get_called_method_from_trace($backtrace, $level = 1) {
		$level = (int)$level;
		if(!$level) $level = 1;
		$called = $backtrace[$level];

		if (isset($called['class'])) {
			return $called['class'] . $called['type'] . $called['function'];
		}
		else {
			return $called['function'];
		}
	}

	/**
	 * Raise a notice indicating the method is deprecated if the version passed as the second argument is greater
	 * than or equal to the check version set via ::notification_version
	 *
	 * @static
	 * @param $string - The notice to raise
	 * @param $atVersion - The version at which this notice should start being raised
	 * @return void
	 */
	public static function notice($atVersion, $string = '') {
		// Never raise deprecation notices in a live environment
		if(Director::isLive()) return;

		// If you pass #.#, assume #.#.0
		if(preg_match('/^[0-9]+\.[0-9]+$/', $atVersion)) $atVersion .= '.0';

		$checkVersion = self::$version;
		// Getting a backtrace is slow, so we only do it if we need it
		$backtrace = null;

		if(self::$module_version_overrides) {
			$module = self::get_calling_module_from_trace($backtrace = debug_backtrace(0));
			if(isset(self::$module_version_overrides[$module])) $checkVersion = self::$module_version_overrides[$module];
		}

		// Check the version against the notice version
		if ($checkVersion && version_compare($checkVersion, $atVersion, '>=')) {
			// Get the calling method
			if (!$backtrace) $backtrace = debug_backtrace(0);
			$caller = self::get_called_method_from_trace($backtrace);

			// Get the level to raise the notice as
			$level = self::$notice_level;
			if (!$level) $level = E_USER_DEPRECATED;

			// Then raise the notice
			if(substr($string,-1) != '.') $string .= ".";

			$string .= " Called from " . self::get_called_method_from_trace($backtrace, 2) . '.';

			user_error($caller.' is deprecated.'.($string ? ' '.$string : ''), $level);
		}
	}

	/**
	 * Method for when testing. Dump all the current version settings to a variable for later passing to restore
	 * @return array - opaque array that should only be used to pass to ::restore_version_settings
	 */
	public static function dump_settings() {
		return array(
			'level' => self::$notice_level,
			'version' => self::$version,
			'moduleVersions' => self::$module_version_overrides
		);
	}

	/**
	 * Method for when testing. Restore all the current version settings from a variable
	 * @static
	 * @param $settings array - An array as returned by ::dump_version_settings
	 * @return void
	 */
	public static function restore_settings($settings) {
		self::$notice_level = $settings['level'];
		self::$version = $settings['version'];
		self::$module_version_overrides = $settings['moduleVersions'];
	}
}
