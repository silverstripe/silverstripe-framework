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
	
	function testTag() {
		$composite = new CompositeField(
			new TextField('A'),
			new TextField('B')
		);
		$this->assertStringStartsWith('<div', trim($composite->FieldHolder()));
		$this->assertStringEndsWith('/div>', trim($composite->FieldHolder()));

		$composite->setTag('fieldset');
		$this->assertStringStartsWith('<fieldset', trim($composite->FieldHolder()));
		$this->assertStringEndsWith('/fieldset>', trim($composite->FieldHolder()));		
	}

	function testLegend() {
		$composite = new CompositeField(
			new TextField('A'),
			new TextField('B')
		);
		$composite->setLegend('My legend');
		$parser = new CSSContentParser($composite->Field());
		$root = $parser->getBySelector('div.composite');
		$this->assertObjectHasAttribute('title', $root[0]->attributes());
		$this->assertEquals('My legend', (string)$root[0]['title']);

		$composite->setTag('fieldset');
		$composite->setLegend('My legend');
		$parser = new CSSContentParser($composite->Field());
		$root = $parser->getBySelector('fieldset.composite');
		$this->assertObjectNotHasAttribute('title', $root[0]->attributes());
		$legend = $parser->getBySelector('fieldset.composite legend');
		$this->assertNotNull($legend);
		$this->assertEquals('My legend', (string)$legend[0]);
	}
}
