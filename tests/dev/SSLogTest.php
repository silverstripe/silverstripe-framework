<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SSLogTest extends SapphireTest {

	protected $testEmailWriter;

	function setUp() {
		parent::setUp();
		SSLog::clear_writers(); // this test will break if existing writers are available!
		$this->testEmailWriter = new SSLogEmailWriter('sean@silverstripe.com');
		$this->testFileWriter = new SSLogFileWriter('../test.log');
		SSLog::add_writer($this->testEmailWriter, SSLog::ERR);
		SSLog::add_writer($this->testFileWriter, SSLog::WARN);
	}

	function testExistingWriter() {
		$writers = SSLog::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(2, count($writers));
	}

	function testRemoveWriter() {
		SSLog::remove_writer($this->testEmailWriter);
		$writers = SSLog::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(1, count($writers));
		SSLog::remove_writer($this->testFileWriter);
		$writers = SSLog::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(0, count($writers));
	}

}