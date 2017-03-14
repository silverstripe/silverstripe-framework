<?php

use League\Flysystem\Filesystem;
use SilverStripe\Assets\Flysystem\AssetAdapter;
use SilverStripe\Assets\Flysystem\FlysystemAssetStore;
use SilverStripe\Assets\Flysystem\FlysystemUrlPlugin;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Filesystem as SSFilesystem;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\SapphireTest;


/**
 * @package framework
 * @subpackage tests
 */
class ImageTest extends SapphireTest {

	protected static $fixture_file = 'ImageTest.yml';

	public function setUp() {
		parent::setUp();

		// Execute specific subclass
		if(get_class($this) == "ImageTest") {
			$this->markTestSkipped(sprintf('Skipping %s ', get_class($this)));
			return;
		}

		// Set backend root to /ImageTest
		AssetStoreTest_SpyStore::activate('ImageTest');

		// Copy test images for each of the fixture references
		$files = File::get()->exclude('ClassName', 'SilverStripe\\Assets\\Folder');
		foreach($files as $image) {
			$filePath = AssetStoreTest_SpyStore::getLocalPath($image); // Only correct for test asset store
			$sourcePath = FRAMEWORK_PATH . '/tests/model/testimages/' . $image->Name;
			if(!file_exists($filePath)) {
				SSFilesystem::makeFolder(dirname($filePath));
				if (!copy($sourcePath, $filePath)) {
					user_error('Failed to copy test images', E_USER_ERROR);
				}
			}
		}
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset();
		parent::tearDown();
	}

	public function testGetTagWithTitle() {
		Config::inst()->update('SilverStripe\\Assets\\Storage\\DBFile', 'force_resample', false);

		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithTitle');
		$expected = '<img src="/assets/ImageTest/folder/444065542b/test-image.png" alt="This is a image Title" />';
		$actual = trim($image->getTag());

		$this->assertEquals($expected, $actual);
	}

	public function testGetTagWithoutTitle() {
		Config::inst()->update('SilverStripe\\Assets\\Storage\\DBFile', 'force_resample', false);

		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithoutTitle');
		$expected = '<img src="/assets/ImageTest/folder/444065542b/test-image.png" alt="test image" />';
		$actual = trim($image->getTag());

		$this->assertEquals($expected, $actual);
	}

	public function testGetTagWithoutTitleContainingDots() {
		Config::inst()->update('SilverStripe\\Assets\\Storage\\DBFile', 'force_resample', false);

		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithoutTitleContainingDots');
		$expected = '<img src="/assets/ImageTest/folder/46affab704/test.image.with.dots.png" alt="test.image.with.dots" />';
		$actual = trim($image->getTag());

		$this->assertEquals($expected, $actual);
	}

	/**
	 * Tests that multiple image manipulations may be performed on a single Image
	 */
	public function testMultipleGenerateManipulationCalls() {
		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithoutTitle');

		$imageFirst = $image->ScaleWidth(200);
		$this->assertNotNull($imageFirst);
		$expected = 200;
		$actual = $imageFirst->getWidth();

		$this->assertEquals($expected, $actual);

		$imageSecond = $imageFirst->ScaleHeight(100);
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
		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithoutTitle');
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
	 * Tests that a URL to a resampled image is provided when force_resample is
	 * set to true, if the resampled file is smaller than the original.
	 */
	public function testForceResample() {
		$imageHQ = $this->objFromFixture('SilverStripe\\Assets\\Image', 'highQualityJPEG');
		$imageHQR = $imageHQ->Resampled();
		$imageLQ = $this->objFromFixture('SilverStripe\\Assets\\Image', 'lowQualityJPEG');
		$imageLQR = $imageLQ->Resampled();

		// Test resampled file is served when force_resample = true
		Config::inst()->update('SilverStripe\\Assets\\Storage\\DBFile', 'force_resample', true);
		$this->assertLessThan($imageHQ->getAbsoluteSize(), $imageHQR->getAbsoluteSize(), 'Resampled image is smaller than original');
		$this->assertEquals($imageHQ->getURL(), $imageHQR->getSourceURL(), 'Path to a resampled image was returned by getURL()');

		// Test original file is served when force_resample = true but original file is low quality
		$this->assertGreaterThanOrEqual($imageLQ->getAbsoluteSize(), $imageLQR->getAbsoluteSize(), 'Resampled image is larger or same size as original');
		$this->assertNotEquals($imageLQ->getURL(), $imageLQR->getSourceURL(), 'Path to the original image file was returned by getURL()');

		// Test original file is served when force_resample = false
		Config::inst()->update('SilverStripe\\Assets\\Storage\\DBFile', 'force_resample', false);
		$this->assertNotEquals($imageHQ->getURL(), $imageHQR->getSourceURL(), 'Path to the original image file was returned by getURL()');
	}

	public function testImageResize() {
		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithoutTitle');
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
		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithoutTitle');
		$image->ScaleHeight('String');
		$image->Pad(600,600,'XXXXXX');
	}

	public function testCacheFilename() {
		$image = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithoutTitle');
		$imageFirst = $image->Pad(200,200,'CCCCCC');
		$imageFilename = $imageFirst->getURL();
			// Encoding of the arguments is duplicated from cacheFilename
		$neededPart = 'Pad' . Convert::base64url_encode(array(200,200,'CCCCCC'));
		$this->assertContains($neededPart, $imageFilename, 'Filename for cached image is correctly generated');
	}

	/**
	 * Test that propertes from the source Image are inherited by resampled images
	 */
	public function testPropertyInheritance() {
		$testString = 'This is a test';
		$origImage = $this->objFromFixture('SilverStripe\\Assets\\Image', 'imageWithTitle');
		$origImage->TestProperty = $testString;
		$resampled = $origImage->ScaleWidth(10);
		$this->assertEquals($resampled->TestProperty, $testString);
		$resampled2 = $resampled->ScaleWidth(5);
		$this->assertEquals($resampled2->TestProperty, $testString);
	}
}
