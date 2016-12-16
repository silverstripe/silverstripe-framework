<?php

namespace SilverStripe\Admin\Tests;

use SilverStripe\Security\Permission;
use SilverStripe\Dev\FunctionalTest;

class ModelAdminTest extends FunctionalTest
{
    protected static $fixture_file = 'ModelAdminTest.yml';

    protected $extraDataObjects = [
        ModelAdminTest\Contact::class,
        ModelAdminTest\Player::class
    ];

    protected $extraControllers = [
        ModelAdminTest\ContactAdmin::class,
        ModelAdminTest\PlayerAdmin::class,
    ];

    public function testModelAdminOpens()
    {
        $this->autoFollowRedirection = false;
        $this->logInAs('admin');
        $this->assertTrue((bool)Permission::check("ADMIN"));
        $this->assertEquals(200, $this->get('ContactAdmin')->getStatusCode());
    }

    public function testExportFieldsDefaultIsSummaryFields()
    {
        $admin = new ModelAdminTest\ContactAdmin();
        $admin->doInit();
        $this->assertEquals(
            $admin->getExportFields(),
            ModelAdminTest\Contact::singleton()->summaryFields()
        );
    }

    public function testExportFieldsOverloadedMethod()
    {
        $admin = new ModelAdminTest\PlayerAdmin();
        $admin->doInit();
        $this->assertEquals($admin->getExportFields(), array(
            'Name' => 'Name',
            'Position' => 'Position'
        ));
    }
}
