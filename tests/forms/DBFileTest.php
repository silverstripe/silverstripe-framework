<?php

/**
 * Description of DBFileTest
 *
 * @author dmooyman
 */
class DBFileTest extends SapphireTest {

	protected $extraDataObjects = array(
		'DBFileTest_Object',
		'DBFileTest_Subclass'
	);
	
	protected $usesDatabase = true;

	public function setUp() {
		parent::setUp();

		// Set backend
		AssetStoreTest_SpyStore::activate('DBFileTest');
		Config::inst()->update('Director', 'alternate_base_url', '/mysite/');
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset('DBFileTest');
		parent::tearDown();
	}

	/**
	 * Test that images in a DBFile are rendered properly
	 */
	public function testRender() {
		$obj = new DBFileTest_Object();

		// Test image tag
		$fish = realpath(__DIR__ .'/../model/testimages/test-image-high-quality.jpg');
		$this->assertFileExists($fish);
		$obj->MyFile->setFromLocalFile($fish, 'awesome-fish.jpg');
		$this->assertEquals(
			'<img src="/mysite/assets/DBFileTest/a870de278b/awesome-fish.jpg" alt="awesome-fish.jpg" />',
			trim($obj->MyFile->forTemplate())
		);

		// Test download tag
		$obj->MyFile->setFromString('puppies', 'subdir/puppy-document.txt');
		$this->assertEquals(
			'<a href="/mysite/assets/DBFileTest/subdir/2a17a9cb4b/puppy-document.txt" title="puppy-document.txt" download="puppy-document.txt"/>',
			trim($obj->MyFile->forTemplate())
		);
	}

	public function testValidation() {
		$obj = new DBFileTest_ImageOnly();
		
		// Test from image
		$fish = realpath(__DIR__ .'/../model/testimages/test-image-high-quality.jpg');
		$this->assertFileExists($fish);
		$obj->MyFile->setFromLocalFile($fish, 'awesome-fish.jpg');

		// This should fail
		$this->setExpectedException('ValidationException');
		$obj->MyFile->setFromString('puppies', 'subdir/puppy-document.txt');
	}

}

/**
 * @property DBFile $MyFile
 */
class DBFileTest_Object extends DataObject implements TestOnly {
	private static $db = array(
		"MyFile" => "DBFile"
	);
}


class DBFileTest_Subclass extends DBFileTest_Object implements TestOnly {
	private static $db = array(
		"AnotherFile" => "DBFile"
	);
}


class DBFileTest_ImageOnly extends DataObject implements TestOnly {
	private static $db = array(
		"MyFile" => "DBFile('image/supported')"
	);
}

