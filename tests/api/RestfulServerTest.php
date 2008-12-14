<?php
/**
 * 
 * @todo Test Relation getters
 * @todo Test filter and limit through GET params
 * @todo Test DELETE verb
 *
 */
class RestfulServerTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/api/RestfulServerTest.yml';

	public function testApiAccess() {
		// normal GET should succeed with $api_access enabled
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 200);
		
		$_SERVER['PHP_AUTH_USER'] = 'user@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'user';
		
		// even with logged in user a GET with $api_access disabled should fail
		$url = "/api/v1/RestfulServerTest_Page/1";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 403);
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
	}
	
	public function testApiAccessBoolean() {
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$response = Director::test($url, null, null, 'GET');
		$this->assertContains('<ID>', $response->getBody());
		$this->assertContains('<Name>', $response->getBody());
		$this->assertContains('<Comment>', $response->getBody());
		$this->assertContains('<Page', $response->getBody());
		$this->assertContains('<Author', $response->getBody());
	}
	
	public function testAuthenticatedGET() {
		// @todo create additional mock object with authenticated VIEW permissions
		$url = "/api/v1/RestfulServerTest_SecretThing/1";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 403);
		
		$_SERVER['PHP_AUTH_USER'] = 'user@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'user';
		
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 200);
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
	}

	public function testAuthenticatedPUT() {
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$data = array('Comment' => 'created');
		
		$response = Director::test($url, $data, null, 'PUT');
		$this->assertEquals($response->getStatusCode(), 403); // Permission failure
		
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		$response = Director::test($url, $data, null, 'PUT');
		$this->assertEquals($response->getStatusCode(), 200); // Success
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
	}
	
	public function testGETRelationshipsXML() {
		$author1 = $this->objFromFixture('RestfulServerTest_Author', 'author1');
		$rating1 = $this->objFromFixture('RestfulServerTest_AuthorRating', 'rating1');
		$rating2 = $this->objFromFixture('RestfulServerTest_AuthorRating', 'rating2');
		
		// @todo should be set up by fixtures, doesn't work for some reason...
		$author1->Ratings()->add($rating1);
		$author1->Ratings()->add($rating2);
		
		$url = "/api/v1/RestfulServerTest_Author/" . $author1->ID;
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 200);

		$responseArr = Convert::xml2array($response->getBody());
		$ratingsArr = $responseArr['Ratings']['RestfulServerTest_AuthorRating'];
		$this->assertEquals(count($ratingsArr), 2);
		$this->assertEquals($ratingsArr[0]['@attributes']['id'], $rating1->ID);
		$this->assertEquals($ratingsArr[1]['@attributes']['id'], $rating2->ID);
	}

	public function testPUTWithFormEncoded() {
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$data = array('Comment' => 'updated');
		$response = Director::test($url, $data, null, 'PUT');
		$this->assertEquals($response->getStatusCode(), 200); // Success
		// Assumption: XML is default output
		$responseArr = Convert::xml2array($response->getBody());
		$this->assertEquals($responseArr['ID'], 1);
		$this->assertEquals($responseArr['Comment'], 'updated');
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
	}

	public function testPOSTWithFormEncoded() {
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		
		$url = "/api/v1/RestfulServerTest_Comment";
		$data = array('Comment' => 'created');
		$response = Director::test($url, $data, null, 'POST');
		$this->assertEquals($response->getStatusCode(), 201); // Created
		// Assumption: XML is default output
		$responseArr = Convert::xml2array($response->getBody());
		$this->assertEquals($responseArr['ID'], 2);
		$this->assertEquals($responseArr['Comment'], 'created');
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
	}
	
	public function testPUTwithJSON() {
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		
		// by mimetype
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$body = '{"Comment":"updated"}';
		$response = Director::test($url, null, null, 'PUT', $body, array('Content-Type'=>'application/json'));
		$this->assertEquals($response->getStatusCode(), 200); // Updated
		$obj = Convert::json2obj($response->getBody());
		$this->assertEquals($obj->ID, 1);
		$this->assertEquals($obj->Comment, 'updated');

		// by extension
		$url = "/api/v1/RestfulServerTest_Comment/1.json";
		$body = '{"Comment":"updated"}';
		$response = Director::test($url, null, null, 'PUT', $body);
		$this->assertEquals($response->getStatusCode(), 200); // Updated
		$obj = Convert::json2obj($response->getBody());
		$this->assertEquals($obj->ID, 1);
		$this->assertEquals($obj->Comment, 'updated');
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
	}
	
	public function testPUTwithXML() {
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		
		// by mimetype
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$body = '<RestfulServerTest_Comment><Comment>updated</Comment></RestfulServerTest_Comment>';
		$response = Director::test($url, null, null, 'PUT', $body, array('Content-Type'=>'text/xml'));
		$this->assertEquals($response->getStatusCode(), 200); // Updated
		$obj = Convert::xml2array($response->getBody());
		$this->assertEquals($obj['ID'], 1);
		$this->assertEquals($obj['Comment'], 'updated');

		// by extension
		$url = "/api/v1/RestfulServerTest_Comment/1.xml";
		$body = '<RestfulServerTest_Comment><Comment>updated</Comment></RestfulServerTest_Comment>';
		$response = Director::test($url, null, null, 'PUT', $body);
		$this->assertEquals($response->getStatusCode(), 200); // Updated
		$obj = Convert::xml2array($response->getBody());
		$this->assertEquals($obj['ID'], 1);
		$this->assertEquals($obj['Comment'], 'updated');
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
	}
		
	public function testHTTPAcceptAndContentType() {
		$url = "/api/v1/RestfulServerTest_Comment/1";
		
		$headers = array('Accept' => 'application/json');
		$response = Director::test($url, null, null, 'GET', null, $headers);
		$this->assertEquals($response->getStatusCode(), 200); // Success
		$obj = Convert::json2obj($response->getBody());
		$this->assertEquals($obj->ID, 1);
		$this->assertEquals($response->getHeader('Content-Type'), 'application/json');
	}

	public function testNotFound(){
		$_SERVER['PHP_AUTH_USER'] = 'user@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'user';
		
		$url = "/api/v1/RestfulServerTest_Comment/99";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 404);
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
	}
	
	public function testMethodNotAllowed() {
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$response = Director::test($url, null, null, 'UNKNOWNHTTPMETHOD');
		$this->assertEquals($response->getStatusCode(), 405);
	}
	
	public function testConflictOnExistingResourceWhenUsingPost() {
		$rating1 = $this->objFromFixture('RestfulServerTest_AuthorRating', 'rating1');
		
		$url = "/api/v1/RestfulServerTest_AuthorRating/" . $rating1->ID;
		$response = Director::test($url, null, null, 'POST');
		$this->assertEquals($response->getStatusCode(), 409);
	}
	
	public function testUnsupportedMediaType() {
		$_SERVER['PHP_AUTH_USER'] = 'user@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'user';

		$url = "/api/v1/RestfulServerTest_Comment";
		$data = "Comment||\/||updated"; // weird format
		$headers = array('Content-Type' => 'text/weirdformat');
		$response = Director::test($url, null, null, 'POST', $data, $headers);
		$this->assertEquals($response->getStatusCode(), 415);
		
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);
	}
	
	public function testXMLValueFormatting() {
		$rating1 = $this->objFromFixture('RestfulServerTest_AuthorRating','rating1');
		
		$url = "/api/v1/RestfulServerTest_AuthorRating/" . $rating1->ID;
		$response = Director::test($url, null, null, 'GET');
		$this->assertContains('<ID>' . $rating1->ID . '</ID>', $response->getBody());
		$this->assertContains('<Rating>' . $rating1->Rating . '</Rating>', $response->getBody());
	}
	
	public function testApiAccessFieldRestrictions() {
		$rating1 = $this->objFromFixture('RestfulServerTest_AuthorRating','rating1');
		
		$url = "/api/v1/RestfulServerTest_AuthorRating/" . $rating1->ID;
		$response = Director::test($url, null, null, 'GET');
		$this->assertContains('<ID>', $response->getBody());
		$this->assertContains('<Rating>', $response->getBody());
		$this->assertContains('<Author', $response->getBody());
		$this->assertNotContains('<SecretField>', $response->getBody());
		$this->assertNotContains('<SecretRelation>', $response->getBody());
		
		$url = "/api/v1/RestfulServerTest_AuthorRating/" . $rating1->ID . '?add_fields=SecretField,SecretRelation';
		$response = Director::test($url, null, null, 'GET');
		$this->assertNotContains('<SecretField>', $response->getBody(),
			'"add_fields" URL parameter filters out disallowed fields from $api_access'
		);
		$this->assertNotContains('<SecretRelation>', $response->getBody(),
			'"add_fields" URL parameter filters out disallowed relations from $api_access'
		);
		
		$url = "/api/v1/RestfulServerTest_AuthorRating/" . $rating1->ID . '?fields=SecretField,SecretRelation';
		$response = Director::test($url, null, null, 'GET');
		$this->assertNotContains('<SecretField>', $response->getBody(),
			'"fields" URL parameter filters out disallowed fields from $api_access'
		);
		$this->assertNotContains('<SecretRelation>', $response->getBody(),
			'"fields" URL parameter filters out disallowed relations from $api_access'
		);
	}
	
	public function testApiAccessWithPUT() {
		$rating1 = $this->objFromFixture('RestfulServerTest_AuthorRating','rating1');
		
		$url = "/api/v1/RestfulServerTest_AuthorRating/" . $rating1->ID;
		$data = array(
			'Rating' => '42',
			'WriteProtectedField' => 'haxx0red'
		);
		$response = Director::test($url, $data, null, 'PUT');
		// Assumption: XML is default output
		$responseArr = Convert::xml2array($response->getBody());
		$this->assertEquals($responseArr['Rating'], 42);
		$this->assertNotEquals($responseArr['WriteProtectedField'], 'haxx0red');
	}
	
	public function testApiAccessWithPOST() {
		$url = "/api/v1/RestfulServerTest_AuthorRating";
		$data = array(
			'Rating' => '42',
			'WriteProtectedField' => 'haxx0red'
		);
		$response = Director::test($url, $data, null, 'POST');
		// Assumption: XML is default output
		$responseArr = Convert::xml2array($response->getBody());
		$this->assertEquals($responseArr['Rating'], 42);
		$this->assertNotEquals($responseArr['WriteProtectedField'], 'haxx0red');
	}
	
}

/**
 * Everybody can view comments, logged in members in the "users" group can create comments,
 * but only "editors" can edit or delete them.
 *
 */
class RestfulServerTest_Comment extends DataObject implements PermissionProvider,TestOnly {
	
	static $api_access = true;
	
	static $db = array(
		"Name" => "Varchar(255)",
		"Comment" => "Text"
	);
	
	static $has_one = array(
		'Page' => 'RestfulServerTest_Page', 
		'Author' => 'RestfulServerTest_Author', 
	);
	
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

class RestfulServerTest_SecretThing extends DataObject implements TestOnly,PermissionProvider{
	static $api_access = true;
	
	static $db = array(
		"Name" => "Varchar(255)",
	);
	
	public function canView($member = null) {
		return Permission::checkMember($member, 'VIEW_SecretThing');
	}
	
	public function providePermissions(){
		return array(
			'VIEW_SecretThing' => 'View Secret Things',
		);
	}
}

class RestfulServerTest_Page extends DataObject implements TestOnly {
	
	static $api_access = false;
	
	static $db = array(
		'Title' => 'Text',	
		'Content' => 'HTMLText',
	);
	
	static $has_many = array(
		'TestComments' => 'RestfulServerTest_Comment'
	);

}

class RestfulServerTest_Author extends DataObject implements TestOnly {
	
	static $api_access = true;
	
	static $db = array(
		'Name' => 'Text',
	);
	
	static $has_many = array(
		'Ratings' => 'RestfulServerTest_AuthorRating', 
	);
	
	public function canView($member = null) {
		return true;
	}
}

class RestfulServerTest_AuthorRating extends DataObject implements TestOnly {
	static $api_access = array(
		'view' => array(
			'Rating',
			'WriteProtectedField',
			'Author'
		),
		'edit' => array(
			'Rating'
		)
	);
	
	static $db = array(
		'Rating' => 'Int',
		'SecretField' => 'Text',
		'WriteProtectedField' => 'Text'
	);
	
	static $has_one = array(
		'Author' => 'RestfulServerTest_Author', 
		'SecretRelation' => 'RestfulServerTest_Author', 
	);
	
	public function canView($member = null) {
		return true;
	}
	
	public function canEdit($member = null) {
		return true;
	}
	
	public function canCreate($member = null) {
		return true;
	}
}
?>