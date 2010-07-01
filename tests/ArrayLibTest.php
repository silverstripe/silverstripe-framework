<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ArrayLibTest extends SapphireTest {
	function testInvert() {
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

	function testValuekey() {
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

}