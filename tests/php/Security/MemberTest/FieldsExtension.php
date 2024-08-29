<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Extension;

class FieldsExtension extends Extension implements TestOnly
{
    protected function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Main', new TextField('TestMemberField', 'Test'));
    }
}
