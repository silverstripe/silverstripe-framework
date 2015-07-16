<?php
class ImagickImageTest extends ImageTest {
	public function setUp() {
		parent::setUp();

		if(!extension_loaded("imagick")) {
			$this->markTestSkipped("The Imagick extension is not available.");
			return;
		}
		
		Config::inst()->update('Injector', 'Image_Backend', 'ImagickBackend');
	}
}
