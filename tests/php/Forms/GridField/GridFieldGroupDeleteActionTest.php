<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldGroupDeleteAction;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class GridFieldGroupDeleteActionTest extends SapphireTest
{
    protected static $fixture_file = 'GridFieldGroupDeleteActionTest.yml';

    public function testCanUnlink()
    {
        /* @var Group $group*/
        $group = $this->objFromFixture(Group::class, 'admingroup');
        /* @var Group $othergroup*/
        $othergroup = $this->objFromFixture(Group::class, 'otheradmingroup');

        /* @var Member $member */
        $member = $this->objFromFixture(Member::class, 'admin');
        Security::setCurrentUser($member);

        $gridField = GridField::create('test');
        Form::create(null, 'dummy', FieldList::create($gridField), FieldList::create());

        $button = new GridFieldGroupDeleteAction($group->ID);
        $actionGroup = $button->getGroup($gridField, $member, 'dummy');
        $column = $button->getColumnContent($gridField, $member, 'dummy');
        $this->assertNotNull($actionGroup, 'The unlink action has a menu group if the member has another admin group');
        $this->assertNotNull($column, 'The unlink action has a column content if the member has another admin group');

        $member->Groups()->remove($othergroup);

        $actionGroup = $button->getGroup($gridField, $member, 'dummy');
        $column = $button->getColumnContent($gridField, $member, 'dummy');
        $this->assertNull($actionGroup, 'The unlink action has no menu group if the member has no other admin group');
        $this->assertNull($column, 'The unlink action has no column content if the member has no other admin group');
    }
}
