<?php

class TableFieldTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/forms/TableFieldTest.yml';

	protected $extraDataObjects = array(
		'TableFieldTest_Object',
		'TableFieldTest_HasManyRelation',
	);

	function testAdd() {
		$group = $this->objFromFixture('Group','group1_no_perms');
		
		$tableField = new TableField(
			"Permissions",
			"Permission",
			array(
				"Code" => 'Code',
				"Arg" => 'Arg',
			),
			array(
				"Code" => "TextField",
				"Arg" => "TextField",
			),
			"GroupID",
			$group->ID
		);
		$form = new Form(
			new TableFieldTest_Controller(), 
			"Form", 
			new FieldSet($tableField), 
			new FieldSet()
		);
		
		// Test Insert
		
		// The field starts emppty.  Save some new data.  
		// We have replicated the array structure that the specific layout of the form generates.
		$tableField->setValue(array(
			'new' => array(
				'Code' => array(
					'CMS_ACCESS_CMSMain',
					'CMS_ACCESS_AssetAdmin',
				),
				'Arg' => array(
					'1',
					'2'
				),
			),			
		));
		$tableField->saveInto($group);

		// Let's check that the 2 permissions entries have been saved
		$permissions = $group->Permissions()->toDropdownMap('Arg', 'Code');
		$this->assertEquals(array(
			1 => 'CMS_ACCESS_CMSMain',
			2 => 'CMS_ACCESS_AssetAdmin',
		), $permissions);
		

		// Test repeated insert
		$value = array();
		foreach($group->Permissions() as $permission) {
			$value[$permission->ID] = array("Code" => $permission->Code, "Arg" => $permission->Arg);
		}
		$value['new'] = array(
			'Code' => array(
				'CMS_ACCESS_NewsletterAdmin',
			),
			'Arg' => array(
				'3',
			),
		);
		$tableField->setValue($value);
		$tableField->saveInto($group);

		// Let's check that the 2 existing permissions entries, and the 1 new one, have been saved
		$permissions = $group->Permissions()->toDropdownMap('Arg', 'Code');
		$this->assertEquals(array(
			1 => 'CMS_ACCESS_CMSMain',
			2 => 'CMS_ACCESS_AssetAdmin',
			3 => 'CMS_ACCESS_NewsletterAdmin',
		), $permissions);

	}
	
	function testEdit() {
		$group = $this->objFromFixture('Group','group2_existing_perms');
		$perm1 = $this->objFromFixture('Permission', 'perm1');
		$perm2 = $this->objFromFixture('Permission', 'perm2');
		
		$tableField = new TableField(
			"Permissions",
			"Permission",
			array(
				"Code" => 'Code',
				"Arg" => 'Arg',
			),
			array(
				"Code" => "TextField",
				"Arg" => "TextField",
			),
			"GroupID",
			$group->ID
		);
		$form = new Form(
			new TableFieldTest_Controller(), 
			"Form", 
			new FieldSet($tableField), 
			new FieldSet()
		);
		
		$this->assertEquals($tableField->sourceItems()->Count(), 2);
		
		// We have replicated the array structure that the specific layout of the form generates.
		$tableField->setValue(array(
			$perm1->ID => array(
				'Code' => 'Perm1 Modified',
				'Arg' => '101'
			),
			$perm2->ID => array(
				'Code' => 'Perm2 Modified',
				'Arg' => '102'
			)
		));
		$tableField->saveInto($group);

		// Let's check that the 2 permissions entries have been saved
		$permissions = $group->Permissions()->toDropdownMap('Arg', 'Code');
		$this->assertEquals(array(
			101 => 'Perm1 Modified',
			102 => 'Perm2 Modified',
		), $permissions);
	}
	
	function testDelete() {
		$group = $this->objFromFixture('Group','group2_existing_perms');
		$perm1 = $this->objFromFixture('Permission', 'perm1');
		$perm2 = $this->objFromFixture('Permission', 'perm2');
		
		$tableField = new TableField(
			"Permissions",
			"Permission",
			array(
				"Code" => 'Code',
				"Arg" => 'Arg',
			),
			array(
				"Code" => "TextField",
				"Arg" => "TextField",
			),
			"GroupID",
			$group->ID
		);
		$form = new Form(
			new TableFieldTest_Controller(), 
			"Form", 
			new FieldSet($tableField), 
			new FieldSet()
		);
		
		$this->assertContains($perm1->ID, $tableField->sourceItems()->column('ID'));
		
		$response = $tableField->Items()->find('ID', $perm1->ID)->delete();
		
		$this->assertNotContains($perm1->ID, $tableField->sourceItems()->column('ID'));
	}

	function testAutoRelationSettingOn() {
		$tf = new TableField(
			'HasManyRelations',
			'TableFieldTest_HasManyRelation',
			array(
				'Value' => 'Value'
			),
			array(
				'Value' => 'TextField'
			)
		);
		
		// Test with auto relation setting
		$form = new Form(new TableFieldTest_Controller(), "Form", new FieldSet($tf), new FieldSet());
		$tf->setValue(array(
			'new' => array(
				'Value' => array('one','two',)
			)
		));
		$tf->setRelationAutoSetting(true);
		$o = new TableFieldTest_Object();
		$o->write();
		$form->saveInto($o);
		$this->assertEquals($o->HasManyRelations()->Count(), 2);
	}
	
	function testAutoRelationSettingOff() {
		$tf = new TableField(
			'HasManyRelations',
			'TableFieldTest_HasManyRelation',
			array(
				'Value' => 'Value'
			),
			array(
				'Value' => 'TextField'
			)
		);
		
		// Test with auto relation setting
		$form = new Form(new TableFieldTest_Controller(), "Form", new FieldSet($tf), new FieldSet());
		$tf->setValue(array(
			'new' => array(
				'Value' => array('one','two',)
			)
		));
		$tf->setRelationAutoSetting(false);
		$o = new TableFieldTest_Object();
		$o->write();
		$form->saveInto($o);
		$this->assertEquals($o->HasManyRelations()->Count(), 0);
	}
	
	function testDataValue() {
		$tf = new TableField(
			'TestTableField',
			'TestTableField',
			array(
				'Currency' => 'Currency'
			),
			array(
				'Currency' => 'CurrencyField'
			)
		);
		$form = new Form(new TableFieldTest_Controller(), "Form", new FieldSet($tf), new FieldSet());
		$tf->setValue(array(
			'new' => array(
				'Currency' => array(
					'$1,234.56',
					'1234.57',
				)
			)
		));
		$data = $form->getData();
		
		// @todo Fix getData()
		//$this->assertEquals($data['TestTableField']['new']['Currency'][0], 1234.56);
		//$this->assertEquals($data['TestTableField']['new']['Currency'][1], 1234.57);
	}

	function testHasItemsWhenSetAsArray() {
		$tf = new TableField(
			'TestTableField',
			'TableFieldTest_HasManyRelation',
			array(
				'Value' => 'Value'
			),
			array(
				'Value' => 'TextField'
			)
		);
		$tf->setValue(array(
			'new' => array(
				'Value' => array(
					'One',
					'Two',
				)
			)
		));
		$items = $tf->Items();
		$itemsArr = $items->toArray();
		
		// includes the two values and an "add" row
		$this->assertEquals($items->Count(), 3);

		// first row
		$this->assertEquals(
			$itemsArr[0]->Fields()->fieldByName('TestTableField[new][Value][]')->Value(), 
			'One'
		);
		
		// second row
		$this->assertEquals(
			$itemsArr[1]->Fields()->fieldByName('TestTableField[new][Value][]')->Value(), 
			'Two'
		);
	}

}

/**
 * Stub controller
 */
class TableFieldTest_Controller extends Controller {
	function Link($action = null) {
		return Controller::join_links('TableFieldTest/', $action);
	}
}
class TableFieldTest_Object extends DataObject implements TestOnly {
	static $has_many = array(
		"HasManyRelations" => 'TableFieldTest_HasManyRelation'
	);
}

class TableFieldTest_HasManyRelation extends DataObject implements TestOnly {
	static $db = array(
		'Value' => 'Text', 
	);
	
	static $has_one = array(
		'HasOneRelation' => 'TableFieldTest_Object'
	);
}