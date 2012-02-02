<?php
/**
 * @package sapphire
 * @subpackage tests
 */

 class UploadFieldTest extends FunctionalTest {

	static $fixture_file = 'UploadFieldTest.yml';

	protected $extraDataObjects = array('UploadFieldTest_Record');

	protected $requiredExtensions = array(
		'File' => array('UploadFieldTest_FileExtension')
	);

	function testAllowedMaxFileNumber() {
		$this->markTestIncomplete();
	}

	function testRemoveFromHasOne() {
		$record = $this->objFromFixture('UploadFieldTest_Record', 'record1');
		$file1 = $this->objFromFixture('File', 'file1');
		$file1 = $this->objFromFixture('File', 'file1');

		// TODO
		// $response = $this->post(
		// 	'UploadFieldTest_Controller/ManyManyForm/fields/MyUploadField/item/' . $file1 . '/remove',
		// 	array()
		// );
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
		$this->assertEquals(array('File4', 'File5'), $field->getItems()->column('Title'));
	}

	protected function getMockForm() {
		return new Form(new Controller(), 'Form', new FieldList(), new FieldList());
	}

}

class UploadFieldTest_Record extends DataObject implements TestOnly {

static $db = array(
	'Title' => 'Text',
);

static $has_one = array(
	'HasOneFile' => 'File',
);

static $has_many = array(
	'HasManyFiles' => 'File',
);

static $many_many = array(
	'ManyManyFiles' => 'File',
);

}

class UploadFieldTest_FileExtension extends DataExtension implements TestOnly {
	function extraStatics() {
		return array(
			'has_one' => array('Record' => 'UploadFieldTest_Record')
 	);
	}
}

class UploadFieldTest_Controller extends Controller implements TestOnly {

	protected $template = 'BlankPage';

	function HasOneForm() {
		return $this->getMockForm('HasOneForm', 'HasOneFile');
	}

	function HasManyForm() {
		return $this->getMockForm('HasManyForm', 'HasManyFiles');
	}

	function ManyManyForm() {
		return $this->getMockForm('ManyManyForm', 'ManyManyFiles');
	}

	protected function getMockForm($formName, $uploadFieldName) {
		$uploadField = new UploadField($uploadFieldName);
		$uploadField->setRecord(DataObject::get_one('UploadFieldTest_Record', '"Title" = \'Record 1\''));
		$form = new Form(
			$this,
			$formName,
			new FieldList($uploadField),
			new FieldList(new FormAction('submit')),
			new RequiredFields($uploadFieldName)
		);
		return $form;
	}
}