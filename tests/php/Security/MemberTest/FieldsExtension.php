<?php

namespace SilverStripe\Security\Tests\MemberTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

class FieldsExtension extends DataExtension implements TestOnly
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Main', new TextField('TestMemberField', 'Test'));
    }
}
