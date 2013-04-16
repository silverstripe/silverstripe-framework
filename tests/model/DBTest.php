<?php
/**
 * @package framework
 * @subpackage tests
 */
class DBTest extends SapphireTest {

	function testValidAlternativeDatabaseName() {
		Config::inst()->update('Director', 'environment_type', 'dev');
		$this->assertTrue(DB::valid_alternative_database_name('ss_tmpdb1234567'));
		$this->assertFalse(DB::valid_alternative_database_name('ss_tmpdb12345678'));
		$this->assertFalse(DB::valid_alternative_database_name('tmpdb1234567'));
		$this->assertFalse(DB::valid_alternative_database_name('random'));
		$this->assertFalse(DB::valid_alternative_database_name(''));

		Config::inst()->update('Director', 'environment_type', 'live');
		$this->assertFalse(DB::valid_alternative_database_name('ss_tmpdb1234567'));

		Config::inst()->update('Director', 'environment_type', 'dev');
	}

}