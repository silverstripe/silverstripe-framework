<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\Constraint\SSListContains;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use PHPUnit\Framework\Attributes\DataProvider;

class SSListContainsTest extends SapphireTest
{
    public static function provideMatchesForList()
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


    public static function provideInvalidMatchesForList()
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
     * @param $matches
     */
    #[DataProvider('provideMatchesForList')]
    public function testEvaluateListMatchesCorrectly($matches)
    {
        $constraint = new SSListContains($matches);

        $this->assertTrue($constraint->evaluate($this->getListToMatch(), '', true));
    }

    /**
     * @param $matches
     */
    #[DataProvider('provideInvalidMatchesForList')]
    public function testEvaluateListDoesNotMatchWrongMatches($matches)
    {
        $constraint = new SSListContains($matches);

        $this->assertFalse($constraint->evaluate($this->getListToMatch(), '', true));
    }

    /**
     * @return ArrayList<Member>
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
