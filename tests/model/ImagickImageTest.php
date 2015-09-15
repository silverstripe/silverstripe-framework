<?php
class ImagickImageTest extends ImageTest {
	public function setUp() {
		$skip = !extension_loaded("imagick");
		if($skip) {
			$this->skipTest = true;
		}

		parent::setUp();
		
		if($skip) {
			$this->markTestSkipped("The Imagick extension is not available.");
		}

		
		Config::inst()->update('Injector', 'Image_Backend', 'ImagickBackend');
	}
}
