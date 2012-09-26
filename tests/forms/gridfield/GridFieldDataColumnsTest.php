<?php
class GridFieldDataColumnsTest extends SapphireTest {

	/**
	 * @covers GridFieldDataColumns::getDisplayFields
	 */
	public function testGridFieldGetDefaultDisplayFields() {
		$obj = new GridField('testfield', 'testfield', Member::get());
		$expected = singleton('Member')->summaryFields();
		$columns = $obj->getConfig()->getComponentByType('GridFieldDataColumns');
		$this->assertEquals($expected, $columns->getDisplayFields($obj));
	}

	/**
	 * @covers GridFieldDataColumns::setDisplayFields
	 * @covers GridFieldDataColumns::getDisplayFields
	 */
	public function testGridFieldCustomDisplayFields() {
		$obj = new GridField('testfield', 'testfield', Member::get());
		$expected = array('Email' => 'Email');
		$columns = $obj->getConfig()->getComponentByType('GridFieldDataColumns');
		$columns->setDisplayFields($expected);
		$this->assertEquals($expected, $columns->getDisplayFields($obj));
	}

	/**
	 * @covers GridFieldDataColumns::setDisplayFields
	 * @covers GridFieldDataColumns::getDisplayFields
	 */
	public function testGridFieldDisplayFieldsWithBadArguments() {
		$this->setExpectedException('InvalidArgumentException');
		$obj = new GridField('testfield', 'testfield', Member::get());
		$columns = $obj->getConfig()->getComponentByType('GridFieldDataColumns');
		$columns->setDisplayFields(new stdClass());
	}

	/**
	 * @covers GridFieldDataColumns::getFieldCasting
	 * @covers GridFieldDataColumns::setFieldCasting
	 */
	public function testFieldCasting() {
		$obj = new GridField('testfield', 'testfield');
		$columns = $obj->getConfig()->getComponentByType('GridFieldDataColumns');
		$this->assertEquals(array(), $columns->getFieldCasting());
		$columns->setFieldCasting(array("MyShortText"=>"Text->FirstSentence"));
		$this->assertEquals(array("MyShortText"=>"Text->FirstSentence"), $columns->getFieldCasting());
	}

	/**
	 * @covers GridFieldDataColumns::getFieldFormatting
	 * @covers GridFieldDataColumns::setFieldFormatting
	 */
	public function testFieldFormatting() {
		$obj = new GridField('testfield', 'testfield');
		$columns = $obj->getConfig()->getComponentByType('GridFieldDataColumns');
		$this->assertEquals(array(), $columns->getFieldFormatting());
		$columns->setFieldFormatting(array("myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'));
		$this->assertEquals(array("myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'),
			$columns->getFieldFormatting());
	}
}
