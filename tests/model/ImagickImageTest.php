<?php
class ImagickImageTest extends ImageTest {
	public function setUp() {
		if(!extension_loaded("imagick")) {
			$this->markTestSkipped("The Imagick extension is not available.");
			$this->skipTest = true;
			parent::setUp();
			return;
		}

		Image::set_backend("ImagickBackend");

		parent::setUp();
	}
}
