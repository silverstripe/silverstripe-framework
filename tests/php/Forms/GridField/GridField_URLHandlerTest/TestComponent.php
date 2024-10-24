<?php

namespace SilverStripe\Forms\Tests\GridField\GridField_URLHandlerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\TextField;
use SilverStripe\View\SSViewer;

/**
 * Test URLHandler with a nested request handler
 */
class TestComponent extends RequestHandler implements GridField_URLHandler
{

    private static $allowed_actions = ['Form', 'showform', 'testpage', 'handleItem'];

    /**
     * @var GridField
     */
    protected $gridField;

    public function getURLHandlers($gridField)
    {
        return [
            'showform' => 'showform',
            'testpage' => 'testpage',
            'Form' => 'Form',
            'item/$ID' => 'handleItem',
        ];
    }

    public function handleItem(GridField $gridField, HTTPRequest $request)
    {
        $this->setRequest($request);
        $id = $request->param("ID");
        return new TestComponent_ItemRequest(
            $gridField,
            $id,
            Controller::join_links($gridField->Link(), 'item/' . $id)
        );
    }

    public function Link($action = null)
    {
        return $this->gridField->Link();
    }

    public function showform(GridField $gridField, HTTPRequest $request)
    {
        $this->setRequest($request);
        return "<head>" . SSViewer::getBaseTag() . "</head>" . $this->Form($gridField, $request)->forTemplate();
    }

    /**
     * @param GridField $gridField
     * @param HTTPRequest $request
     * @return Form
     */
    public function Form(GridField $gridField, HTTPRequest $request)
    {
        $this->setRequest($request);
        $this->gridField = $gridField;
        return new Form(
            $this,
            'Form',
            new FieldList(
                new TextField("Test")
            ),
            new FieldList(
                new FormAction('doAction', 'Go')
            )
        );
    }

    public function doAction(array $data, Form $form): HTTPResponse
    {
        return HTTPResponse::create()->setBody("Submitted " . $data['Test'] . " to component");
    }

    public function testpage(GridField $gridField, HTTPRequest $request)
    {
        $this->setRequest($request);
        return "Test page for component";
    }
}
