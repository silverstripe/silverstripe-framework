<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SS_LogTest extends SapphireTest {

	protected $testEmailWriter;

	function setUp() {
		parent::setUp();
		SS_Log::clear_writers(); // this test will break if existing writers are available!
		$this->testEmailWriter = new SS_LogEmailWriter('sean@silverstripe.com');
		$this->testFileWriter = new SS_LogFileWriter('../test.log');
		SS_Log::add_writer($this->testEmailWriter, SS_Log::ERR);
		SS_Log::add_writer($this->testFileWriter, SS_Log::WARN);
	}

	function testExistingWriter() {
		$writers = SS_Log::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(2, count($writers));
	}

	function testRemoveWriter() {
		SS_Log::remove_writer($this->testEmailWriter);
		$writers = SS_Log::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(1, count($writers));
		SS_Log::remove_writer($this->testFileWriter);
		$writers = SS_Log::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(0, count($writers));
	}

}