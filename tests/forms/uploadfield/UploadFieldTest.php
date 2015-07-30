<?php
/**
 * @package framework
 * @subpackage tests
 */

class UploadFieldTest extends FunctionalTest {

	protected static $fixture_file = 'UploadFieldTest.yml';

	protected $extraDataObjects = array('UploadFieldTest_Record');

	protected $requiredExtensions = array(
		'File' => array('UploadFieldTest_FileExtension')
	);

	/**
	 * Test that files can be uploaded against an object with no relation
	 */
	public function testUploadNoRelation() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');

		$tmpFileName = 'testUploadBasic.txt';
		$response = $this->mockFileUpload('NoRelationField', $tmpFileName);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('File', array(
			'"File"."Name"' => $tmpFileName
		));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');
	}

	/**
	 * Test that an object can be uploaded against an object with a has_one relation
	 */
	public function testUploadHasOneRelation() {
		$this->loginWithPermission('ADMIN');

		// Unset existing has_one relation before re-uploading
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$record->HasOneFileID = null;
		$record->write();

		// Firstly, ensure the file can be uploaded
		$tmpFileName = 'testUploadHasOneRelation.txt';
		$response = $this->mockFileUpload('HasOneFile', $tmpFileName);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('File', array(
			'"File"."Name"' => $tmpFileName
		));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');

		// Secondly, ensure that simply uploading an object does not save the file against the relation
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertFalse($record->HasOneFile()->exists());

		// Thirdly, test submitting the form with the encoded data
		$response = $this->mockUploadFileIDs('HasOneFile', array($uploadedFile->ID));
		$this->assertEmpty($response['errors']);
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertTrue($record->HasOneFile()->exists());
		$this->assertEquals($record->HasOneFile()->Name, $tmpFileName);
	}

	/**
	 * Tests that has_one relations work with subclasses of File
	 */
	public function testUploadHasOneRelationWithExtendedFile() {
		$this->loginWithPermission('ADMIN');

		// Unset existing has_one relation before re-uploading
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$record->HasOneExtendedFileID = null;
		$record->write();

		// Test that the file can be safely uploaded
		$tmpFileName = 'testUploadHasOneRelationWithExtendedFile.txt';
		$response = $this->mockFileUpload('HasOneExtendedFile', $tmpFileName);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('UploadFieldTest_ExtendedFile', array(
			'"File"."Name"' => $tmpFileName
		));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');

		// Test that the record isn't written to automatically
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertFalse($record->HasOneExtendedFile()->exists());

		// Test that saving the form writes the record
		$response = $this->mockUploadFileIDs('HasOneExtendedFile', array($uploadedFile->ID));
		$this->assertEmpty($response['errors']);
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertTrue($record->HasOneExtendedFile()->exists());
		$this->assertEquals($record->HasOneExtendedFile()->Name, $tmpFileName);
	}


	/**
	 * Test that has_many relations work with files
	 */
	public function testUploadHasManyRelation() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');

		// Test that uploaded files can be posted to a has_many relation
		$tmpFileName = 'testUploadHasManyRelation.txt';
		$response = $this->mockFileUpload('HasManyFiles', $tmpFileName);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('File', array(
			'"File"."Name"' => $tmpFileName
		));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');

		// Test that the record isn't written to automatically
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertEquals(2, $record->HasManyFiles()->Count()); // Existing two files should be retained

		// Test that saving the form writes the record
		$ids = array_merge($record->HasManyFiles()->getIDList(), array($uploadedFile->ID));
		$response = $this->mockUploadFileIDs('HasManyFiles', $ids);
		$this->assertEmpty($response['errors']);
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertEquals(3, $record->HasManyFiles()->Count()); // New record should appear here now
	}

	/**
	 * Test that many_many relationships work with files
	 */
	public function testUploadManyManyRelation() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$relationCount = $record->ManyManyFiles()->Count();

		// Test that uploaded files can be posted to a many_many relation
		$tmpFileName = 'testUploadManyManyRelation.txt';
		$response = $this->mockFileUpload('ManyManyFiles', $tmpFileName);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('File', array(
			'"File"."Name"' => $tmpFileName
		));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');

		// Test that the record isn't written to automatically
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		// Existing file count should be retained
		$this->assertEquals($relationCount, $record->ManyManyFiles()->Count());

		// Test that saving the form writes the record
		$ids = array_merge($record->ManyManyFiles()->getIDList(), array($uploadedFile->ID));
		$response = $this->mockUploadFileIDs('ManyManyFiles', $ids);
		$this->assertEmpty($response['errors']);
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$record->flushCache();
		// New record should appear here now
		$this->assertEquals($relationCount + 1, $record->ManyManyFiles()->Count());
	}

	/**
	 * Partially covered by {@link UploadTest->testUploadAcceptsAllowedExtension()},
	 * but this test additionally verifies that those constraints are actually enforced
	 * in this controller method.
	 */
	public function testAllowedExtensions() {
		$this->loginWithPermission('ADMIN');

		// Test invalid file
		// Relies on Upload_Validator failing to allow this extension
		$invalidFile = 'invalid.php';
		$_FILES = array('AllowedExtensionsField' => $this->getUploadFile($invalidFile));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/AllowedExtensionsField/upload',
			array('AllowedExtensionsField' => $this->getUploadFile($invalidFile))
		);
		$response = json_decode($response->getBody(), true);
		$this->assertTrue(array_key_exists('error', $response[0]));
		$this->assertContains('Extension is not allowed', $response[0]['error']);

		// Test valid file
		$validFile = 'valid.txt';
		$_FILES = array('AllowedExtensionsField' => $this->getUploadFile($validFile));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/AllowedExtensionsField/upload',
			array('AllowedExtensionsField' => $this->getUploadFile($validFile))
		);
		$response = json_decode($response->getBody(), true);
		$this->assertFalse(array_key_exists('error', $response[0]));

		// Test that setAllowedExtensions rejects extensions explicitly denied by File.allowed_extensions
		// Relies on File::validate failing to allow this extension
		$invalidFile = 'invalid.php';
		$_FILES = array('AllowedExtensionsField' => $this->getUploadFile($invalidFile));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/InvalidAllowedExtensionsField/upload',
			array('InvalidAllowedExtensionsField' => $this->getUploadFile($invalidFile))
		);
		$response = json_decode($response->getBody(), true);
		$this->assertTrue(array_key_exists('error', $response[0]));
		$this->assertContains('Extension is not allowed', $response[0]['error']);
		
	}

	/**
	 * Test that has_one relations do not support multiple files
	 */
	public function testAllowedMaxFileNumberWithHasOne() {
		$this->loginWithPermission('ADMIN');

		// Get references for each file to upload
		$file1 = $this->objFromFixture('File', 'file1');
		$file2 = $this->objFromFixture('File', 'file2');
		$fileIDs = array($file1->ID, $file2->ID);

		// Test each of the three cases - has one with no max filel limit, has one with a limit of
		// one, has one with a limit of more than one (makes no sense, but should test it anyway).
		// Each of them should public function in the same way - attaching the first file should work, the
		// second should cause an error.
		foreach (array('HasOneFile', 'HasOneFileMaxOne', 'HasOneFileMaxTwo') as $recordName) {

			// Unset existing has_one relation before re-uploading
			$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
			$record->{"{$recordName}ID"} = null;
			$record->write();

			// Post form with two files for this field, should result in an error
			$response = $this->mockUploadFileIDs($recordName, $fileIDs);
			$isError = !empty($response['errors']);

			// Strictly, a has_one should not allow two files, but this is overridden
			// by the setAllowedMaxFileNumber(2) call
			$maxFiles = ($recordName === 'HasOneFileMaxTwo') ? 2 : 1;

			// Assert that the form fails if the maximum number of files is exceeded
			$this->assertTrue((count($fileIDs) > $maxFiles) == $isError);
		}
	}

	/**
	 * Test that max number of items on has_many is validated
	 */
	public function testAllowedMaxFileNumberWithHasMany() {
		$this->loginWithPermission('ADMIN');

		// The 'HasManyFilesMaxTwo' field has a maximum of two files able to be attached to it.
		// We want to add files to it until we attempt to add the third. We expect that the first
		// two should work and the third will fail.
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$record->HasManyFilesMaxTwo()->removeAll();
		$this->assertCount(0, $record->HasManyFilesMaxTwo());

		// Get references for each file to upload
		$file1 = $this->objFromFixture('File', 'file1');
		$file2 = $this->objFromFixture('File', 'file2');
		$file3 = $this->objFromFixture('File', 'file3');
		$this->assertTrue($file1->exists());
		$this->assertTrue($file2->exists());
		$this->assertTrue($file3->exists());

		// Write the first element, should be okay.
		$response = $this->mockUploadFileIDs('HasManyFilesMaxTwo', array($file1->ID));
		$this->assertEmpty($response['errors']);
		$this->assertCount(1, $record->HasManyFilesMaxTwo());
		$this->assertContains($file1->ID, $record->HasManyFilesMaxTwo()->getIDList());


		$record->HasManyFilesMaxTwo()->removeAll();
		$this->assertCount(0, $record->HasManyFilesMaxTwo());
		$this->assertTrue($file1->exists());
		$this->assertTrue($file2->exists());
		$this->assertTrue($file3->exists());



		// Write the second element, should be okay.
		$response = $this->mockUploadFileIDs('HasManyFilesMaxTwo', array($file1->ID, $file2->ID));
		$this->assertEmpty($response['errors']);
		$this->assertCount(2, $record->HasManyFilesMaxTwo());
		$this->assertContains($file1->ID, $record->HasManyFilesMaxTwo()->getIDList());
		$this->assertContains($file2->ID, $record->HasManyFilesMaxTwo()->getIDList());

		$record->HasManyFilesMaxTwo()->removeAll();
		$this->assertCount(0, $record->HasManyFilesMaxTwo());
		$this->assertTrue($file1->exists());
		$this->assertTrue($file2->exists());
		$this->assertTrue($file3->exists());


		// Write the third element, should result in error.
		$response = $this->mockUploadFileIDs('HasManyFilesMaxTwo', array($file1->ID, $file2->ID, $file3->ID));
		$this->assertNotEmpty($response['errors']);
		$this->assertCount(0, $record->HasManyFilesMaxTwo());
	}

	/**
	 * Test that files can be removed from has_one relations
	 */
	public function testRemoveFromHasOne() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file1 = $this->objFromFixture('File', 'file1');

		// Check record exists
		$this->assertTrue($record->HasOneFile()->exists());

		// Remove from record
		$response = $this->mockUploadFileIDs('HasOneFile', array());
		$this->assertEmpty($response['errors']);

		// Check file is removed
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertFalse($record->HasOneFile()->exists());

		// Check file object itself exists
		$this->assertFileExists($file1->FullPath, 'File is only detached, not deleted from filesystem');
	}

	/**
	 * Test that items can be removed from has_many
	 */
	public function testRemoveFromHasMany() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file2 = $this->objFromFixture('File', 'file2');
		$file3 = $this->objFromFixture('File', 'file3');

		// Check record has two files attached
		$this->assertEquals(array('File2', 'File3'), $record->HasManyFiles()->column('Title'));

		// Remove file 2
		$response = $this->mockUploadFileIDs('HasManyFiles', array($file3->ID));
		$this->assertEmpty($response['errors']);

		// check only file 3 is left
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertEquals(array('File3'), $record->HasManyFiles()->column('Title'));

		// Check file 2 object itself exists
		$this->assertFileExists($file3->FullPath, 'File is only detached, not deleted from filesystem');
	}

	/**
	 * Test that items can be removed from many_many
	 */
	public function testRemoveFromManyMany() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$file5 = $this->objFromFixture('File', 'file5');

		// Check that both files are currently set
		$this->assertContains('File4', $record->ManyManyFiles()->column('Title'));
		$this->assertContains('File5', $record->ManyManyFiles()->column('Title'));

		// Remove file 4
		$response = $this->mockUploadFileIDs('ManyManyFiles', array($file5->ID));
		$this->assertEmpty($response['errors']);

		// check only file 5 is left
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertNotContains('File4', $record->ManyManyFiles()->column('Title'));
		$this->assertContains('File5', $record->ManyManyFiles()->column('Title'));

		// check file 4 object exists
		$this->assertFileExists($file4->FullPath, 'File is only detached, not deleted from filesystem');
	}

	/**
	 * Test that files can be deleted from has_one and the filesystem
	 */
	public function testDeleteFromHasOne() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file1 = $this->objFromFixture('File', 'file1');

		// Check that file initially exists
		$this->assertTrue($record->HasOneFile()->exists());
		$this->assertFileExists($file1->FullPath);

		// Delete physical file and update record
		$response = $this->mockFileDelete('HasOneFile', $file1->ID);
		$this->assertFalse($response->isError());
		$response = $this->mockUploadFileIDs('HasOneFile', array());
		$this->assertEmpty($response['errors']);

		// Check that file is not set against record
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertFalse($record->HasOneFile()->exists());

		// Check that the physical file is deleted
		$this->assertFileNotExists($file1->FullPath, 'File is also removed from filesystem');
	}

	/**
	 * Test that files can be deleted from has_many and the filesystem
	 */
	public function testDeleteFromHasMany() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file2 = $this->objFromFixture('File', 'file2');
		$file3 = $this->objFromFixture('File', 'file3');

		// Check that files initially exists
		$this->assertEquals(array('File2', 'File3'), $record->HasManyFiles()->column('Title'));
		$this->assertFileExists($file2->FullPath);
		$this->assertFileExists($file3->FullPath);

		// Delete physical file and update record without file 2
		$response = $this->mockFileDelete('HasManyFiles', $file2->ID);
		$this->assertFalse($response->isError());
		$response = $this->mockUploadFileIDs('HasManyFiles', array($file3->ID));
		$this->assertEmpty($response['errors']);

		// Test that file is removed from record
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertEquals(array('File3'), $record->HasManyFiles()->column('Title'));

		// Test that physical file is removed
		$this->assertFileNotExists($file2->FullPath, 'File is also removed from filesystem');
	}

	/**
	 * Test that files can be deleted from many_many and the filesystem
	 */
	public function testDeleteFromManyMany() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$file5 = $this->objFromFixture('File', 'file5');
		$fileNoDelete = $this->objFromFixture('File', 'file-nodelete');

		// Test that files initially exist
		$setFiles = $record->ManyManyFiles()->column('Title');
		$this->assertContains('File4', $setFiles);
		$this->assertContains('File5', $setFiles);
		$this->assertContains('nodelete.txt', $setFiles);
		$this->assertFileExists($file4->FullPath);
		$this->assertFileExists($file5->FullPath);
		$this->assertFileExists($fileNoDelete->FullPath);

		// Delete physical file and update record without file 4
		$response = $this->mockFileDelete('ManyManyFiles', $file4->ID);
		$this->assertFalse($response->isError());

		// Check file is removed from record
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertNotContains('File4', $record->ManyManyFiles()->column('Title'));
		$this->assertContains('File5', $record->ManyManyFiles()->column('Title'));

		// Check physical file is removed from filesystem
		$this->assertFileNotExists($file4->FullPath, 'File is also removed from filesystem');

		// Test record-based permissions
		$response = $this->mockFileDelete('ManyManyFiles', $fileNoDelete->ID);
		$this->assertEquals(403, $response->getStatusCode());

		// Test that folders can't be deleted
		$folder = $this->objFromFixture('Folder', 'folder1-subfolder1');
		$response = $this->mockFileDelete('ManyManyFiles', $folder->ID);
		$this->assertEquals(403, $response->getStatusCode());
	}

	/**
	 * Test control output html
	 */
	public function testView() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$file5 = $this->objFromFixture('File', 'file5');
		$fileNoView = $this->objFromFixture('File', 'file-noview');
		$fileNoEdit = $this->objFromFixture('File', 'file-noedit');
		$fileNoDelete = $this->objFromFixture('File', 'file-nodelete');

		$response = $this->get('UploadFieldTest_Controller');
		$this->assertFalse($response->isError());

		$parser = new CSSContentParser($response->getBody());
		$items = $parser->getBySelector(
			'#UploadFieldTestForm_Form_HasManyNoViewFiles_Holder .ss-uploadfield-files .ss-uploadfield-item'
		);
		$ids = array();
		foreach($items as $item) $ids[] = (int)$item['data-fileid'];

		$this->assertContains($file4->ID, $ids, 'Views related file');
		$this->assertContains($file5->ID, $ids, 'Views related file');
		$this->assertNotContains($fileNoView->ID, $ids, "Doesn't view files without view permissions");
		$this->assertContains($fileNoEdit->ID, $ids, "Views files without edit permissions");
		$this->assertContains($fileNoDelete->ID, $ids, "Views files without delete permissions");
	}

	public function testEdit() {
		$memberID = $this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$fileNoEdit = $this->objFromFixture('File', 'file-noedit');
		$folder = $this->objFromFixture('Folder', 'folder1-subfolder1');

		$response = $this->mockFileEditForm('ManyManyFiles', $file4->ID);
		$this->assertFalse($response->isError());

		$response = $this->mockFileEdit('ManyManyFiles', $file4->ID, array('Title' => 'File 4 modified'));
		$this->assertFalse($response->isError());

		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$file4 = DataObject::get_by_id($file4->class, $file4->ID, false);
		$this->assertEquals('File 4 modified', $file4->Title);

		// Test record-based permissions
		$response = $this->mockFileEditForm('ManyManyFiles', $fileNoEdit->ID);
		$this->assertEquals(403, $response->getStatusCode());

		$response = $this->mockFileEdit('ManyManyFiles', $fileNoEdit->ID, array());
		$this->assertEquals(403, $response->getStatusCode());

		// Test folder permissions
		$response = $this->mockFileEditForm('ManyManyFiles', $folder->ID);
		$this->assertEquals(403, $response->getStatusCode());

		$response = $this->mockFileEdit('ManyManyFiles', $folder->ID, array());
		$this->assertEquals(403, $response->getStatusCode());
	}

	public function testGetRecord() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$form = $this->getMockForm();

		$field = UploadField::create('MyField');
		$field->setForm($form);
		$this->assertNull($field->getRecord(), 'Returns no record by default');

		$field = UploadField::create('MyField');
		$field->setForm($form);
		$form->loadDataFrom($record);
		$this->assertEquals($record, $field->getRecord(), 'Returns record from form if available');

		$field = UploadField::create('MyField');
		$field->setForm($form);
		$field->setRecord($record);
		$this->assertEquals($record, $field->getRecord(), 'Returns record when set explicitly');
	}

	public function testSetItems() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$items = new ArrayList(array(
			$this->objFromFixture('File', 'file1'),
			$this->objFromFixture('File', 'file2')
		));

		// Field with no record attached
		$field = UploadField::create('DummyField');
		$field->setItems($items);
		$this->assertEquals(array('File1', 'File2'), $field->getItems()->column('Title'));

		// Anonymous field
		$field = UploadField::create('MyField');
		$field->setRecord($record);
		$field->setItems($items);
		$this->assertEquals(array('File1', 'File2'), $field->getItems()->column('Title'));

		// Field with has_one auto-detected
		$field = UploadField::create('HasOneFile');
		$field->setRecord($record);
		$field->setItems($items);
		$this->assertEquals(array('File1', 'File2'), $field->getItems()->column('Title'),
			'Allows overwriting of items even when relationship is detected'
		);
	}

	public function testGetItems() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');

		// Anonymous field
		$field = UploadField::create('MyField');
		$field->setValue(null, $record);
		$this->assertEquals(array(), $field->getItems()->column('Title'));

		// Field with has_one auto-detected
		$field = UploadField::create('HasOneFile');
		$field->setValue(null, $record);
		$this->assertEquals(array('File1'), $field->getItems()->column('Title'));

		// Field with has_many auto-detected
		$field = UploadField::create('HasManyFiles');
		$field->setValue(null, $record);
		$this->assertEquals(array('File2', 'File3'), $field->getItems()->column('Title'));

		// Field with many_many auto-detected
		$field = UploadField::create('ManyManyFiles');
		$field->setValue(null, $record);
		$this->assertNotContains('File1',$field->getItems()->column('Title'));
		$this->assertNotContains('File2',$field->getItems()->column('Title'));
		$this->assertNotContains('File3',$field->getItems()->column('Title'));
		$this->assertContains('File4',$field->getItems()->column('Title'));
		$this->assertContains('File5',$field->getItems()->column('Title'));
	}

	public function testReadonly() {
		$this->loginWithPermission('ADMIN');

		$response = $this->get('UploadFieldTest_Controller');
		$this->assertFalse($response->isError());

		$parser = new CSSContentParser($response->getBody());

		$this->assertFalse(
			(bool)$parser->getBySelector(
				'#UploadFieldTestForm_Form_ReadonlyField .ss-uploadfield-files .ss-uploadfield-item .ss-ui-button'
				),
			'Removes all buttons on items');
		$this->assertFalse(
			(bool)$parser->getBySelector('#UploadFieldTestForm_Form_ReadonlyField .ss-uploadfield-dropzone'),
			'Removes dropzone'
		);
		$this->assertFalse(
			(bool)$parser->getBySelector(
				'#UploadFieldTestForm_Form_ReadonlyField .ss-uploadfield-addfile'
			),
			'Entire "add" area'
		);
	}

	public function testDisabled() {
		$this->loginWithPermission('ADMIN');

		$response = $this->get('UploadFieldTest_Controller');
		$this->assertFalse($response->isError());

		$parser = new CSSContentParser($response->getBody());
		$this->assertFalse(
			(bool)$parser->getBySelector(
				'#UploadFieldTestForm_Form_DisabledField .ss-uploadfield-files .ss-uploadfield-item .ss-ui-button'
			),
			'Removes all buttons on items');
		$this->assertFalse((bool)$parser->getBySelector(
			'#UploadFieldTestForm_Form_DisabledField .ss-uploadfield-dropzone'
			),
			'Removes dropzone');
		$this->assertFalse(
			(bool)$parser->getBySelector('#UploadFieldTestForm_Form_DisabledField .ss-uploadfield-addfile'),
			'Entire "add" area'
		);
	}

	public function testCanUpload() {
		$this->loginWithPermission('ADMIN');
		$response = $this->get('UploadFieldTest_Controller');
		$this->assertFalse($response->isError());

		$parser = new CSSContentParser($response->getBody());
		$this->assertFalse(
			(bool)$parser->getBySelector(
				'#UploadFieldTestForm_Form_CanUploadFalseField_Holder .ss-uploadfield-dropzone'
			),
			'Removes dropzone');
		$this->assertTrue(
			(bool)$parser->getBySelector(
				'#UploadFieldTestForm_Form_CanUploadFalseField_Holder .ss-uploadfield-fromfiles'
			),
			'Keeps "From files" button'
		);
	}

	public function testCanUploadWithPermissionCode() {
		$field = UploadField::create('MyField');

		$field->setCanUpload(true);
		$this->assertTrue($field->canUpload());

		$field->setCanUpload(false);
		$this->assertFalse($field->canUpload());

		$this->loginWithPermission('ADMIN');

		$field->setCanUpload(false);
		$this->assertFalse($field->canUpload());

		$field->setCanUpload('ADMIN');
		$this->assertTrue($field->canUpload());
	}

	public function testCanAttachExisting() {
		$this->loginWithPermission('ADMIN');
		$response = $this->get('UploadFieldTest_Controller');
		$this->assertFalse($response->isError());

		$parser = new CSSContentParser($response->getBody());
		$this->assertTrue(
			(bool)$parser->getBySelector(
				'#UploadFieldTestForm_Form_CanAttachExistingFalseField_Holder .ss-uploadfield-fromcomputer-fileinput'
			),
			'Keeps input file control'
		);
		$this->assertFalse(
			(bool)$parser->getBySelector(
				'#UploadFieldTestForm_Form_CanAttachExistingFalseField_Holder .ss-uploadfield-fromfiles'
			),
			'Removes "From files" button'
		);

		// Test requests to select files have the correct given permission
		$response2 = $this->get('UploadFieldTest_Controller/Form/field/CanAttachExistingFalseField/select');
		$this->assertEquals(403, $response2->getStatusCode());
		$response3 = $this->get('UploadFieldTest_Controller/Form/field/HasOneFile/select');
		$this->assertEquals(200, $response3->getStatusCode());
	}

	public function testSelect() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$fileSubfolder = $this->objFromFixture('File', 'file-subfolder');

		$response = $this->get('UploadFieldTest_Controller/Form/field/ManyManyFiles/select/');
		$this->assertFalse($response->isError());

		// A bit too much coupling with GridField, but a full template overload would make things too complex
		$parser = new CSSContentParser($response->getBody());
		$items = $parser->getBySelector('.ss-gridfield-item');
		$itemIDs = array_map(create_function('$el', 'return (int)$el["data-id"];'), $items);
		$this->assertContains($file4->ID, $itemIDs, 'Contains file in assigned folder');
		$this->assertContains($fileSubfolder->ID, $itemIDs, 'Contains file in subfolder');
	}

	public function testSelectWithDisplayFolderName() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$fileSubfolder = $this->objFromFixture('File', 'file-subfolder');

		$response = $this->get('UploadFieldTest_Controller/Form/field/HasManyDisplayFolder/select/');
		$this->assertFalse($response->isError());

		// A bit too much coupling with GridField, but a full template overload would make things too complex
		$parser = new CSSContentParser($response->getBody());
		$items = $parser->getBySelector('.ss-gridfield-item');
		$itemIDs = array_map(create_function('$el', 'return (int)$el["data-id"];'), $items);
		$this->assertContains($file4->ID, $itemIDs, 'Contains file in assigned folder');
		$this->assertNotContains($fileSubfolder->ID, $itemIDs, 'Does not contain file in subfolder');
	}

	/**
	 * Test that UploadField:overwriteWarning cannot overwrite Upload:replaceFile
	 */
	public function testConfigOverwriteWarningCannotRelaceFiles() {
		$this->loginWithPermission('ADMIN');

		Upload::config()->replaceFile = false;
		UploadField::config()->defaultConfig = array_merge(
			UploadField::config()->defaultConfig, array('overwriteWarning' => true)
		);

		$tmpFileName = 'testUploadBasic.txt';
		$response = $this->mockFileUpload('NoRelationField', $tmpFileName);
		$this->assertFalse($response->isError());
		$responseData = Convert::json2array($response->getBody());
		$this->assertFileExists(ASSETS_PATH . '/UploadFieldTest/' . $responseData[0]['name']);
		$uploadedFile = DataObject::get_by_id('File', (int) $responseData[0]['id']);
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');

		$tmpFileName = 'testUploadBasic.txt';
		$response = $this->mockFileUpload('NoRelationField', $tmpFileName);
		$this->assertFalse($response->isError());
		$responseData = Convert::json2array($response->getBody());
		$this->assertFileExists(ASSETS_PATH . '/UploadFieldTest/' . $responseData[0]['name']);
		$uploadedFile2 = DataObject::get_by_id('File', (int) $responseData[0]['id']);
		$this->assertTrue(is_object($uploadedFile2), 'The file object is created');
		$this->assertTrue(
			$uploadedFile->Filename !== $uploadedFile2->Filename,
			'Filename is not the same'
		);
		$this->assertTrue(
			$uploadedFile->ID !== $uploadedFile2->ID,
			'File database record is not the same'
		);

		$uploadedFile->delete();
		$uploadedFile2->delete();
	}

	/**
	 * Tests that UploadField::fileexist works
	 */
	public function testFileExists() {
		$this->loginWithPermission('ADMIN');

		// Check that fileexist works on subfolders
		$nonFile = uniqid().'.txt';
		$responseEmpty = $this->mockFileExists('NoRelationField', $nonFile);
		$responseEmptyData = json_decode($responseEmpty->getBody());
		$this->assertFalse($responseEmpty->isError());
		$this->assertFalse($responseEmptyData->exists);

		// Check that filexists works on root folder
		$responseRoot = $this->mockFileExists('RootFolderTest', $nonFile);
		$responseRootData = json_decode($responseRoot->getBody());
		$this->assertFalse($responseRoot->isError());
		$this->assertFalse($responseRootData->exists);

		// Check that uploaded files can be detected in the root
		$tmpFileName = 'testUploadBasic.txt';
		$response = $this->mockFileUpload('RootFolderTest', $tmpFileName);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/$tmpFileName");
		$responseExists = $this->mockFileExists('RootFolderTest', $tmpFileName);
		$responseExistsData = json_decode($responseExists->getBody());
		$this->assertFalse($responseExists->isError());
		$this->assertTrue($responseExistsData->exists);

		// Check that uploaded files can be detected
		$response = $this->mockFileUpload('NoRelationField', $tmpFileName);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$responseExists = $this->mockFileExists('NoRelationField', $tmpFileName);
		$responseExistsData = json_decode($responseExists->getBody());
		$this->assertFalse($responseExists->isError());
		$this->assertTrue($responseExistsData->exists);

		// Test that files with invalid characters are rewritten safely and both report exists
		// Check that uploaded files can be detected in the root
		$tmpFileName = '_test___Upload___Bad.txt';
		$tmpFileNameExpected = 'test-Upload-Bad.txt';
		$response = $this->mockFileUpload('NoRelationField', $tmpFileName);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileNameExpected");
		// With original file
		$responseExists = $this->mockFileExists('NoRelationField', $tmpFileName);
		$responseExistsData = json_decode($responseExists->getBody());
		$this->assertFalse($responseExists->isError());
		$this->assertTrue($responseExistsData->exists);
		// With rewritten file
		$responseExists = $this->mockFileExists('NoRelationField', $tmpFileNameExpected);
		$responseExistsData = json_decode($responseExists->getBody());
		$this->assertFalse($responseExists->isError());
		$this->assertTrue($responseExistsData->exists);

		// Test that attempts to navigate outside of the directory return false
		$responseExists = $this->mockFileExists('NoRelationField', "../../../../var/private/$tmpFileName");
		$responseExistsData = json_decode($responseExists->getBody());
		$this->assertTrue($responseExists->isError());
		$this->assertContains('File is not a valid upload', $responseExists->getBody());
	}

	protected function getMockForm() {
		return new Form(new Controller(), 'Form', new FieldList(), new FieldList());
	}

	/**
	 * @return Array Emulating an entry in the $_FILES superglobal
	 */
	protected function getUploadFile($tmpFileName = 'UploadFieldTest-testUpload.txt') {
		$tmpFilePath = TEMP_FOLDER . '/' . $tmpFileName;
		$tmpFileContent = '';
		for($i=0; $i<10000; $i++) $tmpFileContent .= '0';
		file_put_contents($tmpFilePath, $tmpFileContent);

		// emulates the $_FILES array
		return array(
			'name' => array('Uploads' => array($tmpFileName)),
			'type' => array('Uploads' => array('text/plaintext')),
			'size' => array('Uploads' => array(filesize($tmpFilePath))),
			'tmp_name' => array('Uploads' => array($tmpFilePath)),
			'error' => array('Uploads' => array(UPLOAD_ERR_OK)),
		);
	}

	/**
	 * Simulates a form post to the test controller with the specified file IDs
	 *
	 * @param string $fileField Name of field to assign ids to
	 * @param array $ids list of file IDs
	 * @return boolean Array with key 'errors'
	 */
	protected function mockUploadFileIDs($fileField, $ids) {

		// collate file ids
		$files = array();
		foreach($ids as $id) {
			$files[$id] = $id;
		}

		$data = array(
			'action_submit' => 1
		);
		if($files) {
			// Normal post requests can't submit empty array values for fields
			$data[$fileField] = array('Files' => $files);
		}

		$form = new UploadFieldTestForm();
		$form->loadDataFrom($data, true);
		if($form->validate()) {
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
	 * @return SS_HTTPResponse form response
	 */
	protected function mockFileUpload($fileField, $tmpFileName) {
		$upload = $this->getUploadFile($tmpFileName);
		$_FILES = array($fileField => $upload);
		return $this->post(
			"UploadFieldTest_Controller/Form/field/{$fileField}/upload",
			array($fileField => $upload)
		);
	}

	protected function mockFileExists($fileField, $fileName) {
		return $this->get(
			"UploadFieldTest_Controller/Form/field/{$fileField}/fileexists?filename=".urlencode($fileName)
		);
	}

	/**
	 * Gets the edit form for the given file
	 *
	 * @param string $fileField Name of the field
	 * @param integer $fileID ID of the file to delete
	 * @return SS_HTTPResponse form response
	 */
	protected function mockFileEditForm($fileField, $fileID) {
		return $this->get(
			"UploadFieldTest_Controller/Form/field/{$fileField}/item/{$fileID}/edit"
		);
	}

	/**
	 * Mocks edit submissions to a file
	 *
	 * @param string $fileField Name of the field
	 * @param integer $fileID ID of the file to delete
	 * @param array $fields Fields to update
	 * @return SS_HTTPResponse form response
	 */
	protected function mockFileEdit($fileField, $fileID, $fields = array()) {
		return $this->post(
			"UploadFieldTest_Controller/Form/field/{$fileField}/item/{$fileID}/EditForm",
			$fields
		);
	}

	/**
	 * Simulates a physical file deletion
	 *
	 * @param string $fileField Name of the field
	 * @param integer $fileID ID of the file to delete
	 * @return SS_HTTPResponse form response
	 */
	protected function mockFileDelete($fileField, $fileID) {
		return $this->post(
			"UploadFieldTest_Controller/Form/field/{$fileField}/item/{$fileID}/delete",
			array()
		);
	}

	public function setUp() {
		Config::inst()->update('File', 'update_filesystem', false);
		parent::setUp();

		if(!file_exists(ASSETS_PATH)) mkdir(ASSETS_PATH);

		/* Create a test folders for each of the fixture references */
		$folders = Folder::get()->byIDs($this->allFixtureIDs('Folder'));
		foreach($folders as $folder) {
			if(!file_exists($folder->getFullPath())) mkdir($folder->getFullPath());
		}

		/* Create a test files for each of the fixture references */
		$files = File::get()->byIDs($this->allFixtureIDs('File'));
		foreach($files as $file) {
			$fh = fopen($file->getFullPath(), "w");
			fwrite($fh, str_repeat('x',1000000));
			fclose($fh);
		}
	}

	public function tearDown() {
		parent::tearDown();

		/* Remove the test files that we've created */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			if($file && file_exists(BASE_PATH."/$file->Filename")) unlink(BASE_PATH."/$file->Filename");
		}

		/* Remove the test folders that we've crated */
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			if($folder && file_exists(BASE_PATH."/$folder->Filename")) {
				Filesystem::removeFolder(BASE_PATH."/$folder->Filename");
			}
		}

		// Remove left over folders and any files that may exist
		if(file_exists(ASSETS_PATH.'/UploadFieldTest')) {
			Filesystem::removeFolder(ASSETS_PATH.'/UploadFieldTest');
		}

		// Remove file uploaded to root folder
		if(file_exists(ASSETS_PATH.'/testUploadBasic.txt')) {
			unlink(ASSETS_PATH.'/testUploadBasic.txt');
		}
	}

}

class UploadFieldTest_Record extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Text',
	);

	private static $has_one = array(
		'HasOneFile' => 'File',
		'HasOneFileMaxOne' => 'File',
		'HasOneFileMaxTwo' => 'File',
		'HasOneExtendedFile' => 'UploadFieldTest_ExtendedFile'
	);

	private static $has_many = array(
		'HasManyFiles' => 'File.HasManyRecord',
		'HasManyFilesMaxTwo' => 'File.HasManyMaxTwoRecord',
		'HasManyNoViewFiles' => 'File.HasManyNoViewRecord',
		'ReadonlyField' => 'File.ReadonlyRecord'
	);

	private static $many_many = array(
		'ManyManyFiles' => 'File'
	);

}

class UploadFieldTest_FileExtension extends DataExtension implements TestOnly {

	private static $has_one = array(
		'HasManyRecord' => 'UploadFieldTest_Record',
		'HasManyMaxTwoRecord' => 'UploadFieldTest_Record',
		'HasManyNoViewRecord' => 'UploadFieldTest_Record',
		'ReadonlyRecord' => 'UploadFieldTest_Record'
	);

	private static $has_many = array(
		'HasOneRecords' => 'UploadFieldTest_Record.HasOneFile',
		'HasOneMaxOneRecords' => 'UploadFieldTest_Record.HasOneFileMaxOne',
		'HasOneMaxTwoRecords' => 'UploadFieldTest_Record.HasOneFileMaxTwo',
	);

	private static $belongs_many_many = array(
		'ManyManyRecords' => 'UploadFieldTest_Record'
	);

	public function canDelete($member = null) {
		if($this->owner->Name == 'nodelete.txt') return false;
	}

	public function canEdit($member = null) {
		if($this->owner->Name == 'noedit.txt') return false;
	}

	public function canView($member = null) {
		if($this->owner->Name == 'noview.txt') return false;
	}
}

/**
 * Used for testing the create-on-upload
 */
class UploadFieldTest_ExtendedFile extends File implements TestOnly {

	private static $has_many = array(
		'HasOneExtendedRecords' => 'UploadFieldTest_Record.HasOneExtendedFile'
	);
}

class UploadFieldTestForm extends Form implements TestOnly {

	public function getRecord() {
		if(empty($this->record)) {
			$this->record = DataObject::get_one('UploadFieldTest_Record', '"Title" = \'Record 1\'');
		}
		return $this->record;
	}

	function __construct($controller = null, $name = 'Form') {
		if(empty($controller)) {
			$controller = new UploadFieldTest_Controller();
		}

		$fieldRootFolder = UploadField::create('RootFolderTest')
			->setFolderName('/');

		$fieldNoRelation = UploadField::create('NoRelationField')
			->setFolderName('UploadFieldTest');

		$fieldHasOne = UploadField::create('HasOneFile')
			->setFolderName('UploadFieldTest');

		$fieldHasOneExtendedFile = UploadField::create('HasOneExtendedFile')
			->setFolderName('UploadFieldTest');

		$fieldHasOneMaxOne = UploadField::create('HasOneFileMaxOne')
			->setFolderName('UploadFieldTest')
			->setAllowedMaxFileNumber(1);

		$fieldHasOneMaxTwo = UploadField::create('HasOneFileMaxTwo')
			->setFolderName('UploadFieldTest')
			->setAllowedMaxFileNumber(2);

		$fieldHasMany = UploadField::create('HasManyFiles')
			->setFolderName('UploadFieldTest');

		$fieldHasManyMaxTwo = UploadField::create('HasManyFilesMaxTwo')
			->setFolderName('UploadFieldTest')
			->setAllowedMaxFileNumber(2);

		$fieldManyMany = UploadField::create('ManyManyFiles')
			->setFolderName('UploadFieldTest');

		$fieldHasManyNoView = UploadField::create('HasManyNoViewFiles')
			->setFolderName('UploadFieldTest');

		$fieldHasManyDisplayFolder = UploadField::create('HasManyDisplayFolder')
			->setFolderName('UploadFieldTest')
			->setDisplayFolderName('UploadFieldTest');

		$fieldReadonly = UploadField::create('ReadonlyField')
			->setFolderName('UploadFieldTest')
			->performReadonlyTransformation();

		$fieldDisabled = UploadField::create('DisabledField')
			->setFolderName('UploadFieldTest')
			->performDisabledTransformation();

		$fieldSubfolder = UploadField::create('SubfolderField')
			->setFolderName('UploadFieldTest/subfolder1');

		$fieldCanUploadFalse = UploadField::create('CanUploadFalseField')
			->setCanUpload(false);

		$fieldCanAttachExisting = UploadField::create('CanAttachExistingFalseField')
			->setCanAttachExisting(false);

		$fieldAllowedExtensions = new UploadField('AllowedExtensionsField');
		$fieldAllowedExtensions->getValidator()->setAllowedExtensions(array('txt'));

		$fieldInvalidAllowedExtensions = new UploadField('InvalidAllowedExtensionsField');
		$fieldInvalidAllowedExtensions->getValidator()->setAllowedExtensions(array('txt', 'php'));

		$fields = new FieldList(
			$fieldRootFolder,
			$fieldNoRelation,
			$fieldHasOne,
			$fieldHasOneMaxOne,
			$fieldHasOneMaxTwo,
			$fieldHasOneExtendedFile,
			$fieldHasMany,
			$fieldHasManyMaxTwo,
			$fieldManyMany,
			$fieldHasManyNoView,
			$fieldHasManyDisplayFolder,
			$fieldReadonly,
			$fieldDisabled,
			$fieldSubfolder,
			$fieldCanUploadFalse,
			$fieldCanAttachExisting,
			$fieldAllowedExtensions,
			$fieldInvalidAllowedExtensions
		);
		$actions = new FieldList(
			new FormAction('submit')
		);
		$validator = new RequiredFields();

		parent::__construct($controller, $name, $fields, $actions, $validator);

		$this->loadDataFrom($this->getRecord());
	}

	public function submit($data, Form $form) {
		$record = $this->getRecord();
		$form->saveInto($record);
		$record->write();
		return json_encode($record->toMap());
	}
}


class UploadFieldTest_Controller extends Controller implements TestOnly {

	protected $template = 'BlankPage';

	private static $allowed_actions = array('Form', 'index', 'submit');

	public function Form() {
		return new UploadFieldTestForm($this, 'Form');
	}
}
