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
            DataProvider::provideEqualLists()
        );
    }

    /**
     * @return array
     */
    public static function provideEqualLists()
    {
        return [
            'oneParameterOneItem' => [
                [
                    ['FirstName' => 'Ingo'],
                ],
                DataProvider::$oneItemList,
            ],
            'twoParametersOneItem' => [
                [
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                ],
                DataProvider::$oneItemList,
            ],
            'oneParameterTwoItems' => [
                [
                    ['FirstName' => 'Ingo'],
                    ['FirstName' => 'Sam'],
                ],
                DataProvider::$twoItemList,
            ],
            'twoParametersTwoItems' => [
                [
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'Minnee'],
                ],
                DataProvider::$twoItemList,
            ],
            'mixedParametersTwoItems' => [
                [
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam'],
                ],
                DataProvider::$twoItemList,
            ],
        ];
    }

    /**
     * @return array
     */
    public static function provideNonEqualLists()
    {

        return [
            'checkAgainstEmptyList' => [
                [
                    ['FirstName' => 'Ingo'],
                ],
                [],
            ],
            'oneItemExpectedListContainsMore' => [
                [
                    ['FirstName' => 'Ingo'],
                ],
                DataProvider::$twoItemList,
            ],
            'oneExpectationHasWrontParamter' => [
                [
                    ['FirstName' => 'IngoXX'],
                    ['FirstName' => 'Sam'],
                ],
                DataProvider::$twoItemList,
            ],
            'differentParametersInDifferentItemsAreWrong' => [
                [
                    ['FirstName' => 'IngoXXX', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'MinneeXXX'],
                ],
                DataProvider::$twoItemList,
            ],
            'differentParametersNotMatching' => [
                [
                    ['FirstName' => 'Daniel', 'Surname' => 'Foo'],
                    ['FirstName' => 'Dan'],
                ],
                DataProvider::$twoItemList,
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
                DataProvider::$oneItemList,
            ],
            'twoParametersAreWrong' => [
                [
                    ['FirstName' => 'IngoXXX', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'MinneeXXX'],
                ],
                DataProvider::$twoItemList,
            ],
            'mixedList' => [
                [
                    ['FirstName' => 'Daniel', 'Surname' => 'Foo'],
                    ['FirstName' => 'Dan'],
                ],
                DataProvider::$twoItemList,
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
                DataProvider::$memberList,
                'empty list did not match',
            ],
            'allItemsWithLocaleSet' => [
                ['Locale' => 'en_US'],
                DataProvider::$memberList,
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
            'notAllItemsHaveLocaleSet' => [['FirstName' => 'Ingo'], DataProvider::$memberList],
        ];
    }
}
