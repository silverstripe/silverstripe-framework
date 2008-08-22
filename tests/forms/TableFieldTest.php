<?php

class TableFieldTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/forms/TableFieldTest.yml';
	
	function testTableFieldSaving() {
		$group = $this->objFromFixture('Group','a');
		
		$tableField = new TableField(
			"Permissions",
			"Permission",
			array(
			        "Code" => _t('SecurityAdmin.CODE', 'Code'),
			        "Arg" => _t('SecurityAdmin.OPTIONALID', 'Optional ID'),
			),
			array(
				"Code" => "PermissionDropdownField",
				"Arg" => "TextField",
			),
			"GroupID",
			$group->ID
		);
		$form = new Form(new TableFieldTest_Controller(), "Form", new FieldSet($tableField), new FieldSet());
		
		/* The field starts emppty.  Save some new data.  We have replicated the array structure that the specific layout of the form generates. */
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

		/* Let's check that the 2 permissions entries have been saved */
		$permissions = $group->Permissions()->toDropdownMap('Arg', 'Code');
		$this->assertEquals(array(
			1 => 'CMS_ACCESS_CMSMain',
			2 => 'CMS_ACCESS_AssetAdmin',
		), $permissions);
		

		/* Now let's perform an update query */
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

		/* Let's check that the 2 existing permissions entries, and the 1 new one, have been saved */
		$permissions = $group->Permissions()->toDropdownMap('Arg', 'Code');
		$this->assertEquals(array(
			1 => 'CMS_ACCESS_CMSMain',
			2 => 'CMS_ACCESS_AssetAdmin',
			3 => 'CMS_ACCESS_NewsletterAdmin',
		), $permissions);

	}
}

/**
 * Stub controller
 */
class TableFieldTest_Controller extends Controller {
	function Link() {
		return 'TableFieldTest/';
	}
}