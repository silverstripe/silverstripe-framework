<?php
class ImagickImageTest extends ImageTest {
	public function setUp() {
		if(!extension_loaded("imagick")) {
			$this->markTestSkipped("The Imagick extension is not available.");
			$this->skipTest = true;
			parent::setUp();
			return;
		}
		
		parent::setUp();
		
		Image::set_backend("ImagickBackend");
		
		// Create a test files for each of the fixture references
		$fileIDs = $this->allFixtureIDs('Image');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('Image', $fileID);
			
			$image = new Imagick();
			
			$image->newImage(300,300, new ImagickPixel("white"));
			$image->setImageFormat("png");
			$image->writeImage(BASE_PATH."/{$file->Filename}");
			
			$file->write();
		}
	}
}
