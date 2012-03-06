<?php

class GridFieldPopupFormsTest extends FunctionalTest {
	static $fixture_file = 'GridFieldPopupFormsTest.yml';

	protected $extraDataObjects = array(
		'GridFieldPopupFormsTest_Person',
		'GridFieldPopupFormsTest_PeopleGroup'
	);
	

	function testAddForm() {
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
}

class GridFieldPopupFormsTest_Person extends DataObject implements TestOnly {
	static $db = array(
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar'
	);

	static $has_one = array(
		'Group' => 'GridFieldPopupFormsTest_PeopleGroup'
	);
}

class GridFieldPopupFormsTest_PeopleGroup extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar'
	);

	static $has_many = array(
		'People' => 'GridFieldPopupFormsTest_Person'
	);
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

?>