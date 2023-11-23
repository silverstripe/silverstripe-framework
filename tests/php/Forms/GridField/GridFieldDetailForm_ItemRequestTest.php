<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class GridFieldDetailForm_ItemRequestTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testItemEditFormThrowsException()
    {
        $gridField = new GridField('dummy', 'dummy', new ArrayList(), new GridFieldConfig_Base());
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);
        $itemRequest = new GridFieldDetailForm_ItemRequest($gridField, new GridFieldDetailForm(), new ArrayData(), new Controller(), '');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot dynamically determine form fields. Pass the fields to GridFieldDetailForm::setFields()'
            . " or implement a getCMSFields() method on $modelClass"
        );

        $itemRequest->ItemEditForm();
    }
}
