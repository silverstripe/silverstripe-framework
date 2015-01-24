<?php
/**
 * @package framework
 * @subpackage tests
 */
class DBTest extends SapphireTest {

	function testValidAlternativeDatabaseName() {

		$prefix = defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : 'ss_';

		Config::inst()->update('Director', 'environment_type', 'dev');
		$this->assertTrue(DB::valid_alternative_database_name($prefix.'tmpdb1234567'));
		$this->assertFalse(DB::valid_alternative_database_name($prefix.'tmpdb12345678'));
		$this->assertFalse(DB::valid_alternative_database_name('tmpdb1234567'));
		$this->assertFalse(DB::valid_alternative_database_name('random'));
		$this->assertFalse(DB::valid_alternative_database_name(''));

		Config::inst()->update('Director', 'environment_type', 'live');
		$this->assertFalse(DB::valid_alternative_database_name($prefix.'tmpdb1234567'));

		Config::inst()->update('Director', 'environment_type', 'dev');
	}

}
