<?php
/**
 * @package framework
 * @subpackage tests
 */
class DateFieldViewJQueryTest extends SapphireTest {

	public function testConvert() {
		$this->assertEquals(
			'M d, yy',
			DateField_View_JQuery::convert_iso_to_jquery_format('MMM d, yyyy')
		);

		$this->assertEquals(
			'd/mm/yy',
			DateField_View_JQuery::convert_iso_to_jquery_format('d/MM/yyyy')
		);

		$this->assertEquals(
			'dd.m.yy',
			DateField_View_JQuery::convert_iso_to_jquery_format('dd.M.yyyy'),
			'Month, no leading zero'
		);

		$this->assertEquals(
			'dd.mm.yy',
			DateField_View_JQuery::convert_iso_to_jquery_format('dd.MM.yyyy'),
			'Month, two digit'
		);

		$this->assertEquals(
			'dd.M.yy',
			DateField_View_JQuery::convert_iso_to_jquery_format('dd.MMM.yyyy'),
			'Abbreviated month name'
		);

		$this->assertEquals(
			'dd.MM.yy',
			DateField_View_JQuery::convert_iso_to_jquery_format('dd.MMMM.yyyy'),
			'Full month name'
		);
	}
}
