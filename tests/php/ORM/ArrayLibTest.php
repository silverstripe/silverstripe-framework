<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\ArrayLib;
use SilverStripe\Dev\SapphireTest;

class ArrayLibTest extends SapphireTest
{

    public function testInvert()
    {
        $arr = array(
            'row1' => array(
                'col1' =>'val1',
                'col2' => 'val2'
            ),
            'row2' => array(
                'col1' => 'val3',
                'col2' => 'val4'
            )
        );

        $this->assertEquals(
            ArrayLib::invert($arr),
            array(
                'col1' => array(
                    'row1' => 'val1',
                    'row2' => 'val3',
                ),
                'col2' => array(
                    'row1' => 'val2',
                    'row2' => 'val4',
                ),
            )
        );
    }

    public function testValuekey()
    {
        $this->assertEquals(
            ArrayLib::valuekey(
                array(
                    'testkey1' => 'testvalue1',
                    'testkey2' => 'testvalue2'
                )
            ),
            array(
                'testvalue1' => 'testvalue1',
                'testvalue2' => 'testvalue2'
            )
        );
    }

    public function testArrayMapRecursive()
    {
        $array = array(
            'a ',
            array('  b', 'c'),
        );
        $strtoupper = array(
            'A ',
            array('  B', 'C'),
        );
        $trim = array(
            'a',
            array('b', 'c'),
        );
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
        $first = array(
            'first' => 'a',
            'second' => 'b',
        );
        $second = array(
            'third' => 'c',
            'fourth' => 'd',
        );
        $expected = array(
            'first' => 'a',
            'second' => 'b',
            'third' => 'c',
            'fourth' => 'd',
        );
        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'First values should supplement second values'
        );

        $first = array(
            'first' => 'a',
            'second' => 'b',
        );
        $second = array(
            'first' => 'c',
            'third' => 'd',
        );
        $expected = array(
            'first' => 'c',
            'second' => 'b',
            'third' => 'd',
        );
        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'Second values should override first values'
        );

        $first = array(
            'first' => array(
                'first' => 'a',
            ),
            'second' => array(
                'second' => 'b',
            ),
        );
        $second = array(
            'first' => array(
                'first' => 'c',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $expected = array(
            'first' => array(
                'first' => 'c',
            ),
            'second' => array(
                'second' => 'b',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'Nested second values should override first values'
        );

        $first = array(
            'first' => array(
                'first' => 'a',
            ),
            'second' => array(
                'second' => 'b',
            ),
        );
        $second = array(
            'first' => array(
                'second' => 'c',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $expected = array(
            'first' => array(
                'first' => 'a',
                'second' => 'c',
            ),
            'second' => array(
                'second' => 'b',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'Nested first values should supplement second values'
        );

        $first = array(
            'first' => array(
                0 => 'a',
            ),
            'second' => array(
                1 => 'b',
            ),
        );
        $second = array(
            'first' => array(
                0 => 'c',
            ),
            'third' => array(
                2 => 'd',
            ),
        );
        $expected = array(
            'first' => array(
                0 => 'c',
            ),
            'second' => array(
                1 => 'b',
            ),
            'third' => array(
                2 => 'd',
            ),
        );

        $this->assertEquals(
            $expected,
            ArrayLib::array_merge_recursive($first, $second),
            'Numeric keys should behave like string keys'
        );
    }

    public function testFlatten()
    {
        $options = array(
            '1' => 'one',
            '2' => 'two'
        );

        $expected = $options;

        $this->assertEquals($expected, ArrayLib::flatten($options));

        $options = array(
            '1' => array(
                '2' => 'two',
                '3' => 'three'
            ),
            '4' => 'four'
        );

        $expected = array(
            '2' => 'two',
            '3' => 'three',
            '4' => 'four'
        );

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
