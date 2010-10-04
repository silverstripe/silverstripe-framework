<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class UploadTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/filesystem/UploadTest.yml';

	function testUpload() {
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
		
		$v = new UploadTest_Validator();
		
		// test upload into default folder
		$u1 = new Upload();
		$u1->setValidator($v);
		$u1->load($tmpFile);
		$file1 = $u1->getFile();
		$this->assertTrue(
			file_exists($file1->getFullPath()), 
			'File upload to standard directory in /assets'
		);
		$this->assertTrue(
			(strpos($file1->getFullPath(), Director::baseFolder() . '/assets/' . Upload::$uploads_folder) !== false),	
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
			(strpos($file2->getFullPath(), Director::baseFolder() . '/assets/' . $customFolder) !== false),
			'File upload to custom directory in /assets'
		);
		$file2->delete();
		
		unlink($tmpFilePath);
	}
	
	function testAllowedFilesize() {
		// @todo
	}

	function testUploadDoesNotAllowUnknownExtension() {
		// create tmp file
		$tmpFileName = 'UploadTest_testUpload.php';
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
		
		$v = new UploadTest_Validator();
		$v->setAllowedExtensions(array('txt'));
		
		// test upload into default folder
		$u = new Upload();
		$u->setValidator($v);
		$result = $u->load($tmpFile);
		
		$this->assertFalse($result, 'Load failed because extension was not accepted');
		$this->assertEquals(1, count($u->getErrors()), 'There is a single error of the file extension');
	}
	
	function testUploadAcceptsAllowedExtension() {
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
		
		$v = new UploadTest_Validator();
		$v->setAllowedExtensions(array('txt'));
		
		// test upload into default folder
		$u = new Upload();
		$u->setValidator($v);
		$u->load($tmpFile);
		$file = $u->getFile();
		$this->assertTrue(
			file_exists($file->getFullPath()), 
			'File upload to custom directory in /assets'
		);
		$file->delete();
	}
	
}
class UploadTest_Validator extends Upload_Validator implements TestOnly {

	/**
	 * Looser check validation that doesn't do is_upload_file()
	 * checks as we're faking a POST request that PHP didn't generate
	 * itself.
	 * 
	 * @return boolean
	 */
	public function validate() {
		$pathInfo = pathinfo($this->tmpFile['name']);
		// filesize validation
		
		if(!$this->isValidSize()) {
			$this->errors[] = sprintf(
				_t(
					'File.TOOLARGE', 
					'Filesize is too large, maximum %s allowed.',
					PR_MEDIUM,
					'Argument 1: Filesize (e.g. 1MB)'
				),
				File::format_size($this->getAllowedMaxFileSize($pathInfo['extension']))
			);
			return false;
		}

		// extension validation
		if(!$this->isValidExtension()) {
			$this->errors[] = sprintf(
				_t(
					'File.INVALIDEXTENSION', 
					'Extension is not allowed (valid: %s)',
					PR_MEDIUM,
					'Argument 1: Comma-separated list of valid extensions'
				),
				implode(',', $this->allowedExtensions)
			);
			return false;
		}
		
		return true;
	}

}