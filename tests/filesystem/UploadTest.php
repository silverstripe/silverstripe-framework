<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class UploadTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/filesystem/UploadTest.yml';

	function testUpload() {
		// For some reason, this isn't working in cli-script.php
		/*
		
		// create tmp file
		$tmpFileName = 'UploadTest_testUpload.txt';
		$tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
		$tmpFileContent = '';
		for($i=0; $i<10000; $i++) $tmpFileContent .= '0';
		file_put_contents($tmpFilePath, $tmpFileContent);
		
		// emulates the $_FILES array
		$tmpFile = array(
			'name' => $tmpFileName,
			'type' => 'text/plaintext',
			'size' => filesize($tmpFilePath),
			'tmp_name' => $tmpFilePath,
			'extension' => 'txt',
			'error' => UPLOAD_ERR_OK,
		);
		
		// test upload into default folder
		$u1 = new Upload();
		$u1->load($tmpFile);
		$file1 = $u1->getFile();
		$this->assertTrue(
			file_exists($file1->getFullPath()), 
			'File upload to standard directory in /assets'
		);
		$this->assertTrue(
			(strpos($file1->getFullPath(),Director::baseFolder() . '/assets/' . Upload::$uploads_folder) !== false),	
			'File upload to standard directory in /assets'
		);
		$file1->delete();

		// test upload into custom folder
		$customFolder = 'UploadTest_testUpload';
		$u2 = new Upload();
		$u2->load($tmpFile, $customFolder);
		$file2 = $u2->getFile();
		$this->assertTrue(
			file_exists($file2->getFullPath()), 
			'File upload to custom directory in /assets'
		);
		$this->assertTrue(
			(strpos($file2->getFullPath(),Director::baseFolder() . '/assets/' . $customFolder) !== false),
			'File upload to custom directory in /assets'
		);
		$file2->delete();
		
		unlink($tmpFilePath);
		*/
	}
	
	function testAllowedFilesize() {
		// @todo
	}

	function testAllowedExtensions() {
		// @todo
	}
	
}
?>