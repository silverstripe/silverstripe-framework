<?php

/**
 * @package framework
 * @subpackage tests
 */
class ArrayLibTest extends SapphireTest {

	public function testInvert() {
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

	public function testValuekey() {
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

	public function testArrayMergeRecursive() {
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

	public function testFlatten() {
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
}
