<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DBLocaleTest extends SapphireTest {
	function testNice() {
		$l = DBField::create('DBLocale', 'de_DE');
		$this->assertEquals($l->Nice(), 'German');
	}
}
?>