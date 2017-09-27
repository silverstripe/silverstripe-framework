<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\Constraint\SSListContains;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;

class SSListContainsTest extends SapphireTest
{
    public function provideMatchesForList()
    {
        return [
            [
                [['FirstName' => 'Ingo']]
            ],
            [
                [['Surname' => 'Minnee']]
            ],
            [
                [['FirstName' => 'Sam', 'Surname' => 'Minnee']]
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
                [['FirstName' => 'Ingo', 'Surname' => 'Minnee']]
            ],
            [
                [
                    ['FirstName' => 'Ingo', 'Surname' => 'Minnee'],
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
        $constraint = new SSListContains($matches);

        $this->assertTrue($constraint->evaluate($this->getListToMatch(), '', true));
    }

    /**
     * @dataProvider provideInvalidMatchesForList()
     *
     * @param $matches
     */
    public function testEvaluateListDoesNotMatchWrongMatches($matches)
    {
        $constraint = new SSListContains($matches);

        $this->assertFalse($constraint->evaluate($this->getListToMatch(), '', true));
    }

    /**
     * @return ArrayList|Member[]
     */
    private function getListToMatch()
    {
        $list = ArrayList::create();
        $list->push(Member::create(['FirstName' => 'Ingo', 'Surname' => 'Schommer']));
        $list->push(Member::create(['FirstName' => 'Sam', 'Surname' => 'Minnee']));
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Bar']));

        return $list;
    }
}
