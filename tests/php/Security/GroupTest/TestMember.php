<?php

namespace SilverStripe\Security\Tests\GroupTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;

class TestMember extends Member implements TestOnly
{
    private static $table_name = 'GroupTest_Member';

    public function getCMSFields()
    {
        $groups = DataObject::get(Group::class);
        $groupsMap = ($groups) ? $groups->map() : false;
        $fields = new FieldList(
            new HiddenField('ID', 'ID'),
            new CheckboxSetField(
                'Groups',
                'Groups',
                $groupsMap
            )
        );

        return $fields;
    }
}
