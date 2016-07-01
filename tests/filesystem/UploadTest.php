<?php

use SilverStripe\ORM\Versioning\Versioned;

/**
 * @package framework
 * @subpackage tests
 */
class UploadTest extends SapphireTest {

	protected $usesDatabase = true;

	public function setUp() {
		parent::setUp();
		Versioned::set_stage(Versioned::DRAFT);
		AssetStoreTest_SpyStore::activate('UploadTest');
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset();
		parent::tearDown();
	}

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
		$u1->loadIntoFile($tmpFile);
		$file1 = $u1->getFile();
		$this->assertEquals(
			'Uploads/UploadTest-testUpload.txt',
			$file1->getFilename()
		);
		$this->assertEquals(
			BASE_PATH . '/assets/UploadTest/.protected/Uploads/315ae4c3d4/UploadTest-testUpload.txt',
			AssetStoreTest_SpyStore::getLocalPath($file1)
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file1),
			'File upload to standard directory in /assets'
		);

		// test upload into custom folder
		$customFolder = 'UploadTest-testUpload';
		$u2 = new Upload();
		$u2->loadIntoFile($tmpFile, null, $customFolder);
		$file2 = $u2->getFile();
		$this->assertEquals(
			'UploadTest-testUpload/UploadTest-testUpload.txt',
			$file2->getFilename()
		);
		$this->assertEquals(
			BASE_PATH . '/assets/UploadTest/.protected/UploadTest-testUpload/315ae4c3d4/UploadTest-testUpload.txt',
			AssetStoreTest_SpyStore::getLocalPath($file2)
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file2),
			'File upload to custom directory in /assets'
		);
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
		$result = $u1->loadIntoFile($tmpFile);
		$this->assertFalse($result, 'Load failed because size was too big');

		$v->setAllowedMaxFileSize(array('[document]' => 10));
		$u1->setValidator($v);
		$result = $u1->loadIntoFile($tmpFile);
		$this->assertFalse($result, 'Load failed because size was too big');

		$v->setAllowedMaxFileSize(array('txt' => 200000));
		$u1->setValidator($v);
		$result = $u1->loadIntoFile($tmpFile);
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
		$result = $u1->loadIntoFile($tmpFile);
		$this->assertTrue($result, 'Load failed with setting max file size');

		$v->setAllowedMaxFileSize(array('[image]' => '1k'));
		$u1->setValidator($v);
		$result = $u1->loadIntoFile($tmpFile);
		$this->assertFalse($result, 'Load failed because size was too big');

		$v->setAllowedMaxFileSize(array('[image]' => 1000));
		$u1->setValidator($v);
		$result = $u1->loadIntoFile($tmpFile);
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
			'[document]' => 2000,
			'txt' => '4k'
		);
		$v = new UploadTest_Validator();
		$v->setAllowedMaxFileSize($maxFileSizes);

		$retrievedSize = $v->getAllowedMaxFileSize('[document]');
		$this->assertEquals(2000, $retrievedSize, 'Max file size check on instance values failed (instance category set check)');

		// Check that the instance values overwrote the default values
		// ie. The max file size will not exist for [image]
		$retrievedSize = $v->getAllowedMaxFileSize('[image]');
		$this->assertFalse($retrievedSize, 'Max file size check on instance values failed (config overridden check)');

		// Check a category that has not been set before
		$retrievedSize = $v->getAllowedMaxFileSize('[archive]');
		$this->assertFalse($retrievedSize, 'Max file size check on instance values failed (category not set check)');

		// Check a file extension that has not been set before
		$retrievedSize = $v->getAllowedMaxFileSize('mp3');
		$this->assertFalse($retrievedSize, 'Max file size check on instance values failed (extension not set check)');

		$retrievedSize = $v->getAllowedMaxFileSize('txt');
		$this->assertEquals(4096, $retrievedSize, 'Max file size check on instance values failed (instance extension set check)');

		// Check a wildcard max file size against a file with an extension
		$v = new UploadTest_Validator();
		$v->setAllowedMaxFileSize(2000);

		$retrievedSize = $v->getAllowedMaxFileSize('.jpg');
		$this->assertEquals(2000, $retrievedSize, 'Max file size check on instance values failed (wildcard max file size)');

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
		$result = $u1->loadIntoFile($tmpFile);

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
		$result = $u->loadIntoFile($tmpFile);

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
		$u->loadIntoFile($tmpFile);
		$file = $u->getFile();
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file),
			'File upload to custom directory in /assets'
		);
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
		$result = $u->loadIntoFile($tmpFile);

		$this->assertFalse($result, 'Load failed because extension was not accepted');
		$this->assertEquals(1, count($u->getErrors()), 'There is a single error of the file extension');
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

		// test upload into default folder
		$u = new Upload();
		$u->loadIntoFile($tmpFile);
		$file = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload.tar.gz',
			$file->Name,
			'File has a name without a number because it\'s not a duplicate'
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file),
			'File exists'
		);

		$u = new Upload();
		$u->loadIntoFile($tmpFile);
		$file2 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload-v2.tar.gz',
			$file2->Name,
			'File receives a number attached to the end before the extension'
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file2),
			'File exists'
		);
		$this->assertGreaterThan(
			$file->ID,
			$file2->ID,
			'File database record is not the same'
		);

		$u = new Upload();
		$u->loadIntoFile($tmpFile);
		$file3 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload-v3.tar.gz',
			$file3->Name,
			'File receives a number attached to the end before the extension'
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file3),
			'File exists'
		);
		$this->assertGreaterThan(
			$file2->ID,
			$file3->ID,
			'File database record is not the same'
		);
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

		$v = new UploadTest_Validator();
		$v->setAllowedExtensions(array(''));

		// test upload into default folder
		$u = new Upload();
		$u->setValidator($v);
		$u->loadIntoFile($tmpFile);
		$file = $u->getFile();

		$this->assertEquals(
			'UploadTest-testUpload',
			$file->Name,
			'File is uploaded without extension'
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file),
			'File exists'
		);

		$u = new Upload();
		$u->setValidator($v);
		$u->loadIntoFile($tmpFile);
		$file2 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload-v2',
			$file2->Name,
			'File receives a number attached to the end'
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file2),
			'File exists'
		);
		$this->assertGreaterThan(
			$file->ID,
			$file2->ID,
			'File database record is not the same'
		);
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

		$v = new UploadTest_Validator();
		$v->setAllowedExtensions(array(''));

		// test upload into default folder
		$u = new Upload();
		$u->setValidator($v);
		$u->loadIntoFile($tmpFile);
		$file = $u->getFile();

		$this->assertEquals(
			'UploadTest-testUpload',
			$file->Name,
			'File is uploaded without extension'
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file),
			'File exists'
		);

		$u = new Upload();
		$u->setValidator($v);
		$u->setReplaceFile(true);
		$u->loadIntoFile($tmpFile);
		$file2 = $u->getFile();
		$this->assertEquals(
			'UploadTest-testUpload',
			$file2->Name,
			'File does not receive new name'
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file2),
			'File exists'
		);
		$this->assertEquals(
			$file->ID,
			$file2->ID,
			'File database record is the same'
		);
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

		$v = new UploadTest_Validator();

		// test upload into default folder
		$u = new Upload();
		$u->setValidator($v);
		$u->loadIntoFile($tmpFile);
		$file = $u->getFile();

		$this->assertEquals(
			'UploadTest-testUpload.txt',
			$file->Name,
			'File is uploaded without extension'
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file),
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
			AssetStoreTest_SpyStore::getLocalPath($file2),
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
			'UploadTest-testUpload-v2.txt',
			$file3->Name,
			'File does receive new name'
		);
		$this->assertFileExists(
			AssetStoreTest_SpyStore::getLocalPath($file3),
			'File exists'
		);
		$this->assertGreaterThan(
			$file2->ID,
			$file3->ID,
			'File database record is not the same'
		);
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
			$u->loadIntoFile($tmpFile);
			return $u->getFile();
		};

		// Image upload and generate a resampled image
		$image = $uploadImage();
		$resampled = $image->ResizedImage(123, 456);
		$resampledPath = AssetStoreTest_SpyStore::getLocalPath($resampled);
		$this->assertFileExists($resampledPath);

		// Re-upload the image, overwriting the original
		// Resampled images should removed when their parent file is overwritten
		$image = $uploadImage();
		$this->assertFileExists($resampledPath);
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
			$u->loadIntoFile($tmpFile);
			return $u->getFile();
		};

		// test empty file version prefix
		Config::inst()->update('SilverStripe\Filesystem\Storage\DefaultAssetNameGenerator', 'version_prefix', '');

		$file1 = $upload('UploadTest-IMG001.jpg');
		$this->assertEquals(
			'UploadTest-IMG001.jpg',
			$file1->Name,
			'File does not receive new name'
		);

		$file2 = $upload('UploadTest-IMG001.jpg');
		$this->assertEquals(
			'UploadTest-IMG002.jpg',
			$file2->Name,
			'File does receive new name'
		);

		$file3 = $upload('UploadTest-IMG002.jpg');
		$this->assertEquals(
			'UploadTest-IMG003.jpg',
			$file3->Name,
			'File does receive new name'
		);

		$file4 = $upload('UploadTest-IMG3.jpg');
		$this->assertEquals(
			'UploadTest-IMG3.jpg',
			$file4->Name,
			'File does not receive new name'
		);

		$file1->delete();
		$file2->delete();
		$file3->delete();
		$file4->delete();

		// test '-v' file version prefix
		Config::inst()->update('SilverStripe\Filesystem\Storage\DefaultAssetNameGenerator', 'version_prefix', '-v');

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
				'File size is too large, maximum {size} allowed',
				'Argument 1: File size (e.g. 1MB)',
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
