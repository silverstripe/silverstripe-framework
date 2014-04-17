<?php

/**
 * Generic PhpUnitWrapper.
 * Originally intended for use with Composer based installations, but will work
 * with any fully functional autoloader.
 * Extends PhpUnitWrapper_3_5 to inherit useful (and compatible) functionality
 * for before/after test hooks.
 */
class PhpUnitWrapper_Generic extends PhpUnitWrapper_3_5 {

	protected $version = null;

	/**
	 * Returns a version string, like 3.7.34 or 4.2-dev.
	 * @return string
	 */
	public function getVersion() {
		return PHPUnit_Runner_Version::id();
	}

	/**
	 * No work is needed as the autoloader takes care of initialising classes.
	 */
	public function init() {
	}

}
