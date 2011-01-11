<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SiteTreeTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/SiteTreeTest.yml';
	
	protected $illegalExtensions = array(
		'SiteTree' => array('SiteTreeSubsites')
	);
	
	/**
	 * @todo Necessary because of monolithic Translatable design
	 */
	static protected $origTranslatableSettings = array();
	
	static function set_up_once() {
		// needs to recreate the database schema with language properties
		self::kill_temp_db();

		// store old defaults	
		self::$origTranslatableSettings['has_extension'] = singleton('SiteTree')->hasExtension('Translatable');
		self::$origTranslatableSettings['default_locale'] = Translatable::default_locale();

		// overwrite locale
		Translatable::set_default_locale("en_US");

		// refresh the decorated statics - different fields in $db with Translatable enabled
		if(self::$origTranslatableSettings['has_extension']) {
			Object::remove_extension('SiteTree', 'Translatable');
			Object::remove_extension('SiteConfig', 'Translatable');
		}

		// recreate database with new settings
		$dbname = self::create_temp_db();
		DB::set_alternative_database_name($dbname);

		parent::set_up_once();
	}
	
	static function tear_down_once() {
		if(self::$origTranslatableSettings['has_extension']) {
			Object::add_extension('SiteTree', 'Translatable');
			Object::add_extension('SiteConfig', 'Translatable');
		}
			

		Translatable::set_default_locale(self::$origTranslatableSettings['default_locale']);
		Translatable::set_current_locale(self::$origTranslatableSettings['default_locale']);
		
		self::kill_temp_db();
		self::create_temp_db();
		
		parent::tear_down_once();
	}
	
	function testCreateDefaultpages() {
			$remove = DataObject::get('SiteTree');
			if($remove) foreach($remove as $page) $page->delete();
			// Make sure the table is empty
			$this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 0);
			
			// Disable the creation
			SiteTree::set_create_default_pages(false);
			singleton('SiteTree')->requireDefaultRecords();
			
			// The table should still be empty
			$this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 0);
			
			// Enable the creation
			SiteTree::set_create_default_pages(true);
			singleton('SiteTree')->requireDefaultRecords();
			
			// The table should now have three rows (home, about-us, contact-us)
			$this->assertEquals(DB::query('SELECT COUNT("ID") FROM "SiteTree"')->value(), 3);
	}

	/**
	 * Test generation of the URLSegment values.
	 *  - Turns things into lowercase-hyphen-format
	 *  - Generates from Title by default, unless URLSegment is explicitly set
	 *  - Resolves duplicates by appending a number
	 *  - renames classes with a class name conflict
	 */
	function testURLGeneration() {
		$expectedURLs = array(
			'home' => 'home',
			'staff' => 'my-staff',
			'about' => 'about-us',
			'staffduplicate' => 'my-staff-2',
			'product1' => '1-1-test-product',
			'product2' => 'another-product',
			'product3' => 'another-product-2',
			'product4' => 'another-product-3',
			'object'   => 'object',
			'controller' => 'controller-2',
			'numericonly' => '1930',
		);
		
		foreach($expectedURLs as $fixture => $urlSegment) {
			$obj = $this->objFromFixture('Page', $fixture);
			$this->assertEquals($urlSegment, $obj->URLSegment);
		}
	}
	
	/**
	 * Test that publication copies data to SiteTree_Live
	 */
	function testPublishCopiesToLiveTable() {
		$obj = $this->objFromFixture('Page','about');
		$obj->publish('Stage', 'Live');
		
		$createdID = DB::query("SELECT \"ID\" FROM \"SiteTree_Live\" WHERE \"URLSegment\" = '$obj->URLSegment'")->value();
		$this->assertEquals($obj->ID, $createdID);
	}
	
	/**
	 * Test that field which are set and then cleared are also transferred to the published site.
	 */
	function testPublishDeletedFields() {
		$this->logInWithPermission('ADMIN');
		
		$obj = $this->objFromFixture('Page', 'about');
		$obj->MetaTitle = "asdfasdf";
		$obj->write();
		$this->assertTrue($obj->doPublish());
		
		$this->assertEquals('asdfasdf', DB::query("SELECT \"MetaTitle\" FROM \"SiteTree_Live\" WHERE \"ID\" = '$obj->ID'")->value());
	
		$obj->MetaTitle = null;
		$obj->write();
		$this->assertTrue($obj->doPublish());
	
		$this->assertNull(DB::query("SELECT \"MetaTitle\" FROM \"SiteTree_Live\" WHERE \"ID\" = '$obj->ID'")->value());
		
	}
	
	function testParentNodeCachedInMemory() {
		$parent = new SiteTree();
	     	$parent->Title = 'Section Title';
	     	$child = new SiteTree();
	     	$child->Title = 'Page Title';
		$child->setParent($parent);
		
		$this->assertType("SiteTree", $child->Parent);
		$this->assertEquals("Section Title", $child->Parent->Title);
	}
	
	function testParentModelReturnType() {
		$parent = new SiteTreeTest_PageNode();
		$child = new SiteTreeTest_PageNode();
	
		$child->setParent($parent);
		$this->assertType('SiteTreeTest_PageNode', $child->Parent);
	}
	
	/**
	 * Confirm that DataObject::get_one() gets records from SiteTree_Live
	 */
	function testGetOneFromLive() {
		$s = new SiteTree();
		$s->Title = "V1";
		$s->URLSegment = "get-one-test-page";
		$s->write();
		$s->publish("Stage", "Live");
		$s->Title = "V2";
		$s->write();
		
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage('Live');
		
		$checkSiteTree = DataObject::get_one("SiteTree", "\"URLSegment\" = 'get-one-test-page'");
		$this->assertEquals("V1", $checkSiteTree->Title);
	
		Versioned::set_reading_mode($oldMode);
	}
	
	function testChidrenOfRootAreTopLevelPages() {
		$pages = DataObject::get("SiteTree");
		foreach($pages as $page) $page->publish('Stage', 'Live');
		unset($pages);
		
		/* If we create a new SiteTree object with ID = 0 */
		$obj = new SiteTree();
		/* Then its children should be the top-level pages */
		$stageChildren = $obj->stageChildren()->toDropDownMap('ID','Title');
		$liveChildren = $obj->liveChildren()->toDropDownMap('ID','Title');
		$allChildren = $obj->AllChildrenIncludingDeleted()->toDropDownMap('ID','Title');
		
		$this->assertContains('Home', $stageChildren);
		$this->assertContains('Products', $stageChildren);
		$this->assertNotContains('Staff', $stageChildren);
	
		$this->assertContains('Home', $liveChildren);
		$this->assertContains('Products', $liveChildren);
		$this->assertNotContains('Staff', $liveChildren);
	
		$this->assertContains('Home', $allChildren);
		$this->assertContains('Products', $allChildren);
		$this->assertNotContains('Staff', $allChildren);
	}
	
	function testCanSaveBlankToHasOneRelations() {
		/* DataObject::write() should save to a has_one relationship if you set a field called (relname)ID */
		$page = new SiteTree();
		$parentID = $this->idFromFixture('Page', 'home');
		$page->ParentID = $parentID;
		$page->write();
		$this->assertEquals($parentID, DB::query("SELECT \"ParentID\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());
	
		/* You should then be able to save a null/0/'' value to the relation */
		$page->ParentID = null;
		$page->write();
		$this->assertEquals(0, DB::query("SELECT \"ParentID\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());
	}
	
	function testStageStates() {
		// newly created page
		$createdPage = new SiteTree();
		$createdPage->write();
		$this->assertFalse($createdPage->IsDeletedFromStage);
		$this->assertTrue($createdPage->IsAddedToStage);
		$this->assertTrue($createdPage->IsModifiedOnStage);
		
		// published page 
		$publishedPage = new SiteTree();
		$publishedPage->write();
		$publishedPage->publish('Stage','Live');
		$this->assertFalse($publishedPage->IsDeletedFromStage);
		$this->assertFalse($publishedPage->IsAddedToStage);
		$this->assertFalse($publishedPage->IsModifiedOnStage); 
		
		// published page, deleted from stage
		$deletedFromDraftPage = new SiteTree();
		$deletedFromDraftPage->write();
		$deletedFromDraftPageID = $deletedFromDraftPage->ID;
		$deletedFromDraftPage->publish('Stage','Live');
		$deletedFromDraftPage->deleteFromStage('Stage');
		$this->assertTrue($deletedFromDraftPage->IsDeletedFromStage);
		$this->assertFalse($deletedFromDraftPage->IsAddedToStage);
		$this->assertFalse($deletedFromDraftPage->IsModifiedOnStage);
		
		// published page, deleted from live
		$deletedFromLivePage = new SiteTree();
		$deletedFromLivePage->write();
		$deletedFromLivePage->publish('Stage','Live');
		$deletedFromLivePage->deleteFromStage('Stage');
		$deletedFromLivePage->deleteFromStage('Live');
		$this->assertTrue($deletedFromLivePage->IsDeletedFromStage);
		$this->assertFalse($deletedFromLivePage->IsAddedToStage);
		$this->assertFalse($deletedFromLivePage->IsModifiedOnStage);
		
		// published page, modified
		$modifiedOnDraftPage = new SiteTree();
		$modifiedOnDraftPage->write();
		$modifiedOnDraftPage->publish('Stage','Live');
		$modifiedOnDraftPage->Content = 'modified';
		$modifiedOnDraftPage->write();
		$this->assertFalse($modifiedOnDraftPage->IsDeletedFromStage);
		$this->assertFalse($modifiedOnDraftPage->IsAddedToStage);
		$this->assertTrue($modifiedOnDraftPage->IsModifiedOnStage);
	}
	
	/**
	 * Test that a page can be completely deleted and restored to the stage site
	 */
	function testRestoreToStage() {
		$page = $this->objFromFixture('Page', 'about');
		$pageID = $page->ID;
		$page->delete();
		$this->assertTrue(!DataObject::get_by_id("Page", $pageID));
		
		$deletedPage = Versioned::get_latest_version('SiteTree', $pageID);
		$resultPage = $deletedPage->doRestoreToStage();
		
		$requeriedPage = DataObject::get_by_id("Page", $pageID);
		
		$this->assertEquals($pageID, $resultPage->ID);
		$this->assertEquals($pageID, $requeriedPage->ID);
		$this->assertEquals('About Us', $requeriedPage->Title);
		$this->assertEquals('Page', $requeriedPage->class);
	
	
		$page2 = $this->objFromFixture('Page', 'products');
		$page2ID = $page2->ID;
		$page2->doUnpublish();
		$page2->delete();
		
		// Check that if we restore while on the live site that the content still gets pushed to
		// stage
		Versioned::reading_stage('Live');
		$deletedPage = Versioned::get_latest_version('SiteTree', $page2ID);
		$deletedPage->doRestoreToStage();
		$this->assertTrue(!Versioned::get_one_by_stage("Page", "Live", "\"SiteTree\".\"ID\" = " . $page2ID));
	
		Versioned::reading_stage('Stage');
		$requeriedPage = DataObject::get_by_id("Page", $page2ID);
		$this->assertEquals('Products', $requeriedPage->Title);
		$this->assertEquals('Page', $requeriedPage->class);
		
	}
	
	public function testGetByLink() {
		$home     = $this->objFromFixture('Page', 'home');
		$about    = $this->objFromFixture('Page', 'about');
		$staff    = $this->objFromFixture('Page', 'staff');
		$product  = $this->objFromFixture('Page', 'product1');
		$notFound = $this->objFromFixture('ErrorPage', '404');
		
		SiteTree::disable_nested_urls();
		
		$this->assertEquals($home->ID, SiteTree::get_by_link('/', false)->ID);
		$this->assertEquals($home->ID, SiteTree::get_by_link('/home/', false)->ID);
		$this->assertEquals($about->ID, SiteTree::get_by_link($about->Link(), false)->ID);
		$this->assertEquals($staff->ID, SiteTree::get_by_link($staff->Link(), false)->ID);
		$this->assertEquals($product->ID, SiteTree::get_by_link($product->Link(), false)->ID);
		$this->assertEquals($notFound->ID, SiteTree::get_by_link($notFound->Link(), false)->ID);
		
		SiteTree::enable_nested_urls();
		
		$this->assertEquals($home->ID, SiteTree::get_by_link('/', false)->ID);
		$this->assertEquals($home->ID, SiteTree::get_by_link('/home/', false)->ID);
		$this->assertEquals($about->ID, SiteTree::get_by_link($about->Link(), false)->ID);
		$this->assertEquals($staff->ID, SiteTree::get_by_link($staff->Link(), false)->ID);
		$this->assertEquals($product->ID, SiteTree::get_by_link($product->Link(), false)->ID);
		$this->assertEquals($notFound->ID, SiteTree::get_by_link($notFound->Link(), false)->ID);
		
		$this->assertEquals (
			$staff->ID, SiteTree::get_by_link('/my-staff/', false)->ID, 'Assert a unique URLSegment can be used for b/c.'
		);
	}
	
	function testDeleteFromStageOperatesRecursively() {
		SiteTree::set_enforce_strict_hierarchy(false);
		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
		
		$pageAbout->delete();
		
		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaff->ID) instanceof Page);
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaffDuplicate->ID) instanceof Page);
		SiteTree::set_enforce_strict_hierarchy(true);
	}
	
	function testDeleteFromStageOperatesRecursivelyStrict() {
		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
		
		$pageAbout->delete();
		
		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertFalse(DataObject::get_by_id('Page', $pageStaff->ID));
		$this->assertFalse(DataObject::get_by_id('Page', $pageStaffDuplicate->ID));
	}
	
	function testDeleteFromLiveOperatesRecursively() {
		SiteTree::set_enforce_strict_hierarchy(false);
		$this->logInWithPermission('ADMIN');
		
		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageAbout->doPublish();
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaff->doPublish();
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
		$pageStaffDuplicate->doPublish();
		
		$parentPage = $this->objFromFixture('Page', 'about');
		$parentPage->doDeleteFromLive();
		
		Versioned::reading_stage('Live');
		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaff->ID) instanceof Page);
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaffDuplicate->ID) instanceof Page);
		Versioned::reading_stage('Stage');
		SiteTree::set_enforce_strict_hierarchy(true);
	}
	
	function testUnpublishDoesNotDeleteChildrenWithLooseHierachyOn() {
		SiteTree::set_enforce_strict_hierarchy(false);
		$this->logInWithPermission('ADMIN');
		
		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageAbout->doPublish();
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaff->doPublish();
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
		$pageStaffDuplicate->doPublish();
		
		$parentPage = $this->objFromFixture('Page', 'about');
		$parentPage->doUnpublish();
		
		Versioned::reading_stage('Live');
		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaff->ID) instanceof Page);
		$this->assertTrue(DataObject::get_by_id('Page', $pageStaffDuplicate->ID) instanceof Page);
		Versioned::reading_stage('Stage');
		SiteTree::set_enforce_strict_hierarchy(true);
	}
	
	
	function testDeleteFromLiveOperatesRecursivelyStrict() {
		$this->logInWithPermission('ADMIN');
		
		$pageAbout = $this->objFromFixture('Page', 'about');
		$pageAbout->doPublish();
		$pageStaff = $this->objFromFixture('Page', 'staff');
		$pageStaff->doPublish();
		$pageStaffDuplicate = $this->objFromFixture('Page', 'staffduplicate');
		$pageStaffDuplicate->doPublish();
		
		$parentPage = $this->objFromFixture('Page', 'about');
		$parentPage->doDeleteFromLive();
		
		Versioned::reading_stage('Live');
		$this->assertFalse(DataObject::get_by_id('Page', $pageAbout->ID));
		$this->assertFalse(DataObject::get_by_id('Page', $pageStaff->ID));
		$this->assertFalse(DataObject::get_by_id('Page', $pageStaffDuplicate->ID));
		Versioned::reading_stage('Stage');
	}
	
	/**
	 * Simple test to confirm that querying from a particular archive date doesn't throw
	 * an error
	 */
	function testReadArchiveDate() {
		Versioned::reading_archived_date('2009-07-02 14:05:07');
		
		DataObject::get('SiteTree', "\"ParentID\" = 0");
		
		Versioned::reading_archived_date(null);
	}
	
	function testEditPermissions() {
		$editor = $this->objFromFixture("Member", "editor");
		
		$home = $this->objFromFixture("Page", "home");
		$products = $this->objFromFixture("Page", "products");
		$product1 = $this->objFromFixture("Page", "product1");
		$product4 = $this->objFromFixture("Page", "product4");

		// Can't edit a page that is locked to admins
		$this->assertFalse($home->canEdit($editor));
		
		// Can edit a page that is locked to editors
		$this->assertTrue($products->canEdit($editor));
		
		// Can edit a child of that page that inherits
		$this->assertTrue($product1->canEdit($editor));
		
		// Can't edit a child of that page that has its permissions overridden
		$this->assertFalse($product4->canEdit($editor));
	}
	
	function testEditPermissionsOnDraftVsLive() {
		// Create an inherit-permission page
		$page = new Page();
		$page->write();
		$page->CanEditType = "Inherit";
		$page->doPublish();
		$pageID = $page->ID;
		
		// Lock down the site config
		$sc = $page->SiteConfig;
		$sc->CanEditType = 'OnlyTheseUsers';
		$sc->EditorGroups()->add($this->idFromFixture('Group', 'admins'));
		$sc->write();
		
		// Confirm that Member.editor can't edit the page
		$this->objFromFixture('Member','editor')->logIn();
		$this->assertFalse($page->canEdit());
		
		// Change the page to be editable by Group.editors, but do not publish
		$this->objFromFixture('Member','admin')->logIn();
		$page->CanEditType = 'OnlyTheseUsers';
		$page->EditorGroups()->add($this->idFromFixture('Group', 'editors'));
		$page->write();
		
		// Confirm that Member.editor can now edit the page
		$this->objFromFixture('Member','editor')->logIn();
		$this->assertTrue($page->canEdit());
		
		// Publish the changes to the page
		$this->objFromFixture('Member','admin')->logIn();
		$page->doPublish();
		
		// Confirm that Member.editor can still edit the page
		$this->objFromFixture('Member','editor')->logIn();
		$this->assertTrue($page->canEdit());
	}
	
	function testCompareVersions() {
		$page = new Page();
		$page->write();
		$this->assertEquals(1, $page->Version);
		
		$page->Content = "<p>This is a test</p>";
		$page->write();
		$this->assertEquals(2, $page->Version);
		
		$diff = $page->compareVersions(1, 2);
		
		$processedContent = trim($diff->Content);
		$processedContent = preg_replace('/\s*</','<',$processedContent);
		$processedContent = preg_replace('/>\s*/','>',$processedContent);
		$this->assertEquals("<ins><p>This is a test</p></ins>", $processedContent);
		
	}

	function testAuthorIDAndPublisherIDFilledOutOnPublish() {
		// Ensure that we have a member ID who is doing all this work
		$member = Member::currentUser();
		if($member) {
			$memberID = $member->ID;
		} else {
			$memberID = $this->idFromFixture("Member", "admin");
			Session::set("loggedInAs", $memberID);
		}

		// Write the page
		$about = $this->objFromFixture('Page','about');
		$about->Title = "Another title";
		$about->write();
		
		// Check the version created
		$savedVersion = DB::query("SELECT \"AuthorID\", \"PublisherID\" FROM \"SiteTree_versions\" 
			WHERE \"RecordID\" = $about->ID ORDER BY \"Version\" DESC")->first();
		$this->assertEquals($memberID, $savedVersion['AuthorID']);
		$this->assertEquals(0, $savedVersion['PublisherID']);
		
		// Publish the page
		$about->doPublish();
		$publishedVersion = DB::query("SELECT \"AuthorID\", \"PublisherID\" FROM \"SiteTree_versions\" 
			WHERE \"RecordID\" = $about->ID ORDER BY \"Version\" DESC")->first();
			
		// Check the version created
		$this->assertEquals($memberID, $publishedVersion['AuthorID']);
		$this->assertEquals($memberID, $publishedVersion['PublisherID']);
		
	}
	
	public function testLinkShortcodeHandler() {
		$aboutPage = $this->objFromFixture('Page', 'about');
		$errorPage = $this->objFromFixture('ErrorPage', '404');
		
		$parser = new ShortcodeParser();
		$parser->register('sitetree_link', array('SiteTree', 'link_shortcode_handler'));
		
		$aboutShortcode = sprintf('[sitetree_link id=%d]', $aboutPage->ID);
		$aboutEnclosed  = sprintf('[sitetree_link id=%d]Example Content[/sitetree_link]', $aboutPage->ID);
		
		$aboutShortcodeExpected = $aboutPage->Link();
		$aboutEnclosedExpected  = sprintf('<a href="%s">Example Content</a>', $aboutPage->Link());
		
		$this->assertEquals($aboutShortcodeExpected, $parser->parse($aboutShortcode), 'Test that simple linking works.');
		$this->assertEquals($aboutEnclosedExpected, $parser->parse($aboutEnclosed), 'Test enclosed content is linked.');
		
		$aboutPage->delete();
		
		$this->assertEquals($aboutShortcodeExpected, $parser->parse($aboutShortcode), 'Test that deleted pages still link.');
		$this->assertEquals($aboutEnclosedExpected, $parser->parse($aboutEnclosed));
		
		$aboutShortcode = '[sitetree_link id="-1"]';
		$aboutEnclosed  = '[sitetree_link id="-1"]Example Content[/sitetree_link]';
		
		$aboutShortcodeExpected = $errorPage->Link();
		$aboutEnclosedExpected  = sprintf('<a href="%s">Example Content</a>', $errorPage->Link());
		
		$this->assertEquals($aboutShortcodeExpected, $parser->parse($aboutShortcode), 'Test link to 404 page if no suitable matches.');
		$this->assertEquals($aboutEnclosedExpected, $parser->parse($aboutEnclosed));
		
		$this->assertEquals('', $parser->parse('[sitetree_link]'), 'Test that invalid ID attributes are not parsed.');
		$this->assertEquals('', $parser->parse('[sitetree_link id="text"]'));
		$this->assertEquals('', $parser->parse('[sitetree_link]Example Content[/sitetree_link]'));
	}
	
	public function testIsCurrent() {
		$aboutPage = $this->objFromFixture('Page', 'about');
		$errorPage = $this->objFromFixture('ErrorPage', '404');
		
		Director::set_current_page($aboutPage);
		$this->assertTrue($aboutPage->isCurrent(), 'Assert that basic isSection checks works.');
		$this->assertFalse($errorPage->isCurrent());
		
		Director::set_current_page($errorPage);
		$this->assertTrue($errorPage->isCurrent(), 'Assert isSection works on error pages.');
		$this->assertFalse($aboutPage->isCurrent());
		
		Director::set_current_page($aboutPage);
		$this->assertTrue (
			DataObject::get_one('SiteTree', '"Title" = \'About Us\'')->isCurrent(),
			'Assert that isCurrent works on another instance with the same ID.'
		);
		
		Director::set_current_page($newPage = new SiteTree());
		$this->assertTrue($newPage->isCurrent(), 'Assert that isCurrent works on unsaved pages.');
	}
	
	public function testIsSection() {
		$about = $this->objFromFixture('Page', 'about');
		$staff = $this->objFromFixture('Page', 'staff');
		$ceo   = $this->objFromFixture('Page', 'ceo');
		
		Director::set_current_page($about);
		$this->assertTrue($about->isSection());
		$this->assertFalse($staff->isSection());
		$this->assertFalse($ceo->isSection());
		
		Director::set_current_page($staff);
		$this->assertTrue($about->isSection());
		$this->assertTrue($staff->isSection());
		$this->assertFalse($ceo->isSection());
		
		Director::set_current_page($ceo);
		$this->assertTrue($about->isSection());
		$this->assertTrue($staff->isSection());
		$this->assertTrue($ceo->isSection());
	}
	
	/**
	 * @covers SiteTree::validURLSegment
	 */
	public function testValidURLSegmentURLSegmentConflicts() {
		$sitetree = new SiteTree();
		SiteTree::disable_nested_urls();
		
		$sitetree->URLSegment = 'home';
		$this->assertFalse($sitetree->validURLSegment(), 'URLSegment conflicts are recognised');
		$sitetree->URLSegment = 'home-noconflict';
		$this->assertTrue($sitetree->validURLSegment());
		
		$sitetree->ParentID   = $this->idFromFixture('Page', 'about');
		$sitetree->URLSegment = 'home';
		$this->assertFalse($sitetree->validURLSegment(), 'Conflicts are still recognised with a ParentID value');
		
		SiteTree::enable_nested_urls();
		
		$sitetree->ParentID   = 0;
		$sitetree->URLSegment = 'home';
		$this->assertFalse($sitetree->validURLSegment(), 'URLSegment conflicts are recognised');
		
		$sitetree->ParentID = $this->idFromFixture('Page', 'about');
		$this->assertTrue($sitetree->validURLSegment(), 'URLSegments can be the same across levels');
		
		$sitetree->URLSegment = 'my-staff';
		$this->assertFalse($sitetree->validURLSegment(), 'Nested URLSegment conflicts are recognised');
		$sitetree->URLSegment = 'my-staff-noconflict';
		$this->assertTrue($sitetree->validURLSegment());
	}
	
	/**
	 * @covers SiteTree::validURLSegment
	 */
	public function testValidURLSegmentClassNameConflicts() {
		$sitetree = new SiteTree();
		$sitetree->URLSegment = 'Controller';
		
		$this->assertFalse($sitetree->validURLSegment(), 'Class name conflicts are recognised');
	}
	
	/**
	 * @covers SiteTree::validURLSegment
	 */
	public function testValidURLSegmentControllerConflicts() {
		SiteTree::enable_nested_urls();
		
		$sitetree = new SiteTree();
		$sitetree->ParentID = $this->idFromFixture('SiteTreeTest_Conflicted', 'parent');
		
		$sitetree->URLSegment = 'index';
		$this->assertFalse($sitetree->validURLSegment(), 'index is not a valid URLSegment');
		
		$sitetree->URLSegment = 'conflicted-action';
		$this->assertFalse($sitetree->validURLSegment(), 'allowed_actions conflicts are recognised');
		
		$sitetree->URLSegment = 'conflicted-template';
		$this->assertFalse($sitetree->validURLSegment(), 'Action-specific template conflicts are recognised');
		
		$sitetree->URLSegment = 'valid';
		$this->assertTrue($sitetree->validURLSegment(), 'Valid URLSegment values are allowed');
	}
	
	public function testVersionsAreCreated() {
		$p = new Page();
		$p->Content = "one";
		$p->write();
		$this->assertEquals(1, $p->Version);
		
		// No changes don't bump version
		$p->write();
		$this->assertEquals(1, $p->Version);

		$p->Content = "two";
		$p->write();
		$this->assertEquals(2, $p->Version);

		// Only change meta-data don't bump version
		$p->HasBrokenLink = true;
		$p->write();
		$p->HasBrokenLink = false;
		$p->write();
		$this->assertEquals(2, $p->Version);

		$p->Content = "three";
		$p->write();
		$this->assertEquals(3, $p->Version);

	}
	
	function testPageTypeClasses() {
		$classes = SiteTree::page_type_classes();
		$this->assertNotContains('SiteTree', $classes, 'Page types do not include base class');
		$this->assertContains('Page', $classes, 'Page types do contain subclasses');
	}

}

/**#@+
 * @ignore
 */

class SiteTreeTest_PageNode extends Page implements TestOnly { }
class SiteTreeTest_PageNode_Controller extends Page_Controller implements TestOnly {
}

class SiteTreeTest_Conflicted extends Page implements TestOnly { }
class SiteTreeTest_Conflicted_Controller extends Page_Controller implements TestOnly {
	
	public static $allowed_actions = array (
		'conflicted-action'
	);
	
	public function hasActionTemplate($template) {
		if($template == 'conflicted-template') {
			return true;
		} else {
			return parent::hasActionTemplate($template);
		}
	}
	 
}

/**#@-*/
