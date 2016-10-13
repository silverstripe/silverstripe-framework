<?php

use SilverStripe\Core\Config\Config;
class ImagickImageTest extends ImageTest {
	public function setUp() {
		parent::setUp();

		if(!extension_loaded("imagick")) {
			$this->markTestSkipped("The Imagick extension is not available.");
			return;
		}

		/** @skipUpgrade */
		Config::inst()->update(
			'SilverStripe\\Core\\Injector\\Injector',
			'Image_Backend',
			'SilverStripe\\Assets\\ImagickBackend'
		);
	}
}
