<?php
/**
 * @package framework
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
	
	function testTag() {
		$div = new CompositeField(
			new TextField('A'),
			new TextField('B')
		);
		$this->assertStringStartsWith('<div', trim($div->FieldHolder()));
		$this->assertStringEndsWith('/div>', trim($div->FieldHolder()));
		
		$fieldset = new CompositeField();
		$fieldset->setTag('fieldset');

		$this->assertStringStartsWith('<fieldset', trim($fieldset->FieldHolder()));
		$this->assertStringEndsWith('/fieldset>', trim($fieldset->FieldHolder()));		
	}

	function testLegend() {
		$composite = new CompositeField(
			new TextField('A'),
			new TextField('B')
		);
		
		$composite->setTag('fieldset');
		$composite->setLegend('My legend');
		
		$parser = new CSSContentParser($composite->Field());
		$root = $parser->getBySelector('fieldset.composite');
		$legend = $parser->getBySelector('fieldset.composite legend');
		
		$this->assertNotNull($legend);
		$this->assertEquals('My legend', (string)$legend[0]);
	}
}
