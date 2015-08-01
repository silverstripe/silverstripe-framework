<?php

/**
 * @package framework
 * @subpackage tests
 */
class ArrayLibTest extends SapphireTest {

	public function testInvert() {
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

	public function testValuekey() {
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

	public function testArrayMergeRecursive() {
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

	public function testFlatten() {
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
}
