<?php
/**
 * @package framework
 * @subpackage tests
 */
class DBTest extends SapphireTest {

	protected $origEnvType;

	function setUp() {
		$this->origEnvType = Director::get_environment_type();
		Director::set_environment_type('dev');

		parent::setUp();
	}

	function tearDown() {
		Director::set_environment_type($this->origEnvType);

		parent::tearDown();
	}

	function testValidAlternativeDatabaseName() {
		$this->assertTrue(DB::valid_alternative_database_name('ss_tmpdb1234567'));
		$this->assertFalse(DB::valid_alternative_database_name('ss_tmpdb12345678'));
		$this->assertFalse(DB::valid_alternative_database_name('tmpdb1234567'));
		$this->assertFalse(DB::valid_alternative_database_name('random'));
		$this->assertFalse(DB::valid_alternative_database_name(''));

		$origEnvType = Director::get_environment_type();
		Director::set_environment_type('live');		
		$this->assertFalse(DB::valid_alternative_database_name('ss_tmpdb1234567'));
		Director::set_environment_type($origEnvType);		
	}

}