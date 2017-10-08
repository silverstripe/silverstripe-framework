<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\Constraint\ViewableDataContains;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\View\ArrayData;

class ViewableDataContainsTest extends SapphireTest
{
    private $test_data = [
        'FirstName' => 'Ingo',
        'Surname' => 'Schommer'
    ];

    public function provideMatchesForList()
    {
        return [
            [
                ['FirstName' => 'Ingo']
            ],
            [
                ['Surname' => 'Schommer']
            ],
            [
                ['FirstName' => 'Ingo', 'Surname' => 'Schommer']
            ]
        ];
    }


    public function provideInvalidMatchesForList()
    {
        return [
            [
                ['FirstName' => 'AnyoneNotInList']
            ],
            [
                ['Surname' => 'NotInList']
            ],
            [
                ['FirstName' => 'Ingo', 'Surname' => 'Minnee']
            ]
        ];
    }

    /**
     * @dataProvider provideMatchesForList()
     *
     * @param $match
     */
    public function testEvaluateMatchesCorrectlyArrayData($match)
    {
        $constraint = new ViewableDataContains($match);

        $item = ArrayData::create($this->test_data);

        $this->assertTrue($constraint->evaluate($item, '', true));
    }

    /**
     * @dataProvider provideMatchesForList()
     *
     * @param $match
     */
    public function testEvaluateMatchesCorrectlyDataObject($match)
    {
        $constraint = new ViewableDataContains($match);

        $item = Member::create($this->test_data);

        $this->assertTrue($constraint->evaluate($item, '', true));
    }

    /**
     * @dataProvider provideInvalidMatchesForList()
     *
     * @param $matches
     */
    public function testEvaluateDoesNotMatchWrongMatchInArrayData($match)
    {
        $constraint = new ViewableDataContains($match);

        $item = ArrayData::create($this->test_data);

        $this->assertFalse($constraint->evaluate($item, '', true));
    }

    /**
     * @dataProvider provideInvalidMatchesForList()
     *
     * @param $matches
     */
    public function testEvaluateDoesNotMatchWrongMatchInDataObject($match)
    {
        $constraint = new ViewableDataContains($match);

        $item = Member::create($this->test_data);

        $this->assertFalse($constraint->evaluate($item, '', true));
    }
}
