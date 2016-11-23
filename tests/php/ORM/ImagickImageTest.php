<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Assets\ImagickBackend;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;

class ImagickImageTest extends ImageTest {
	public function setUp() {
		parent::setUp();

		if(!extension_loaded("imagick")) {
			$this->markTestSkipped("The Imagick extension is not available.");
			return;
		}

		/** @skipUpgrade */
		Config::inst()->update(Injector::class, 'Image_Backend', ImagickBackend::class);
	}
}
