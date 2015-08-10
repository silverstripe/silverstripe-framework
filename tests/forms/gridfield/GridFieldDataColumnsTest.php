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
		$expected = ['Email' => 'Email'];
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
		$this->assertEquals([], $columns->getFieldCasting());
		$columns->setFieldCasting(["MyShortText"=>"Text->FirstSentence"]);
		$this->assertEquals(["MyShortText"=>"Text->FirstSentence"], $columns->getFieldCasting());
	}

	/**
	 * @covers GridFieldDataColumns::getFieldFormatting
	 * @covers GridFieldDataColumns::setFieldFormatting
	 */
	public function testFieldFormatting() {
		$obj = new GridField('testfield', 'testfield');
		$columns = $obj->getConfig()->getComponentByType('GridFieldDataColumns');
		$this->assertEquals([], $columns->getFieldFormatting());
		$columns->setFieldFormatting(["myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>']);
		$this->assertEquals(["myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'],
			$columns->getFieldFormatting());
	}
}
