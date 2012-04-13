<?php
/**
 * @package framework
 * @subpackage tests
 */
class OptionsetFieldTest extends SapphireTest {
	function testSetDisabledItems() {
		$f = new OptionsetField(
			'Test', 
			false, 
			array(0 => 'Zero', 1 => 'One')
		);
		
		$f->setDisabledItems(array(0));
		$p = new CSSContentParser($f->Field());
		$item0 = $p->getBySelector('#Test_0');
		$item1 = $p->getBySelector('#Test_1');
		$this->assertEquals(
			(string)$item0[0]['disabled'],
			'disabled'
		);
		$this->assertEquals(
			(string)$item1[0]['disabled'],
			''
		);
	}
}
