<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Assets\Folder;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Assets\File;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Control\Session;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\AssetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Tests\AssetFieldTest\TestForm;
use SilverStripe\Forms\Tests\AssetFieldTest\TestController;
use SilverStripe\Forms\Tests\AssetFieldTest\TestObject;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;

class AssetFieldTest extends FunctionalTest {

	protected static $fixture_file = 'AssetFieldTest.yml';

	protected $extraDataObjects = array(
		TestObject::class
	);

	protected $extraControllers = [
		TestController::class
	];

	public function setUp() {
		parent::setUp();

		$this->logInWithPermission('ADMIN');
		Versioned::set_stage(Versioned::DRAFT);

		// Set backend root to /AssetFieldTest
		TestAssetStore::activate('AssetFieldTest');
		$create = function($path) {
			Filesystem::makeFolder(dirname($path));
			$fh = fopen($path, "w+");
			fwrite($fh, str_repeat('x', 1000000));
			fclose($fh);
		};

		// Write all DBFile references
		foreach(TestObject::get() as $object) {
			$path = TestAssetStore::getLocalPath($object->File);
			$create($path);
		}

		// Create a test files for each of the fixture references
		$files = File::get()->exclude('ClassName', Folder::class);
		foreach($files as $file) {
			$path = TestAssetStore::getLocalPath($file);
			$create($path);
		}
	}

	public function tearDown() {
		TestAssetStore::reset();
		parent::tearDown();
	}

	/**
	 * Test that files can be uploaded against an object with no relation
	 */
	public function testUploadNoRelation() {
		$this->logInWithPermission('ADMIN');

		$tmpFileName = 'testUploadBasic.txt';
		$response = $this->mockFileUpload('NoRelationField', $tmpFileName);
		$responseJSON = json_decode($response->getBody(), true);
		$this->assertFalse($response->isError());
		$this->assertEquals('MyDocuments/testUploadBasic.txt', $responseJSON[0]['filename']);
		$this->assertEquals('315ae4c3d44412baa0c81515b6fb35829a337a5a', $responseJSON[0]['hash']);
		$this->assertEmpty($responseJSON[0]['variant']);
		$this->assertFileExists(
			BASE_PATH . '/assets/AssetFieldTest/.protected/MyDocuments/315ae4c3d4/testUploadBasic.txt'
		);
	}

	/**
	 * Test that an object can be uploaded against a DBFile field
	 *
	 * @skipUpgrade
	 */
	public function testUploadDBFile() {
		$this->logInWithPermission('ADMIN');

		// Unset existing has_one relation before re-uploading
		$record = $this->objFromFixture(TestObject::class, 'object1');
		$record->FileFilename = null;
		$record->FileHash = null;
		$record->write();

		// Firstly, ensure the file can be uploaded
		$tmpFileName = 'testUploadHasOneRelation.txt';
		$response = $this->mockFileUpload('File', $tmpFileName);
		$responseJSON = json_decode($response->getBody(), true);
		$this->assertFalse($response->isError());
		$this->assertFileExists(
			BASE_PATH . '/assets/AssetFieldTest/.protected/MyFiles/315ae4c3d4/testUploadHasOneRelation.txt'
		);

		// Secondly, ensure that simply uploading an object does not save the file against the relation
		$record = TestObject::get()->byID($record->ID);
		$this->assertFalse($record->File->exists());

		// Thirdly, test submitting the form with the encoded data
		$response = $this->mockUploadFileSave(
			'File',
			$responseJSON[0]['filename'],
			$responseJSON[0]['hash'],
			$responseJSON[0]['variant']
		);
		$this->assertEmpty($response['errors']);
		$record = TestObject::get()->byID($record->ID);
		$this->assertTrue($record->File->exists());
		$this->assertEquals('315ae4c3d44412baa0c81515b6fb35829a337a5a', $record->File->Hash);
		$this->assertEquals('MyFiles/testUploadHasOneRelation.txt', $record->File->Filename);
		$this->assertEmpty($record->File->Variant);
	}

	/**
	 * Partially covered by {@link UploadTest->testUploadAcceptsAllowedExtension()},
	 * but this test additionally verifies that those constraints are actually enforced
	 * in this controller method.
	 *
	 * @skipUpgrade
	 */
	public function testAllowedExtensions() {
		$this->logInWithPermission('ADMIN');

		// Test invalid file
		// Relies on Upload_Validator failing to allow this extension
		$response = $this->mockFileUpload('File', 'invalid.php');
		$response = json_decode($response->getBody(), true);
		$this->assertTrue(array_key_exists('error', $response[0]));
		$this->assertContains('Extension is not allowed', $response[0]['error']);

		// Test valid file
		$response = $this->mockFileUpload('File', 'valid.txt');
		$response = json_decode($response->getBody(), true);
		$this->assertFalse(array_key_exists('error', $response[0]));

		// Test that allowed files cannot be uploaded to restricted field
		$response = $this->mockFileUpload('Image', 'valid.txt');
		$response = json_decode($response->getBody(), true);
		$this->assertTrue(array_key_exists('error', $response[0]));
		$this->assertContains('Extension is not allowed', $response[0]['error']);
	}

	/**
	 * Test that files can be removed from an existing field
	 */
	public function testRemoveFromHasOne() {
		$record = $this->objFromFixture(TestObject::class, 'object1');

		// Check record exists
		$this->assertTrue($record->File->exists());
		$filePath = TestAssetStore::getLocalPath($record->File);
		$this->assertFileExists($filePath);

		// Remove from record
		$response = $this->mockUploadFileSave(File::class, null, null, null);
		$this->assertEmpty($response['errors']);

		// Check file is removed
		$record = TestObject::get()->byID($record->ID);
		$this->assertFalse($record->File->exists());

		// Check file object itself exists
		$this->assertFileNotExists($filePath, 'File is deleted once detached');
	}

	/**
	 * Test control output html
	 */
	public function testView() {
		$this->logInWithPermission('ADMIN');

		$record = $this->objFromFixture(TestObject::class, 'object1');

		// Requesting form is not an error
		$response = $this->get('AssetFieldTest_Controller');
		$this->assertFalse($response->isError());

		// File exists in this response
		$parser = new CSSContentParser($response->getBody());
		$tuple = array();
		$result = $parser->getBySelector(
			"#TestForm_Form_File_Holder .ss-uploadfield-files .ss-uploadfield-item input[type='hidden']"
		);
		foreach($result as $part) {
			$name = (string)$part['name'];
			$value = (string)$part['value'];
			switch($name) {
				case 'File[Filename]':
					$tuple['Filename'] = $value;
					break;
				case 'File[Hash]':
					$tuple['Hash'] = $value;
					break;
				case 'File[Variant]':
					$tuple['Variant'] = $value;
					break;
			}
		}

		// Assert this value is correct
		$expected = array(
			'Filename' => 'MyFiles/subfolder1/file-subfolder.txt',
			'Hash' => '55b443b60176235ef09801153cca4e6da7494a0c',
			'Variant' => '',
		);
		$this->assertEquals($expected, $record->File->getValue());
		$this->assertEquals($expected, $tuple);
	}

	public function testGetRecord() {
		$record = $this->objFromFixture(TestObject::class, 'object1');
		$form = $this->getMockForm();

		$field = AssetField::create('MyField');
		$field->setForm($form);
		$this->assertNull($field->getRecord(), 'Returns no record by default');

		$field = AssetField::create('MyField');
		$field->setForm($form);
		$form->loadDataFrom($record);
		$this->assertEquals($record, $field->getRecord(), 'Returns record from form if available');

		$field = AssetField::create('MyField');
		$field->setForm($form);
		$field->setRecord($record);
		$this->assertEquals($record, $field->getRecord(), 'Returns record when set explicitly');
	}

	/**
	 * Test that getValue() / Value() methods work
	 *
	 * @skipUpgrade
	 */
	public function testValue() {
		$record = $this->objFromFixture(TestObject::class, 'object1');

		// File field
		$field = AssetField::create('File');
		$this->assertEmpty($field->Value());
		$field->setValue(null, $record);
		$this->assertEquals(array(
			'Filename' => 'MyFiles/subfolder1/file-subfolder.txt',
			'Hash' => '55b443b60176235ef09801153cca4e6da7494a0c',
			'Variant' => null,
		), $field->Value());

		// Empty field
		$field = AssetField::create('Image');
		$this->assertEmpty($field->Value());
		$field->setValue(null, $record);
		$this->assertEmpty($field->Value());

		// Set via file (copies only tuple not the actual file reference)
		$file = $this->objFromFixture(File::class, 'file1');
		$field->setValue($file);
		$this->assertEquals(array(
			'Filename' => 'MyAssets/file1.txt',
			'Hash' => '55b443b60176235ef09801153cca4e6da7494a0c',
			'Variant' => null,
		), $field->Value());
	}

	public function testCanUploadWithPermissionCode() {
		Session::clear("loggedInAs");
		$field = AssetField::create('MyField');

		$field->setCanUpload(true);
		$this->assertTrue($field->canUpload());

		$field->setCanUpload(false);
		$this->assertFalse($field->canUpload());

		$field->setCanUpload('ADMIN');
		$this->assertFalse($field->canUpload());

		$this->logInWithPermission('ADMIN');

		$field->setCanUpload(false);
		$this->assertFalse($field->canUpload());

		$field->setCanUpload('ADMIN');
		$this->assertTrue($field->canUpload());
	}


	protected function getMockForm() {
		/** @skipUpgrade */
		return new Form(new Controller(), 'Form', new FieldList(), new FieldList());
	}

	/**
	 * @param string $tmpFileName
	 * @return array Emulating an entry in the $_FILES superglobal
	 */
	protected function getUploadFile($tmpFileName = 'AssetFieldTest-testUpload.txt') {
		$tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
		$tmpFileContent = '';
		for($i=0; $i<10000; $i++) $tmpFileContent .= '0';
		file_put_contents($tmpFilePath, $tmpFileContent);

		// emulates the $_FILES array
		// Notice that unlike UploadFieldTest::getUploadFile the key is 'Upload' not 'Uploads'
		// and the value is a literal not an array
		return array(
			'name' => array('Upload' => $tmpFileName),
			'type' => array('Upload' => 'text/plaintext'),
			'size' => array('Upload' => filesize($tmpFilePath)),
			'tmp_name' => array('Upload' => $tmpFilePath),
			'error' => array('Upload' => UPLOAD_ERR_OK),
		);
	}

	/**
	 * Simulates a form post to the test controller with the specified file tuple (Filename, Hash, Variant)
	 *
	 * @param string $fileField Name of field to assign ids to
	 * @param string $filename
	 * @param string $hash
	 * @param string $variant
	 * @return array Array with key 'errors'
	 */
	protected function mockUploadFileSave($fileField, $filename, $hash, $variant = null) {
		// collate file ids
		$data = array(
			'action_submit' => 1,
			$fileField => array(
				'Filename' => $filename,
				'Hash' => $hash,
				'Variant' => $variant
			)
		);

		$form = new TestForm();
		$form->loadDataFrom($data, true);
		if($form->validationResult()->isValid()) {
			$record = $form->getRecord();
			$form->saveInto($record);
			$record->write();
			return array('errors' => null);
		} else {
			return array('errors' => $form->getValidator()->getErrors());
		}
	}

	/**
	 * Simulates a file upload
	 *
	 * @param string $fileField Name of the field to mock upload for
	 * @param array $tmpFileName Name of temporary file to upload
	 * @return HTTPResponse form response
	 */
	protected function mockFileUpload($fileField, $tmpFileName) {
		$upload = $this->getUploadFile($tmpFileName);
		$_FILES = array($fileField => $upload);
		return $this->post(
			"AssetFieldTest_Controller/Form/field/{$fileField}/upload",
			array($fileField => $upload)
		);
	}
}
