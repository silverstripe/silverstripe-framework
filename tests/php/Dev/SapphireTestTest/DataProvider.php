<?php

namespace SilverStripe\Dev\Tests\SapphireTestTest;

use SilverStripe\Dev\TestOnly;


class DataProvider implements TestOnly
{

    public static function provideEqualLists()
    {
        $oneItemList = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer']
        ];
        $twoItemList = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
            ['FirstName' => 'Sam', 'Surname' => 'Minnee']
        ];

        return [
            [ //empty list
                [],
                []
            ],
            [
                [ //one param
                    ['FirstName' => 'Ingo']
                ]
                ,
                $oneItemList
            ],
            [
                [ //two params
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer']
                ],
                $oneItemList
            ],
            [ //only one param
                [
                    ['FirstName' => 'Ingo'],
                    ['FirstName' => 'Sam']
                ]
                ,
                $twoItemList
            ],
            [
                [ //two params
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'Minnee']
                ],
                $twoItemList
            ],
            [
                [ //mixed
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam']
                ],
                $twoItemList
            ],
        ];
    }

    public function provideNonEqualLists()
    {
        $oneItemList = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer']
        ];
        $twoItemList = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
            ['FirstName' => 'Sam', 'Surname' => 'Minnee']
        ];

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
                , $twoItemList
            ],
            [ //one item with wrong param
                [
                    ['FirstName' => 'IngoXX'],
                    ['FirstName' => 'Sam']
                ]
                , $twoItemList
            ],
            [
                [ //two params wrong
                    ['FirstName' => 'IngoXXX', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'MinneeXXX']
                ],
                $twoItemList
            ],
            [
                [ //mixed
                    ['FirstName' => 'Daniel', 'Surname' => 'Foo'],
                    ['FirstName' => 'Dan']
                ],
                $twoItemList
            ],
        ];
    }

}
