<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\SS_Cache;

class GDImageTest extends ImageTest {

	public function setUp() {
		parent::setUp();

		if(!extension_loaded("gd")) {
			$this->markTestSkipped("The GD extension is required");
			return;
		}

		/** @skipUpgrade */
		Config::inst()->update(
			'SilverStripe\\Core\\Injector\\Injector',
			'Image_Backend',
			'SilverStripe\\Assets\\GDBackend'
		);
	}

	public function tearDown() {
		$cache = SS_Cache::factory('GDBackend_Manipulations');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		parent::tearDown();
	}
}
