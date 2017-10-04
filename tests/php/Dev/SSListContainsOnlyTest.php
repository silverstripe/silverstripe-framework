<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\Constraint\SSListContainsOnly;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;

class SSListContainsOnlyTest extends SapphireTest
{
    public function provideMatchesForList()
    {
        return [
            [
                [
                    ['FirstName' => 'Ingo'],
                    ['Surname' => 'Minnee']
                ]
            ],
            [
                [
                    ['FirstName' => 'Sam', 'Surname' => 'Minnee'], //Sam Minee or Ingo
                    ['FirstName' => 'Ingo']
                ]
            ],
        ];
    }


    public function provideInvalidMatchesForList()
    {
        return [
            [
                [['FirstName' => 'AnyoneNotInList']]
            ],
            [
                [['Surname' => 'NotInList']]
            ],
            [
                [['FirstName' => 'Ingo', 'Surname' => 'Minnee']] //more matches in List
            ],
            [
                [
                    ['FirstName' => 'Ingo', 'Surname' => 'Minnee'], //mixed
                    ['FirstName' => 'NotInList']
                ]
            ],
        ];
    }

    /**
     * @dataProvider provideMatchesForList()
     *
     * @param $matches
     */
    public function testEvaluateListMatchesCorrectly($matches)
    {
        $constraint = new SSListContainsOnly($matches);

        $this->assertTrue($constraint->evaluate($this->getListToMatch(), '', true));
    }

    /**
     * @return ArrayList|Member[]
     */
    private function getListToMatch()
    {
        $list = ArrayList::create();
        $list->push(Member::create(['FirstName' => 'Ingo', 'Surname' => 'Schommer']));
        $list->push(Member::create(['FirstName' => 'Sam', 'Surname' => 'Minnee']));

        return $list;
    }

    /**
     * @dataProvider provideInvalidMatchesForList()
     *
     * @param $matches
     */
    public function testEvaluateListDoesNotMatchWrongMatches($matches)
    {
        $constraint = new SSListContainsOnly($matches);

        $this->assertFalse($constraint->evaluate($this->getListToMatch(), '', true));
    }
}
