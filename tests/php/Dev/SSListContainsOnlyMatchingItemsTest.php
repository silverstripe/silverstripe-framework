<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\Constraint\SSListContainsOnly;
use SilverStripe\Dev\Constraint\SSListContainsOnlyMatchingItems;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;

class SSListContainsOnlyMatchingItemsTest extends SapphireTest
{
    public function testEvaluateListMatchesCorrectly()
    {
        $constraint = new SSListContainsOnlyMatchingItems(['IsActive' => 1]);

        $this->assertTrue($constraint->evaluate($this->getListToMatch(), '', true));
    }

    /**
     * @return ArrayList|Member[]
     */
    private function getListToMatch()
    {
        $list = ArrayList::create();
        $list->push(Member::create(['FirstName' => 'Ingo', 'Surname' => 'Schommer', 'IsActive' => 1]));
        $list->push(Member::create(['FirstName' => 'Sam', 'Surname' => 'Minnee', 'IsActive' => 1]));

        return $list;
    }


    public function testEvaluateListDoesNotMatchWrongMatches()
    {
        $constraint = new SSListContainsOnlyMatchingItems(['IsActive' => 1]);

        $failingList = $this->getListToMatch();
        $failingList->push(Member::create(['FirstName' => 'Foo', 'IsActive' => 0]));

        $this->assertFalse($constraint->evaluate($failingList, '', true));
    }
}
