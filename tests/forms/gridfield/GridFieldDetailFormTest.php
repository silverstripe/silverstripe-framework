<?php

class GridFieldDetailFormTest extends FunctionalTest {
	static $fixture_file = 'GridFieldDetailFormTest.yml';

	protected $extraDataObjects = array(
		'GridFieldDetailFormTest_Person',
		'GridFieldDetailFormTest_PeopleGroup'
	);
	

	function testAddForm() {
		$this->logInWithPermission('ADMIN');
		$group = DataList::create('GridFieldDetailFormTest_PeopleGroup')
		            ->filter('Name', 'My Group')
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

		$group = DataList::create('GridFieldDetailFormTest_PeopleGroup')
            ->filter('Name', 'My Group')
            ->First();
        $this->assertEquals($count + 1, $group->People()->Count());
	}

	function testEditForm() {
		$this->logInWithPermission('ADMIN');
		$group = DataList::create('GridFieldDetailFormTest_PeopleGroup')
		            ->filter('Name', 'My Group')
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

		$group = DataList::create('GridFieldDetailFormTest_PeopleGroup')
            ->filter('Name', 'My Group')
            ->First();
        $firstperson = $group->People()->First();
        $this->assertEquals($firstperson->Surname, 'Baggins');
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
}

class GridFieldDetailFormTest_PeopleGroup extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar'
	);

	static $has_many = array(
		'People' => 'GridFieldDetailFormTest_Person'
	);
}

class GridFieldDetailFormTest_Controller extends Controller implements TestOnly {
	protected $template = 'BlankPage';

	function Form() {
		$group = DataList::create('GridFieldDetailFormTest_PeopleGroup')
		            ->filter('Name', 'My Group')
		            ->First();

		$field = new GridField('testfield', 'testfield', $group->People());
		$field->getConfig()->addComponent($gridFieldForm = new GridFieldDetailForm($this, 'Form'));
		$field->getConfig()->addComponent(new GridFieldEditButton());
		return new Form($this, 'Form', new FieldList($field), new FieldList());
	}
}

?>