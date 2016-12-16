<?php

namespace SilverStripe\Forms\Tests\GridField\GridField_URLHandlerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\TextField;
use SilverStripe\View\SSViewer;

/**
 * Test URLHandler with a nested request handler
 */
class TestComponent extends RequestHandler implements GridField_URLHandler
{

    private static $allowed_actions = array('Form', 'showform', 'testpage', 'handleItem');

    protected $gridField;

    public function getURLHandlers($gridField)
    {
        /**
 * @skipUpgrade
*/
        return array(
            'showform' => 'showform',
            'testpage' => 'testpage',
            'Form' => 'Form',
            'item/$ID' => 'handleItem',
        );
    }

    public function handleItem($gridField, $request)
    {
        $id = $request->param("ID");
        return new TestComponent_ItemRequest(
            $gridField,
            $id,
            Controller::join_links($gridField->Link(), 'item/' . $id)
        );
    }

    public function Link()
    {
        return $this->gridField->Link();
    }

    public function showform($gridField, $request)
    {
        return "<head>" . SSViewer::get_base_tag("") . "</head>" . $this->Form($gridField, $request)->forTemplate();
    }

    public function Form($gridField, $request)
    {
        $this->gridField = $gridField;
        /**
 * @skipUpgrade
*/
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

    public function doAction($data, $form)
    {
        return "Submitted " . $data['Test'] . " to component";
    }

    public function testpage($gridField, $request)
    {
        return "Test page for component";
    }
}
