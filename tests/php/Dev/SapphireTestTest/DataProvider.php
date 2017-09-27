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

    public static function provideEqualLists()
    {
        return [
            [ //empty list
                [],
                []
            ],
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
}
