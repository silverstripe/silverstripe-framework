<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\ArrayLib;
use SilverStripe\Dev\SapphireTest;

class ArrayLibTest extends SapphireTest
{

    public function testInvert()
    {
        $arr = [
            'row1' => [
                'col1' =>'val1',
                'col2' => 'val2'
            ],
            'row2' => [
                'col1' => 'val3',
                'col2' => 'val4'
            ]
        ];

        $this->assertEquals(
            ArrayLib::invert($arr),
            [
                'col1' => [
                    'row1' => 'val1',
                    'row2' => 'val3',
                ],
                'col2' => [
                    'row1' => 'val2',
                    'row2' => 'val4',
                ],
            ]
        );
    }

    public function testValuekey()
    {
        $this->assertEquals(
            ArrayLib::valuekey(
                [
                    'testkey1' => 'testvalue1',
                    'testkey2' => 'testvalue2'
                ]
            ),
            [
                'testvalue1' => 'testvalue1',
                'testvalue2' => 'testvalue2'
            ]
        );
    }

    public function testArrayMapRecursive()
    {
        $array = [
            'a ',
            ['  b', 'c'],
        ];
        $strtoupper = [
            'A ',
            ['  B', 'C'],
        ];
        $trim = [
            'a',
            ['b', 'c'],
        ];
        $this->assertEquals(
            $strtoupper,
            ArrayLib::array_map_recursive('strtoupper', $array)
        );
        $this->assertEquals(
            $trim,
            ArrayLib::array_map_recursive('trim', $array)
        );
    }

    public function testArrayMergeRecursive()
    {
        $first = [
            'first' => 'a',
            'second' => 'b',
        ];
        $second = [
            'third' => 'c',
            'fourth' => 'd',
        ];
        $expected = [
            'first' => 'a',
            'second' => 'b',
            'third' => 'c',
            'fourth' => 'd',
        ];
        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'First values should supplement second values'
        );

        $first = [
            'first' => 'a',
            'second' => 'b',
        ];
        $second = [
            'first' => 'c',
            'third' => 'd',
        ];
        $expected = [
            'first' => 'c',
            'second' => 'b',
            'third' => 'd',
        ];
        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'Second values should override first values'
        );

        $first = [
            'first' => [
                'first' => 'a',
            ],
            'second' => [
                'second' => 'b',
            ],
        ];
        $second = [
            'first' => [
                'first' => 'c',
            ],
            'third' => [
                'third' => 'd',
            ],
        ];
        $expected = [
            'first' => [
                'first' => 'c',
            ],
            'second' => [
                'second' => 'b',
            ],
            'third' => [
                'third' => 'd',
            ],
        ];
        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'Nested second values should override first values'
        );

        $first = [
            'first' => [
                'first' => 'a',
            ],
            'second' => [
                'second' => 'b',
            ],
        ];
        $second = [
            'first' => [
                'second' => 'c',
            ],
            'third' => [
                'third' => 'd',
            ],
        ];
        $expected = [
            'first' => [
                'first' => 'a',
                'second' => 'c',
            ],
            'second' => [
                'second' => 'b',
            ],
            'third' => [
                'third' => 'd',
            ],
        ];
        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'Nested first values should supplement second values'
        );

        $first = [
            'first' => [
                0 => 'a',
            ],
            'second' => [
                1 => 'b',
            ],
        ];
        $second = [
            'first' => [
                0 => 'c',
            ],
            'third' => [
                2 => 'd',
            ],
        ];
        $expected = [
            'first' => [
                0 => 'c',
            ],
            'second' => [
                1 => 'b',
            ],
            'third' => [
                2 => 'd',
            ],
        ];

        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'Numeric keys should behave like string keys'
        );
    }

    public function testFlatten()
    {
        $options = [
            '1' => 'one',
            '2' => 'two'
        ];

        $expected = $options;

        $this->assertEquals($expected, ArrayLib::flatten($options));

        $options = [
            '1' => [
                '2' => 'two',
                '3' => 'three'
            ],
            '4' => 'four'
        ];

        $expected = [
            '2' => 'two',
            '3' => 'three',
            '4' => 'four'
        ];

        $this->assertEquals($expected, ArrayLib::flatten($options));
    }

    /**
     * Test that items can be added during iteration
     */
    public function testIterateVolatileAppended()
    {
        $initial = [
            'one' => [ 'next' => 'two', 'prev' => null ],
            'two' => [ 'next' => 'three', 'prev' => 'one' ],
            'three' => [ 'next' => null, 'prev' => 'two' ],
        ];

        // Test new items are iterated
        $items = $initial;
        $seen = [];
        foreach (ArrayLib::iterateVolatile($items) as $key => $value) {
            $seen[$key] = $value;
            // Append four
            if ($key === 'three') {
                $items['three']['next'] = 'four';
                $items['four'] = [ 'next' => null, 'prev' => 'three'];
            }
            // Prepend zero won't force it to be iterated next, but it will be iterated
            if ($key === 'one') {
                $items['one']['next'] = 'zero';
                $items = array_merge(
                    ['zero' => [ 'next' => 'one', 'prev' => 'three']],
                    $items
                );
            }
        }
        $expected = [
            'one' => [ 'next' => 'two', 'prev' => null ],
            'two' => [ 'next' => 'three', 'prev' => 'one' ],
            'three' => [ 'next' => null, 'prev' => 'two' ],
            'zero' => [ 'next' => 'one', 'prev' => 'three'],
            'four' => [ 'next' => null, 'prev' => 'three']
        ];
        // All items are iterated (order not deterministic)
        $this->assertEquals(
            $expected,
            $seen,
            'New items are iterated over'
        );
    }

    /**
     * Test that items can be modified during iteration
     */
    public function testIterateVolatileModified()
    {
        $initial = [
            'one' => [ 'next' => 'two', 'prev' => null ],
            'two' => [ 'next' => 'three', 'prev' => 'one' ],
            'three' => [ 'next' => 'four', 'prev' => 'two' ],
            'four' => [ 'next' => null, 'prev' => 'three' ],
        ];

        // Test new items are iterated
        $items = $initial;
        $seen = [];
        foreach (ArrayLib::iterateVolatile($items) as $key => $value) {
            $seen[$key] = $value;
            // One modifies two
            if ($key === 'one') {
                $items['two']['modifiedby'] = 'one';
            }
            // Two removes three, preventing it from being iterated next
            if ($key === 'two') {
                unset($items['three']);
            }
            // Four removes two, but since it's already been iterated by this point
            // it's too late.
            if ($key === 'four') {
                unset($items['two']);
            }
        }
        $expected = [
            'one' => [ 'next' => 'two', 'prev' => null ],
            'two' => [ 'next' => 'three', 'prev' => 'one', 'modifiedby' => 'one' ],
            'four' => [ 'next' => null, 'prev' => 'three' ],
        ];
        // All items are iterated (order not deterministic)
        $this->assertEquals(
            ksort($expected),
            ksort($seen),
            'New items are iterated over'
        );
    }
}
