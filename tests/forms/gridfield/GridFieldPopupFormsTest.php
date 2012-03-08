<?php

class GridFieldPopupFormsTest extends FunctionalTest {
	static $fixture_file = 'GridFieldPopupFormsTest.yml';

	protected $extraDataObjects = array(
		'GridFieldPopupFormsTest_Person',
		'GridFieldPopupFormsTest_PeopleGroup',
		'GridFieldPopupFormsTest_Category',
	);
	

	function testAddForm() {
		$this->logInWithPermission('ADMIN');
		$group = DataList::create('GridFieldPopupFormsTest_PeopleGroup')
		            ->filter('Name', 'My Group')
		            ->First();
		$count = $group->People()->Count();

		$response = $this->get('GridFieldPopupFormsTest_Controller');
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

		$group = DataList::create('GridFieldPopupFormsTest_PeopleGroup')
            ->filter('Name', 'My Group')
            ->First();
        $this->assertEquals($count + 1, $group->People()->Count());
	}

	function testEditForm() {
		$this->logInWithPermission('ADMIN');
		$group = DataList::create('GridFieldPopupFormsTest_PeopleGroup')
		            ->filter('Name', 'My Group')
		            ->First();
		$firstperson = $group->People()->First();
		$this->assertTrue($firstperson->Surname != 'Baggins');

		$response = $this->get('GridFieldPopupFormsTest_Controller');
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

		$group = DataList::create('GridFieldPopupFormsTest_PeopleGroup')
            ->filter('Name', 'My Group')
            ->First();
        $firstperson = $group->People()->First();
        $this->assertEquals($firstperson->Surname, 'Baggins');
	}

	function testNestedEditForm() {
		$this->logInWithPermission('ADMIN');

		$group = $this->objFromFixture('GridFieldPopupFormsTest_PeopleGroup', 'group');
		$person = $group->People()->First();
		$category = $person->Categories()->First();

		// Get first form (GridField managing PeopleGroup)
		$response = $this->get('GridFieldPopupFormsTest_GroupController');
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());

		$groupEditLink = $parser->getByXpath('//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $group->ID . '")]//a');
		$this->assertEquals(
			'GridFieldPopupFormsTest_GroupController/Form/field/testfield/item/1/edit',
			(string)$groupEditLink[0]['href']
		);

		// Get second level form (GridField managing Person)
		$response = $this->get((string)$groupEditLink[0]['href']);
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$personEditLink = $parser->getByXpath('//fieldset[@id="Form_ItemEditForm_People"]//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $person->ID . '")]//a');		
		$this->assertEquals(
			'GridFieldPopupFormsTest_GroupController/Form/field/testfield/item/1/ItemEditForm/field/People/item/1/edit',
			(string)$personEditLink[0]['href']
		);

		// Get third level form (GridField managing Category)
		$response = $this->get((string)$personEditLink[0]['href']);
		$this->assertFalse($response->isError());
		$parser = new CSSContentParser($response->getBody());
		$categoryEditLink = $parser->getByXpath('//fieldset[@id="Form_ItemEditForm_Categories"]//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $category->ID . '")]//a');	

		// Get fourth level form (Category detail view)
		$this->assertEquals(
			'GridFieldPopupFormsTest_GroupController/Form/field/testfield/item/1/ItemEditForm/field/People/item/1/ItemEditForm/field/Categories/item/1/edit',
			(string)$categoryEditLink[0]['href']
		);
	}
}

class GridFieldPopupFormsTest_Person extends DataObject implements TestOnly {
	static $db = array(
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar'
	);

	static $has_one = array(
		'Group' => 'GridFieldPopupFormsTest_PeopleGroup'
	);

	static $many_many = array(
		'Categories' => 'GridFieldPopupFormsTest_Category'
	);

	function getCMSFields() {
		$fields = parent::getCMSFields();
		// TODO No longer necessary once FormScaffolder uses GridField
		$fields->replaceField('Categories',
			Object::create('GridField', 'Categories', 'Categories',
				$this->Categories(),
				GridFieldConfig_RelationEditor::create()
			)
		);
		return $fields;
	}
}

class GridFieldPopupFormsTest_PeopleGroup extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar'
	);

	static $has_many = array(
		'People' => 'GridFieldPopupFormsTest_Person'
	);
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		// TODO No longer necessary once FormScaffolder uses GridField
		$fields->replaceField('People',
			Object::create('GridField', 'People', 'People',
				$this->People(),
				GridFieldConfig_RelationEditor::create()
			)
		);
		return $fields;
	}
}

class GridFieldPopupFormsTest_Category extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar'
	);

	static $belongs_many_many = array(
		'People' => 'GridFieldPopupFormsTest_Person'
	);

	function getCMSFields() {
		$fields = parent::getCMSFields();
		// TODO No longer necessary once FormScaffolder uses GridField
		$fields->replaceField('People',
			Object::create('GridField', 'People', 'People',
				$this->People(),
				GridFieldConfig_RelationEditor::create()
			)
		);
		return $fields;
	}
}

class GridFieldPopupFormsTest_Controller extends Controller implements TestOnly {
	protected $template = 'BlankPage';

	function Form() {
		$group = DataList::create('GridFieldPopupFormsTest_PeopleGroup')
		            ->filter('Name', 'My Group')
		            ->First();

		$field = new GridField('testfield', 'testfield', $group->People());
		$field->getConfig()->addComponent($gridFieldForm = new GridFieldPopupForms($this, 'Form'));
		$field->getConfig()->addComponent(new GridFieldEditAction());
		return new Form($this, 'Form', new FieldList($field), new FieldList());
	}
}

class GridFieldPopupFormsTest_GroupController extends Controller implements TestOnly {
	protected $template = 'BlankPage';

	function Form() {
		$field = new GridField('testfield', 'testfield', DataList::create('GridFieldPopupFormsTest_PeopleGroup'));
		$field->getConfig()->addComponent($gridFieldForm = new GridFieldPopupForms($this, 'Form'));
		$field->getConfig()->addComponent(new GridFieldEditAction());
		return new Form($this, 'Form', new FieldList($field), new FieldList());
	}
}
