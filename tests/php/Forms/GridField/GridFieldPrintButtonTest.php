<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridField;






class GridFieldPrintButtonTest extends SapphireTest {

	protected $extraDataObjects = array(
		'GridFieldPrintButtonTest_DO'
	);

	public function setUp() {
		parent::setUp();

		// 42 items
		for($i = 1; $i <= 42; $i++) {
			$obj = new GridFieldPrintButtonTest_DO();
			$obj->Name = "Object {$i}";
			$obj->write();
		}
	}

	public function testLimit() {
		$list = GridFieldPrintButtonTest_DO::get();

		$button = new GridFieldPrintButton();
		$button->setPrintColumns(array('Name' => 'My Name'));

		// Get paginated gridfield config
		$config = GridFieldConfig::create()
			->addComponent(new GridFieldPaginator(10))
			->addComponent($button);
		$gridField = new GridField('testfield', 'testfield', $list, $config);
		$controller = new Controller();
		/** @skipUpgrade */
		$form = new Form($controller, 'Form', new FieldList($gridField), new FieldList());

		// Printed data should ignore pagination limit
		$printData = $button->generatePrintData($gridField);
		$rows = $printData->ItemRows;
		$this->assertEquals(42, $rows->count());
	}
}

class GridFieldPrintButtonTest_DO extends DataObject {
	private static $db = array(
		'Name' => 'Varchar'
	);
}
