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

	protected $extraDataObjects = array(
		'SoapModelAccessTest_Comment',
		'SoapModelAccessTest_Page',
	);

	public function getTestSoapConnection() {
		// We can't actually test the SOAP server itself because there's not currently a way of putting it into "test mode"
		return new SOAPModelAccess();
		
		// One day, we should build this facility and then return something more like the item below:
		// return new SoapClient(Director::absoluteBaseURL() . 'soap/v1/wsdl');
	}

	public function testApiAccess() {
		$c = $this->getTestSoapConnection();
		$soapResponse = $c->getXML(
			"SoapModelAccessTest_Comment", 
			1,
			null,
			null,
			'editor@test.com',
			'editor'
		);

		$responseArr = Convert::xml2array($soapResponse);
		$this->assertEquals($responseArr['ID'], 1);
		$this->assertEquals($responseArr['Name'], 'Joe');
	}
	
	public function testAuthenticatedPUT() {
		$comment1 = $this->objFromFixture('SoapModelAccessTest_Comment', 'comment1');
		$comment1ID = $comment1->ID;
		
		// test wrong details
		$c = $this->getTestSoapConnection();

		$updateXML = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
		<SoapModelAccessTest_Comment>
			<ID>$comment1ID</ID>
			<Name>Jimmy</Name>
		</SoapModelAccessTest_Comment>			
XML;

		$soapResponse = $c->putXML(
			"SoapModelAccessTest_Comment", 
			$comment1->ID,
			null,
			$updateXML,
			'editor@test.com',
			'wrongpassword'
		);
		$this->assertEquals('<error type="authentication" code="401">Unauthorized</error>', $soapResponse);
		
		// Check that the details weren't saved
		$c = $this->getTestSoapConnection();
		$soapResponse = $c->getXML("SoapModelAccessTest_Comment", $comment1->ID, null, 'editor@test.com', 'editor');
		$responseArr = Convert::xml2array($soapResponse);
		$this->assertEquals($comment1->ID, $responseArr['ID']);
		$this->assertEquals('Joe', $responseArr['Name']);

		// Now do an update with the right password
		$soapResponse = $c->putXML(
			"SoapModelAccessTest_Comment", 
			$comment1->ID,
			null,
			$updateXML,
			'editor@test.com',
			'editor'
		);

		// Check that the details were saved
		$c = $this->getTestSoapConnection();
		$soapResponse = $c->getXML("SoapModelAccessTest_Comment", $comment1->ID, null, 'editor@test.com', 'editor');
		$responseArr = Convert::xml2array($soapResponse);
		$this->assertEquals($comment1->ID, $responseArr['ID']);
		$this->assertEquals('Jimmy', $responseArr['Name']);
	}
	
	public function testAuthenticatedPOST() {
		/*
		$c = $this->getTestSoapConnection();
		$soapResponse = $c->getXML(
			"SoapModelAccessTest_Comment", 
			null,
			null,
			'editor@test.com',
			'editor'
		);
		Debug::message($soapResponse);
		$responseArr = Convert::xml2array($soapResponse);
		Debug::show($responseArr);
		$this->assertEquals($responseArr['Name'], 'Created Name');
		*/
	}
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