<?php

namespace SilverStripe\Dev\Tests\SapphireTestTest;

use SilverStripe\Dev\TestOnly;

class DataProvider implements TestOnly
{
    protected static $oneItemList = [
        ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
    ];

    protected static $twoItemList = [
        ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
        ['FirstName' => 'Sam', 'Surname' => 'Minnee'],
    ];

    protected static $memberList = [
        ['FirstName' => 'Ingo', 'Surname' => 'Schommer', 'Locale' => 'en_US'],
        ['FirstName' => 'Sam', 'Surname' => 'Minnee', 'Locale' => 'en_US'],
    ];

    /**
     * @return array
     */
    public static function provideEqualListsWithEmptyList()
    {
        return array_merge(
            [
                'emptyLists' => [
                    [],
                    [],
                ],
            ],
            self::provideEqualLists()
        );
    }

    /**
     * @return array
     */
    public static function provideEqualLists()
    {
        return [
            [
                'oneParameterOneItem' => [
                    ['FirstName' => 'Ingo'],
                ],
                self::$oneItemList,
            ],
            [
                'twoParametersOneItem' => [
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                ],
                self::$oneItemList,
            ],
            [
                'oneParameterTwoItems' => [
                    ['FirstName' => 'Ingo'],
                    ['FirstName' => 'Sam'],
                ],
                self::$twoItemList,
            ],
            [
                'twoParametersTwoItems' => [
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'Minnee'],
                ],
                self::$twoItemList,
            ],
            [
                'mixedParametersTwoItems' => [
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam'],
                ],
                self::$twoItemList,
            ],
        ];
    }

    /**
     * @return array
     */
    public static function provideNonEqualLists()
    {

        return [
            [
                'checkAgainstEmptyList' => [
                    ['FirstName' => 'Ingo'],
                ],
                [],
            ],
            [
                'oneItemExpectedListContainsMore' => [
                    ['FirstName' => 'Ingo'],
                ],
                self::$twoItemList,
            ],
            [
                'oneExpectationHasWrontParamter' => [
                    ['FirstName' => 'IngoXX'],
                    ['FirstName' => 'Sam'],
                ],
                self::$twoItemList,
            ],
            [
                'differentParametersInDifferentItemsAreWrong' => [
                    ['FirstName' => 'IngoXXX', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'MinneeXXX'],
                ],
                self::$twoItemList,
            ],
            [
                'differentParametersNotMatching' => [
                    ['FirstName' => 'Daniel', 'Surname' => 'Foo'],
                    ['FirstName' => 'Dan'],
                ],
                self::$twoItemList,
            ],
        ];
    }

    /**
     * @return array
     */
    public static function provideNotContainingList()
    {
        return [
            'listIsEmpty' => [
                [
                    ['FirstName' => 'Ingo'],
                ],
                [],
            ],
            'oneItemIsExpected' => [
                [
                    ['FirstName' => 'Sam'],
                ],
                self::$oneItemList,
            ],
            'twoParametersAreWrong' => [
                [
                    ['FirstName' => 'IngoXXX', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'MinneeXXX'],
                ],
                self::$twoItemList,
            ],
            'mixedList' => [
                [
                    ['FirstName' => 'Daniel', 'Surname' => 'Foo'],
                    ['FirstName' => 'Dan'],
                ],
                self::$twoItemList,
            ],
        ];
    }

    /**
     * @return array
     */
    public static function provideAllMatchingList()
    {
        return [
            'emptyMatch' => [
                [],
                self::$memberList,
                'empty list did not match',
            ],
            'allItemsWithLocaleSet' => [
                ['Locale' => 'en_US'],
                self::$memberList,
                'list with Locale set in all items did not match',
            ],
        ];
    }

    /**
     * @return array
     */
    public static function provideNotMatchingList()
    {
        return [
            'notAllItemsHaveLocaleSet' => [['FirstName' => 'Ingo'], self::$memberList],
        ];
    }
}
