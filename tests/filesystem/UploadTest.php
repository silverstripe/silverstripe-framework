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
		
		// test upload into default folder
		$u1 = new Upload();
		$v = new UploadTest_Validator();
		
		$v->setAllowedMaxFileSize(array('txt' => 10));
		$u1->setValidator($v);
		$result = $u1->load($tmpFile);
		$this->assertFalse($result, 'Load failed because size was too big');
		
		$v->setAllowedMaxFileSize(array('[doc]' => 10));
		$u1->setValidator($v);
		$result = $u1->load($tmpFile);
		$this->assertFalse($result, 'Load failed because size was too big');
		
		$v->setAllowedMaxFileSize(array('txt' => 200000));
		$u1->setValidator($v);
		$result = $u1->load($tmpFile);
		$this->assertTrue($result, 'Load failed with setting max file size');
		
		// check max file size set by app category
		$tmpFileName = 'UploadTest-testUpload.jpg';
		$tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
		file_put_contents($tmpFilePath, $tmpFileContent . $tmpFileContent);
		
		$tmpFile = array(
			'name' => $tmpFileName,
			'type' => 'image/jpeg',
			'size' => filesize($tmpFilePath),
			'tmp_name' => $tmpFilePath,
			'extension' => 'jpg',
			'error' => UPLOAD_ERR_OK,
		);
		
		$v->setAllowedMaxFileSize(array('[image]' => '40k'));
		$u1->setValidator($v);
		$result = $u1->load($tmpFile);
		$this->assertTrue($result, 'Load failed with setting max file size');
		
		$v->setAllowedMaxFileSize(array('[image]' => '1k'));
		$u1->setValidator($v);
		$result = $u1->load($tmpFile);
		$this->assertFalse($result, 'Load failed because size was too big');
		
		$v->setAllowedMaxFileSize(array('[image]' => 1000));
		$u1->setValidator($v);
		$result = $u1->load($tmpFile);
		$this->assertFalse($result, 'Load failed because size was too big');
	}
	
	public function testGetAllowedMaxFileSize() {
		Config::nest();
		
		// Check the max file size uses the config values
		$configMaxFileSizes = array(
			'[image]' => '1k',
			'txt' => 1000
		);
		Config::inst()->update('Upload_Validator', 'default_max_file_size', $configMaxFileSizes);
		$v = new UploadTest_Validator();
		
		$retrievedSize = $v->getAllowedMaxFileSize('[image]');
		$this->assertEquals(1024, $retrievedSize, 'Max file size check on default values failed (config category set check)');
		
		$retrievedSize = $v->getAllowedMaxFileSize('txt');
		$this->assertEquals(1000, $retrievedSize, 'Max file size check on default values failed (config extension set check)');
		
		// Check instance values for max file size
		$maxFileSizes = array(
			'[doc]' => 2000,
			'txt' => '4k'
		);
		$v = new UploadTest_Validator();
		$v->setAllowedMaxFileSize($maxFileSizes);
		
		$retrievedSize = $v->getAllowedMaxFileSize('[doc]');
		$this->assertEquals(2000, $retrievedSize, 'Max file size check on instance values failed (instance category set check)');
		
		// Check that the instance values overwrote the default values
		// ie. The max file size will not exist for [image]
		$retrievedSize = $v->getAllowedMaxFileSize('[image]');
		$this->assertFalse($retrievedSize, 'Max file size check on instance values failed (config overridden check)');
		
		// Check a category that has not been set before
		$retrievedSize = $v->getAllowedMaxFileSize('[zip]');
		$this->assertFalse($retrievedSize, 'Max file size check on instance values failed (category not set check)');
		
		// Check a file extension that has not been set before
		$retrievedSize = $v->getAllowedMaxFileSize('mp3');
		$this->assertFalse($retrievedSize, 'Max file size check on instance values failed (extension not set check)');
		
		$retrievedSize = $v->getAllowedMaxFileSize('txt');
		$this->assertEquals(4096, $retrievedSize, 'Max file size check on instance values failed (instance extension set check)');
		
		Config::unnest();
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
		$this->assertFileExists(
			BASE_PATH . '/'  . $file->getRelativePath(),
			'File exists'
		);
		
		$u = new Upload();
		$u->load($tmpFile);
		$file2 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload2.tar.gz',
			$file2->Name,
			'File receives a number attached to the end before the extension'
		);
		$this->assertFileExists(
			BASE_PATH . '/'  . $file2->getRelativePath(),
			'File exists'
		);
		$this->assertGreaterThan(
			$file->ID,
			$file2->ID,
			'File database record is not the same'
		);
		
		$u = new Upload();
		$u->load($tmpFile);
		$file3 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload3.tar.gz',
			$file3->Name,
			'File receives a number attached to the end before the extension'
		);
		$this->assertFileExists(
			BASE_PATH . '/'  . $file3->getRelativePath(),
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
		$this->assertFileExists(
			BASE_PATH . '/'  . $file->getRelativePath(),
			'File exists'
		);
		
		$u = new Upload();
		$u->setValidator($v);
		$u->load($tmpFile);
		$file2 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload2',
			$file2->Name,
			'File receives a number attached to the end'
		);
		$this->assertFileExists(
			BASE_PATH . '/'  . $file2->getRelativePath(),
			'File exists'
		);
		$this->assertGreaterThan(
			$file->ID,
			$file2->ID,
			'File database record is not the same'
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
		$this->assertFileExists(
			BASE_PATH . '/'  . $file->getRelativePath(),
			'File exists'
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
		$this->assertFileExists(
			BASE_PATH . '/'  . $file2->getRelativePath(),
			'File exists'
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

	public function testFileVersioningWithAnExistingFile() {
		$upload = function($tmpFileName) {
			// create tmp file
			$tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
			$tmpFileContent = '';
			for ($i = 0; $i < 10000; $i++) {
				$tmpFileContent .= '0';
			}
			file_put_contents($tmpFilePath, $tmpFileContent);

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
			$u->setReplaceFile(false);
			$u->setValidator($v);
			$u->load($tmpFile);
			return $u->getFile();
		};
		
		// test empty file version prefix
		$originalVersionPrefix = Config::inst()->get('Upload', 'version_prefix');
		Config::inst()->update('Upload', 'version_prefix', '');

		$file1 = $upload('UploadTest-IMG001.jpg');
		$this->assertEquals(
			'UploadTest-IMG001.jpg',
			$file1->Name,
			'File does not receive new name'
		);

		$file2 = $upload('UploadTest-IMG001.jpg');
		$this->assertEquals(
			'UploadTest-IMG2.jpg',
			$file2->Name,
			'File does receive new name'
		);

		$file3 = $upload('UploadTest-IMG001.jpg');
		$this->assertEquals(
			'UploadTest-IMG3.jpg',
			$file3->Name,
			'File does receive new name'
		);

		$file4 = $upload('UploadTest-IMG3.jpg');
		$this->assertEquals(
			'UploadTest-IMG4.jpg',
			$file4->Name,
			'File does receive new name'
		);

		$file1->delete();
		$file2->delete();
		$file3->delete();
		$file4->delete();
		
		// test '-v' file version prefix
		Config::inst()->update('Upload', 'version_prefix', '-v');

		$file1 = $upload('UploadTest2-IMG001.jpg');
		$this->assertEquals(
			'UploadTest2-IMG001.jpg',
			$file1->Name,
			'File does not receive new name'
		);

		$file2 = $upload('UploadTest2-IMG001.jpg');
		$this->assertEquals(
			'UploadTest2-IMG001-v2.jpg',
			$file2->Name,
			'File does receive new name'
		);

		$file3 = $upload('UploadTest2-IMG001.jpg');
		$this->assertEquals(
			'UploadTest2-IMG001-v3.jpg',
			$file3->Name,
			'File does receive new name'
		);

		$file4 = $upload('UploadTest2-IMG001-v3.jpg');
		$this->assertEquals(
			'UploadTest2-IMG001-v4.jpg',
			$file4->Name,
			'File does receive new name'
		);

		$file1->delete();
		$file2->delete();
		$file3->delete();
		$file4->delete();
		
		Config::inst()->update('Upload', 'version_prefix', $originalVersionPrefix);
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
