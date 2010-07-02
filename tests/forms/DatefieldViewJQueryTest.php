<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DateFieldViewJQueryTest extends SapphireTest {
	
	function testConvert() {
		$this->assertEquals(
			'M d, yy',
			DateField_View_JQuery::convert_iso_to_jquery_format('MMM d, yyyy')
		);
		
		$this->assertEquals(
			'd/mm/yy',
			DateField_View_JQuery::convert_iso_to_jquery_format('d/MM/yyyy')
		);
		
		$this->assertEquals(
			'dd.mm.yy',
			DateField_View_JQuery::convert_iso_to_jquery_format('dd.MM.yyyy')
		);
	}
}