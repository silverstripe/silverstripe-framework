<?php

class GDImageTest extends ImageTest {

	public function setUp() {
		$skip = !extension_loaded("gd");
		if($skip) {
			$this->skipTest = true;
		}

		parent::setUp();

		if($skip) {
			$this->markTestSkipped("The GD extension is required");
		}

		Config::inst()->update('Injector', 'Image_Backend', 'GDBackend');
	}

	public function tearDown() {
		$cache = SS_Cache::factory('GDBackend_Manipulations');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		parent::tearDown();
	}
}
