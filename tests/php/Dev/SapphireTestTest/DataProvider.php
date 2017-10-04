<?php

namespace SilverStripe\Dev\Tests\SapphireTestTest;

use SilverStripe\Dev\TestOnly;

class DataProvider implements TestOnly
{
    protected static $oneItemList = [
        ['FirstName' => 'Ingo', 'Surname' => 'Schommer']
    ];

    protected static $twoItemList = [
        ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
        ['FirstName' => 'Sam', 'Surname' => 'Minnee']
    ];

    /**
     * @return array
     */
    public static function provideEqualListsWithEmptyList()
    {
        return array_merge(
            [ //empty list
                [
                    [],
                    []
                ]
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
                [ //one param
                    ['FirstName' => 'Ingo']
                ],
                self::$oneItemList
            ],
            [
                [ //two params
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer']
                ],
                self::$oneItemList
            ],
            [ //only one param
                [
                    ['FirstName' => 'Ingo'],
                    ['FirstName' => 'Sam']
                ],
                self::$twoItemList
            ],
            [
                [ //two params
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'Minnee']
                ],
                self::$twoItemList
            ],
            [
                [ //mixed
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam']
                ],
                self::$twoItemList
            ],
        ];
    }

    /**
     * @return array
     */
    public static function provideNonEqualLists()
    {

        return [
            [ //empty list
                [
                    ['FirstName' => 'Ingo']
                ],
                []
            ],
            [
                [ //one item expected
                    ['FirstName' => 'Ingo']
                ]
                ,
                self::$twoItemList
            ],
            [ //one item with wrong param
                [
                    ['FirstName' => 'IngoXX'],
                    ['FirstName' => 'Sam']
                ]
                ,
                self::$twoItemList
            ],
            [
                [ //two params wrong
                    ['FirstName' => 'IngoXXX', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'MinneeXXX']
                ],
                self::$twoItemList
            ],
            [
                [ //mixed
                    ['FirstName' => 'Daniel', 'Surname' => 'Foo'],
                    ['FirstName' => 'Dan']
                ],
                self::$twoItemList
            ],
        ];
    }

    /**
     * @return array
     */
    public static function provideNotContainingList()
    {
        return [
            [ //empty list
                [
                    ['FirstName' => 'Ingo']
                ],
                []
            ],
            [
                [ //one item expected
                    ['FirstName' => 'Sam']
                ]
                ,
                self::$oneItemList
            ],
            [
                [ //two params wrong
                    ['FirstName' => 'IngoXXX', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'MinneeXXX']
                ],
                self::$twoItemList
            ],
            [
                [ //mixed
                    ['FirstName' => 'Daniel', 'Surname' => 'Foo'],
                    ['FirstName' => 'Dan']
                ],
                self::$twoItemList
            ],
        ];
    }

    /**
     * @return array
     */
    public static function provideAllMatchingList()
    {
        $list = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer', 'Locale' => 'en_US'],
            ['FirstName' => 'Sam', 'Surname' => 'Minnee', 'Locale' => 'en_US']
        ];

        return [
            [[], $list], //empty match
            [['Locale' => 'en_US'], $list] //all items have this field set
        ];
    }

    /**
     * @return array
     */
    public static function provideNotMatchingList()
    {
        $list = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer', 'Locale' => 'en_US'],
            ['FirstName' => 'Sam', 'Surname' => 'Minnee', 'Locale' => 'en_US']
        ];

        return [
            [['FirstName' => 'Ingo'], $list] //not all items have this field set
        ];
    }
}
