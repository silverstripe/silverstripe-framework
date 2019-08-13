<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\GridField\GridFieldStateManager;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Tests\GridField\GridFieldPrintButtonTest\TestObject;
use SilverStripe\View\ArrayData;

class GridFieldStateManagerTest extends SapphireTest
{
    public function testStateKey()
    {
        $manager = new GridFieldStateManager();
        $controller = new Controller();
        $form1 = new Form($controller, 'form1', new FieldList(), new FieldList());
        $itemRequest = new GridFieldDetailForm_ItemRequest(
            new GridField('test'),
            new GridFieldDetailForm(),
            new TestObject(),
            $controller,
            'itemRequest'
        );
        $form2 = new Form($itemRequest, 'form1', new FieldList(), new FieldList());

        $grid1 = new GridField('A');
        $grid2 = new GridField('B');
        $grid1->setForm($form1);
        $grid2->setForm($form2);
        $this->assertEquals('gridState-A-0', $manager->getStateKey($grid1));
        $this->assertEquals('gridState-B-1', $manager->getStateKey($grid2));
    }

    public function testAddStateToURL()
    {
        $manager = new GridFieldStateManager();
        $grid = new GridField('TestGrid');
        $grid->getState()->testValue = 'foo';
        $link = '/link-to/something';
        $state = $grid->getState(false)->Value();
        $this->assertEquals(
            '/link-to/something?gridState-TestGrid-0=' . urlencode($state),
            $manager->addStateToURL($grid, $link)
        );

        $link = '/link-to/something-else?someParam=somevalue';
        $state = $grid->getState(false)->Value();
        $this->assertEquals(
            '/link-to/something-else?someParam=somevalue&gridState-TestGrid-0=' . urlencode($state),
            $manager->addStateToURL($grid, $link)
        );
    }

    public function testGetStateFromRequest()
    {
        $manager = new GridFieldStateManager();
        $controller = new Controller();
        $form = new Form($controller, 'form1', new FieldList(), new FieldList());
        $grid = new GridField('TestGrid');
        $grid->setForm($form);

        $grid->getState()->testValue = 'foo';
        $state = urlencode($grid->getState(false)->Value());
        $request = new HTTPRequest(
            'GET',
            '/link-to/something',
            [
                $manager->getStateKey($grid) => $state
            ]
        );
        $result = $manager->getStateFromRequest($grid, $request);

        $this->assertEquals($state, $result);
    }
}
