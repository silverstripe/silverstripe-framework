<?php
/**
 * @package sapphire
 * @subpackage tests
 * 
 * @todo Test canAddChildren()
 * @todo Test canCreate()
 */
class SiteTreePermissionsTest extends SapphireTest {
	static $fixture_file = "sapphire/tests/SiteTreePermissionsTest.yml";
	
	function testRestrictedViewLoggedInUsers() {
		$page = $this->objFromFixture('Page', 'restrictedViewLoggedInUsers');
		
		/*
		 NOTE: This isn't correct.  An "unauthed member" test needs to be done by setting the loggedInAs data in the session
		 to zero and then confirming that a 403 response is returned.  Alternatively, the canView() method needs a well-defined
		 way of asking "can a person who isn't logged in view this?" perhaps by passing the integer value 0 to the canView() method
		 as opposed to leaving it omitted, which uses Member::currentUser() as the default.
		$randomUnauthedMember = new Member();
		$randomUnauthedMember->ID = 99;
		$this->assertFalse(
			$page->canView($randomUnauthedMember),
			'Unauthenticated members cant view a page marked as "Viewable for any logged in users"'
		);
		 */
		
		$websiteuser = $this->objFromFixture('Member', 'websiteuser');
		$websiteuser->logIn();
		$this->assertTrue(
			$page->canView($websiteuser),
			'Authenticated members can view a page marked as "Viewable for any logged in users" even if they dont have access to the CMS'
		);
		
		$websiteuser->logOut();
	}
	
	function testRestrictedViewOnlyTheseUsers() {
		$page = $this->objFromFixture('Page', 'restrictedViewOnlyWebsiteUsers');
		
		$randomUnauthedMember = new Member();
		$randomUnauthedMember->ID = 99;
		$this->assertFalse(
			$page->canView($randomUnauthedMember),
			'Unauthenticated members cant view a page marked as "Viewable by these groups"'
		);
		
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertFalse(
			$page->canView($subadminuser),
			'Authenticated members cant view a page marked as "Viewable by these groups" if theyre not in the listed groups'
		);
		
		$websiteuser = $this->objFromFixture('Member', 'websiteuser');
		$this->assertTrue(
			$page->canView($websiteuser),
			'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups'
		);
	}
	
	function testRestrictedEditLoggedInUsers() {
		$page = $this->objFromFixture('Page', 'restrictedEditLoggedInUsers');
		
		$randomUnauthedMember = new Member();
		$randomUnauthedMember->ID = 99;
		$this->assertFalse(
			$page->canEdit($randomUnauthedMember),
			'Unauthenticated members cant edit a page marked as "Editable by logged in users"'
		);
		
		$websiteuser = $this->objFromFixture('Member', 'websiteuser');
		$websiteuser->logIn();
		$this->assertFalse(
			$page->canEdit($websiteuser),
			'Authenticated members cant edit a page marked as "Editable by logged in users" if they dont have cms permissions'
		);
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertTrue(
			$page->canEdit($subadminuser),
			'Authenticated members can edit a page marked as "Editable by logged in users" if they have cms permissions and belong to any of these groups'
		);
		
		$websiteuser->logOut();
	}
	
	function testRestrictedEditOnlySubadminGroup() {
		$page = $this->objFromFixture('Page', 'restrictedEditOnlySubadminGroup');
		
		$randomUnauthedMember = new Member();
		$randomUnauthedMember->ID = 99;
		$this->assertFalse(
			$page->canEdit($randomUnauthedMember),
			'Unauthenticated members cant edit a page marked as "Editable by these groups"'
		);
		
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertTrue(
			$page->canEdit($subadminuser),
			'Authenticated members can view a page marked as "Editable by these groups" if theyre in the listed groups'
		);
		
		$websiteuser = $this->objFromFixture('Member', 'websiteuser');
		$websiteuser->logIn();
		$this->assertFalse(
			$page->canEdit($websiteuser),
			'Authenticated members cant edit a page marked as "Editable by these groups" if theyre not in the listed groups'
		);
		
		$websiteuser->logOut();
	}
	
	function testRestrictedViewInheritance() {
		$parentPage = $this->objFromFixture('Page', 'parent_restrictedViewOnlySubadminGroup');
		$childPage = $this->objFromFixture('Page', 'child_restrictedViewOnlySubadminGroup');

		$randomUnauthedMember = new Member();
		$randomUnauthedMember->ID = 99;
		$this->assertFalse(
			$childPage->canView($randomUnauthedMember),
			'Unauthenticated members cant view a page marked as "Viewable by these groups" by inherited permission'
		);

		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertTrue(
			$childPage->canView($subadminuser),
			'Authenticated members can view a page marked as "Viewable by these groups" if theyre in the listed groups by inherited permission'
		);
	}
	
	function testRestrictedEditInheritance() {
		$parentPage = $this->objFromFixture('Page', 'parent_restrictedEditOnlySubadminGroup');
		$childPage = $this->objFromFixture('Page', 'child_restrictedEditOnlySubadminGroup');

		$randomUnauthedMember = new Member();
		$randomUnauthedMember->ID = 99;
		$this->assertFalse(
			$childPage->canEdit($randomUnauthedMember),
			'Unauthenticated members cant edit a page marked as "Editable by these groups" by inherited permission'
		);

		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertTrue(
			$childPage->canEdit($subadminuser),
			'Authenticated members can edit a page marked as "Editable by these groups" if theyre in the listed groups by inherited permission'
		);
	}
	
	function testDeleteRestrictedChild() {
		$parentPage = $this->objFromFixture('Page', 'deleteTestParentPage');
		$childPage = $this->objFromFixture('Page', 'deleteTestChildPage');

		$randomUnauthedMember = new Member();
		$randomUnauthedMember->ID = 99;
		$this->assertFalse(
			$parentPage->canDelete($randomUnauthedMember),
			'Unauthenticated members cant delete a page if it doesnt have delete permissions on any of its descendants'
		);
		$this->assertFalse(
			$childPage->canDelete($randomUnauthedMember),
			'Unauthenticated members cant delete a child page marked as "Editable by these groups"'
		);
	}

}
?>