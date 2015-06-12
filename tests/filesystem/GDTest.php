<?php

/**
 * Tests for the {@link GD} class.
 *
 * @package framework
 * @subpackage tests
 */
class GDTest extends SapphireTest {

	public static $filenames = array(
		'gif' => 'test_gif.gif',
		'jpg' => 'test_jpg.jpg',
		'png8' => 'test_png8.png',
		'png32' => 'test_png32.png'
	);

	public function tearDown() {
		$cache = SS_Cache::factory('GDBackend_Manipulations');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
	}

	/**
	 * Loads all images into an associative array of GD objects.
	 * Optionally applies an operation to each GD
	 * @param callable $callback Action to perform on each GD
	 * @return array List of GD
	 */
	protected function applyToEachImage($callback = null) {
		$gds = array();
		foreach(self::$filenames as $type => $file) {
			$fullPath = realpath(dirname(__FILE__) . '/gdtest/' . $file);
			$gd = new GDBackend($fullPath);
			if($callback) {
				$gd = $callback($gd);
			}
			$gds[$type] = $gd;
		}
		return $gds;
	}

	/**
	 * Takes samples from the given GD at 5 pixel increments
	 * @param GDBackend $gd The source image
	 * @param integer $horizontal Number of samples to take horizontally
	 * @param integer $vertical Number of samples to take vertically
	 * @return array List of colours for each sample, each given as an associative
	 * array with red, blue, green, and alpha components
	 */
	protected function sampleAreas(GDBackend $gd, $horizontal = 4, $vertical = 4) {
		$samples = array();
		for($y = 0; $y < $vertical; $y++) {
			for($x = 0; $x < $horizontal; $x++) {
				$colour = imagecolorat($gd->getImageResource(), $x * 5, $y * 5);
				$samples[] = ImageColorsforIndex($gd->getImageResource(), $colour);
			}
		}
		return $samples;
	}

	/**
	 * Asserts that two colour channels are equivalent within a given tolerance range
	 * @param integer $expected
	 * @param integer $actual
	 * @param integer $tolerance
	 */
	protected function assertColourEquals($expected, $actual, $tolerance = 0) {
		$match =
			($expected + $tolerance >= $actual) &&
			($expected - $tolerance <= $actual);
		$this->assertTrue($match);
	}

	/**
	 * Asserts that all samples given correctly correspond to a greyscale version
	 * of the test image pattern
	 * @param array $samples List of 16 colour samples representing each of the
	 * 8 x 8 squares on the image pattern
	 * @param int $alphaBits Depth of alpha channel in bits
	 * @param int $tolerance Reasonable tolerance level for colour comparison
	 */
	protected function assertGreyscale($samples, $alphaBits = 0, $tolerance = 0) {

		// Check that all colour samples match
		foreach($samples as $sample) {
			$matches =
				($sample['red'] === $sample['green']) &&
				($sample['blue'] === $sample['green']);
			$this->assertTrue($matches, 'Assert colour is greyscale');
			if(!$matches) return;
		}

		// check various sample points
		$this->assertColourEquals(96, $samples[0]['red'], $tolerance);
		$this->assertColourEquals(91, $samples[2]['red'], $tolerance);
		$this->assertColourEquals(0, $samples[8]['red'], $tolerance);
		$this->assertColourEquals(127, $samples[9]['red'], $tolerance);

		// check alpha of various points
		switch($alphaBits) {
			case 0:
				$this->assertColourEquals(0, $samples[2]['alpha'], $tolerance);
				$this->assertColourEquals(0, $samples[12]['alpha'], $tolerance);
				break;
			case 1:
				$this->assertColourEquals(0, $samples[2]['alpha'], $tolerance);
				$this->assertColourEquals(127, $samples[12]['alpha'], $tolerance);
				break;
			default:
				$this->assertColourEquals(63, $samples[2]['alpha'], $tolerance);
				$this->assertColourEquals(127, $samples[12]['alpha'], $tolerance);
				break;
		}

	}

	/**
	 * Tests that images are correctly transformed to greyscale
	 */
	function testGreyscale() {

		// Apply greyscaling to each image
		$images = $this->applyToEachImage(function(GDBackend $gd) {
			return $gd->greyscale();
		});

		// Test GIF (256 colour, transparency)
		$samplesGIF = $this->sampleAreas($images['gif']);
		$this->assertGreyscale($samplesGIF, 1);

		// Test JPG
		$samplesJPG = $this->sampleAreas($images['jpg']);
		$this->assertGreyscale($samplesJPG, 0, 4);

		// Test PNG 8 (indexed with alpha transparency)
		$samplesPNG8 = $this->sampleAreas($images['png8']);
		$this->assertGreyscale($samplesPNG8, 8, 4);

		// Test PNG 32 (full alpha transparency)
		$samplesPNG32 = $this->sampleAreas($images['png32']);
		$this->assertGreyscale($samplesPNG32, 8);
	}

	/**
	 * Tests that GD doesn't attempt to load images when they're deemed unavailable
	 * @return void
	 */
	public function testImageSkippedWhenUnavailable() {
		$fullPath = realpath(dirname(__FILE__) . '/gdtest/test_jpg.jpg');
		$gd = new GDBackend_ImageUnavailable($fullPath);

		/* Ensure no image resource is created if the image is unavailable */
		$this->assertNull($gd->getImageResource());
	}

	/**
	 * Tests the integrity of the manipulation cache when an error occurs
	 * @return void
	 */
	public function testCacheIntegrity() {
		$fullPath = realpath(dirname(__FILE__) . '/gdtest/test_jpg.jpg');

		try {
			$gdFailure = new GDBackend_Failure($fullPath, array('ScaleWidth', 123));
			$this->fail('GDBackend_Failure should throw an exception when setting image resource');
		} catch (GDBackend_Failure_Exception $e) {
			$cache = SS_Cache::factory('GDBackend_Manipulations');
			$key = md5(implode('_', array($fullPath, filemtime($fullPath))));

			$data = unserialize($cache->load($key));

			$this->assertArrayHasKey('ScaleWidth|123', $data);
			$this->assertTrue($data['ScaleWidth|123']);
		}
	}

	/**
	 * Test that GD::failedResample() returns true for the current image
	 * manipulation only if it previously failed
	 * @return void
	 */
	public function testFailedResample() {
		$fullPath = realpath(dirname(__FILE__) . '/gdtest/test_jpg.jpg');

		try {
			$gdFailure = new GDBackend_Failure($fullPath, array('ScaleWidth-failed', 123));
			$this->fail('GDBackend_Failure should throw an exception when setting image resource');
		} catch (GDBackend_Failure_Exception $e) {
			$gd = new GDBackend($fullPath, array('ScaleWidth', 123));
			$this->assertTrue($gd->failedResample($fullPath, 'ScaleWidth-failed|123'));
			$this->assertFalse($gd->failedResample($fullPath, 'ScaleWidth-not-failed|123'));
		}
	}

}

class GDBackend_ImageUnavailable extends GDBackend implements TestOnly {

	public function imageAvailable($filename, $manipulation) {
		return false;
	}

}

class GDBackend_Failure extends GDBackend implements TestOnly {

	public function setImageResource($resource) {
		throw new GDBackend_Failure_Exception('GD failed to load image');
	}

}

class GDBackend_Failure_Exception extends Exception {

}
