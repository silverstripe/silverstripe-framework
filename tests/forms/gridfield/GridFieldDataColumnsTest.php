<?php

use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;


class GridFieldDataColumnsTest extends SapphireTest {

	/**
	 * @covers SilverStripe\Forms\GridField\GridFieldDataColumns::getDisplayFields
	 */
	public function testGridFieldGetDefaultDisplayFields() {
		$obj = new GridField('testfield', 'testfield', Member::get());
		$expected = Member::singleton()->summaryFields();
		$columns = $obj->getConfig()->getComponentByType('SilverStripe\\Forms\\GridField\\GridFieldDataColumns');
		$this->assertEquals($expected, $columns->getDisplayFields($obj));
	}

	/**
	 * @covers SilverStripe\Forms\GridField\GridFieldDataColumns::setDisplayFields
	 * @covers SilverStripe\Forms\GridField\GridFieldDataColumns::getDisplayFields
	 */
	public function testGridFieldCustomDisplayFields() {
		$obj = new GridField('testfield', 'testfield', Member::get());
		/** @skipUpgrade */
		$expected = array('Email' => 'Email');
		$columns = $obj->getConfig()->getComponentByType('SilverStripe\\Forms\\GridField\\GridFieldDataColumns');
		$columns->setDisplayFields($expected);
		$this->assertEquals($expected, $columns->getDisplayFields($obj));
	}

	/**
	 * @covers SilverStripe\Forms\GridField\GridFieldDataColumns::setDisplayFields
	 * @covers SilverStripe\Forms\GridField\GridFieldDataColumns::getDisplayFields
	 */
	public function testGridFieldDisplayFieldsWithBadArguments() {
		$this->setExpectedException('InvalidArgumentException');
		$obj = new GridField('testfield', 'testfield', Member::get());
		$columns = $obj->getConfig()->getComponentByType('SilverStripe\\Forms\\GridField\\GridFieldDataColumns');
		$columns->setDisplayFields(new stdClass());
	}

	/**
	 * @covers SilverStripe\Forms\GridField\GridFieldDataColumns::getFieldCasting
	 * @covers SilverStripe\Forms\GridField\GridFieldDataColumns::setFieldCasting
	 */
	public function testFieldCasting() {
		$obj = new GridField('testfield', 'testfield');
		$columns = $obj->getConfig()->getComponentByType('SilverStripe\\Forms\\GridField\\GridFieldDataColumns');
		$this->assertEquals(array(), $columns->getFieldCasting());
		$columns->setFieldCasting(array("MyShortText"=>"Text->FirstSentence"));
		$this->assertEquals(array("MyShortText"=>"Text->FirstSentence"), $columns->getFieldCasting());
	}

	/**
	 * @covers SilverStripe\Forms\GridField\GridFieldDataColumns::getFieldFormatting
	 * @covers SilverStripe\Forms\GridField\GridFieldDataColumns::setFieldFormatting
	 */
	public function testFieldFormatting() {
		$obj = new GridField('testfield', 'testfield');
		$columns = $obj->getConfig()->getComponentByType('SilverStripe\\Forms\\GridField\\GridFieldDataColumns');
		$this->assertEquals(array(), $columns->getFieldFormatting());
		$columns->setFieldFormatting(array("myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'));
		$this->assertEquals(array("myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'),
			$columns->getFieldFormatting());
	}
}
