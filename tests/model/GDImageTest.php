<?php

class GDImageTest extends ImageTest {

	public function setUp() {
		if(!extension_loaded("gd")) {
			$this->markTestSkipped("The GD extension is required");
			$this->skipTest = true;
			parent::setUp();
			return;
		}

		Image::set_backend("GDBackend");

		parent::setUp();
	}

	public function tearDown() {
		$cache = SS_Cache::factory('GDBackend_Manipulations');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		parent::tearDown();
	}

	/**
	 * Test that the cache of manipulation failures is cleared when deleting
	 * the image object
	 * @return void
	 */
	public function testCacheCleaningOnDelete() {
		$image = $this->objFromFixture('Image', 'imageWithTitle');
		$cache = SS_Cache::factory('GDBackend_Manipulations');
		$fullPath = $image->getFullPath();
		$key = md5(implode('_', array($fullPath, filemtime($fullPath))));

		try {
			// Simluate a failed manipulation
			$gdFailure = new GDBackend_Failure($fullPath, array('ScaleWidth', 123));
			$this->fail('GDBackend_Failure should throw an exception when setting image resource');
		} catch (GDBackend_Failure_Exception $e) {
			// Check that the cache has stored the manipulation failure
			$data = unserialize($cache->load($key));
			$this->assertArrayHasKey('ScaleWidth|123', $data);
			$this->assertTrue($data['ScaleWidth|123']);

			// Delete the image object
			$image->delete();

			// Check that the cache has been removed
			$data = unserialize($cache->load($key));
			$this->assertFalse($data);
		}
	}

}
