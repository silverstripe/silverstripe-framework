<?php

class GDImageTest extends ImageTest {

	public function setUp() {
		parent::setUp();

		if(!extension_loaded("gd")) {
			$this->markTestSkipped("The GD extension is required");
			return;
		}

		Config::inst()->update('Injector', 'Image_Backend', 'GDBackend');
	}

	public function tearDown() {
		$cache = SS_Cache::factory('GDBackend_Manipulations');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		parent::tearDown();
	}
}
