<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\SessionGridFieldStateManager;
use SilverStripe\Forms\Tests\GridField\GridFieldPrintButtonTest\TestObject;

class SessionGridFieldStateManagerTest extends SapphireTest
{
    public function testStateKey()
    {
        $manager = new SessionGridFieldStateManager();
        $controller = new Controller();
        $form1 = new Form($controller, 'form1', new FieldList(), new FieldList());
        $testObject = new TestObject();
        $testObject->ID = 1;
        $form2 = new Form($controller, 'form2', new FieldList(), new FieldList());
        $form2->loadDataFrom($testObject);

        $grid1 = new GridField('A');
        $grid2 = new GridField('B');
        $grid1->setForm($form1);
        $grid2->setForm($form2);
        $this->assertEquals('A-0', $manager->getStateKey($grid1));
        $this->assertEquals('B-1', $manager->getStateKey($grid2));
    }

    public function testAddStateToURL()
    {
        $manager = new SessionGridFieldStateManager();
        $grid = new GridField('TestGrid');
        $grid->getState()->testValue = 'foo';
        $stateRequestVar = $manager->getStateRequestVar();
        $link = '/link-to/something';
        $this->assertTrue(
            preg_match(
                "|^$link\?{$stateRequestVar}=[a-zA-Z0-9]+$|",
                $manager->addStateToURL($grid, $link)
            ) == 1
        );

        $link = '/link-to/something-else?someParam=somevalue';
        $this->assertTrue(
            preg_match(
                "|^/link-to/something-else\?someParam=somevalue&{$stateRequestVar}=[a-zA-Z0-9]+$|",
                $manager->addStateToURL($grid, $link)
            ) == 1
        );
    }

    public function testGetStateFromRequest()
    {
        $manager = new SessionGridFieldStateManager();

        $session = new Session([]);
        $request = new HTTPRequest(
            'GET',
            '/link-to/something',
            [
                $manager->getStateRequestVar() => 'testGetStateFromRequest'
            ]
        );
        $request->setSession($session);

        $controller = new Controller();
        $controller->setRequest($request);
        $controller->pushCurrent();
        $form = new Form($controller, 'form1', new FieldList(), new FieldList());
        $grid = new GridField('TestGrid');
        $grid->setForm($form);

        $grid->getState()->testValue = 'foo';
        $state = $grid->getState(false)->Value() ?? '{}';
        $result = $manager->getStateFromRequest($grid, $request);

        $this->assertEquals($state, $result);
        $controller->popCurrent();
    }

    public function testDefaultStateLeavesURLUnchanged()
    {
        $manager = new SessionGridFieldStateManager();
        $grid = new GridField('DefaultStateGrid');
        $grid->getState(false)->getData()->testValue->initDefaults(['foo' => 'bar']);
        $link = '/link-to/something';

        $this->assertEquals('{}', $grid->getState(false)->Value());

        $this->assertEquals(
            '/link-to/something',
            $manager->addStateToURL($grid, $link)
        );
    }

    public function testStoreState()
    {
        $manager = new SessionGridFieldStateManager();

        $session = new Session([]);
        $request = new HTTPRequest(
            'GET',
            '/link-to/something',
            [
                $manager->getStateRequestVar() => 'testStoreState'
            ]
        );
        $request->setSession($session);

        $controller = new Controller();
        $controller->setRequest($request);
        $controller->pushCurrent();
        $form = new Form($controller, 'form1', new FieldList(), new FieldList());
        $grid = new GridField('TestGrid');
        $grid->setForm($form);

        $grid->getState()->testValue = 'foo';
        $state = $grid->getState(false)->Value() ?? '{}';

        $manager->storeState($grid);
        $this->assertEquals($state, $session->get('testStoreState')['TestGrid-0']);

        $controller->popCurrent();
    }
}
