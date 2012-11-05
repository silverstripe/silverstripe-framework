<?php
/**
 * @package framework
 * @subpackage tests
 */
class DBLocaleTest extends SapphireTest {

	protected static $build_db_each_test = false;
	public function testNice() {
		$l = DBField::create_field('DBLocale', 'de_DE');
		$this->assertEquals($l->Nice(), 'German');
	}
	
	public function testNiceNative() {
		$l = DBField::create_field('DBLocale', 'de_DE');
		$this->assertEquals($l->Nice(true), 'Deutsch');
	}
	
	public function testNativeName() {
		$l = DBField::create_field('DBLocale', 'de_DE');
		$this->assertEquals($l->getNativeName(), 'Deutsch');
	}
}
