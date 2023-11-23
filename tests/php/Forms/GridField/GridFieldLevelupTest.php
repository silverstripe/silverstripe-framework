<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldLevelup;
use SilverStripe\View\ArrayData;

class GridFieldLevelupTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testGetHTMLFragmentsThrowsException()
    {
        $component = new GridFieldLevelup(0);
        $gridField = new GridField('dummy');
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            GridFieldLevelup::class . " must be used with DataObject subclasses. Found '$modelClass'"
        );

        $component->getHTMLFragments($gridField);
    }
}
