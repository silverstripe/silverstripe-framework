<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ContentControllerPermissionTest extends FunctionalTest {
	
	protected $usesDatabase = true;
	
	protected $autoFollowRedirection = false;
	
	public function testCanViewStage() {
		$page = new Page();
		$page->URLSegment = 'testpage';
		$page->write();
		$page->publish('Stage', 'Live');
		
		$response = $this->get('/testpage');
		$this->assertEquals($response->getStatusCode(), 200);
		
		$response = $this->get('/testpage/?stage=Live');
		$this->assertEquals($response->getStatusCode(), 200);
		
		$response = $this->get('/testpage/?stage=Stage');
		// should redirect to login
		$this->assertEquals($response->getStatusCode(), 302);
		
		$this->logInWithPermission('CMS_ACCESS_CMSMain');
		
		$response = $this->get('/testpage/?stage=Stage');
		$this->assertEquals($response->getStatusCode(), 200);
	}
	
	
}