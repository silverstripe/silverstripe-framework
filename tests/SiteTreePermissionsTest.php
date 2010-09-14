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
	
	protected $illegalExtensions = array(
		'SiteTree' => array('SiteTreeSubsites')
	);
	
	static function set_up_once() {
		SiteTreeTest::set_up_once();

		parent::set_up_once();
	}
	
	static function tear_down_once() {
		SiteTreeTest::tear_down_once();
		
		parent::tear_down_once();
	}
	
	function setUp() {
		parent::setUp();
		
		$this->useDraftSite();
		
		// we're testing HTTP status codes before being redirected to login forms
		$this->autoFollowRedirection = false;
	}

	
	function testAccessingStageWithBlankStage() {
		$this->useDraftSite(false);
		$this->autoFollowRedirection = false;
		
		$page = $this->objFromFixture('Page', 'draftOnlyPage');

		if($member = Member::currentUser()) {
			$member->logOut();
		}
		
		$response = $this->get($page->URLSegment . '?stage=Live');
		$this->assertEquals($response->getStatusCode(), '404');
		
		$response = $this->get($page->URLSegment . '?stage=');
		$this->assertEquals($response->getStatusCode(), '404');
		
		// should be prompted for a login
		$response = $this->get($page->URLSegment . '?stage=Stage');
		$this->assertEquals($response->getStatusCode(), '302');
		
		$this->logInWithPermission('ADMIN');
		
		$response = $this->get($page->URLSegment . '?stage=Live');
		$this->assertEquals($response->getStatusCode(), '404');
		
		$response = $this->get($page->URLSegment . '?stage=Stage');
		$this->assertEquals($response->getStatusCode(), '200');
		
		$response = $this->get($page->URLSegment . '?stage=');
		$this->assertEquals($response->getStatusCode(), '404');
	}
	
	function testPermissionCheckingWorksOnDeletedPages() {
		// Set up fixture - a published page deleted from draft
		$this->logInWithPermission("ADMIN");
		$page = $this->objFromFixture('Page','restrictedEditOnlySubadminGroup');
		$pageID = $page->ID;
		$this->assertTrue($page->doPublish());
		$page->delete();

		// Re-fetch the page from the live site
 		$page = Versioned::get_one_by_stage('SiteTree', 'Live', "\"SiteTree\".\"ID\" = $pageID");

		// subadmin has edit rights on that page
		$member = $this->objFromFixture('Member','subadmin');
		$member->logIn();
		
		// Test can_edit_multiple
		$this->assertEquals(
			array($pageID => true),
			SiteTree::can_edit_multiple(array($pageID), $member->ID)
		);
		
		// Test canEdit
		$member->logIn();
		$this->assertTrue($page->canEdit());
	}
	
	function testPermissionCheckingWorksOnUnpublishedPages() {
		// Set up fixture - an unpublished page
		$this->logInWithPermission("ADMIN");
		$page = $this->objFromFixture('Page','restrictedEditOnlySubadminGroup');
		$pageID = $page->ID;
		$page->doUnpublish();

		// subadmin has edit rights on that page
		$member = $this->objFromFixture('Member','subadmin');
		$member->logIn();

		// Test can_edit_multiple
		$this->assertEquals(
			array($pageID => true),
			SiteTree::can_edit_multiple(array($pageID), $member->ID)
		);

		// Test canEdit
		$member->logIn();
		$this->assertTrue($page->canEdit());
	}

	function testCanEditOnPageDeletedFromStageAndLiveReturnsFalse() {
		// Find a page that exists and delete it from both stage and published
		$this->logInWithPermission("ADMIN");
		$page = $this->objFromFixture('Page','restrictedEditOnlySubadminGroup');
		$pageID = $page->ID;
		$page->doUnpublish();
		$page->delete();

		// We'll need to resurrect the page from the version cache to test this case
		$page = Versioned::get_latest_version('SiteTree', $pageID);

		// subadmin had edit rights on that page, but now it's gone
		$member = $this->objFromFixture('Member','subadmin');
		$member->logIn();
		
		$this->assertFalse($page->canEdit());
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
		$response = $this->get($page->RelativeLink());
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
		$response = $this->get($page->RelativeLink());
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
		$response = $this->get($page->RelativeLink());
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
		$response = $this->get($page->RelativeLink());
		$this->assertEquals(
			$response->getStatusCode(),
			403,
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
		$response = $this->get($page->RelativeLink());
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
		$response = $this->get($childPage->RelativeLink());
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
		$response = $this->get($childPage->RelativeLink());
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
	
	function testRestrictedEditLoggedInUsersDeletedFromStage() {
		$page = $this->objFromFixture('Page', 'restrictedEditLoggedInUsers');
		$pageID = $page->ID;
		
		$this->logInWithPermission("ADMIN");
		
		$page->doPublish();
		$page->deleteFromStage('Stage');

		// Get the live version of the page
		$page = Versioned::get_one_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" = $pageID");
		$this->assertTrue(is_object($page), 'Versioned::get_one_by_stage() is returning an object');

		// subadmin users
		$subadminuser = $this->objFromFixture('Member', 'subadmin');
		$this->assertTrue(
			$page->canEdit($subadminuser),
			'Authenticated members can edit a page that was deleted from stage and marked as "Editable by logged in users" if they have cms permissions and belong to any of these groups'
		);
	}

	function testInheritCanViewFromSiteConfig() {
		$page = $this->objFromFixture('Page', 'inheritWithNoParent');
		$siteconfig = $this->objFromFixture('SiteConfig', 'default');
		$editor = $this->objFromFixture('Member', 'editor');
		$editorGroup = $this->objFromFixture('Group', 'editorgroup');
		
		$siteconfig->CanViewType = 'Anyone';
		$siteconfig->write();
		$this->assertTrue($page->canView(FALSE), 'Anyone can view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to LoggedInUsers');
		
		$siteconfig->CanViewType = 'LoggedInUsers';
		$siteconfig->write();
		$this->assertFalse($page->canView(FALSE), 'Anonymous can\'t view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to LoggedInUsers');
		
		$siteconfig->CanViewType = 'LoggedInUsers';
		$siteconfig->write();
		$this->assertTrue($page->canView($editor), 'Users can view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to LoggedInUsers');
		
		$siteconfig->CanViewType = 'OnlyTheseUsers';
		$siteconfig->ViewerGroups()->add($editorGroup);
		$siteconfig->ViewerGroups()->write();
		$siteconfig->write();
		$this->assertTrue($page->canView($editor), 'Editors can view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to OnlyTheseUsers');
		$this->assertFalse($page->canView(FALSE), 'Anonymous can\'t view a page when set to inherit from the SiteConfig, and SiteConfig has canView set to OnlyTheseUsers');
	}
	
	function testInheritCanEditFromSiteConfig() {
		$page = $this->objFromFixture('Page', 'inheritWithNoParent');
		$siteconfig = $this->objFromFixture('SiteConfig', 'default');
		$editor = $this->objFromFixture('Member', 'editor');
		$user = $this->objFromFixture('Member', 'websiteuser');
		$editorGroup = $this->objFromFixture('Group', 'editorgroup');
		
		$siteconfig->CanEditType = 'LoggedInUsers';
		$siteconfig->write();
		
		$this->assertFalse($page->canEdit(FALSE), 'Anonymous can\'t edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to LoggedInUsers');
		$this->session()->inst_set('loggedInAs', $editor->ID);
		$this->assertTrue($page->canEdit(), 'Users can edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to LoggedInUsers');
		
		$siteconfig->CanEditType = 'OnlyTheseUsers';
		$siteconfig->EditorGroups()->add($editorGroup);
		$siteconfig->EditorGroups()->write();
		$siteconfig->write();
		$this->assertTrue($page->canEdit($editor), 'Editors can edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to OnlyTheseUsers');
		$this->session()->inst_set('loggedInAs', null);
		$this->assertFalse($page->canEdit(FALSE), 'Anonymous can\'t edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to OnlyTheseUsers');
		$this->session()->inst_set('loggedInAs', $user->ID);
		$this->assertFalse($page->canEdit($user), 'Website user can\'t edit a page when set to inherit from the SiteConfig, and SiteConfig has canEdit set to OnlyTheseUsers');
	}
	
}
?>