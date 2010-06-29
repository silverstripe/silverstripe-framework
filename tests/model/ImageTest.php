<?php
class ImageTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/model/ImageTest.yml';
	
	function testGetTagWithTitle() {
		$image = $this->objFromFixture('Image', 'imageWithTitle');
		$expected = '<img src="' . Director::baseUrl() . 'assets/ImageTest/test_image.png" alt="This is a image Title" />';
		$actual = $image->getTag();
		
		$this->assertEquals($expected, $actual);
	}
	
	function testGetTagWithoutTitle() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$expected = '<img src="' . Director::baseUrl() . 'assets/ImageTest/test_image.png" alt="test_image" />';
		$actual = $image->getTag();
		
		$this->assertEquals($expected, $actual);
	}
	
	function testGetTagWithoutTitleContainingDots() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitleContainingDots');
		$expected = '<img src="' . Director::baseUrl() . 'assets/ImageTest/test.image.with.dots.png" alt="test.image.with.dots" />';
		$actual = $image->getTag();
		
		$this->assertEquals($expected, $actual);
	}
	
	function setUp() {
		parent::setUp();
		
		if(!file_exists(ASSETS_PATH)) mkdir(ASSETS_PATH);

		/* Create a test folders for each of the fixture references */
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			if(!file_exists(BASE_PATH."/$folder->Filename")) mkdir(BASE_PATH."/$folder->Filename");
		}
		
		/* Create a test files for each of the fixture references */
		$fileIDs = $this->allFixtureIDs('Image');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('Image', $fileID);
			$fh = fopen(BASE_PATH."/$file->Filename", "w");
			fwrite($fh, str_repeat('x',1000000));
			fclose($fh);
		}
	} 
	
	function tearDown() {
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
			if($folder && file_exists(BASE_PATH."/$folder->Filename")) Filesystem::removeFolder(BASE_PATH."/$folder->Filename");
		}
		
		parent::tearDown();
	}
}
?>