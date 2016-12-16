<?php

namespace SilverStripe\Admin\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;
use SilverStripe\View\Requirements;

class LeftAndMainTest extends FunctionalTest
{

    protected static $fixture_file = 'LeftAndMainTest.yml';

    protected $extraDataObjects = [
        LeftAndMainTest\TestObject::class
    ];

    protected $extraControllers = [
        LeftAndMainTest\TestController::class,
    ];

    protected $backupCombined;

    public function setUp()
    {
        parent::setUp();

        // @todo fix controller stack problems and re-activate
        //$this->autoFollowRedirection = false;
        $this->resetMenu();
        $this->backupCombined = Requirements::get_combined_files_enabled();

        LeftAndMain::config()
            ->update('extra_requirements_css', array(
                FRAMEWORK_ADMIN_DIR . '/tests/assets/LeftAndMainTest.css'
            ))
            ->update('extra_requirements_javascript', array(
                FRAMEWORK_ADMIN_DIR . '/tests/assets/LeftAndMainTest.js'
            ));

        Requirements::set_combined_files_enabled(false);
    }

    /**
     * Clear menu to default state as per LeftAndMain::init()
     */
    protected function resetMenu()
    {
        CMSMenu::clear_menu();
        CMSMenu::populate_menu();
        CMSMenu::add_link(
            'Help',
            _t('LeftAndMain.HELP', 'Help', 'Menu title'),
            LeftAndMain::config()->help_link,
            -2,
            array(
                'target' => '_blank'
            )
        );
    }

    public function tearDown()
    {
        parent::tearDown();
        Requirements::set_combined_files_enabled($this->backupCombined);
    }

    public function testExtraCssAndJavascript()
    {
        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->session()->inst_set('loggedInAs', $admin->ID);
        $response = $this->get('LeftAndMainTest_Controller');

        $this->assertRegExp(
            '/tests\/assets\/LeftAndMainTest.css/i',
            $response->getBody(),
            "body should contain custom css"
        );
        $this->assertRegExp(
            '/tests\/assets\/LeftAndMainTest.js/i',
            $response->getBody(),
            "body should contain custom js"
        );
    }

    /**
     * Note: This test would typically rely on SiteTree and CMSMain, but is mocked by
     * LeftAndMain_Controller and LeftAndMain_Object here to remove this dependency.
     */
    public function testSaveTreeNodeSorting()
    {
        $this->logInWithPermission('ADMIN');

        // forcing sorting for non-MySQL
        $rootPages = LeftAndMainTest\TestObject::get()
            ->filter("ParentID", 0)
            ->sort('"ID"');
        $siblingIDs = $rootPages->column('ID');
        $page1 = $rootPages->offsetGet(0);
        $page2 = $rootPages->offsetGet(1);
        $page3 = $rootPages->offsetGet(2);

        // Move page2 before page1
        $siblingIDs[0] = $page2->ID;
        $siblingIDs[1] = $page1->ID;
        $data = array(
            'SiblingIDs' => $siblingIDs,
            'ID' => $page2->ID,
            'ParentID' => 0
        );

        $response = $this->post('LeftAndMainTest_Controller/savetreenode', $data);
        $this->assertEquals(200, $response->getStatusCode());
        $page1 = DataObject::get_by_id(LeftAndMainTest\TestObject::class, $page1->ID, false);
        $page2 = DataObject::get_by_id(LeftAndMainTest\TestObject::class, $page2->ID, false);
        $page3 = DataObject::get_by_id(LeftAndMainTest\TestObject::class, $page3->ID, false);

        $this->assertEquals(2, $page1->Sort, 'Page1 is sorted after Page2');
        $this->assertEquals(1, $page2->Sort, 'Page2 is sorted before Page1');
        $this->assertEquals(3, $page3->Sort, 'Sort order for other pages is unaffected');
    }

    public function testSaveTreeNodeParentID()
    {
        $this->logInWithPermission('ADMIN');

        $page2 = $this->objFromFixture(LeftAndMainTest\TestObject::class, 'page2');
        $page3 = $this->objFromFixture(LeftAndMainTest\TestObject::class, 'page3');
        $page31 = $this->objFromFixture(LeftAndMainTest\TestObject::class, 'page31');
        $page32 = $this->objFromFixture(LeftAndMainTest\TestObject::class, 'page32');

        // Move page2 into page3, between page3.1 and page 3.2
        $siblingIDs = array(
            $page31->ID,
            $page2->ID,
            $page32->ID
        );
        $data = array(
            'SiblingIDs' => $siblingIDs,
            'ID' => $page2->ID,
            'ParentID' => $page3->ID
        );
        $response = $this->post('LeftAndMainTest_Controller/savetreenode', $data);
        $this->assertEquals(200, $response->getStatusCode());
        $page2 = DataObject::get_by_id(LeftAndMainTest\TestObject::class, $page2->ID, false);
        $page31 = DataObject::get_by_id(LeftAndMainTest\TestObject::class, $page31->ID, false);
        $page32 = DataObject::get_by_id(LeftAndMainTest\TestObject::class, $page32->ID, false);

        $this->assertEquals($page3->ID, $page2->ParentID, 'Moved page gets new parent');
        $this->assertEquals(1, $page31->Sort, 'Children pages before insertaion are unaffected');
        $this->assertEquals(2, $page2->Sort, 'Moved page is correctly sorted');
        $this->assertEquals(3, $page32->Sort, 'Children pages after insertion are resorted');
    }

    /**
     * Check that all subclasses of leftandmain can be accessed
     */
    public function testLeftAndMainSubclasses()
    {
        $adminuser = $this->objFromFixture(Member::class, 'admin');
        $this->session()->inst_set('loggedInAs', $adminuser->ID);

        $this->resetMenu();
        $menuItems = LeftAndMain::singleton()->MainMenu(false);
        foreach ($menuItems as $menuItem) {
            $link = $menuItem->Link;

            // don't test external links
            if (preg_match('/^(https?:)?\/\//', $link)) {
                continue;
            }

            $response = $this->get($link);

            $this->assertInstanceOf('SilverStripe\\Control\\HTTPResponse', $response, "$link should return a response object");
            $this->assertEquals(200, $response->getStatusCode(), "$link should return 200 status code");
            // Check that a HTML page has been returned
            $this->assertRegExp('/<html[^>]*>/i', $response->getBody(), "$link should contain <html> tag");
            $this->assertRegExp('/<head[^>]*>/i', $response->getBody(), "$link should contain <head> tag");
            $this->assertRegExp('/<body[^>]*>/i', $response->getBody(), "$link should contain <body> tag");
        }

        $this->session()->inst_set('loggedInAs', null);
    }

    public function testCanView()
    {
        $adminuser = $this->objFromFixture(Member::class, 'admin');
        $securityonlyuser = $this->objFromFixture(Member::class, 'securityonlyuser');
        $allcmssectionsuser = $this->objFromFixture(Member::class, 'allcmssectionsuser');

        // anonymous user
        $this->session()->inst_set('loggedInAs', null);
        $this->resetMenu();
        $menuItems = LeftAndMain::singleton()->MainMenu(false);
        $this->assertEquals(
            $menuItems->column('Code'),
            array(),
            'Without valid login, members cant access any menu entries'
        );

        // restricted cms user
        $this->logInAs($securityonlyuser);
        $this->resetMenu();
        $menuItems = LeftAndMain::singleton()->MainMenu(false);
        $menuItems = $menuItems->column('Code');
        sort($menuItems);

        $this->assertEquals(
            array(
                'Help',
                'SilverStripe-Admin-CMSProfileController',
                'SilverStripe-Admin-SecurityAdmin'
            ),
            $menuItems,
            'Groups with limited access can only access the interfaces they have permissions for'
        );

        // all cms sections user
        $this->logInAs($allcmssectionsuser);
        $this->resetMenu();
        $menuItems = LeftAndMain::singleton()->MainMenu(false);
        $this->assertContains(
            'SilverStripe-Admin-CMSProfileController',
            $menuItems->column('Code'),
            'Group with CMS_ACCESS_SilverStripe\\Admin\\LeftAndMain permission can edit own profile'
        );
        $this->assertContains(
            'SilverStripe-Admin-SecurityAdmin',
            $menuItems->column('Code'),
            'Group with CMS_ACCESS_SilverStripe\\Admin\\LeftAndMain permission can access all sections'
        );
        $this->assertContains(
            'Help',
            $menuItems->column('Code'),
            'Group with CMS_ACCESS_SilverStripe\\Admin\\LeftAndMain permission can access all sections'
        );

        // admin
        $this->logInAs($adminuser);
        $this->resetMenu();
        $menuItems = LeftAndMain::singleton()->MainMenu(false);
        $this->assertContains(
            'SilverStripe-Admin-SecurityAdmin',
            $menuItems->column('Code'),
            'Administrators can access Security Admin'
        );

        $this->session()->inst_set('loggedInAs', null);
    }

    /**
     * Test {@see LeftAndMain::updatetreenodes}
     */
    public function testUpdateTreeNodes()
    {
        $page1 = $this->objFromFixture(LeftAndMainTest\TestObject::class, 'page1');
        $page2 = $this->objFromFixture(LeftAndMainTest\TestObject::class, 'page2');
        $page3 = $this->objFromFixture(LeftAndMainTest\TestObject::class, 'page3');
        $page31 = $this->objFromFixture(LeftAndMainTest\TestObject::class, 'page31');
        $page32 = $this->objFromFixture(LeftAndMainTest\TestObject::class, 'page32');
        $this->logInWithPermission('ADMIN');

        // Check page
        $result = $this->get('LeftAndMainTest_Controller/updatetreenodes?ids='.$page1->ID);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('text/json', $result->getHeader('Content-Type'));
        $data = json_decode($result->getBody(), true);
        $pageData = $data[$page1->ID];
        $this->assertEquals(0, $pageData['ParentID']);
        $this->assertEquals($page2->ID, $pageData['NextID']);
        $this->assertEmpty($pageData['PrevID']);

        // check subpage
        $result = $this->get('LeftAndMainTest_Controller/updatetreenodes?ids='.$page31->ID);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('text/json', $result->getHeader('Content-Type'));
        $data = json_decode($result->getBody(), true);
        $pageData = $data[$page31->ID];
        $this->assertEquals($page3->ID, $pageData['ParentID']);
        $this->assertEquals($page32->ID, $pageData['NextID']);
        $this->assertEmpty($pageData['PrevID']);

        // Multiple pages
        $result = $this->get('LeftAndMainTest_Controller/updatetreenodes?ids='.$page1->ID.','.$page2->ID);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('text/json', $result->getHeader('Content-Type'));
        $data = json_decode($result->getBody(), true);
        $this->assertEquals(2, count($data));

        // Invalid IDs
        $result = $this->get('LeftAndMainTest_Controller/updatetreenodes?ids=-3');
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('text/json', $result->getHeader('Content-Type'));
        $data = json_decode($result->getBody(), true);
        $this->assertEquals(0, count($data));
    }
}
