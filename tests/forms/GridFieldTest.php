<?php
class GridFieldTest extends SapphireTest {

	/**
	 * @covers GridField::__construct
	 */
	public function testGridField() {
		$obj = new GridField('testfield', 'testfield');
		$this->assertTrue($obj instanceof GridField, 'Test that the constructor arguments are valid');
	}
	
	/**
	 * @covers GridField::__construct
	 * @covers GridField::getList
	 */
	public function testGridFieldSetList() {
		$list = ArrayList::create(array(1=>'hello', 2=>'goodbye'));
		$obj = new GridField('testfield', 'testfield', $list);
		$this->assertEquals($list, $obj->getList(), 'Testing getList');
	}

	/**
	 * @covers GridField::__construct
	 * @covers GridField::getConfig
	 * @covers GridField::setComponents
	 * @covers GridField::getDefaultConfig
	 */
	public function testGridFieldDefaultConfig() {
		$obj = new GridField('testfield', 'testfield');

		$expectedComponents = new ArrayList(array(
			new GridFieldTitle(),
			new GridFieldSortableHeader,
			new GridFieldFilter,
			new GridFieldDefaultColumns,
			new GridFieldPaginator,
			new GridState_Component,
		));
		
		$this->assertEquals($expectedComponents, $obj->getConfig()->getComponents(), 'Testing default Config');
	}

	/**
	 * @covers GridField::__construct
	 * @covers GridField::setComponents
	 */
	public function testGridFieldSetCustomConfig() {

		$config = GridFieldConfig::create();
		$config->addComponent(new GridFieldSortableHeader());
		$config->addComponent(new GridFieldDefaultColumns());

		$obj = new GridField('testfield', 'testfield', ArrayList::create(array()),$config);

		$expectedComponents = new ArrayList(array(
			0 => new GridFieldSortableHeader,
			1 => new GridFieldDefaultColumns,
			2 => new GridState_Component,
		));

		$this->assertEquals($expectedComponents, $obj->getConfig()->getComponents(), 'Testing default Config');
	}

	/**
	 * @covers GridField::getModelClass
	 * @covers GridField::setModelClass
	 */
	public function testGridFieldModelClass() {
		$obj = new GridField('testfield', 'testfield', DataList::create('Member'));
		$this->assertEquals('Member', $obj->getModelClass(), 'Should return Member');
		$obj->setModelClass('DataModel');
		$this->assertEquals('DataModel', $obj->getModelClass(), 'Should return Member');
	}

	/**
	 * @covers GridField::getModelClass
	 */
	public function testGridFieldModelClassThrowsException() {
		$this->setExpectedException('LogicException');
		$obj = new GridField('testfield', 'testfield', ArrayList::create());
		$obj->getModelClass();
	}

	/**
	 * @covers GridField::getDisplayFields
	 */
	public function testGridFieldGetDefaultDisplayFields() {
		$obj = new GridField('testfield', 'testfield', DataList::create('Member'));
		$expected = array(
			'FirstName' => 'First Name',
			'Surname' => 'Last Name',
			'Email' => 'Email',
		);
		$this->assertEquals($expected, $obj->getDisplayFields());
	}

	/**
	 * @covers GridField::setDisplayFields
	 * @covers GridField::getDisplayFields
	 */
	public function testGridFieldCustomDisplayFields() {
		$obj = new GridField('testfield', 'testfield', DataList::create('Member'));
		$expected = array('Email' => 'Email');
		$obj->setDisplayFields($expected);
		$this->assertEquals($expected, $obj->getDisplayFields());
	}

	/**
	 * @covers GridField::setDisplayFields
	 * @covers GridField::getDisplayFields
	 */
	public function testGridFieldDisplayFieldsWithBadArguments() {
		$this->setExpectedException('InvalidArgumentException');
		$obj = new GridField('testfield', 'testfield', DataList::create('Member'));
		$obj->setDisplayFields(new stdClass());
	}

	/**
	 * @covers GridField::setList
	 * @covers GridField::getList
	 */
	public function testSetAndGetList() {
		$list = DataList::create('Member');
		$arrayList = ArrayList::create(array(1,2,3));
		$obj = new GridField('testfield', 'testfield', $list);
		$this->assertEquals($list, $obj->getList());
		$obj->setList($arrayList);
		$this->assertEquals($arrayList, $obj->getList());
	}

	/**
	 * @covers GridField::getState
	 */
	public function testGetState() {
		$obj = new GridField('testfield', 'testfield');
		$this->assertTrue($obj->getState() instanceof GridState_Data);
		$this->assertTrue($obj->getState(false) instanceof GridState);
	}

	/**
	 * @covers GridField::getColumns
	 */
	public function testGetColumns(){
		$obj = new GridField('testfield', 'testfield', DataList::create('Member'));
		$expected = array (
			0 => 'FirstName',
			1 => 'Surname',
			2 => 'Email',
		);
		$this->assertEquals($expected, $obj->getColumns());
	}

	/**
	 * @covers GridField::getColumnCount
	 */
	public function testGetColumnCount() {
		$obj = new GridField('testfield', 'testfield', DataList::create('Member'));
		$this->assertEquals(3, $obj->getColumnCount());
	}

	/**
	 * @covers GridField::getColumnContent
	 */
	public function testGetColumnContent() {
		$list = new ArrayList(array(
			new Member(array("ID" => 1, "Email" => "test@example.org" ))
		));
		$obj = new GridField('testfield', 'testfield', $list);
		$this->assertEquals('test@example.org', $obj->getColumnContent($list->first(), 'Email'));
	}

	/**
	 * @covers GridField::getColumnContent
	 */
	public function testGetColumnContentBadArguments() {
		$this->setExpectedException('InvalidArgumentException');
		$list = new ArrayList(array(
			new Member(array("ID" => 1, "Email" => "test@example.org" ))
		));
		$obj = new GridField('testfield', 'testfield', $list);
		$obj->getColumnContent($list->first(), 'non-existing');
	}

	/**
	 * @covers GridField::getColumnAttributes
	 */
	public function testGetColumnAttributesEmptyArray() {
		$list = new ArrayList(array(
			new Member(array("ID" => 1, "Email" => "test@example.org" ))
		));
		$obj = new GridField('testfield', 'testfield', $list);
		$this->assertEquals(array('class' => 'col-Email'), $obj->getColumnAttributes($list->first(), 'Email'));
	}

	/**
	 * @covers GridField::getColumnAttributes
	 */
	public function testGetColumnAttributes() {
		$list = new ArrayList(array(
			new Member(array("ID" => 1, "Email" => "test@example.org" ))
		));
		$config = GridFieldConfig::create()->addComponent(new GridFieldTest_Component);
		$obj = new GridField('testfield', 'testfield', $list, $config);
		$this->assertEquals(array('class'=>'css-class'), $obj->getColumnAttributes($list->first(), 'Email'));
	}

	/**
	 * @covers GridField::getColumnAttributes
	 */
	public function testGetColumnAttributesBadArguments() {
		$this->setExpectedException('InvalidArgumentException');
		$list = new ArrayList(array(
			new Member(array("ID" => 1, "Email" => "test@example.org" ))
		));
		$config = GridFieldConfig::create()->addComponent(new GridFieldTest_Component);
		$obj = new GridField('testfield', 'testfield', $list, $config);
		$obj->getColumnAttributes($list->first(), 'Non-existing');
	}

	public function testGetColumnAttributesBadResponseFromComponent() {
		$this->setExpectedException('LogicException');
		$list = new ArrayList(array(
			new Member(array("ID" => 1, "Email" => "test@example.org" ))
		));
		$config = GridFieldConfig::create()->addComponent(new GridFieldTest_Component);
		$obj = new GridField('testfield', 'testfield', $list, $config);
		$obj->getColumnAttributes($list->first(), 'Surname');
	}

	/**
	 * @covers GridField::getColumnMetadata
	 */
	public function testGetColumnMetadata() {
		$list = new ArrayList(array(
			new Member(array("ID" => 1, "Email" => "test@example.org" ))
		));
		$config = GridFieldConfig::create()->addComponent(new GridFieldTest_Component);
		$obj = new GridField('testfield', 'testfield', $list, $config);
		$this->assertEquals(array('metadata'=>'istrue'), $obj->getColumnMetadata('Email'));
	}

	/**
	 * @covers GridField::getColumnMetadata
	 */
	public function testGetColumnMetadataBadResponseFromComponent() {
		$this->setExpectedException('LogicException');
		$list = new ArrayList(array(
			new Member(array("ID" => 1, "Email" => "test@example.org" ))
		));
		$config = GridFieldConfig::create()->addComponent(new GridFieldTest_Component);
		$obj = new GridField('testfield', 'testfield', $list, $config);
		$obj->getColumnMetadata('Surname');
	}

	/**
	 * @covers GridField::getColumnMetadata
	 */
	public function testGetColumnMetadataBadArguments() {
		$this->setExpectedException('InvalidArgumentException');
		$list = ArrayList::create();
		$config = GridFieldConfig::create()->addComponent(new GridFieldTest_Component);
		$obj = new GridField('testfield', 'testfield', $list, $config);
		$obj->getColumnMetadata('non-exist-qweqweqwe');
	}

	/**
	 * @covers GridField::handleAction
	 */
	public function testHandleActionBadArgument() {
		$this->setExpectedException('InvalidArgumentException');
		$obj = new GridField('testfield', 'testfield');
		$obj->handleAction('prft', array(), array());
	}
	
	/**
	 * @covers GridField::handleAction
	 */
	public function testHandleAction() {
		$config = GridFieldConfig::create()->addComponent(new GridFieldTest_Component);
		$obj = new GridField('testfield', 'testfield', ArrayList::create(), $config);
		$this->assertEquals('handledAction is executed', $obj->handleAction('jump', array(), array()));
	}

	/**
	 * @covers GridField::getFieldCasting
	 * @covers GridField::setFieldCasting
	 */
	public function testFieldCasting() {
		$obj = new GridField('testfield', 'testfield');
		$this->assertEquals(array(), $obj->getFieldCasting());
		$obj->setFieldCasting(array("MyShortText"=>"Text->FirstSentence"));
		$this->assertEquals(array("MyShortText"=>"Text->FirstSentence"), $obj->getFieldCasting());
	}
	
	/**
	 * @covers GridField::getFieldFormatting
	 * @covers GridField::setFieldFormatting
	 */
	public function testFieldFormatting() {
		$obj = new GridField('testfield', 'testfield');
		$this->assertEquals(array(), $obj->getFieldFormatting());
		$obj->setFieldFormatting(array("myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'));
		$this->assertEquals(array("myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'), $obj->getFieldFormatting());
	}

	/**
	 * @covers GridField::getCastedValue
	 */
	public function testGetCastedValue() {
		$obj = new GridField('testfield', 'testfield');
		$value = $obj->getCastedValue('This is a sentance. This ia another.', array('Text->FirstSentence'));
		$this->assertEquals('This is a sentance.', $value);
	}
	
	/**
	 * @covers GridField::getCastedValue
	 */
	public function testGetCastedValueObject() {
		$obj = new GridField('testfield', 'testfield');
		$value = $obj->getCastedValue('This is a sentance. This ia another.', 'Date');
		$this->assertEquals('1970-01-01', $value);
	}

	/**
	 * @covers GridField::gridFieldAlterAction
	 */
	public function testGridFieldAlterAction() {
		$config = GridFieldConfig::create()->addComponent(new GridFieldTest_Component);
		$obj = new GridField('testfield', 'testfield', ArrayList::create(), $config);
		$id = 'testGridStateActionField';
		Session::set($id, array('grid'=>'', 'actionName'=>'jump'));
		$form = new Form(new Controller(), 'mockform', new FieldList(array($obj)), new FieldList());
		$request = new SS_HTTPRequest('POST', 'url');
		$obj->gridFieldAlterAction(array('StateID'=>$id), $form, $request);
	}

}

class GridFieldTest_Component implements GridField_ColumnProvider, GridField_ActionProvider, TestOnly{

	public function augmentColumns($gridField, &$columns) {}

	public function getColumnContent($gridField, $record, $columnName) {}

	public function getColumnAttributes($gridField, $record, $columnName) {
		if($columnName=='Surname'){
			return 'shouldnotbestring';
		}
		return array('class'=>'css-class');
	}

	public function getColumnMetadata($gridField, $columnName) {
		if($columnName=='Surname'){
			return 'shouldnotbestring';
		} elseif( $columnName == 'FirstName') {
			return array();
		}
		return array('metadata'=>'istrue');
	}
	public function getColumnsHandled($gridField) {
		return array('Email', 'Surname', 'FirstName');
	}

	public function getActions($gridField) {
		return array('jump');
	}

	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		return 'handledAction is executed';
	}

	
}

class GridFieldTest_Team extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'City' => 'Varchar'
	);

	static $many_many = array('Players' => 'GridFieldTest_Player');
}

class GridFieldTest_Player extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'Email' => 'Varchar',
	);

	static $belongs_many_many = array('Teams' => 'GridFieldTest_Team');
}