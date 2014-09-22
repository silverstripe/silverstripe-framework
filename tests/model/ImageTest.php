<?php

/**
 * @package framework
 * @subpackage tests
 */
class ImageTest extends SapphireTest {
	
	protected static $fixture_file = 'ImageTest.yml';
	
	protected $origBackend;
	
	public function setUp() {
		if(get_class($this) == "ImageTest") $this->skipTest = true;
	
		parent::setUp();
		
		$this->origBackend = Image::get_backend();
		
		if($this->skipTest)
			return;
	
		if(!file_exists(ASSETS_PATH)) mkdir(ASSETS_PATH);

		// Create a test folders for each of the fixture references
		$folderIDs = $this->allFixtureIDs('Folder');
		
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			
			if(!file_exists(BASE_PATH."/$folder->Filename")) mkdir(BASE_PATH."/$folder->Filename");
		}
	}
	
	public function tearDown() {
		if($this->origBackend) Image::set_backend($this->origBackend);
	
		/* Remove the test files that we've created */
		$fileIDs = $this->allFixtureIDs('Image');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('Image', $fileID);
			if($file && file_exists(BASE_PATH."/$file->Filename")) unlink(BASE_PATH."/$file->Filename");
		}

		/* Remove the test folders that we've crated */
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			if($folder && file_exists(BASE_PATH."/$folder->Filename")) {
				Filesystem::removeFolder(BASE_PATH."/$folder->Filename");
			}
		}
		
		parent::tearDown();
	}
	
	public function testGetTagWithTitle() {
		$image = $this->objFromFixture('Image', 'imageWithTitle');
		$expected = '<img src="' . Director::baseUrl() 
			. 'assets/ImageTest/test_image.png" alt="This is a image Title" />';
		$actual = $image->getTag();
		
		$this->assertEquals($expected, $actual);
	}
	
	public function testGetTagWithoutTitle() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$expected = '<img src="' . Director::baseUrl() . 'assets/ImageTest/test_image.png" alt="test_image" />';
		$actual = $image->getTag();
		
		$this->assertEquals($expected, $actual);
	}
	
	public function testGetTagWithoutTitleContainingDots() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitleContainingDots');
		$expected = '<img src="' . Director::baseUrl()
			. 'assets/ImageTest/test.image.with.dots.png" alt="test.image.with.dots" />';
		$actual = $image->getTag();
		
		$this->assertEquals($expected, $actual);
	}
	
	/**
	 * Tests that multiple image manipulations may be performed on a single Image
	 */
	public function testMultipleGenerateManipulationCalls() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		
		$imageFirst = $image->SetWidth(200);
		$this->assertNotNull($imageFirst);
		$expected = 200;
		$actual = $imageFirst->getWidth();

		$this->assertEquals($expected, $actual);
		
		$imageSecond = $imageFirst->setHeight(100);
		$this->assertNotNull($imageSecond);
		$expected = 100;
		$actual = $imageSecond->getHeight();
		$this->assertEquals($expected, $actual);
	}
	
	/**
	 * Tests that image manipulations that do not affect the resulting dimensions
	 * of the output image do not resample the file.
	 */
	public function testReluctanceToResampling() {

		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$this->assertTrue($image->isSize(300, 300));
		
		// Set width to 300 pixels
		$imageSetWidth = $image->SetWidth(300);
		$this->assertEquals($imageSetWidth->getWidth(), 300);
		$this->assertEquals($image->Filename, $imageSetWidth->Filename);
		
		// Set height to 300 pixels
		$imageSetHeight = $image->SetHeight(300);
		$this->assertEquals($imageSetHeight->getHeight(), 300);
		$this->assertEquals($image->Filename, $imageSetHeight->Filename);
		
		// Crop image to 300 x 300
		$imageCropped = $image->CroppedImage(300, 300);
		$this->assertTrue($imageCropped->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageCropped->Filename);
		
		// Resize (padded) to 300 x 300
		$imageSized = $image->SetSize(300, 300);
		$this->assertTrue($imageSized->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageSized->Filename);
		
		// Padded image 300 x 300 (same as above)
		$imagePadded = $image->PaddedImage(300, 300);
		$this->assertTrue($imagePadded->isSize(300, 300));
		$this->assertEquals($image->Filename, $imagePadded->Filename);
		
		// Resized (stretched) to 300 x 300
		$imageStretched = $image->ResizedImage(300, 300);
		$this->assertTrue($imageStretched->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageStretched->Filename);
		
		// SetRatioSize (various options)
		$imageSetRatioSize = $image->SetRatioSize(300, 600);
		$this->assertTrue($imageSetRatioSize->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageSetRatioSize->Filename);
		$imageSetRatioSize = $image->SetRatioSize(600, 300);
		$this->assertTrue($imageSetRatioSize->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageSetRatioSize->Filename);
		$imageSetRatioSize = $image->SetRatioSize(300, 300);
		$this->assertTrue($imageSetRatioSize->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageSetRatioSize->Filename);
	}

	/**
	 * Tests that image manipulations that do not affect the resulting dimensions
	 * of the output image resample the file when force_resample is set to true.
	 */
	public function testForceResample() {

		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$this->assertTrue($image->isSize(300, 300));

		$origForceResample = Config::inst()->get('Image', 'force_resample');
		Config::inst()->update('Image', 'force_resample', true);

		// Set width to 300 pixels
		$imageSetWidth = $image->SetWidth(300);
		$this->assertEquals($imageSetWidth->getWidth(), 300);
		$this->assertNotEquals($image->Filename, $imageSetWidth->Filename);

		// Set height to 300 pixels
		$imageSetHeight = $image->SetHeight(300);
		$this->assertEquals($imageSetHeight->getHeight(), 300);
		$this->assertNotEquals($image->Filename, $imageSetHeight->Filename);

		// Crop image to 300 x 300
		$imageCropped = $image->CroppedImage(300, 300);
		$this->assertTrue($imageCropped->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageCropped->Filename);

		// Resize (padded) to 300 x 300
		$imageSized = $image->SetSize(300, 300);
		$this->assertTrue($imageSized->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageSized->Filename);

		// Padded image 300 x 300 (same as above)
		$imagePadded = $image->PaddedImage(300, 300);
		$this->assertTrue($imagePadded->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imagePadded->Filename);

		// Resized (stretched) to 300 x 300
		$imageStretched = $image->ResizedImage(300, 300);
		$this->assertTrue($imageStretched->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageStretched->Filename);

		// SetRatioSize (various options)
		$imageSetRatioSize = $image->SetRatioSize(300, 600);
		$this->assertTrue($imageSetRatioSize->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageSetRatioSize->Filename);
		$imageSetRatioSize = $image->SetRatioSize(600, 300);
		$this->assertTrue($imageSetRatioSize->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageSetRatioSize->Filename);
		$imageSetRatioSize = $image->SetRatioSize(300, 300);
		$this->assertTrue($imageSetRatioSize->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageSetRatioSize->Filename);
		Config::inst()->update('Image', 'force_resample', $origForceResample);
	}
	
	public function testImageResize() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$this->assertTrue($image->isSize(300, 300));
		
		// Test normal resize
		$resized = $image->SetSize(150, 100);
		$this->assertTrue($resized->isSize(150, 100));
		
		// Test cropped resize
		$cropped = $image->CroppedImage(100, 200);
		$this->assertTrue($cropped->isSize(100, 200));
		
		// Test padded resize
		$padded = $image->PaddedImage(200, 100);
		$this->assertTrue($padded->isSize(200, 100));
		
		// Test SetRatioSize
		$ratio = $image->SetRatioSize(80, 160);
		$this->assertTrue($ratio->isSize(80, 80));
	}
	
	public function testGeneratedImageDeletion() {
		$image = $this->objFromFixture('Image', 'imageWithMetacharacters');
		$image_generated = $image->SetWidth(200);
		$p = $image_generated->getFullPath();
		$this->assertTrue(file_exists($p));
		$image->deleteFormattedImages();
		$this->assertFalse(file_exists($p));
	}

	/**
	 * Tests that generated images with multiple image manipulations are all deleted
	 */
	public function testMultipleGenerateManipulationCallsImageDeletion() {
		$image = $this->objFromFixture('Image', 'imageWithMetacharacters');

		$firstImage = $image->SetWidth(200);
		$firstImagePath = $firstImage->getFullPath();
		$this->assertTrue(file_exists($firstImagePath));

		$secondImage = $firstImage->SetHeight(100);
		$secondImagePath = $secondImage->getFullPath();
		$this->assertTrue(file_exists($secondImagePath));

		$image->deleteFormattedImages();
		$this->assertFalse(file_exists($firstImagePath));
		$this->assertFalse(file_exists($secondImagePath));
	}

	/**
	 * Tests path properties of cached images with multiple image manipulations
	 */
	public function testPathPropertiesCachedImage() {
		$image = $this->objFromFixture('Image', 'imageWithMetacharacters');
		$firstImage = $image->SetWidth(200);
		$firstImagePath = $firstImage->getRelativePath();
		$this->assertEquals($firstImagePath, $firstImage->Filename);

		$secondImage = $firstImage->SetHeight(100);
		$secondImagePath = $secondImage->getRelativePath();
		$this->assertEquals($secondImagePath, $secondImage->Filename);
	}
}
