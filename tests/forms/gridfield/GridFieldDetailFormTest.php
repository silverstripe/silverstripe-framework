<?php

class GridFieldDetailFormTest extends FunctionalTest {
	static $fixture_file = 'GridFieldDetailFormTest.yml';

	protected $extraDataObjects = array(
		'GridFieldDetailFormTest_Person',
		'GridFieldDetailFormTest_PeopleGroup',
		'GridFieldDetailFormTest_Category',
	);

	function testAddForm() {
		$this->logInWithPermission('ADMIN');
		$group = GridFieldDetailFormTest_PeopleGroup::get()
			->filter('Name', 'My Group')
			->sort('Name')
			->First();
		$count = $group->People()->Count();

		$response = $this->get('GridFieldDetailFormTest_Controller');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$addlinkitem = $parser->getBySelector('.ss-gridfield .new-link');
		$addlink = (string) $addlinkitem[0]['href'];

		$response = $this->get($addlink);
		$this->assertFalse($response->isError());

		$parser = new CSSContentParser($response->getBody());
		$addform = $parser->getBySelector('#Form_ItemEditForm');
		$addformurl = (string) $addform[0]['action'];

		$response = $this->post(
			$addformurl,
			array(
				'FirstName' => 'Jeremiah',
				'Surname' => 'BullFrog',
				'action_doSave' => 1
			)
		);
		$this->assertFalse($response->isError());

		$group = GridFieldDetailFormTest_PeopleGroup::get()
			->filter('Name', 'My Group')
			->sort('Name')
			->First();
		$this->assertEquals($count + 1, $group->People()->Count());
	}

	public function testViewForm() {
		$this->logInWithPermission('ADMIN');

		$response = $this->get('GridFieldDetailFormTest_Controller');
		$parser   = new CSSContentParser($response->getBody());

		$viewLink = $parser->getBySelector('.ss-gridfield-items .first .view-link');
		$viewLink = (string) $viewLink[0]['href'];

		$response = $this->get($viewLink);
		$parser   = new CSSContentParser($response->getBody());

		$firstName = $parser->getBySelector('#Form_ItemEditForm_FirstName');
		$surname   = $parser->getBySelector('#Form_ItemEditForm_Surname');

		$this->assertFalse($response->isError());
		$this->assertEquals('Jane', (string) $firstName[0]);
		$this->assertEquals('Doe', (string) $surname[0]);
	}

	function testEditForm() {
		$this->logInWithPermission('ADMIN');
		$group = GridFieldDetailFormTest_PeopleGroup::get()
			->filter('Name', 'My Group')
			->sort('Name')
			->First();
		$firstperson = $group->People()->First();
		$this->assertTrue($firstperson->Surname != 'Baggins');

		$response = $this->get('GridFieldDetailFormTest_Controller');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$editlinkitem = $parser->getBySelector('.ss-gridfield-items .first .edit-link');
		$editlink = (string) $editlinkitem[0]['href'];

		$response = $this->get($editlink);
		$this->assertFalse($response->isError());

		$parser = new CSSContentParser($response->getBody());
		$editform = $parser->getBySelector('#Form_ItemEditForm');
		$editformurl = (string) $editform[0]['action'];
		
		$response = $this->post(
			$editformurl,
			array(
				'FirstName' => 'Bilbo',
				'Surname' => 'Baggins',
				'action_doSave' => 1
			)
		);
		$this->assertFalse($response->isError());

		$group = GridFieldDetailFormTest_PeopleGroup::get()
			->filter('Name', 'My Group')
			->sort('Name')
			->First();
		$this->assertDOSContains(array(array('Surname' => 'Baggins')), $group->People());
	}

	function testNestedEditForm() {
		$this->logInWithPermission('ADMIN');

		$group = $this->objFromFixture('GridFieldDetailFormTest_PeopleGroup', 'group');
		$person = $group->People()->First();
		$category = $person->Categories()->First();

		// Get first form (GridField managing PeopleGroup)
		$response = $this->get('GridFieldDetailFormTest_GroupController');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());

		$groupEditLink = $parser->getByXpath('//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $group->ID . '")]//a');
		$this->assertEquals(
			'GridFieldDetailFormTest_GroupController/Form/field/testfield/item/' . $group->ID . '/edit',
			(string)$groupEditLink[0]['href']
		);

		// Get second level form (GridField managing Person)
		$response = $this->get((string)$groupEditLink[0]['href']);
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$personEditLink = $parser->getByXpath('//fieldset[@id="Form_ItemEditForm_People"]//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $person->ID . '")]//a');		
		$this->assertEquals(
			sprintf('GridFieldDetailFormTest_GroupController/Form/field/testfield/item/%d/ItemEditForm/field/People/item/%d/edit', $group->ID, $person->ID),
			(string)$personEditLink[0]['href']
		);

		// Get third level form (GridField managing Category)
		$response = $this->get((string)$personEditLink[0]['href']);
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$categoryEditLink = $parser->getByXpath('//fieldset[@id="Form_ItemEditForm_Categories"]//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $category->ID . '")]//a');	
		$this->assertEquals(
			sprintf('GridFieldDetailFormTest_GroupController/Form/field/testfield/item/%d/ItemEditForm/field/People/item/%d/ItemEditForm/field/Categories/item/%d/edit', $group->ID, $person->ID, $category->ID),
			(string)$categoryEditLink[0]['href']
		);

		// Fourth level form would be a Category detail view
	}

	function testCustomItemRequestClass() {
		$component = new GridFieldDetailForm();
		$this->assertEquals('GridFieldDetailForm_ItemRequest', $component->getItemRequestClass());
		$component->setItemRequestClass('GridFieldDetailFormTest_ItemRequest');
		$this->assertEquals('GridFieldDetailFormTest_ItemRequest', $component->getItemRequestClass());
	}

	function testItemEditFormCallback() {
		$category = new GridFieldDetailFormTest_Category();
		$component = new GridFieldDetailForm();
		$component->setItemEditFormCallback(function($form, $component) {
			$form->Fields()->push(new HiddenField('Callback'));
		});
		// Note: A lot of scaffolding to execute the tested logic,
		// due to the coupling of form creation with request handling (and its context)
		$request = new GridFieldDetailForm_ItemRequest(
			GridField::create('Categories', 'Categories'),
			$component,
			$category,
			new Controller(),
			'Form'
		);
		$form = $request->ItemEditForm();
		$this->assertNotNull($form->Fields()->fieldByName('Callback'));
	}
}

class GridFieldDetailFormTest_Person extends DataObject implements TestOnly {
	static $db = array(
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar'
	);

	static $has_one = array(
		'Group' => 'GridFieldDetailFormTest_PeopleGroup'
	);

	static $many_many = array(
		'Categories' => 'GridFieldDetailFormTest_Category'
	);

	static $default_sort = 'FirstName';

	function getCMSFields() {
		$fields = parent::getCMSFields();
		// TODO No longer necessary once FormScaffolder uses GridField
		$fields->replaceField('Categories',
			GridField::create('Categories', 'Categories',
				$this->Categories(),
				GridFieldConfig_RelationEditor::create()
			)
		);
		return $fields;
	}
}

class GridFieldDetailFormTest_PeopleGroup extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar'
	);

	static $has_many = array(
		'People' => 'GridFieldDetailFormTest_Person'
	);

	static $default_sort = 'Name';

	function getCMSFields() {
		$fields = parent::getCMSFields();
		// TODO No longer necessary once FormScaffolder uses GridField
		$fields->replaceField('People',
			GridField::create('People', 'People',
				$this->People(),
				GridFieldConfig_RelationEditor::create()
			)
		);
		return $fields;
	}
}

class GridFieldDetailFormTest_Category extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar'
	);

	static $belongs_many_many = array(
		'People' => 'GridFieldDetailFormTest_Person'
	);

	static $default_sort = 'Name';

	function getCMSFields() {
		$fields = parent::getCMSFields();
		// TODO No longer necessary once FormScaffolder uses GridField
		$fields->replaceField('People',
			GridField::create('People', 'People',
				$this->People(),
				GridFieldConfig_RelationEditor::create()
			)
		);
		return $fields;
	}
}

class GridFieldDetailFormTest_Controller extends Controller implements TestOnly {
	protected $template = 'BlankPage';

	function Form() {
		$group = GridFieldDetailFormTest_PeopleGroup::get()
			->filter('Name', 'My Group')
			->sort('Name')
			->First();

		$field = new GridField('testfield', 'testfield', $group->People());
		$field->getConfig()->addComponent(new GridFieldToolbarHeader());
		$field->getConfig()->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
		$field->getConfig()->addComponent(new GridFieldViewButton());
		$field->getConfig()->addComponent(new GridFieldEditButton());
		$field->getConfig()->addComponent($gridFieldForm = new GridFieldDetailForm($this, 'Form'));
		$field->getConfig()->addComponent(new GridFieldEditButton());
		return new Form($this, 'Form', new FieldList($field), new FieldList());
	}
}

class GridFieldDetailFormTest_GroupController extends Controller implements TestOnly {
	protected $template = 'BlankPage';

	function Form() {
		$field = new GridField('testfield', 'testfield', GridFieldDetailFormTest_PeopleGroup::get()->sort('Name'));
		$field->getConfig()->addComponent($gridFieldForm = new GridFieldDetailForm($this, 'Form'));
		$field->getConfig()->addComponent(new GridFieldToolbarHeader());
		$field->getConfig()->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
		$field->getConfig()->addComponent(new GridFieldEditButton());
		return new Form($this, 'Form', new FieldList($field), new FieldList());
	}
}

class GridFieldDetailFormTest_ItemRequest extends GridFieldDetailForm_ItemRequest implements TestOnly {
}
