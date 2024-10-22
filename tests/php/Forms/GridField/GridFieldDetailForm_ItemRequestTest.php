<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest\TestBreadcrumbsController;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest\TestStatusFlagsObject;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;
use SilverStripe\ORM\FieldType\DBHTMLText;

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

    public function testBreadcrumbs(): void
    {
        $record = new TestStatusFlagsObject();
        $gridField = new GridField('dummy', 'dummy', new ArrayList([$record]), new GridFieldConfig_Base());
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);
        $itemRequest = new GridFieldDetailForm_ItemRequest($gridField, new GridFieldDetailForm(), $record, new TestBreadcrumbsController(), '');

        $crumbs = $itemRequest->Breadcrumbs();
        $this->assertCount(2, $crumbs);
        $this->assertTrue($crumbs->last()->hasField('Extra'));
        $crumbFlags = $crumbs->last()->getField('Extra');
        $statusFlagMarkup = $record->getStatusFlagMarkup('badge--breadcrumbs');

        // Check status flags are in the right spot
        $this->assertInstanceOf(DBHTMLText::class, $crumbFlags);
        $this->assertSame($statusFlagMarkup, $crumbFlags->__toString());

        // Check crumbs are as expected
        $this->assertSame('My Controller', $crumbs->first()->Title);
        $this->assertSame('my-link', $crumbs->first()->Link);
        $this->assertSame('New TestStatusFlagsObject', $crumbs->last()->Title);
        $this->assertSame(false, $crumbs->last()->Link);
    }
}
