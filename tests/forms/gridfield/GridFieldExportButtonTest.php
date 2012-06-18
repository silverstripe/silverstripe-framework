<?php
class GridFieldExportButtonTest extends SapphireTest {

	protected $list;

	protected $gridField;

	protected $form;

	public static $fixture_file = 'GridFieldExportButtonTest.yml';

	protected $extraDataObjects = array(
		'GridFieldExportButtonTest_Team'
	);

	public function setUp() {
		parent::setUp();

		$this->list = new DataList('GridFieldExportButtonTest_Team');
		$this->list = $this->list->sort('Name');
		$config = GridFieldConfig::create()->addComponent(new GridFieldExportButton());
		$this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
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

}
class GridFieldExportButtonTest_Team extends DataObject implements TestOnly {

	static $db = array(
		'Name' => 'Varchar',
		'City' => 'Varchar'
	);

	public function canView($member = null) {
		return true;
	}

}

