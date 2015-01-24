<?php

class ModelAdminTest extends FunctionalTest {
	protected static $fixture_file = 'ModelAdminTest.yml';

	protected $extraDataObjects = array(
		'ModelAdminTest_Admin',
		'ModelAdminTest_Contact',
		'ModelAdminTest_Player'
	);

	public function testModelAdminOpens() {
		$this->autoFollowRedirection = false;
		$this->logInAs('admin');
		$this->assertTrue((bool)Permission::check("ADMIN"));
		$this->assertEquals(200, $this->get('ModelAdminTest_Admin')->getStatusCode());
	}

	public function testExportFieldsDefaultIsSummaryFields() {
		$admin = new ModelAdminTest_Admin();
		$admin->modelClass = 'ModelAdminTest_Contact';
		$this->assertEquals($admin->getExportFields(), singleton('ModelAdminTest_Contact')->summaryFields());
	}

	public function testExportFieldsOverloadedMethod() {
		$admin = new ModelAdminTest_PlayerAdmin();
		$admin->modelClass = 'ModelAdminTest_Player';
		$this->assertEquals($admin->getExportFields(), array(
			'Name' => 'Name',
			'Position' => 'Position'
		));
	}

}

class ModelAdminTest_Admin extends ModelAdmin implements TestOnly {
	private static $url_segment = 'testadmin';

	private static $managed_models = array(
		'ModelAdminTest_Contact',
	);
}
class ModelAdminTest_PlayerAdmin extends ModelAdmin implements TestOnly {
	private static $url_segment = 'testadmin';

	private static $managed_models = array(
		'ModelAdminTest_Player'
	);

	public function getExportFields() {
		return array(
			'Name' => 'Name',
			'Position' => 'Position'
		);
	}
}
class ModelAdminTest_Contact extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar',
		'Phone' => 'Varchar',
	);
	private static $summary_fields = array(
		'Name' => 'Name',
		'Phone' => 'Phone'
	);
}
class ModelAdminTest_Player extends DataObject implements TestOnly {
	private static $db = array(
		'Name' => 'Varchar',
		'Position' => 'Varchar',
	);
	private static $has_one = array(
		'Contact' => 'ModelAdminTest_Contact'
	);
}
