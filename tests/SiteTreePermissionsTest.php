<?php
/**
 * @package sapphire
 * @subpackage tests
 * 
 * @todo Test canAddChildren()
 * @todo Test canCreate()
 */
class SiteTreePermissionsTest extends FunctionalTest {
	static $fixture_file = "sapphire/tests/SiteTreePermissionsTest.yml";
	
	function setUp() {
		parent::setUp();
		
		$this->useDraftSite();
		
		// we're testing HTTP status codes before being redirected to login forms
		$this->autoFollowRedirection = false;
	}
	
	function testAccessTabOnlyDisplaysWithGrantAccessPermissions() {
		$page = $this->objFromFixture('Page', 'standardpage');
		
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->session()->inst_set('loggedInAs', $subadminuser->ID);
		$fields = $page->getCMSFields();
		$this->assertFalse(
			$fields->dataFieldByName('CanViewType')->isReadonly(),
			'Users with SITETREE_GRANT_ACCESS permission can change "view" permissions in cms fields'
		);
		$this->assertFalse(
			$fields->dataFieldByName('CanEditType')->isReadonly(),
			'Users with SITETREE_GRANT_ACCESS permission can change "edit" permissions in cms fields'
		);
		
		$editoruser = $this->objFromFixture('Member', 'editor');
		$this->session()->inst_set('loggedInAs', $editoruser->ID);
		$fields = $page->getCMSFields();
		$this->assertTrue(
			$fields->dataFieldByName('CanViewType')->isReadonly(),
			'Users without SITETREE_GRANT_ACCESS permission cannot change "view" permissions in cms fields'
		);
		$this->assertTrue(
			$fields->dataFieldByName('CanEditType')->isReadonly(),
			'Users without SITETREE_GRANT_ACCESS permission cannot change "edit" permissions in cms fields'
		);
		
		$this->session()->inst_set('loggedInAs', null);
	}
	
	function testRestrictedViewLoggedInUsers() {
		$page = $this->objFromFixture('Page', 'restrictedViewLoggedInUsers');
		
		// unauthenticated users
		$this->assertFalse(
			$page->canView(FALSE),
			'Unauthenticated members cant view a page marked as "Viewable for any logged in users"'
		);
		$this->session()->inst_set('loggedInAs', null);
		$response = $this->get($page->URLSegment);
		$this->assertEquals(
			$response->getStatusCode(),
			302,
			'Unauthenticated members cant view a page marked as "Viewable for any logged in users"'
		);

		// website users
		$websiteuser = $this->objFromFixture('Member', 'websiteuser');
		$this->assertTrue(
			$page->canView($websiteuser),
			'Authenticated members can view a page marked as "Viewable for any logged in users" even if they dont have access to the CMS'
		);
		$this->session()->inst_set('loggedInAs', $websiteuser->ID);
		$response = $this->get($page->URLSegment);
		$this->assertEquals(
			$response->getStatusCode(),
			200,
			'Authenticated members can view a page marked as "Viewable for any logged in users" even if they dont have access to the CMS'
		);
		$this->session()->inst_set('loggedInAs', null);
	}
	
	function testRestrictedViewOnlyTheseUsers() {
		$page = $this->objFromFixture('Page', 'restrictedViewOnlyWebsiteUsers');
		
		// unauthenticcated users
		$this->assertFalse(
			$page->canView(FALSE),
			'Unauthenticated members cant view a page marked as "Viewable by these groups"'
		);
		$this->session()->inst_set('loggedInAs', null);
		$response = $this->get($page->URLSegment);
		$this->assertEquals(
			$response->getStatusCode(),
			302,
			'Unauthenticated members cant view a page marked as "Viewable by these groups"'
		);
		
		// subadmin users
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertFalse(
			$page->canView($subadminuser),
			'Authenticated members cant view a page marked as "Viewable by these groups" if theyre not in the listed groups'
		);
		$this->session()->inst_set('loggedInAs', $subadminuser->ID);
		$response = $this->get($page->URLSegment);
		$this->assertEquals(
			$response->getStatusCode(),
			302,
			'Authenticated members cant view a page marked as "Viewable by these groups" if theyre not in the listed groups'
		);
		$this->session()->inst_set('loggedInAs', null);
		
		// website users
		$websiteuser = $this->objFromFixture('Member', 'websiteuser');
		$this->assertTrue(
			$page->canView($websiteuser),
			'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups'
		);
		$this->session()->inst_set('loggedInAs', $websiteuser->ID);
		$response = $this->get($page->URLSegment);
		$this->assertEquals(
			$response->getStatusCode(),
			200,
			'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups'
		);
		$this->session()->inst_set('loggedInAs', null);
	}
	
	function testRestrictedEditLoggedInUsers() {
		$page = $this->objFromFixture('Page', 'restrictedEditLoggedInUsers');
		
		// unauthenticcated users
		$this->assertFalse(
			$page->canEdit(FALSE),
			'Unauthenticated members cant edit a page marked as "Editable by logged in users"'
		);
		
		// website users
		$websiteuser = $this->objFromFixture('Member', 'websiteuser');
		$websiteuser->logIn();
		$this->assertFalse(
			$page->canEdit($websiteuser),
			'Authenticated members cant edit a page marked as "Editable by logged in users" if they dont have cms permissions'
		);
		
		// subadmin users
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertTrue(
			$page->canEdit($subadminuser),
			'Authenticated members can edit a page marked as "Editable by logged in users" if they have cms permissions and belong to any of these groups'
		);
	}
	
	function testRestrictedEditOnlySubadminGroup() {
		$page = $this->objFromFixture('Page', 'restrictedEditOnlySubadminGroup');
		
		// unauthenticated users
		$this->assertFalse(
			$page->canEdit(FALSE),
			'Unauthenticated members cant edit a page marked as "Editable by these groups"'
		);
		
		// subadmin users
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertTrue(
			$page->canEdit($subadminuser),
			'Authenticated members can view a page marked as "Editable by these groups" if theyre in the listed groups'
		);
		
		// website users
		$websiteuser = $this->objFromFixture('Member', 'websiteuser');
		$this->assertFalse(
			$page->canEdit($websiteuser),
			'Authenticated members cant edit a page marked as "Editable by these groups" if theyre not in the listed groups'
		);
	}
	
	function testRestrictedViewInheritance() {
		$parentPage = $this->objFromFixture('Page', 'parent_restrictedViewOnlySubadminGroup');
		$childPage = $this->objFromFixture('Page', 'child_restrictedViewOnlySubadminGroup');

		// unauthenticated users
		$this->assertFalse(
			$childPage->canView(FALSE),
			'Unauthenticated members cant view a page marked as "Viewable by these groups" by inherited permission'
		);
		$this->session()->inst_set('loggedInAs', null);
		$response = $this->get($childPage->URLSegment);
		$this->assertEquals(
			$response->getStatusCode(),
			302,
			'Unauthenticated members cant view a page marked as "Viewable by these groups" by inherited permission'
		);

		// subadmin users
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertTrue(
			$childPage->canView($subadminuser),
			'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups by inherited permission'
		);
		$this->session()->inst_set('loggedInAs', $subadminuser->ID);
		$response = $this->get($childPage->URLSegment);
		$this->assertEquals(
			$response->getStatusCode(),
			200,
			'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups by inherited permission'
		);
		$this->session()->inst_set('loggedInAs', null);
	}
	
	function testRestrictedEditInheritance() {
		$parentPage = $this->objFromFixture('Page', 'parent_restrictedEditOnlySubadminGroup');
		$childPage = $this->objFromFixture('Page', 'child_restrictedEditOnlySubadminGroup');

		// unauthenticated users
		$this->assertFalse(
			$childPage->canEdit(FALSE),
			'Unauthenticated members cant edit a page marked as "Editable by these groups" by inherited permission'
		);

		// subadmin users
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertTrue(
			$childPage->canEdit($subadminuser),
			'Authenticated members can edit a page marked as "Editable by these groups" if theyre in the listed groups by inherited permission'
		);
	}
	
	function testDeleteRestrictedChild() {
		$parentPage = $this->objFromFixture('Page', 'deleteTestParentPage');
		$childPage = $this->objFromFixture('Page', 'deleteTestChildPage');

		// unauthenticated users
		$this->assertFalse(
			$parentPage->canDelete(FALSE),
			'Unauthenticated members cant delete a page if it doesnt have delete permissions on any of its descendants'
		);
		$this->assertFalse(
			$childPage->canDelete(FALSE),
			'Unauthenticated members cant delete a child page marked as "Editable by these groups"'
		);
	}

}
?>