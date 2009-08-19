<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SSLogTest extends SapphireTest {

	protected $testEmailWriter;

	function setUp() {
		parent::setUp();
		$this->testEmailWriter = new SSLogEmailWriter('sean@silverstripe.com');
		SSLog::add_writer($this->testEmailWriter, SSLog::ERR);
	}

	function testExistingWriter() {
		$writers = SSLog::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(1, count($writers));
	}

	function testRemoveWriter() {
		SSLog::remove_writer($this->testEmailWriter);
		$writers = SSLog::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(0, count($writers));
	}

}