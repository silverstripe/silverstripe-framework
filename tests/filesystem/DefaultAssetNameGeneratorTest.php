<?php

use SilverStripe\Filesystem\Storage\DefaultAssetNameGenerator;

/**
 * covers {@see DefaultAssetNameGenerator}
 */
class DefaultAssetNameGeneratorTest extends SapphireTest {

	/**
	 * Test non-prefix behaviour
	 */
	public function testWithoutPrefix() {
		Config::inst()->update('SilverStripe\Filesystem\Storage\DefaultAssetNameGenerator', 'version_prefix', '');
		$generator = new DefaultAssetNameGenerator('folder/MyFile-001.jpg');
		$suggestions = iterator_to_array($generator);

		// Expect 100 suggestions
		$this->assertEquals(100, count($suggestions));

		// First item is always the same as input
		$this->assertEquals('folder/MyFile-001.jpg', $suggestions[0]);

		// Check that padding is respected
		$this->assertEquals('folder/MyFile-002.jpg', $suggestions[1]);
		$this->assertEquals('folder/MyFile-003.jpg', $suggestions[2]);
		$this->assertEquals('folder/MyFile-004.jpg', $suggestions[3]);
		$this->assertEquals('folder/MyFile-021.jpg', $suggestions[20]);
		$this->assertEquals('folder/MyFile-099.jpg', $suggestions[98]);

		// Last item should be some semi-random string, not in the same numeric sequence
		$this->assertNotEquals('folder/MyFile-0100.jpg', $suggestions[99]);
		$this->assertNotEquals('folder/MyFile-100.jpg', $suggestions[99]);

		// Test with a value starting above 1
		$generator = new DefaultAssetNameGenerator('folder/MyFile-024.jpg');
		$suggestions = iterator_to_array($generator);
		$this->assertEquals(100, count($suggestions));
		$this->assertEquals('folder/MyFile-024.jpg', $suggestions[0]);
		$this->assertEquals('folder/MyFile-025.jpg', $suggestions[1]);
		$this->assertEquals('folder/MyFile-026.jpg', $suggestions[2]);
		$this->assertEquals('folder/MyFile-048.jpg', $suggestions[24]);
		$this->assertEquals('folder/MyFile-122.jpg', $suggestions[98]);
		$this->assertNotEquals('folder/MyFile-0123.jpg', $suggestions[99]);
		$this->assertNotEquals('folder/MyFile-123.jpg', $suggestions[99]); // Last suggestion is semi-random

		// Test without numeric value
		$generator = new DefaultAssetNameGenerator('folder/MyFile.jpg');
		$suggestions = iterator_to_array($generator);
		$this->assertEquals(100, count($suggestions));
		$this->assertEquals('folder/MyFile.jpg', $suggestions[0]);
		$this->assertEquals('folder/MyFile2.jpg', $suggestions[1]);
		$this->assertEquals('folder/MyFile3.jpg', $suggestions[2]);
		$this->assertEquals('folder/MyFile25.jpg', $suggestions[24]);
		$this->assertEquals('folder/MyFile99.jpg', $suggestions[98]);
		$this->assertNotEquals('folder/MyFile100.jpg', $suggestions[99]); // Last suggestion is semi-random
	}

	/**
	 * Test with default -v prefix
	 */
	public function testWithDefaultPrefix() {
		Config::inst()->update('SilverStripe\Filesystem\Storage\DefaultAssetNameGenerator', 'version_prefix', '-v');

		// Test with item that doesn't contain the prefix
		$generator = new DefaultAssetNameGenerator('folder/MyFile-001.jpg');
		$suggestions = iterator_to_array($generator);
		$this->assertEquals(100, count($suggestions));
		$this->assertEquals('folder/MyFile-001.jpg', $suggestions[0]);
		$this->assertEquals('folder/MyFile-001-v2.jpg', $suggestions[1]);
		$this->assertEquals('folder/MyFile-001-v4.jpg', $suggestions[3]);
		$this->assertEquals('folder/MyFile-001-v21.jpg', $suggestions[20]);
		$this->assertEquals('folder/MyFile-001-v99.jpg', $suggestions[98]);
		$this->assertNotEquals('folder/MyFile-001-v100.jpg', $suggestions[99]); // Last suggestion is semi-random


		// Test with item that contains prefix
		$generator = new DefaultAssetNameGenerator('folder/MyFile-v24.jpg');
		$suggestions = iterator_to_array($generator);
		$this->assertEquals(100, count($suggestions));
		$this->assertEquals('folder/MyFile-v24.jpg', $suggestions[0]);
		$this->assertEquals('folder/MyFile-v25.jpg', $suggestions[1]);
		$this->assertEquals('folder/MyFile-v26.jpg', $suggestions[2]);
		$this->assertEquals('folder/MyFile-v48.jpg', $suggestions[24]);
		$this->assertEquals('folder/MyFile-v122.jpg', $suggestions[98]);
		$this->assertNotEquals('folder/MyFile-v123.jpg', $suggestions[99]);
		$this->assertNotEquals('folder/MyFile-123.jpg', $suggestions[99]);

		// Test without numeric value
		$generator = new DefaultAssetNameGenerator('folder/MyFile.jpg');
		$suggestions = iterator_to_array($generator);
		$this->assertEquals(100, count($suggestions));
		$this->assertEquals('folder/MyFile.jpg', $suggestions[0]);
		$this->assertEquals('folder/MyFile-v2.jpg', $suggestions[1]);
		$this->assertEquals('folder/MyFile-v3.jpg', $suggestions[2]);
		$this->assertEquals('folder/MyFile-v25.jpg', $suggestions[24]);
		$this->assertEquals('folder/MyFile-v99.jpg', $suggestions[98]);
		$this->assertNotEquals('folder/MyFile-v100.jpg', $suggestions[99]);
	}
}
