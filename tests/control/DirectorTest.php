<?php
/**
 * @package sapphire
 * @subpackage tests
 * 
 * @todo test Director::alternateBaseFolder()
 */
class DirectorTest extends SapphireTest {
	
	public function testFileExists() {
		$tempFileName = 'DirectorTest_testFileExists.tmp';
		$tempFilePath = TEMP_FOLDER . '/' . $tempFileName;
		
		// create temp file
		file_put_contents($tempFilePath, '');
		
		$this->assertTrue(
			Director::fileExists($tempFilePath), 
			'File exist check with absolute path'
		);
		
		$this->assertTrue(
			Director::fileExists($tempFilePath . '?queryparams=1&foo[bar]=bar'), 
			'File exist check with query params ignored'
		);
		
		unlink($tempFilePath);
	}
	/*
	public function testAlternativeBaseURL() {
		// relative base URLs
		Director::setBaseURL('/relativebase');
		$this->assertEquals(Director::baseURL(), '/relativebase');
		$this->assertEquals(Director::absoluteBaseURL(), BASE_URL . '/relativebase');
		$this->assertEquals(Director::absoluteURL('subfolder'), $origBaseURL . '/relativebase/subfolder');
		
		// absolute base URLs
		Director::setBaseURL('http://www.example.org');
		$this->assertEquals(Director::baseURL(), 'http://www.example.org');
		$this->assertEquals(Director::absoluteBaseURL(), 'http://www.example.org');
		$this->assertEquals(Director::absoluteURL('subfolder'), 'http://www.example.org/subfolder');
		
		Director::setBaseURL(false);
		$this->assertEquals(Director::baseURL(), BASE_URL);
		$this->assertEquals(Director::absoluteBaseURL(BASE_URL), BASE_URL);
		$this->assertEquals(Director::absoluteURL('subfolder'), BASE_URL . '/subfolder');
	}
	*/
}
?>