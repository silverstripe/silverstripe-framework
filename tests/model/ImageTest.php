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

		// Copy test images for each of the fixture references
		$imageIDs = $this->allFixtureIDs('Image');
		foreach($imageIDs as $imageID) {
			$image = DataObject::get_by_id('Image', $imageID);
			$filePath = BASE_PATH."/$image->Filename";
			$sourcePath = str_replace('assets/ImageTest/', 'framework/tests/model/testimages/', $filePath);
			if(!file_exists($filePath)) {
				if (!copy($sourcePath, $filePath)) user_error('Failed to copy test images', E_USER_ERROR);
			}
		}
	}

	public function tearDown() {
		if($this->origBackend) Image::set_backend($this->origBackend);

		// Remove the test files that we've created
		$fileIDs = $this->allFixtureIDs('Image');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('Image', $fileID);
			if($file && file_exists(BASE_PATH."/$file->Filename")) unlink(BASE_PATH."/$file->Filename");
		}

		// Remove the test folders that we've created
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			if($folder && file_exists(BASE_PATH."/$folder->Filename")) {
				Filesystem::removeFolder(BASE_PATH."/$folder->Filename");
			}
			if($folder && file_exists(BASE_PATH."/".$folder->Filename."_resampled")) {
				Filesystem::removeFolder(BASE_PATH."/".$folder->Filename."_resampled");
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

		$imageFirst = $image->ScaleWidth(200);
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
		$imageScaleWidth = $image->ScaleWidth(300);
		$this->assertEquals($imageScaleWidth->getWidth(), 300);
		$this->assertEquals($image->Filename, $imageScaleWidth->Filename);

		// Set height to 300 pixels
		$imageScaleHeight = $image->ScaleHeight(300);
		$this->assertEquals($imageScaleHeight->getHeight(), 300);
		$this->assertEquals($image->Filename, $imageScaleHeight->Filename);

		// Crop image to 300 x 300
		$imageCropped = $image->Fill(300, 300);
		$this->assertTrue($imageCropped->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageCropped->Filename);

		// Resize (padded) to 300 x 300
		$imageSized = $image->Pad(300, 300);
		$this->assertTrue($imageSized->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageSized->Filename);

		// Padded image 300 x 300 (same as above)
		$imagePadded = $image->Pad(300, 300);
		$this->assertTrue($imagePadded->isSize(300, 300));
		$this->assertEquals($image->Filename, $imagePadded->Filename);

		// Resized (stretched) to 300 x 300
		$imageStretched = $image->ResizedImage(300, 300);
		$this->assertTrue($imageStretched->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageStretched->Filename);

		// Fit (various options)
		$imageFit = $image->Fit(300, 600);
		$this->assertTrue($imageFit->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageFit->Filename);
		$imageFit = $image->Fit(600, 300);
		$this->assertTrue($imageFit->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageFit->Filename);
		$imageFit = $image->Fit(300, 300);
		$this->assertTrue($imageFit->isSize(300, 300));
		$this->assertEquals($image->Filename, $imageFit->Filename);
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
		$imageScaleWidth = $image->ScaleWidth(300);
		$this->assertEquals($imageScaleWidth->getWidth(), 300);
		$this->assertNotEquals($image->Filename, $imageScaleWidth->Filename);

		// Set height to 300 pixels
		$imageScaleHeight = $image->ScaleHeight(300);
		$this->assertEquals($imageScaleHeight->getHeight(), 300);
		$this->assertNotEquals($image->Filename, $imageScaleHeight->Filename);

		// Crop image to 300 x 300
		$imageCropped = $image->Fill(300, 300);
		$this->assertTrue($imageCropped->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageCropped->Filename);

		// Resize (padded) to 300 x 300
		$imageSized = $image->Pad(300, 300);
		$this->assertTrue($imageSized->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageSized->Filename);

		// Padded image 300 x 300 (same as above)
		$imagePadded = $image->Pad(300, 300);
		$this->assertTrue($imagePadded->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imagePadded->Filename);

		// Resized (stretched) to 300 x 300
		$imageStretched = $image->ResizedImage(300, 300);
		$this->assertTrue($imageStretched->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageStretched->Filename);

		// Fit (various options)
		$imageFit = $image->Fit(300, 600);
		$this->assertTrue($imageFit->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageFit->Filename);
		$imageFit = $image->Fit(600, 300);
		$this->assertTrue($imageFit->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageFit->Filename);
		$imageFit = $image->Fit(300, 300);
		$this->assertTrue($imageFit->isSize(300, 300));
		$this->assertNotEquals($image->Filename, $imageFit->Filename);
		Config::inst()->update('Image', 'force_resample', $origForceResample);
	}

	public function testImageResize() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$this->assertTrue($image->isSize(300, 300));

		// Test normal resize
		$resized = $image->Pad(150, 100);
		$this->assertTrue($resized->isSize(150, 100));

		// Test cropped resize
		$cropped = $image->Fill(100, 200);
		$this->assertTrue($cropped->isSize(100, 200));

		// Test padded resize
		$padded = $image->Pad(200, 100);
		$this->assertTrue($padded->isSize(200, 100));

		// Test Fit
		$ratio = $image->Fit(80, 160);
		$this->assertTrue($ratio->isSize(80, 80));

		// Test FitMax
		$fitMaxDn = $image->FitMax(200, 100);
		$this->assertTrue($fitMaxDn->isSize(100, 100));
		$fitMaxUp = $image->FitMax(500, 400);
		$this->assertTrue($fitMaxUp->isSize(300, 300));
		
		//Test ScaleMax
		$scaleMaxWDn = $image->ScaleMaxWidth(200);
		$this->assertTrue($scaleMaxWDn->isSize(200, 200));
		$scaleMaxWUp = $image->ScaleMaxWidth(400);
		$this->assertTrue($scaleMaxWUp->isSize(300, 300));
		$scaleMaxHDn = $image->ScaleMaxHeight(200);
		$this->assertTrue($scaleMaxHDn->isSize(200, 200));
		$scaleMaxHUp = $image->ScaleMaxHeight(400);
		$this->assertTrue($scaleMaxHUp->isSize(300, 300));

		// Test FillMax
		$cropMaxDn = $image->FillMax(200, 100);
		$this->assertTrue($cropMaxDn->isSize(200, 100));
		$cropMaxUp = $image->FillMax(400, 200);
		$this->assertTrue($cropMaxUp->isSize(300, 150));
		
		// Test Clip
		$clipWDn = $image->CropWidth(200);
		$this->assertTrue($clipWDn->isSize(200, 300));
		$clipWUp = $image->CropWidth(400);
		$this->assertTrue($clipWUp->isSize(300, 300));
		$clipHDn = $image->CropHeight(200);
		$this->assertTrue($clipHDn->isSize(300, 200));
		$clipHUp = $image->CropHeight(400);
		$this->assertTrue($clipHUp->isSize(300, 300));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGenerateImageWithInvalidParameters() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$image->setHeight('String');
		$image->Pad(600,600,'XXXXXX');
	}

	public function testCacheFilename() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$imageFirst = $image->Pad(200,200,'CCCCCC');
		$imageFilename = $imageFirst->getFullPath();
			// Encoding of the arguments is duplicated from cacheFilename
		$neededPart = 'Pad' . Convert::base64url_encode(array(200,200,'CCCCCC'));
		$this->assertContains($neededPart, $imageFilename, 'Filename for cached image is correctly generated');
	}

	public function testMultipleGenerateManipulationCalls_Regeneration() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$folder = new SS_FileFinder();

		$imageFirst = $image->Pad(200,200);
		$this->assertNotNull($imageFirst);
		$expected = 200;
		$actual = $imageFirst->getWidth();

		$this->assertEquals($expected, $actual);

		$imageSecond = $imageFirst->setHeight(100);
		$this->assertNotNull($imageSecond);
		$expected = 100;
		$actual = $imageSecond->getHeight();
		$this->assertEquals($expected, $actual);

		$imageThird = $imageSecond->Pad(600,600,'0F0F0F');
		// Encoding of the arguments is duplicated from cacheFilename
		$argumentString = Convert::base64url_encode(array(600,600,'0F0F0F'));
		$this->assertNotNull($imageThird);
		$this->assertContains($argumentString, $imageThird->getFullPath(),
			'Image contains background color for padded resizement');

		$imageThirdPath = $imageThird->getFullPath();
		$filesInFolder = $folder->find(dirname($imageThirdPath));
		$this->assertEquals(3, count($filesInFolder),
			'Image folder contains only the expected number of images before regeneration');

		$stats = getimagesize($imageThirdPath);
		$this->assertEquals(3, $image->regenerateFormattedImages(),
			'Cached images were regenerated in the right number');
		$this->assertEquals($stats, getimagesize($imageThirdPath), 'Regeneration of third image is correct');

		/* Check that no other images exist, to ensure that the regeneration did not create other images */
		$this->assertEquals($filesInFolder, $folder->find(dirname($imageThirdPath)),
			'Image folder contains only the expected image files after regeneration');
	}

	public function testRegenerateImages() {
		$image = $this->objFromFixture('Image', 'imageWithMetacharacters');
		$image_generated = $image->ScaleWidth(200);
		$p = $image_generated->getFullPath();
		$this->assertTrue(file_exists($p), 'Resized image exists after creation call');
		$this->assertEquals(1, $image->regenerateFormattedImages(), 'Cached images were regenerated correct');
		$this->assertEquals($image_generated->getWidth(), 200,
			'Resized image has correct width after regeneration call');
		$this->assertTrue(file_exists($p), 'Resized image exists after regeneration call');
	}

	/**
	 * Tests that cached images are regenerated properly after a cached file is renamed with new arguments
	 * ToDo: This doesn't seem like something that is worth testing - what is the point of this?
	 */
	public function testRegenerateImagesWithRenaming() {
		$image = $this->objFromFixture('Image', 'imageWithMetacharacters');
		$image_generated = $image->ScaleWidth(200);
		$p = $image_generated->getFullPath();
		$this->assertTrue(file_exists($p), 'Resized image exists after creation call');

		// Encoding of the arguments is duplicated from cacheFilename
		$oldArgumentString = Convert::base64url_encode(array(200));
		$newArgumentString = Convert::base64url_encode(array(300));

		$newPath = str_replace($oldArgumentString, $newArgumentString, $p);
		$newRelative = str_replace($oldArgumentString, $newArgumentString, $image_generated->getFileName());
		rename($p, $newPath);
		$this->assertFalse(file_exists($p), 'Resized image does not exist at old path after renaming');
		$this->assertTrue(file_exists($newPath), 'Resized image exists at new path after renaming');
		$this->assertEquals(1, $image->regenerateFormattedImages(),
			'Cached images were regenerated in the right number');

		$image_generated_2 = new Image_Cached($newRelative);
		$this->assertEquals(300, $image_generated_2->getWidth(), 'Cached image was regenerated with correct width');
	}

	public function testGeneratedImageDeletion() {
		$image = $this->objFromFixture('Image', 'imageWithMetacharacters');
		$image_generated = $image->ScaleWidth(200);
		$p = $image_generated->getFullPath();
		$this->assertTrue(file_exists($p), 'Resized image exists after creation call');
		$numDeleted = $image->deleteFormattedImages();
		$this->assertEquals(1, $numDeleted, 'Expected one image to be deleted, but deleted ' . $numDeleted . ' images');
		$this->assertFalse(file_exists($p), 'Resized image not existing after deletion call');
	}

	/**
	 * Tests that generated images with multiple image manipulations are all deleted
	 */
	public function testMultipleGenerateManipulationCallsImageDeletion() {
		$image = $this->objFromFixture('Image', 'imageWithMetacharacters');

		$firstImage = $image->ScaleWidth(200);
		$firstImagePath = $firstImage->getFullPath();
		$this->assertTrue(file_exists($firstImagePath));

		$secondImage = $firstImage->ScaleHeight(100);
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
		$firstImage = $image->ScaleWidth(200);
		$firstImagePath = $firstImage->getRelativePath();
		$this->assertEquals($firstImagePath, $firstImage->Filename);

		$secondImage = $firstImage->ScaleHeight(100);
		$secondImagePath = $secondImage->getRelativePath();
		$this->assertEquals($secondImagePath, $secondImage->Filename);
	}

	/**
	 * Test all generate methods
	 */
	public function testGenerateMethods() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$generateMethods = $this->getGenerateMethods();

		// test each generate method
		foreach ($generateMethods as $method) {
			$generatedImage = $image->$method(333, 333, 'FFFFFF');
			$this->assertFileExists(
				$generatedImage->getFullPath(),
				'Formatted ' . $method . ' image exists'
			);
		}
	}

	/**
	 * Test deleteFormattedImages() against all generate methods
	 */
	public function testDeleteFormattedImages() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$generateMethods = $this->getGenerateMethods();

		// get paths for each generate method
		$paths = array();
		foreach ($generateMethods as $method) {
			$generatedImage = $image->$method(333, 333, 'FFFFFF');
			$paths[$method] = $generatedImage->getFullPath();
		}

		// delete formatted images
		$image->deleteFormattedImages();

		// test that all formatted images are deleted
		foreach ($paths as $method => $path) {
			$this->assertFalse(
				file_exists($path),
				'Formatted ' . $method . ' image does not exist'
			);
		}
	}

	/**
	 * @param bool $custom include methods added dynamically at runtime
	 * @return array
	 */
	protected function getGenerateMethods($custom = true) {
		$generateMethods = array();
		$methodNames = Image::create()->allMethodNames($custom);

		foreach ($methodNames as $methodName) {
			if (substr($methodName, 0, 8) == 'generate' && $methodName != 'generateformattedimage') {
				$format = substr($methodName, 8);
				$generateMethods[] = $format;
			}
		}

		return $generateMethods;
	}

}
