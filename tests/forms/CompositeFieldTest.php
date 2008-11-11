<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class CompositeFieldTest extends SapphireTest {
	
	function testFieldPosition() {
		$compositeOuter = new CompositeField(
			new TextField('A'),
			new TextField('B'),
			$compositeInner = new CompositeField(
				new TextField('C1'),
				new TextField('C2')
			),
			new TextField('D')
		);
		
		$this->assertEquals(0, $compositeOuter->fieldPosition('A'));
		$this->assertEquals(1, $compositeOuter->fieldPosition('B'));
		$this->assertEquals(3, $compositeOuter->fieldPosition('D'));
		
		$this->assertEquals(0, $compositeInner->fieldPosition('C1'));
		$this->assertEquals(1, $compositeInner->fieldPosition('C2'));
		
		$compositeOuter->insertBefore(new TextField('AB'), 'B');
		$this->assertEquals(0, $compositeOuter->fieldPosition('A'));
		$this->assertEquals(1, $compositeOuter->fieldPosition('AB'));
		$this->assertEquals(2, $compositeOuter->fieldPosition('B'));
	}
	
}
?>