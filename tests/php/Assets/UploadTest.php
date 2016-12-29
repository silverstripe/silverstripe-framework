<?php

namespace SilverStripe\Assets\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Versioning\Versioned;

class UploadTest extends SapphireTest
{
    /**
     * {@inheritDoc}
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * The temporary file path used for upload tests
     * @var string
     */
    protected $tmpFilePath;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        Versioned::set_stage(Versioned::DRAFT);
        TestAssetStore::activate('UploadTest');
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();

        if (file_exists($this->tmpFilePath)) {
            unlink($this->tmpFilePath);
        }
    }

    public function testUpload()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload.txt';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
            'extension' => 'txt',
            'error' => UPLOAD_ERR_OK,
        );

        $v = new UploadTest\Validator();

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
            TestAssetStore::getLocalPath($file1)
        );
        $this->assertFileExists(
            TestAssetStore::getLocalPath($file1),
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
            TestAssetStore::getLocalPath($file2)
        );
        $this->assertFileExists(
            TestAssetStore::getLocalPath($file2),
            'File upload to custom directory in /assets'
        );
    }

    public function testAllowedFilesize()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload.txt';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
            'extension' => 'txt',
            'error' => UPLOAD_ERR_OK,
        );

        // test upload into default folder
        $u1 = new Upload();
        $v = new UploadTest\Validator();

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
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        file_put_contents($this->tmpFilePath, $tmpFileContent . $tmpFileContent);

        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'image/jpeg',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
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

    public function testPHPUploadErrors()
    {
        $configMaxFileSizes = ['*' => '1k'];
        Config::inst()->update(
            'SilverStripe\\Assets\\Upload_Validator',
            'default_max_file_size',
            $configMaxFileSizes
        );
        // create tmp file
        $tmpFileName = 'myfile.jpg';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent(100);
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // Build file
        $upload = new Upload();
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => '',
            'tmp_name' => $this->tmpFilePath,
            'size' => filesize($this->tmpFilePath),
            'error' => UPLOAD_ERR_OK,
        );

        // Test ok
        $this->assertTrue($upload->validate($tmpFile));

        // Test zero size file
        $upload->clearErrors();
        $tmpFile['size'] = 0;
        $this->assertFalse($upload->validate($tmpFile));
        $this->assertContains(
            _t('File.NOFILESIZE', 'Filesize is zero bytes.'),
            $upload->getErrors()
        );

        // Test file too large
        $upload->clearErrors();
        $tmpFile['error'] = UPLOAD_ERR_INI_SIZE;
        $this->assertFalse($upload->validate($tmpFile));
        $this->assertContains(
            _t(
                'File.TOOLARGE',
                'Filesize is too large, maximum {size} allowed',
                'Argument 1: Filesize (e.g. 1MB)',
                array('size' => '1 KB')
            ),
            $upload->getErrors()
        );

        // Test form size
        $upload->clearErrors();
        $tmpFile['error'] = UPLOAD_ERR_FORM_SIZE;
        $this->assertFalse($upload->validate($tmpFile));
        $this->assertContains(
            _t(
                'File.TOOLARGE',
                'Filesize is too large, maximum {size} allowed',
                'Argument 1: Filesize (e.g. 1MB)',
                array('size' => '1 KB')
            ),
            $upload->getErrors()
        );

        // Test no file
        $upload->clearErrors();
        $tmpFile['error'] = UPLOAD_ERR_NO_FILE;
        $this->assertFalse($upload->validate($tmpFile));
        $this->assertContains(
            _t('File.NOVALIDUPLOAD', 'File is not a valid upload'),
            $upload->getErrors()
        );
    }

    public function testGetAllowedMaxFileSize()
    {
        Config::nest();

        // Check the max file size uses the config values
        $configMaxFileSizes = array(
            '[image]' => '1k',
            'txt' => 1000
        );
        Config::inst()->update('SilverStripe\\Assets\\Upload_Validator', 'default_max_file_size', $configMaxFileSizes);
        $v = new UploadTest\Validator();

        $retrievedSize = $v->getAllowedMaxFileSize('[image]');
        $this->assertEquals(
            1024,
            $retrievedSize,
            'Max file size check on default values failed (config category set check)'
        );

        $retrievedSize = $v->getAllowedMaxFileSize('txt');
        $this->assertEquals(
            1000,
            $retrievedSize,
            'Max file size check on default values failed (config extension set check)'
        );

        // Check instance values for max file size
        $maxFileSizes = array(
            '[document]' => 2000,
            'txt' => '4k'
        );
        $v = new UploadTest\Validator();
        $v->setAllowedMaxFileSize($maxFileSizes);

        $retrievedSize = $v->getAllowedMaxFileSize('[document]');
        $this->assertEquals(
            2000,
            $retrievedSize,
            'Max file size check on instance values failed (instance category set check)'
        );

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
        $this->assertEquals(
            4096,
            $retrievedSize,
            'Max file size check on instance values failed (instance extension set check)'
        );

        // Check a wildcard max file size against a file with an extension
        $v = new UploadTest\Validator();
        $v->setAllowedMaxFileSize(2000);

        $retrievedSize = $v->getAllowedMaxFileSize('.jpg');
        $this->assertEquals(
            2000,
            $retrievedSize,
            'Max file size check on instance values failed (wildcard max file size)'
        );

        Config::unnest();
    }

    public function testAllowedSizeOnFileWithNoExtension()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
            'extension' => '',
            'error' => UPLOAD_ERR_OK,
        );

        $v = new UploadTest\Validator();
        $v->setAllowedMaxFileSize(array('' => 10));

        // test upload into default folder
        $u1 = new Upload();
        $u1->setValidator($v);
        $result = $u1->loadIntoFile($tmpFile);

        $this->assertFalse($result, 'Load failed because size was too big');
    }

    public function testUploadDoesNotAllowUnknownExtension()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload.php';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
            'extension' => 'php',
            'error' => UPLOAD_ERR_OK,
        );

        $v = new UploadTest\Validator();
        $v->setAllowedExtensions(array('txt'));

        // test upload into default folder
        $u = new Upload();
        $u->setValidator($v);
        $result = $u->loadIntoFile($tmpFile);

        $this->assertFalse($result, 'Load failed because extension was not accepted');
    }

    public function testUploadAcceptsAllowedExtension()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload.txt';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
            'extension' => 'txt',
            'error' => UPLOAD_ERR_OK,
        );

        $v = new UploadTest\Validator();
        $v->setAllowedExtensions(array('txt'));

        // test upload into default folder
        $u = new Upload();
        $u->setValidator($v);
        $u->loadIntoFile($tmpFile);
        $file = $u->getFile();
        $this->assertFileExists(
            TestAssetStore::getLocalPath($file),
            'File upload to custom directory in /assets'
        );
    }

    public function testUploadDeniesNoExtensionFilesIfNoEmptyStringSetForValidatorExtensions()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
            'extension' => '',
            'error' => UPLOAD_ERR_OK,
        );

        $v = new UploadTest\Validator();
        $v->setAllowedExtensions(array('txt'));

        // test upload into default folder
        $u = new Upload();
        $result = $u->loadIntoFile($tmpFile);

        $this->assertFalse($result, 'Load failed because extension was not accepted');
        $this->assertEquals(1, count($u->getErrors()), 'There is a single error of the file extension');
    }

    public function testUploadTarGzFileTwiceAppendsNumber()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload.tar.gz';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
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
            TestAssetStore::getLocalPath($file),
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
            TestAssetStore::getLocalPath($file2),
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
            TestAssetStore::getLocalPath($file3),
            'File exists'
        );
        $this->assertGreaterThan(
            $file2->ID,
            $file3->ID,
            'File database record is not the same'
        );
    }

    public function testUploadFileWithNoExtensionTwiceAppendsNumber()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
            'extension' => 'txt',
            'error' => UPLOAD_ERR_OK,
        );

        $v = new UploadTest\Validator();
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
            TestAssetStore::getLocalPath($file),
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
            TestAssetStore::getLocalPath($file2),
            'File exists'
        );
        $this->assertGreaterThan(
            $file->ID,
            $file2->ID,
            'File database record is not the same'
        );
    }

    public function testReplaceFile()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
            'extension' => 'txt',
            'error' => UPLOAD_ERR_OK,
        );

        $v = new UploadTest\Validator();
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
            TestAssetStore::getLocalPath($file),
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
            TestAssetStore::getLocalPath($file2),
            'File exists'
        );
        $this->assertEquals(
            $file->ID,
            $file2->ID,
            'File database record is the same'
        );
    }

    public function testReplaceFileWithLoadIntoFile()
    {
        // create tmp file
        $tmpFileName = 'UploadTest-testUpload.txt';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
        $tmpFileContent = $this->getTemporaryFileContent();
        file_put_contents($this->tmpFilePath, $tmpFileContent);

        // emulates the $_FILES array
        $tmpFile = array(
            'name' => $tmpFileName,
            'type' => 'text/plaintext',
            'size' => filesize($this->tmpFilePath),
            'tmp_name' => $this->tmpFilePath,
            'extension' => 'txt',
            'error' => UPLOAD_ERR_OK,
        );

        $v = new UploadTest\Validator();

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
            TestAssetStore::getLocalPath($file),
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
            TestAssetStore::getLocalPath($file2),
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
            TestAssetStore::getLocalPath($file3),
            'File exists'
        );
        $this->assertGreaterThan(
            $file2->ID,
            $file3->ID,
            'File database record is not the same'
        );
    }

    public function testDeleteResampledImagesOnUpload()
    {
        $tmpFileName = 'UploadTest-testUpload.jpg';
        $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;

        $uploadImage = function () use ($tmpFileName) {
            copy(__DIR__ . '/GDTest/images/test_jpg.jpg', $this->tmpFilePath);

            // emulates the $_FILES array
            $tmpFile = array(
                'name' => $tmpFileName,
                'type' => 'text/plaintext',
                'size' => filesize($this->tmpFilePath),
                'tmp_name' => $this->tmpFilePath,
                'extension' => 'jpg',
                'error' => UPLOAD_ERR_OK,
            );

            $v = new UploadTest\Validator();

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
        $resampledPath = TestAssetStore::getLocalPath($resampled);
        $this->assertFileExists($resampledPath);

        // Re-upload the image, overwriting the original
        // Resampled images should removed when their parent file is overwritten
        $image = $uploadImage();
        $this->assertFileExists($resampledPath);
    }

    public function testFileVersioningWithAnExistingFile()
    {
        $upload = function ($tmpFileName) {
            // create tmp file
            $this->tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
            $tmpFileContent = $this->getTemporaryFileContent();
            file_put_contents($this->tmpFilePath, $tmpFileContent);

            // emulates the $_FILES array
            $tmpFile = array(
                'name' => $tmpFileName,
                'type' => 'text/plaintext',
                'size' => filesize($this->tmpFilePath),
                'tmp_name' => $this->tmpFilePath,
                'extension' => 'jpg',
                'error' => UPLOAD_ERR_OK,
            );

            $v = new UploadTest\Validator();

            // test upload into default folder
            $u = new Upload();
            $u->setReplaceFile(false);
            $u->setValidator($v);
            $u->loadIntoFile($tmpFile);
            return $u->getFile();
        };

        // test empty file version prefix
        Config::inst()->update('SilverStripe\\Assets\\Storage\\DefaultAssetNameGenerator', 'version_prefix', '');

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
        Config::inst()->update('SilverStripe\\Assets\\Storage\\DefaultAssetNameGenerator', 'version_prefix', '-v');

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

    /**
     * Generate some dummy file content
     *
     * @param  int $reps How many zeros to return
     * @return string
     */
    protected function getTemporaryFileContent($reps = 10000)
    {
        return str_repeat('0', $reps);
    }
}
