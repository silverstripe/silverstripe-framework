<?php
/**
 * 
 * @todo Test Relation getters
 * @todo Test filter and limit through GET params
 * @todo Test DELETE verb
 *
 * @package sapphire
 * @subpackage testing
 */
class SoapModelAccessTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/SoapModelAccessTest.yml';
/*
	public function testApiAccess() {
		$c = new SoapClient(Director::absoluteBaseURL() . 'soap/v1/wsdl');
		$soapResponse = $c->getXML(
			"SoapModelAccessTest_Comment", 
			1,
			null,
			null,
			'editor@test.com',
			'editor'
		);
		var_dump($soapResponse);
		die();
		$responseArr = Convert::xml2array($soapResponse);
		$this->assertEquals($responseArr['ID'], 1);
		$this->assertEquals($responseArr['Name'], 'Joe');
	}
	
	public function testAuthenticatedPUT() {
		// test wrong details
		$c = new SoapClient(Director::absoluteBaseURL() . 'soap/v1/wsdl');
		$soapResponse = $c->getXML(
			"SoapModelAccessTest_Comment", 
			1,
			null,
			array(
				'Name' => 'Updated Name'
			),
			'editor@test.com',
			'wrongpassword'
		);
		$this->assertEquals(
			$soapResponse,
			'<error type="authentication" code="403">Forbidden</error>'
		);
		
		// test correct details
		$c = new SoapClient(Director::absoluteBaseURL() . 'soap/v1/wsdl');
		$soapResponse = $c->getXML(
			"SoapModelAccessTest_Comment", 
			1,
			null,
			array(
				'Name' => 'Updated Name'
			),
			'editor@test.com',
			'editor'
		);
		$responseArr = Convert::xml2array($soapResponse);
		$this->assertEquals($responseArr['ID'], 1);
		$this->assertEquals($responseArr['Name'], 'Updated Name');
	}
	
	public function testAuthenticatedPOST() {
		$c = new SoapClient(Director::absoluteBaseURL() . 'soap/v1/wsdl');
		$soapResponse = $c->getXML(
			"SoapModelAccessTest_Comment", 
			null,
			null,
			array(
				'Name' => 'Created Name'
			),
			'editor@test.com',
			'editor'
		);
		$responseArr = Convert::xml2array($soapResponse);
		$this->assertEquals($responseArr['Name'], 'Created Name');
	}
	*/
		
}

/**
 * Everybody can view comments, logged in members in the "users" group can create comments,
 * but only "editors" can edit or delete them.
 *
 */
class SoapModelAccessTest_Comment extends DataObject implements PermissionProvider,TestOnly {
	
	static $api_access = true;
	
	static $db = array(
		"Name" => "Varchar(255)",
		"Comment" => "Text"
	);
	
	static $has_many = array();
	
	public function providePermissions(){
		return array(
			'EDIT_Comment' => 'Edit Comment Objects',
			'CREATE_Comment' => 'Create Comment Objects',
			'DELETE_Comment' => 'Delete Comment Objects',
		);
	}
	
	public function canView($member = null) {
		return true;
	}
	
	public function canEdit($member = null) {
		return Permission::checkMember($member, 'EDIT_Comment');
	}
	
	public function canDelete($member = null) {
		return Permission::checkMember($member, 'DELETE_Comment');
	}
	
	public function canCreate($member = null) {
		return Permission::checkMember($member, 'CREATE_Comment');
	}
	
}

class SoapModelAccessTest_Page extends DataObject implements TestOnly {
	
	static $api_access = false;
	
	static $db = array(
		'Title' => 'Text',	
		'Content' => 'HTMLText',
	);
}
?>