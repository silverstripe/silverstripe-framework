<?php
/**
 * @package framework
 * @subpackage tests
 */
class OptionsetFieldTest extends SapphireTest {
	public function testSetDisabledItems() {
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

	public function testReadonlyField() {
		$sourceArray = array(0 => 'No', 1 => 'Yes');
		$field = new OptionsetField('FeelingOk', 'are you feeling ok?', $sourceArray, 1);
		$field->setEmptyString('(Select one)');
		$field->setValue(1);
		$readonlyField = $field->performReadonlyTransformation();
		preg_match('/Yes/', $field->Field(), $matches);
		$this->assertEquals($matches[0], 'Yes');
	}
}
