<?php
class GridFieldTitleTest extends SapphireTest {


	public function testGridTitleAddNewEnabled() {
		$this->logInWithPermission('ADMIN');
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
		$this->logInWithPermission('ADMIN');
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
	
	public function testGridTitleAddNewWithoutPermission() {
		if(Member::currentUser()) { Member::currentUser()->logOut(); }
		$config = new GridFieldConfig();
		$config->addComponent($titleField = new GridFieldTitle());
		$grid = new GridField('TestField', 'Test Field', new DataList('Company'),$config);
		$fields = new FieldList(new TabSet("Root",$tabMain = new Tab('Main',$grid)));
		$form = new Form(Controller::curr(), "TestForm", $fields, new FieldList());

		$html = $form->forTemplate();
		$this->assertNotContains('data-icon="add"', $html, "HTML should not contain the 'add new' button");
	}
}