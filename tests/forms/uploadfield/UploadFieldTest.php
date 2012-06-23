<?php
/**
 * @package framework
 * @subpackage tests
 */

 class UploadFieldTest extends FunctionalTest {

	static $fixture_file = 'UploadFieldTest.yml';

	protected $extraDataObjects = array('UploadFieldTest_Record');

	protected $requiredExtensions = array(
		'File' => array('UploadFieldTest_FileExtension')
	);

	function testUploadNoRelation() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');

		$tmpFileName = 'testUploadBasic.txt';
		$_FILES = array('NoRelationField' => $this->getUploadFile($tmpFileName));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/NoRelationField/upload',
			array('NoRelationField' => $this->getUploadFile($tmpFileName))
		);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('File', sprintf('"Name" = \'%s\'', $tmpFileName));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');
	}

	function testUploadHasOneRelation() {
		$this->loginWithPermission('ADMIN');

		// Unset existing has_one relation before re-uploading
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$record->HasOneFileID = null;
		$record->write();

		$tmpFileName = 'testUploadHasOneRelation.txt';
		$_FILES = array('HasOneFile' => $this->getUploadFile($tmpFileName));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasOneFile/upload',
			array('HasOneFile' => $this->getUploadFile($tmpFileName))
		);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('File', sprintf('"Name" = \'%s\'', $tmpFileName));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');

		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertTrue($record->HasOneFile()->exists());
		$this->assertEquals($record->HasOneFile()->Name, $tmpFileName);
	}

	function testUploadHasOneRelationWithExtendedFile() {
		$this->loginWithPermission('ADMIN');

		// Unset existing has_one relation before re-uploading
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$record->HasOneExtendedFileID = null;
		$record->write();

		$tmpFileName = 'testUploadHasOneRelationWithExtendedFile.txt';
		$_FILES = array('HasOneExtendedFile' => $this->getUploadFile($tmpFileName));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasOneExtendedFile/upload',
			array('HasOneExtendedFile' => $this->getUploadFile($tmpFileName))
		);
		$this->assertFalse($response->isError());

		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('UploadFieldTest_ExtendedFile', sprintf('"Name" = \'%s\'', $tmpFileName));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');

		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertTrue($record->HasOneExtendedFile()->exists(), 'The extended file is attached to the class');
		$this->assertEquals($record->HasOneExtendedFile()->Name, $tmpFileName, 'Proper file has been attached');
	}

	function testUploadHasManyRelation() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');

		$tmpFileName = 'testUploadHasManyRelation.txt';
		$_FILES = array('HasManyFiles' => $this->getUploadFile($tmpFileName));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasManyFiles/upload',
			array('HasManyFiles' => $this->getUploadFile($tmpFileName))
		);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('File', sprintf('"Name" = \'%s\'', $tmpFileName));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');

		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertEquals(3, $record->HasManyFiles()->Count());
		$this->assertEquals($record->HasManyFiles()->Last()->Name, $tmpFileName);
	}

	function testUploadManyManyRelation() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$relationCount = $record->ManyManyFiles()->Count();

		$tmpFileName = 'testUploadManyManyRelation.txt';
		$_FILES = array('ManyManyFiles' => $this->getUploadFile($tmpFileName));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/ManyManyFiles/upload',
			array('ManyManyFiles' => $this->getUploadFile($tmpFileName))
		);
		$this->assertFalse($response->isError());
		$this->assertFileExists(ASSETS_PATH . "/UploadFieldTest/$tmpFileName");
		$uploadedFile = DataObject::get_one('File', sprintf('"Name" = \'%s\'', $tmpFileName));
		$this->assertTrue(is_object($uploadedFile), 'The file object is created');

		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertEquals($relationCount+1, $record->ManyManyFiles()->Count());
		$this->assertEquals($record->ManyManyFiles()->Last()->Name, $tmpFileName);
	}

	function testAllowedMaxFileNumberWithHasOne() {
		$this->loginWithPermission('ADMIN');
		
		// Test each of the three cases - has one with no max filel limit, has one with a limit of
		// one, has one with a limit of more than one (makes no sense, but should test it anyway).
		// Each of them should function in the same way - attaching the first file should work, the
		// second should cause an error.
		foreach (array('HasOneFile', 'HasOneFileMaxOne', 'HasOneFileMaxTwo') as $recordName) {
			// Unset existing has_one relation before re-uploading
			$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
			$record->{$recordName . 'ID'} = null;
			$record->write();

			$tmpFileName = 'testUploadHasOneRelation.txt';
			$_FILES = array($recordName => $this->getUploadFile($tmpFileName));
			$response = $this->post(
				"UploadFieldTest_Controller/Form/field/$recordName/upload",
				array($recordName => $this->getUploadFile($tmpFileName))
			);
			$body = json_decode($response->getBody());
			$this->assertEquals(0, $body[0]->error);
		
			// Write to it again, should result in an error.
			$response = $this->post(
				"UploadFieldTest_Controller/Form/field/$recordName/upload",
				array($recordName => $this->getUploadFile($tmpFileName))
			);
			$body = json_decode($response->getBody());
			$this->assertNotEquals(0, $body[0]->error);
		}
	}

	function testAllowedMaxFileNumberWithHasMany() {
		$this->loginWithPermission('ADMIN');
		
		// The 'HasManyFilesMaxTwo' field has a maximum of two files able to be attached to it.
		// We want to add files to it until we attempt to add the third. We expect that the first
		// two should work and the third will fail.
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$record->HasManyFilesMaxTwo()->removeAll();
		
		$tmpFileName = 'testUploadHasManyRelation.txt';
		$_FILES = array('HasManyFilesMaxTwo' => $this->getUploadFile($tmpFileName));

		// Write the first element, should be okay.
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasManyFilesMaxTwo/upload',
			array('HasManyFilesMaxTwo' => $this->getUploadFile($tmpFileName))
		);
		$body = json_decode($response->getBody());
		$this->assertEquals(0, $body[0]->error);

		// Write the second element, should be okay.
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasManyFilesMaxTwo/upload',
			array('HasManyFilesMaxTwo' => $this->getUploadFile($tmpFileName))
		);
		$body = json_decode($response->getBody());
		$this->assertEquals(0, $body[0]->error);

		// Write the third element, should result in error.
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasManyFilesMaxTwo/upload',
			array('HasManyFilesMaxTwo' => $this->getUploadFile($tmpFileName))
		);
		$body = json_decode($response->getBody());
		$this->assertNotEquals(0, $body[0]->error);
	}

	function testRemoveFromHasOne() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file1 = $this->objFromFixture('File', 'file1');

		$this->assertTrue($record->HasOneFile()->exists());
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasOneFile/item/' . $file1->ID . '/remove',
			array()
		);
		$this->assertFalse($response->isError());
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertFalse($record->HasOneFile()->exists());
		$this->assertFileExists($file1->FullPath, 'File is only detached, not deleted from filesystem');
	}

	function testRemoveFromHasMany() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file2 = $this->objFromFixture('File', 'file2');
		$file3 = $this->objFromFixture('File', 'file3');

		$this->assertEquals(array('File2', 'File3'), $record->HasManyFiles()->column('Title'));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasManyFiles/item/' . $file2->ID . '/remove',
			array()
		);
		$this->assertFalse($response->isError());
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertEquals(array('File3'), $record->HasManyFiles()->column('Title'));
		$this->assertFileExists($file3->FullPath, 'File is only detached, not deleted from filesystem');
	}

	function testRemoveFromManyMany() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$file5 = $this->objFromFixture('File', 'file5');

		$this->assertContains('File4', $record->ManyManyFiles()->column('Title'));
		$this->assertContains('File5', $record->ManyManyFiles()->column('Title'));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/ManyManyFiles/item/' . $file4->ID . '/remove',
			array()
		);
		$this->assertFalse($response->isError());
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertNotContains('File4', $record->ManyManyFiles()->column('Title'));
		$this->assertContains('File5', $record->ManyManyFiles()->column('Title'));
		$this->assertFileExists($file4->FullPath, 'File is only detached, not deleted from filesystem');
	}

	function testDeleteFromHasOne() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file1 = $this->objFromFixture('File', 'file1');

		$this->assertTrue($record->HasOneFile()->exists());
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasOneFile/item/' . $file1->ID . '/delete',
			array()
		);
		$this->assertFalse($response->isError());
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertFalse($record->HasOneFile()->exists());
		$this->assertFileNotExists($file1->FullPath, 'File is also removed from filesystem');
	}

	function testDeleteFromHasMany() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file2 = $this->objFromFixture('File', 'file2');
		$file3 = $this->objFromFixture('File', 'file3');

		$this->assertEquals(array('File2', 'File3'), $record->HasManyFiles()->column('Title'));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasManyFiles/item/' . $file2->ID . '/delete',
			array()
		);
		$this->assertFalse($response->isError());
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertEquals(array('File3'), $record->HasManyFiles()->column('Title'));
		$this->assertFileNotExists($file2->FullPath, 'File is also removed from filesystem');

		$fileNotOnRelationship = $this->objFromFixture('File', 'file1');
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasManyFiles/item/' . $fileNotOnRelationship->ID . '/delete',
			array()
		);
		$this->assertEquals(403, $response->getStatusCode(), "Denies deleting files if they're not on the current relationship");
	}

	function testDeleteFromManyMany() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$file5 = $this->objFromFixture('File', 'file5');
		$fileNoDelete = $this->objFromFixture('File', 'file-nodelete');

		$this->assertContains('File4', $record->ManyManyFiles()->column('Title'));
		$this->assertContains('File5', $record->ManyManyFiles()->column('Title'));
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/ManyManyFiles/item/' . $file4->ID . '/delete',
			array()
		);
		$this->assertFalse($response->isError());
		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertNotContains('File4', $record->ManyManyFiles()->column('Title'));
		$this->assertContains('File5', $record->ManyManyFiles()->column('Title'));
		$this->assertFileNotExists($file4->FullPath, 'File is also removed from filesystem');

		// Test record-based permissions
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/ManyManyFiles/item/' . $fileNoDelete->ID . '/delete',
			array()
		);
		$this->assertEquals(403, $response->getStatusCode());
	}

	function testView() {
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
		$items = $parser->getBySelector('#ManyManyFiles .ss-uploadfield-files .ss-uploadfield-item');
		$ids = array();
		foreach($items as $item) $ids[] = (int)$item['data-fileid'];
		
		$this->assertContains($file4->ID, $ids, 'Views related file');
		$this->assertContains($file5->ID, $ids, 'Views related file');
		$this->assertNotContains($fileNoView->ID, $ids, "Doesn't view files without view permissions");
		$this->assertContains($fileNoEdit->ID, $ids, "Views files without edit permissions");
		$this->assertContains($fileNoDelete->ID, $ids, "Views files without delete permissions");
	}

	function testEdit() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$file5 = $this->objFromFixture('File', 'file5');
		$fileNoEdit = $this->objFromFixture('File', 'file-noedit');
		$baseUrl = 'UploadFieldTest_Controller/Form/field/ManyManyFiles/item/' . $file4->ID;

		$response = $this->get($baseUrl . '/edit');
		$this->assertFalse($response->isError());

		$response = $this->post($baseUrl . '/EditForm', array('Title' => 'File 4 modified'));
		$this->assertFalse($response->isError());

		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$file4 = DataObject::get_by_id($file4->class, $file4->ID, false);
		$this->assertEquals('File 4 modified', $file4->Title);

		// Test record-based permissions
		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/ManyManyFiles/item/' . $fileNoEdit->ID . '/edit',
			array()
		);
		$this->assertEquals(403, $response->getStatusCode());
	}

	function testGetRecord() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$form = $this->getMockForm();

		$field = new UploadField('MyField');
		$field->setForm($form);
		$this->assertNull($field->getRecord(), 'Returns no record by default');

		$field = new UploadField('MyField');
		$field->setForm($form);
		$form->loadDataFrom($record);
		$this->assertEquals($record, $field->getRecord(), 'Returns record from form if available');

		$field = new UploadField('MyField');
		$field->setForm($form);
		$field->setRecord($record);
		$this->assertEquals($record, $field->getRecord(), 'Returns record when set explicitly');
	}

	function testSetItems() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$form = $this->getMockForm();
		$items = new ArrayList(array(
			$this->objFromFixture('File', 'file1'),
			$this->objFromFixture('File', 'file2')
		));

		// Anonymous field
		$field = new UploadField('MyField');
		$field->setForm($form);
		$field->setRecord($record);
		$field->setItems($items);
		$this->assertEquals(array('File1', 'File2'), $field->getItems()->column('Title'));

		// Field with has_one auto-detected
		$field = new UploadField('HasOneFile');
		$field->setForm($form);
		$field->setRecord($record);
		$field->setItems($items);
		$this->assertEquals(array('File1', 'File2'), $field->getItems()->column('Title'),
			'Allows overwriting of items even when relationship is detected'
		);
	}

	function testGetItems() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$form = $this->getMockForm();

		// Anonymous field
		$field = new UploadField('MyField');
		$field->setForm($form);
		$field->setRecord($record);
		$this->assertEquals(array(), $field->getItems()->column('Title'));

		// Field with has_one auto-detected
		$field = new UploadField('HasOneFile');
		$field->setForm($form);
		$field->setRecord($record);
		$this->assertEquals(array('File1'), $field->getItems()->column('Title'));

		// Field with has_many auto-detected
		$field = new UploadField('HasManyFiles');
		$field->setForm($form);
		$field->setRecord($record);
		$this->assertEquals(array('File2', 'File3'), $field->getItems()->column('Title'));

		// Field with many_many auto-detected
		$field = new UploadField('ManyManyFiles');
		$field->setForm($form);
		$field->setRecord($record);
		$this->assertNotContains('File1',$field->getItems()->column('Title'));
		$this->assertNotContains('File2',$field->getItems()->column('Title'));
		$this->assertNotContains('File3',$field->getItems()->column('Title'));
		$this->assertContains('File4',$field->getItems()->column('Title'));
		$this->assertContains('File5',$field->getItems()->column('Title'));
	}

	function testReadonly() {
		$this->loginWithPermission('ADMIN');
		
		$response = $this->get('UploadFieldTest_Controller');
		$this->assertFalse($response->isError());

		$parser = new CSSContentParser($response->getBody());
		$this->assertFalse((bool)$parser->getBySelector('#ReadonlyField .ss-uploadfield-files .ss-uploadfield-item .ss-ui-button'), 'Removes all buttons on items');
		$this->assertFalse((bool)$parser->getBySelector('#ReadonlyField .ss-uploadfield-dropzone'), 'Removes dropzone');
		$this->assertFalse((bool)$parser->getBySelector('#ReadonlyField .ss-uploadfield-addfile .ss-ui-button'), 'Removes all buttons from "add" area');
	}

	function testDisabled() {
		$this->loginWithPermission('ADMIN');
		
		$response = $this->get('UploadFieldTest_Controller');
		$this->assertFalse($response->isError());

		$parser = new CSSContentParser($response->getBody());
		$this->assertFalse((bool)$parser->getBySelector('#DisabledField .ss-uploadfield-files .ss-uploadfield-item .ss-ui-button'), 'Removes all buttons on items');
		$this->assertFalse((bool)$parser->getBySelector('#DisabledField .ss-uploadfield-dropzone'), 'Removes dropzone');
		$this->assertFalse((bool)$parser->getBySelector('#DisabledField .ss-uploadfield-addfile .ss-ui-button'), 'Removes all buttons from "add" area');
		
	}

	function testIsSaveable() {
		$form = $this->getMockForm();

		$field = new UploadField('MyField');
		$this->assertTrue($field->isSaveable(), 'Field without relation is always marked as saveable');

		$field = new UploadField('HasOneFile');
		$this->assertTrue($field->isSaveable(), 'Field with has_one relation is saveable without record on form');

		$field = new UploadField('HasOneFile');
		$newRecord = new UploadFieldTest_Record();
		$form->loadDataFrom($newRecord);
		$field->setForm($form);
		$this->assertFalse($field->isSaveable(), 'Field with has_one relation not saveable with new record on form');

		$field = new UploadField('HasOneFile');
		$existingRecord = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$form->loadDataFrom($existingRecord);
		$field->setForm($form);
		$this->assertTrue($field->isSaveable(), 'Field with has_one relation saveable with saved record on form');
	}

	function testSelect() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file4 = $this->objFromFixture('File', 'file4');
		$file5 = $this->objFromFixture('File', 'file5');
		$fileSubfolder = $this->objFromFixture('File', 'file-subfolder');
		$fileNoEdit = $this->objFromFixture('File', 'file-noedit');

		$response = $this->get('UploadFieldTest_Controller/Form/field/ManyManyFiles/select/');
		$this->assertFalse($response->isError());

		// A bit too much coupling with GridField, but a full template overload would make things too complex
		$parser = new CSSContentParser($response->getBody());
		$items = $parser->getBySelector('.ss-gridfield-item');
		$itemIDs = array_map(create_function('$el', 'return (int)$el["data-id"];'), $items);
		$this->assertContains($file4->ID, $itemIDs, 'Contains file in assigned folder');
		$this->assertNotContains($fileSubfolder->ID, $itemIDs, 'Does not contain file in subfolder');
	}

	function testAttachHasOne() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file1 = $this->objFromFixture('File', 'file1');
		$file2 = $this->objFromFixture('File', 'file2');
		$file3AlreadyAttached = $this->objFromFixture('File', 'file3');

		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasOneFile/attach', 
			array('ids' => array($file1->ID/* first file should be ignored */, $file2->ID))
		);
		$this->assertFalse($response->isError());

		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertEquals($file2->ID, $record->HasOneFileID, 'Attaches new relations');
	}

	function testAttachHasMany() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file1 = $this->objFromFixture('File', 'file1');
		$file2 = $this->objFromFixture('File', 'file2');
		$file3AlreadyAttached = $this->objFromFixture('File', 'file3');

		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/HasManyFiles/attach', 
			array('ids' => array($file1->ID, $file2->ID))
		);
		$this->assertFalse($response->isError());

		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertContains($file1->ID, $record->HasManyFiles()->column('ID'), 'Attaches new relations');
		$this->assertContains($file2->ID, $record->HasManyFiles()->column('ID'), 'Attaches new relations');
		$this->assertContains($file3AlreadyAttached->ID, $record->HasManyFiles()->column('ID'), 'Does not detach existing relations');
	}

	function testAttachManyMany() {
		$this->loginWithPermission('ADMIN');

		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file1 = $this->objFromFixture('File', 'file1');
		$file2 = $this->objFromFixture('File', 'file2');
		$file5AlreadyAttached = $this->objFromFixture('File', 'file5');

		$response = $this->post(
			'UploadFieldTest_Controller/Form/field/ManyManyFiles/attach', 
			array('ids' => array($file1->ID, $file2->ID))
		);
		$this->assertFalse($response->isError());

		$record = DataObject::get_by_id($record->class, $record->ID, false);
		$this->assertContains($file1->ID, $record->ManyManyFiles()->column('ID'), 'Attaches new relations');
		$this->assertContains($file2->ID, $record->ManyManyFiles()->column('ID'), 'Attaches new relations');
		$this->assertContains($file5AlreadyAttached->ID, $record->ManyManyFiles()->column('ID'), 'Does not detach existing relations');
	}

	function testManagesRelation() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');

		$field = new UploadField('ManyManyFiles');
		$this->assertFalse($field->managesRelation(), 'False if no record is set');

		$field = new UploadField('NoRelationField');
		$field->setRecord($record);
		$this->assertFalse($field->managesRelation(), 'False if no relation found by name');

		$field = new UploadField('HasOneFile');
		$field->setRecord($record);
		$this->assertTrue($field->managesRelation(), 'True for has_one');

		$field = new UploadField('HasManyFiles');
		$field->setRecord($record);
		$this->assertTrue($field->managesRelation(), 'True for has_many');

		$field = new UploadField('ManyManyFiles');
		$field->setRecord($record);
		$this->assertTrue($field->managesRelation(), 'True for many_many');
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
			'name' => $tmpFileName,
			'type' => 'text/plaintext',
			'size' => filesize($tmpFilePath),
			'tmp_name' => $tmpFilePath,
			'extension' => 'txt',
			'error' => UPLOAD_ERR_OK,
		);
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
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			$fh = fopen(BASE_PATH."/$file->Filename", "w");
			fwrite($fh, str_repeat('x',1000000));
			fclose($fh);
		}
	}
	
	function tearDown() {
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
			if($folder && file_exists(BASE_PATH."/$folder->Filename")) Filesystem::removeFolder(BASE_PATH."/$folder->Filename");
		}

		// Remove left over folders and any files that may exist
		if(file_exists('../assets/UploadFieldTest')) Filesystem::removeFolder('../assets/UploadFieldTest');
	}

}

class UploadFieldTest_Record extends DataObject implements TestOnly {

	static $db = array(
		'Title' => 'Text',
	);

	static $has_one = array(
		'HasOneFile' => 'File',
		'HasOneFileMaxOne' => 'File',
		'HasOneFileMaxTwo' => 'File',
		'HasOneExtendedFile' => 'UploadFieldTest_ExtendedFile'
	);

	static $has_many = array(
		'HasManyFiles' => 'File',
		'HasManyFilesMaxTwo' => 'File',
	);

	static $many_many = array(
		'ManyManyFiles' => 'File',
	);

}

class UploadFieldTest_FileExtension extends DataExtension implements TestOnly {

	public static $has_one = array(
		'Record' => 'UploadFieldTest_Record'
	);

	function canDelete($member = null) {
		if($this->owner->Name == 'nodelete.txt') return false;
	}

	function canEdit($member = null) {
		if($this->owner->Name == 'noedit.txt') return false;
	}

	function canView($member = null) {
		if($this->owner->Name == 'noview.txt') return false;
	}
}

class UploadFieldTest_Controller extends Controller implements TestOnly {

	protected $template = 'BlankPage';

	function Form() {
		$record = DataObject::get_one('UploadFieldTest_Record', '"Title" = \'Record 1\'');

		$fieldNoRelation = new UploadField('NoRelationField');
		$fieldNoRelation->setFolderName('UploadFieldTest');
		$fieldNoRelation->setRecord($record);
		
		$fieldHasOne = new UploadField('HasOneFile');
		$fieldHasOne->setFolderName('UploadFieldTest');
		$fieldHasOne->setRecord($record);

		$fieldHasOneExtendedFile = new UploadField('HasOneExtendedFile');
		$fieldHasOneExtendedFile->setFolderName('UploadFieldTest');
		$fieldHasOneExtendedFile->setRecord($record);
		
		$fieldHasOneMaxOne = new UploadField('HasOneFileMaxOne');
		$fieldHasOneMaxOne->setFolderName('UploadFieldTest');
		$fieldHasOneMaxOne->setConfig('allowedMaxFileNumber', 1);
		$fieldHasOneMaxOne->setRecord($record);
		
		$fieldHasOneMaxTwo = new UploadField('HasOneFileMaxTwo');
		$fieldHasOneMaxTwo->setFolderName('UploadFieldTest');
		$fieldHasOneMaxTwo->setConfig('allowedMaxFileNumber', 2);
		$fieldHasOneMaxTwo->setRecord($record);
		
		$fieldHasMany = new UploadField('HasManyFiles');
		$fieldHasMany->setFolderName('UploadFieldTest');
		$fieldHasMany->setRecord($record);
		
		$fieldHasManyMaxTwo = new UploadField('HasManyFilesMaxTwo');
		$fieldHasManyMaxTwo->setFolderName('UploadFieldTest');
		$fieldHasManyMaxTwo->setConfig('allowedMaxFileNumber', 2);
		$fieldHasManyMaxTwo->setRecord($record);
		
		$fieldManyMany = new UploadField('ManyManyFiles');
		$fieldManyMany->setFolderName('UploadFieldTest');
		$fieldManyMany->setRecord($record);
		
		$fieldReadonly = new UploadField('ReadonlyField');
		$fieldReadonly->setFolderName('UploadFieldTest');
		$fieldReadonly->setRecord($record);
		$fieldReadonly = $fieldReadonly->performReadonlyTransformation();

		$fieldDisabled = new UploadField('DisabledField');
		$fieldDisabled->setFolderName('UploadFieldTest');
		$fieldDisabled->setRecord($record);
		$fieldDisabled = $fieldDisabled->performDisabledTransformation();

		$fieldSubfolder = new UploadField('SubfolderField');
		$fieldSubfolder->setFolderName('UploadFieldTest/subfolder1');
		$fieldSubfolder->setRecord($record);

		$form = new Form(
			$this,
			'Form',
			new FieldList(
				$fieldNoRelation,
				$fieldHasOne,
				$fieldHasOneMaxOne,
				$fieldHasOneMaxTwo,
				$fieldHasOneExtendedFile,
				$fieldHasMany,
				$fieldHasManyMaxTwo,
				$fieldManyMany,
				$fieldReadonly,
				$fieldDisabled,
				$fieldSubfolder
			),
			new FieldList(
				new FormAction('submit')
			),
			new RequiredFields(
				'NoRelationField',
				'HasOneFile',
				'HasOneFileMaxOne',
				'HasOneFileMaxTwo',
				'HasOneExtendedFile',
				'HasManyFiles',
				'HasManyFilesMaxTwo',
				'ManyManyFiles',
				'ReadonlyField',
				'DisabledField',
				'SubfolderField'
			)
		);
		return $form;
	}

	function submit($data, $form) {
		
	}

}

/**
 * Used for testing the create-on-upload
 */
class UploadFieldTest_ExtendedFile extends File implements TestOnly {

}
