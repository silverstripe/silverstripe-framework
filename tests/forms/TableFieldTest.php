<?php

class TableFieldTest extends SapphireTest {
	static $fixture_file = 'TableFieldTest.yml';

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
			new FieldList($tableField), 
			new FieldList()
		);
		
		// Test Insert
		
		// The field starts emppty.  Save some new data.  
		// We have replicated the array structure that the specific layout of the form generates.
		$tableField->setValue(array(
			'new' => array(
				'Code' => array(
					'CustomPerm1',
					'CustomPerm2',
				),
				'Arg' => array(
					'1',
					'2'
				),
			),			
		));
		$tableField->saveInto($group);

		// Let's check that the 2 permissions entries have been saved
		$permissions = $group->Permissions()->map('Arg', 'Code');
		$this->assertEquals(array(
			1 => 'CustomPerm1',
			2 => 'CustomPerm2',
		), $permissions->toArray());
		

		// Test repeated insert
		$value = array();
		foreach($group->Permissions() as $permission) {
			$value[$permission->ID] = array("Code" => $permission->Code, "Arg" => $permission->Arg);
		}
		$value['new'] = array(
			'Code' => array(
				'CustomPerm3',
			),
			'Arg' => array(
				'3',
			),
		);
		$tableField->setValue($value);
		$tableField->saveInto($group);

		// Let's check that the 2 existing permissions entries, and the 1 new one, have been saved
		$permissions = $group->Permissions()->map('Arg', 'Code');
		$this->assertEquals(array(
			1 => 'CustomPerm1',
			2 => 'CustomPerm2',
			3 => 'CustomPerm3',
		), $permissions->toArray());

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
			new FieldList($tableField), 
			new FieldList()
		);
		
		$this->assertEquals(2, $tableField->sourceItems()->Count());
		
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
		$permissions = $group->Permissions()->map('Arg', 'Code');
		$this->assertEquals(array(
			101 => 'Perm1 Modified',
			102 => 'Perm2 Modified',
		), $permissions->toArray());
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
			new FieldList($tableField), 
			new FieldList()
		);
		
		$this->assertContains($perm1->ID, $tableField->sourceItems()->column('ID'));
		
		$response = $tableField->Items()->find('ID', $perm1->ID)->delete();
		
		$this->assertNotContains($perm1->ID, $tableField->sourceItems()->column('ID'));
	}

	/**
	 * Relation auto-setting is now the only option
	 */
	function testAutoRelationSettingOn() {
		$o = new TableFieldTest_Object();
		$o->write();

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
		$form = new Form(new TableFieldTest_Controller(), "Form", new FieldList($tf), new FieldList());
		$form->loadDataFrom($o);
		
		$tf->setValue(array(
			'new' => array(
				'Value' => array('one','two',)
			)
		));
		
		$form->saveInto($o);
		$this->assertEquals(2, $o->HasManyRelations()->Count());
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
class TableFieldTest_Controller extends Controller implements TestOnly {
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
