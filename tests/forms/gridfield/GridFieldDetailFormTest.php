<?php

/**
 * @package framework
 * @subpackage tests
 */
class GridFieldDetailFormTest extends FunctionalTest {

	protected static $fixture_file = 'GridFieldDetailFormTest.yml';

	protected $extraDataObjects = array(
		'GridFieldDetailFormTest_Person',
		'GridFieldDetailFormTest_PeopleGroup',
		'GridFieldDetailFormTest_Category',
	);

	public function testValidator() {
		$this->logInWithPermission('ADMIN');

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
				'ajax' => 1,
				'action_doSave' => 1
			)
		);

		$parser = new CSSContentParser($response->getBody());
		$errors = $parser->getBySelector('span.required');
		$this->assertEquals(1, count($errors));

		$response = $this->post(
			$addformurl,
			array(
				'ajax' => 1,
				'action_doSave' => 1
			)
		);

		$parser = new CSSContentParser($response->getBody());
		$errors = $parser->getBySelector('span.required');
		$this->assertEquals(2, count($errors));
	}

	public function testAddForm() {
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

	public function testEditForm() {
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

	public function testEditFormWithManyMany() {
		$this->logInWithPermission('ADMIN');

		// Edit the first person
		$response = $this->get('GridFieldDetailFormTest_CategoryController');
		// Find the link to add a new favourite group
		$parser = new CSSContentParser($response->getBody());
		$addLink = $parser->getBySelector('#Form_Form_testgroupsfield .new-link');
		$addLink = (string) $addLink[0]['href'];

		// Add a new favourite group
		$response = $this->get($addLink);
		$parser = new CSSContentParser($response->getBody());
		$addform = $parser->getBySelector('#Form_ItemEditForm');
		$addformurl = (string) $addform[0]['action'];

		$response = $this->post(
			$addformurl,
			array(
				'Name' => 'My Favourite Group',
				'ajax' => 1,
				'action_doSave' => 1
			)
		);
		$this->assertFalse($response->isError());

		$person = GridFieldDetailFormTest_Person::get()->sort('FirstName')->First();
		$favouriteGroup = $person->FavouriteGroups()->first();

		$this->assertInstanceOf('GridFieldDetailFormTest_PeopleGroup', $favouriteGroup);
	}

	public function testEditFormWithManyManyExtraData() {
		$this->logInWithPermission('ADMIN');

		// Lists all categories for a person
		$response = $this->get('GridFieldDetailFormTest_CategoryController');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$editlinkitem = $parser->getBySelector('.ss-gridfield-items .first .edit-link');
		$editlink = (string) $editlinkitem[0]['href'];

		// Edit a single category, incl. manymany extrafields added manually
		// through GridFieldDetailFormTest_CategoryController
		$response = $this->get($editlink);
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$editform = $parser->getBySelector('#Form_ItemEditForm');
		$editformurl = (string) $editform[0]['action'];

		$manyManyField = $parser->getByXpath('//*[@id="Form_ItemEditForm"]//input[@name="ManyMany[IsPublished]"]');
		$this->assertTrue((bool)$manyManyField);

		// Test save of IsPublished field
		$response = $this->post(
			$editformurl,
			array(
				'Name' => 'Updated Category',
				'ManyMany' => array(
					'IsPublished' => 1,
					'PublishedBy' => 'Richard'
				),
				'action_doSave' => 1
			)
		);
		$this->assertFalse($response->isError());

		$person = GridFieldDetailFormTest_Person::get()->sort('FirstName')->First();
		$category = $person->Categories()->filter(array('Name' => 'Updated Category'))->First();
		$this->assertEquals(
			array(
				'IsPublished' => 1,
				'PublishedBy' => 'Richard'
			),
			$person->Categories()->getExtraData('', $category->ID)
		);
		
		// Test update of value with falsey value
		$response = $this->post(
			$editformurl,
			array(
				'Name' => 'Updated Category',
				'ManyMany' => array(
					'PublishedBy' => ''
				),
				'action_doSave' => 1
			)
		);
		$this->assertFalse($response->isError());

		$person = GridFieldDetailFormTest_Person::get()->sort('FirstName')->First();
		$category = $person->Categories()->filter(array('Name' => 'Updated Category'))->First();
		$this->assertEquals(
			array(
				'IsPublished' => 0,
				'PublishedBy' => ''
			),
			$person->Categories()->getExtraData('', $category->ID)
		);
	}

	public function testNestedEditForm() {
		$this->logInWithPermission('ADMIN');

		$group = $this->objFromFixture('GridFieldDetailFormTest_PeopleGroup', 'group');
		$person = $group->People()->First();
		$category = $person->Categories()->First();

		// Get first form (GridField managing PeopleGroup)
		$response = $this->get('GridFieldDetailFormTest_GroupController');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());

		$groupEditLink = $parser->getByXpath('//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "'
			. $group->ID . '")]//a');
		$this->assertEquals(
			'GridFieldDetailFormTest_GroupController/Form/field/testfield/item/' . $group->ID . '/edit',
			(string)$groupEditLink[0]['href']
		);

		// Get second level form (GridField managing Person)
		$response = $this->get((string)$groupEditLink[0]['href']);
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$personEditLink = $parser->getByXpath('//fieldset[@id="Form_ItemEditForm_People"]' .
			'//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $person->ID . '")]//a');
		$this->assertEquals(
			sprintf('GridFieldDetailFormTest_GroupController/Form/field/testfield/item/%d/ItemEditForm/field/People'
				. '/item/%d/edit', $group->ID, $person->ID),
			(string)$personEditLink[0]['href']
		);

		// Get third level form (GridField managing Category)
		$response = $this->get((string)$personEditLink[0]['href']);
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$categoryEditLink = $parser->getByXpath('//fieldset[@id="Form_ItemEditForm_Categories"]'
			. '//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $category->ID . '")]//a');
		$this->assertEquals(
			sprintf('GridFieldDetailFormTest_GroupController/Form/field/testfield/item/%d/ItemEditForm/field/People'
				. '/item/%d/ItemEditForm/field/Categories/item/%d/edit', $group->ID, $person->ID, $category->ID),
			(string)$categoryEditLink[0]['href']
		);

		// Fourth level form would be a Category detail view
	}

	public function testCustomItemRequestClass() {
		$this->logInWithPermission('ADMIN');

		$component = new GridFieldDetailForm();
		$this->assertEquals('GridFieldDetailForm_ItemRequest', $component->getItemRequestClass());
		$component->setItemRequestClass('GridFieldDetailFormTest_ItemRequest');
		$this->assertEquals('GridFieldDetailFormTest_ItemRequest', $component->getItemRequestClass());
	}

	public function testItemEditFormCallback() {
		$this->logInWithPermission('ADMIN');

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

	/**
	 * Tests that a has-many detail form is pre-populated with the parent ID.
	 */
	public function testHasManyFormPrePopulated() {
		$group = $this->objFromFixture(
			'GridFieldDetailFormTest_PeopleGroup', 'group'
		);

		$this->logInWithPermission('ADMIN');

		$response = $this->get('GridFieldDetailFormTest_Controller');
		$parser = new CSSContentParser($response->getBody());
		$addLink = $parser->getBySelector('.ss-gridfield .new-link');
		$addLink = (string) $addLink[0]['href'];

		$response = $this->get($addLink);
		$parser = new CSSContentParser($response->getBody());
		$title = $parser->getBySelector('#Form_ItemEditForm_GroupID_Holder span');
		$id = $parser->getBySelector('#Form_ItemEditForm_GroupID_Holder input');

		$this->assertEquals($group->Name, (string) $title[0]);
		$this->assertEquals($group->ID, (string) $id[0]['value']);
	}

}

/**
 * @package framework
 * @subpackage tests
 */

class GridFieldDetailFormTest_Person extends DataObject implements TestOnly {

	private static $db = array(
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar'
	);

	private static $has_one = array(
		'Group' => 'GridFieldDetailFormTest_PeopleGroup'
	);

	private static $many_many = array(
		'Categories' => 'GridFieldDetailFormTest_Category',
		'FavouriteGroups' => 'GridFieldDetailFormTest_PeopleGroup'
	);

	private static $many_many_extraFields = array(
		'Categories' => array(
			'IsPublished' => 'Boolean',
			'PublishedBy' => 'Varchar'
		)
	);

	private static $default_sort = '"FirstName"';

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		// TODO No longer necessary once FormScaffolder uses GridField
		$fields->replaceField('Categories',
			GridField::create('Categories', 'Categories',
				$this->Categories(),
				GridFieldConfig_RelationEditor::create()
			)
		);
		$fields->replaceField('FavouriteGroups',
			GridField::create('FavouriteGroups', 'Favourite Groups',
				$this->FavouriteGroups(),
				GridFieldConfig_RelationEditor::create()
			)
		);
		return $fields;
	}

	public function getCMSValidator() {
		return new RequiredFields(array(
			'FirstName', 'Surname'
		));
	}
}

/**
 * @package framework
 * @subpackage tests
 */

class GridFieldDetailFormTest_PeopleGroup extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar'
	);

	private static $has_many = array(
		'People' => 'GridFieldDetailFormTest_Person'
	);

	private static $belongs_many_many = array(
		'People' => 'GridFieldDetailFormTest_Person'
	);

	private static $default_sort = '"Name"';

	public function getCMSFields() {
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

/**
 * @package framework
 * @subpackage tests
 */

class GridFieldDetailFormTest_Category extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar'
	);

	private static $belongs_many_many = array(
		'People' => 'GridFieldDetailFormTest_Person'
	);

	private static $default_sort = '"Name"';

	public function getCMSFields() {
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

/**
 * @package framework
 * @subpackage tests
 */

class GridFieldDetailFormTest_Controller extends Controller implements TestOnly {

	private static $allowed_actions = array('Form');

	protected $template = 'BlankPage';

	public function Form() {
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

/**
 * @package framework
 * @subpackage tests
 */

class GridFieldDetailFormTest_GroupController extends Controller implements TestOnly {

	private static $allowed_actions = array('Form');

	protected $template = 'BlankPage';

	public function Form() {
		$field = new GridField('testfield', 'testfield', GridFieldDetailFormTest_PeopleGroup::get()->sort('Name'));
		$field->getConfig()->addComponent($gridFieldForm = new GridFieldDetailForm($this, 'Form'));
		$field->getConfig()->addComponent(new GridFieldToolbarHeader());
		$field->getConfig()->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
		$field->getConfig()->addComponent(new GridFieldEditButton());
		return new Form($this, 'Form', new FieldList($field), new FieldList());
	}
}

/**
 * @package framework
 * @subpackage tests
 */

class GridFieldDetailFormTest_CategoryController extends Controller implements TestOnly {

	private static $allowed_actions = array('Form');

	protected $template = 'BlankPage';

	public function Form() {
		// GridField lists categories for a specific person
		$person = GridFieldDetailFormTest_Person::get()->sort('FirstName')->First();
		$detailFields = singleton('GridFieldDetailFormTest_Category')->getCMSFields();
		$detailFields->addFieldsToTab('Root.Main', array(
			new CheckboxField('ManyMany[IsPublished]'),
			new TextField('ManyMany[PublishedBy]'))
		);
		$categoriesField = new GridField('testfield', 'testfield', $person->Categories());
		$categoriesField->getConfig()->addComponent($gridFieldForm = new GridFieldDetailForm($this, 'Form'));
		$gridFieldForm->setFields($detailFields);
		$categoriesField->getConfig()->addComponent(new GridFieldToolbarHeader());
		$categoriesField->getConfig()->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
		$categoriesField->getConfig()->addComponent(new GridFieldEditButton());

		$favGroupsField = new GridField('testgroupsfield', 'testgroupsfield', $person->FavouriteGroups());
		$favGroupsField->getConfig()->addComponent(new GridFieldDetailForm($this, 'Form'));
		$favGroupsField->getConfig()->addComponent(new GridFieldToolbarHeader());
		$favGroupsField->getConfig()->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
		$favGroupsField->getConfig()->addComponent(new GridFieldEditButton());

		$fields = new FieldList($categoriesField, $favGroupsField);

		return new Form($this, 'Form', $fields, new FieldList());
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class GridFieldDetailFormTest_ItemRequest extends GridFieldDetailForm_ItemRequest implements TestOnly { }
