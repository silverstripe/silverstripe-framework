<?php

namespace SilverStripe\Security\Tests\GroupTest;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;

class MemberForm extends Form
{

    public function __construct($controller, $name)
    {
        $fields = TestMember::singleton()->getCMSFields();
        $actions = new FieldList(
            new FormAction('doSave', 'save')
        );

        parent::__construct($controller, $name, $fields, $actions);
    }

    public function doSave($data, $form)
    {
        // done in testing methods
    }
}
