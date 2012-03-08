<?php
class GridFieldTitleTest extends SapphireTest {


	public function testGridTitleAddNewEnabled() {
		//construct a fake form field to render out the grid field within it
		$config = new GridFieldConfig();
		$config->addComponent($titleField = new GridFieldTitle());
		$actions = new FieldList();
		$grid = new GridField('TestField', 'Test Field', new DataList('Company'),$config);
		$fields = new FieldList($rootTab = new TabSet("Root",$tabMain = new Tab('Main',$grid)));
		$form = new Form(Controller::curr(), "TestForm", $fields, $actions);

		$titleField->setNewEnabled(true);
		$html = $form->forTemplate();
		$this->assertContains('data-icon="add"', $html,"HTML contains the 'add new' button");
	}

	public function testGridTitleAddNewDisabled() {
		//construct a fake form field to render out the grid field within it
		$config = new GridFieldConfig();
		$config->addComponent($titleField = new GridFieldTitle());
		$actions = new FieldList();
		$grid = new GridField('TestField', 'Test Field', new DataList('Company'),$config);
		$fields = new FieldList($rootTab = new TabSet("Root",$tabMain = new Tab('Main',$grid)));
		$form = new Form(Controller::curr(), "TestForm", $fields, $actions);

		$titleField->setNewEnabled(false);
		$html = $form->forTemplate();
		$this->assertNotContains('data-icon="add"', $html,"HTML does not contain the 'add new' button");
	}
}
?>