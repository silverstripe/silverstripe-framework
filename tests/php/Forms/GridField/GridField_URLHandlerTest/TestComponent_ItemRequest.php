<?php

namespace SilverStripe\Forms\Tests\GridField\GridField_URLHandlerTest;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\View\SSViewer;

class TestComponent_ItemRequest extends RequestHandler
{

    private static $allowed_actions = array('Form', 'showform', 'testpage');

    protected $gridField;

    protected $link;

    protected $id;

    public function __construct($gridField, $id, $link)
    {
        $this->gridField = $gridField;
        $this->id = $id;
        $this->link = $link;
        parent::__construct();
    }

    public function Link($action = null)
    {
        return $this->link;
    }

    public function showform()
    {
        return "<head>" . SSViewer::get_base_tag("") . "</head>" . $this->Form()->forTemplate();
    }

    public function Form()
    {
        /** @skipUpgrade */
        return new Form(
            $this,
            Form::DEFAULT_NAME,
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
        return "Submitted " . $data['Test'] . " to item #" . $this->id;
    }

    public function testpage()
    {
        return "Test page for item #" . $this->id;
    }
}
