<?php

/**
 * @package framework
 * @subpackage tests
 */
class GridFieldExportButtonTest extends SapphireTest {

	protected $list;

	protected $gridField;

	protected $form;

	protected static $fixture_file = 'GridFieldExportButtonTest.yml';

	protected $extraDataObjects = array(
		'GridFieldExportButtonTest_Team',
		'GridFieldExportButtonTest_NoView'
	);

	public function setUp() {
		parent::setUp();

		$this->list = new DataList('GridFieldExportButtonTest_Team');
		$this->list = $this->list->sort('Name');
		$config = GridFieldConfig::create()->addComponent(new GridFieldExportButton());
		$this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
	}

	public function testCanView() {
		$list = new DataList('GridFieldExportButtonTest_NoView');

		$button = new GridFieldExportButton();
		$button->setExportColumns(array('Name' => 'My Name'));

		$config = GridFieldConfig::create()->addComponent(new GridFieldExportButton());
		$gridField = new GridField('testfield', 'testfield', $list, $config);

		$this->assertEquals(
			"\"My Name\"\n",
			$button->generateExportFileData($gridField)
		);
	}

	public function testGenerateFileDataBasicFields() {
		$button = new GridFieldExportButton();
		$button->setExportColumns(array('Name' => 'My Name'));

		$this->assertEquals(
			"\"My Name\"\n\"Test\"\n\"Test2\"\n",
			$button->generateExportFileData($this->gridField)
		);
	}

	public function testGenerateFileDataAnonymousFunctionField() {
		$button = new GridFieldExportButton();
		$button->setExportColumns(array(
			'Name' => 'Name',
			'City' => function($obj) {
				return $obj->getValue() . ' city';
			}
		));

		$this->assertEquals(
			"\"Name\",\"City\"\n\"Test\",\"City city\"\n\"Test2\",\"City2 city\"\n",
			$button->generateExportFileData($this->gridField)
		);
	}

	public function testBuiltInFunctionNameCanBeUsedAsHeader() {
		$button = new GridFieldExportButton();
		$button->setExportColumns(array(
			'Name' => 'Name',
			'City' => 'strtolower'
		));

		$this->assertEquals(
			"\"Name\",\"strtolower\"\n\"Test\",\"City\"\n\"Test2\",\"City2\"\n",
			$button->generateExportFileData($this->gridField)
		);
	}

	public function testNoCsvHeaders() {
		$button = new GridFieldExportButton();
		$button->setExportColumns(array(
			'Name' => 'Name',
			'City' => 'City'
		));
		$button->setCsvHasHeader(false);

		$this->assertEquals(
			"\"Test\",\"City\"\n\"Test2\",\"City2\"\n",
			$button->generateExportFileData($this->gridField)
		);
	}
	
	public function testArrayListInput() {
		$button = new GridFieldExportButton();
		$this->gridField->getConfig()->addComponent(new GridFieldPaginator());
		
		//Create an ArrayList 1 greater the Paginator's default 15 rows
		$arrayList = new ArrayList();
		for ($i = 1; $i <= 16; $i++) {
			$dataobject = new DataObject( 
				array ( 'ID' => $i )
			);
			$arrayList->add($dataobject);
		}
		$this->gridField->setList($arrayList);
		
		$this->assertEquals(
			"\"ID\"\n\"1\"\n\"2\"\n\"3\"\n\"4\"\n\"5\"\n\"6\"\n\"7\"\n\"8\"\n"
			."\"9\"\n\"10\"\n\"11\"\n\"12\"\n\"13\"\n\"14\"\n\"15\"\n\"16\"\n",
			$button->generateExportFileData($this->gridField)
		);
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class GridFieldExportButtonTest_Team extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar',
		'City' => 'Varchar'
	);

	public function canView($member = null) {
		return true;
	}

}

/**
 * @package framework
 * @subpackage tests
 */
class GridFieldExportButtonTest_NoView extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar',
		'City' => 'Varchar'
	);

	public function canView($member = null) {
		return false;
	}

}

