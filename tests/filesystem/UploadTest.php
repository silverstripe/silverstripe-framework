<?php
/**
 * @package framework
 * @subpackage tests
 */
class UploadTest extends SapphireTest {
	protected static $fixture_file = 'UploadTest.yml';

	public function testUpload() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload.txt';
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
			(
				strpos(
					$file1->getFullPath(), 
					Director::baseFolder() . '/assets/' . Config::inst()->get('Upload', 'uploads_folder')
				) 
				!== false
			),	
			'File upload to standard directory in /assets'
		);
		$file1->delete();

		// test upload into custom folder
		$customFolder = 'UploadTest-testUpload';
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
		rmdir(Director::baseFolder() . '/assets/' . $customFolder);
	}
	
	public function testAllowedFilesize() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload.txt';
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
		$v->setAllowedMaxFileSize(array('txt' => 10));
		
		// test upload into default folder
		$u1 = new Upload();
		$u1->setValidator($v);
		$result = $u1->load($tmpFile);
		
		$this->assertFalse($result, 'Load failed because size was too big');
	}

	public function testAllowedSizeOnFileWithNoExtension() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload';
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
			'extension' => '',
			'error' => UPLOAD_ERR_OK,
		);
		
		$v = new UploadTest_Validator();
		$v->setAllowedMaxFileSize(array('' => 10));
		
		// test upload into default folder
		$u1 = new Upload();
		$u1->setValidator($v);
		$result = $u1->load($tmpFile);
		
		$this->assertFalse($result, 'Load failed because size was too big');
	}

	public function testUploadDoesNotAllowUnknownExtension() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload.php';
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
			'extension' => 'php',
			'error' => UPLOAD_ERR_OK,
		);
		
		$v = new UploadTest_Validator();
		$v->setAllowedExtensions(array('txt'));
		
		// test upload into default folder
		$u = new Upload();
		$u->setValidator($v);
		$result = $u->load($tmpFile);
		
		$this->assertFalse($result, 'Load failed because extension was not accepted');
	}
	
	public function testUploadAcceptsAllowedExtension() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload.txt';
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
	
	public function testUploadDeniesNoExtensionFilesIfNoEmptyStringSetForValidatorExtensions() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload';
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
			'extension' => '',
			'error' => UPLOAD_ERR_OK,
		);

		$v = new UploadTest_Validator();
		$v->setAllowedExtensions(array('txt'));
		
		// test upload into default folder
		$u = new Upload();
		$result = $u->load($tmpFile);
		
		$this->assertFalse($result, 'Load failed because extension was not accepted');
		$this->assertEquals(1, count($u->getErrors()), 'There is a single error of the file extension');
		
	}

	// Delete files in the default uploads directory that match the name pattern.
	// @param String $namePattern	A regular expression applied to files in the directory. If the name matches
	// the pattern, it is deleted. Directories, . and .. are excluded.
	public function deleteTestUploadFiles($namePattern) {
		$tmpFolder = ASSETS_PATH . "/" . Config::inst()->get('Upload', 'uploads_folder');
		$files = scandir($tmpFolder);
		foreach ($files as $f) {
			if ($f == "." || $f == ".." || is_dir("$tmpFolder/$f")) continue;
			if (preg_match($namePattern, $f)) unlink("$tmpFolder/$f");
		}
	}

	public function testUploadTarGzFileTwiceAppendsNumber() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload.tar.gz';
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
			'extension' => 'tar.gz',
			'error' => UPLOAD_ERR_OK,
		);

		// Make sure there are none here, otherwise they get renamed incorrectly for the test.
		$this->deleteTestUploadFiles("/UploadTest-testUpload.*tar\.gz/");

		// test upload into default folder
		$u = new Upload();
		$u->load($tmpFile);
		$file = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload.tar.gz',
			$file->Name,
			'File has a name without a number because it\'s not a duplicate'
		);
		
		$u = new Upload();
		$u->load($tmpFile);
		$file2 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload2.tar.gz',
			$file2->Name,
			'File receives a number attached to the end before the extension'
		);
		
		$file->delete();
		$file2->delete();
	}
	
	public function testUploadFileWithNoExtensionTwiceAppendsNumber() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload';
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
		
		// Make sure there are none here, otherwise they get renamed incorrectly for the test.
		$this->deleteTestUploadFiles("/UploadTest-testUpload.*/");

		$v = new UploadTest_Validator();
		$v->setAllowedExtensions(array(''));

		// test upload into default folder
		$u = new Upload();
		$u->setValidator($v);
		$u->load($tmpFile);
		$file = $u->getFile();

		$this->assertEquals(
			'UploadTest-testUpload',
			$file->Name,
			'File is uploaded without extension'
		);
		
		$u = new Upload();
		$u->setValidator($v);
		$u->load($tmpFile);
		$file2 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload-2',
			$file2->Name,
			'File receives a number attached to the end'
		);
		
		$file->delete();
		$file2->delete();
	}

	public function testReplaceFile() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload';
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
		
		// Make sure there are none here, otherwise they get renamed incorrectly for the test.
		$this->deleteTestUploadFiles("/UploadTest-testUpload.*/");

		$v = new UploadTest_Validator();
		$v->setAllowedExtensions(array(''));

		// test upload into default folder
		$u = new Upload();
		$u->setValidator($v);
		$u->load($tmpFile);
		$file = $u->getFile();

		$this->assertEquals(
			'UploadTest-testUpload',
			$file->Name,
			'File is uploaded without extension'
		);
		
		$u = new Upload();
		$u->setValidator($v);
		$u->setReplaceFile(true);
		$u->load($tmpFile);
		$file2 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload',
			$file2->Name,
			'File does not receive new name'
		);
		$this->assertEquals(
			$file->ID,
			$file2->ID,
			'File database record is the same'
		);
		
		$file->delete();
		$file2->delete();
	}
	
	public function testReplaceFileWithLoadIntoFile() {
		// create tmp file
		$tmpFileName = 'UploadTest-testUpload.txt';
		$tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
		$tmpFileContent = '';
		for ($i = 0; $i < 10000; $i++)
			$tmpFileContent .= '0';
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

		// Make sure there are none here, otherwise they get renamed incorrectly for the test.
		$this->deleteTestUploadFiles("/UploadTest-testUpload.*/");

		$v = new UploadTest_Validator();

		// test upload into default folder
		$u = new Upload();
		$u->setValidator($v);
		$u->load($tmpFile);
		$file = $u->getFile();

		$this->assertEquals(
				'UploadTest-testUpload.txt',
				$file->Name,
				'File is uploaded without extension'
		);
		$this->assertFileExists(
				BASE_PATH . '/' . $file->getFilename(),
				'File exists'
		);

		// replace=true
		$u = new Upload();
		$u->setValidator($v);
		$u->setReplaceFile(true);
		$u->loadIntoFile($tmpFile, new File());
		$file2 = $u->getFile();
		$this->assertEquals(
				'UploadTest-testUpload.txt',
				$file2->Name,
				'File does not receive new name'
		);
		$this->assertFileExists(
				BASE_PATH . '/' . $file2->getFilename(),
				'File exists'
		);
		$this->assertEquals(
				$file->ID,
				$file2->ID,
				'File database record is the same'
		);

		// replace=false
		$u = new Upload();
		$u->setValidator($v);
		$u->setReplaceFile(false);
		$u->loadIntoFile($tmpFile, new File());
		$file3 = $u->getFile();
		$this->assertEquals(
				'UploadTest-testUpload2.txt',
				$file3->Name,
				'File does receive new name'
		);
		$this->assertFileExists(
				BASE_PATH . '/' . $file3->getFilename(),
				'File exists'
		);
		$this->assertGreaterThan(
				$file2->ID,
				$file3->ID,
				'File database record is not the same'
		);

		$file->delete();
		$file2->delete();
		$file3->delete();
	}

	public function testDeleteResampledImagesOnUpload() {
		$tmpFileName = 'UploadTest-testUpload.jpg';
		$tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;

		$uploadImage = function() use ($tmpFileName, $tmpFilePath) {
			copy(__DIR__ . '/gdtest/test_jpg.jpg', $tmpFilePath);

			// emulates the $_FILES array
			$tmpFile = array(
				'name' => $tmpFileName,
				'type' => 'text/plaintext',
				'size' => filesize($tmpFilePath),
				'tmp_name' => $tmpFilePath,
				'extension' => 'jpg',
				'error' => UPLOAD_ERR_OK,
			);

			$v = new UploadTest_Validator();

			// test upload into default folder
			$u = new Upload();
			$u->setReplaceFile(true);
			$u->setValidator($v);
			$u->load($tmpFile);
			return $u->getFile();
		};

		// Image upload and generate a resampled image
		$image = $uploadImage();
		$resampled = $image->ResizedImage(123, 456);
		$resampledPath = $resampled->getFullPath();
		$this->assertTrue(file_exists($resampledPath));

		// Re-upload the image, overwriting the original
		// Resampled images should removed when their parent file is overwritten
		$image = $uploadImage();
		$this->assertFalse(file_exists($resampledPath));

		unlink($tmpFilePath);
		$image->delete();
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
			$ext = (isset($pathInfo['extension'])) ? $pathInfo['extension'] : '';
			$arg = File::format_size($this->getAllowedMaxFileSize($ext));
			$this->errors[] = _t(
				'File.TOOLARGE', 
				'Filesize is too large, maximum {size} allowed',
				'Argument 1: Filesize (e.g. 1MB)',
				array('size' => $arg)
			);
			return false;
		}

		// extension validation
		if(!$this->isValidExtension()) {
			$this->errors[] = _t(
				'File.INVALIDEXTENSION', 
				'Extension is not allowed (valid: {extensions})',
				'Argument 1: Comma-separated list of valid extensions',
				array('extensions' => implode(',', $this->allowedExtensions))
			);
			return false;
		}
		
		return true;
	}

}
