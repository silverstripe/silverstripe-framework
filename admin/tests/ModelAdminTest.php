<?php

class ModelAdminTest extends FunctionalTest {
	static $fixture_file = 'sapphire/admin/tests/ModelAdminTest.yml';
	
	protected $extraDataObjects = array(
		'ModelAdminTest_Admin',
		'ModelAdminTest_Contact',
	);
	
	function testModelAdminOpens() {
		$this->autoFollowRedirection = false;
		$this->logInAs('admin');
		$this->assertTrue((bool)Permission::check("ADMIN"));
		$this->assertEquals(200, $this->get('ModelAdminTest_Admin')->getStatusCode());
	}
}

class ModelAdminTest_Admin extends ModelAdmin implements TestOnly {
	static $url_segment = 'testadmin';
	
	public static $managed_models = array(
		'ModelAdminTest_Contact',
	);
}

class ModelAdminTest_Contact extends DataObject implements TestOnly {
	static $db = array(
		"Name" => "Varchar",
		"Phone" => "Varchar",
	);
}