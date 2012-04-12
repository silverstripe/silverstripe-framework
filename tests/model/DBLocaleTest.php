<?php
/**
 * @package framework
 * @subpackage tests
 */
class DBLocaleTest extends SapphireTest {
	function testNice() {
		$l = DBField::create_field('DBLocale', 'de_DE');
		$this->assertEquals($l->Nice(), 'German');
	}
	
	function testNiceNative() {
		$l = DBField::create_field('DBLocale', 'de_DE');
		$this->assertEquals($l->Nice(true), 'Deutsch');
	}
	
	function testNativeName() {
		$l = DBField::create_field('DBLocale', 'de_DE');
		$this->assertEquals($l->getNativeName(), 'Deutsch');
	}
}
