<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SS_LogTest extends SapphireTest {

	protected $testEmailWriter;

	protected $testFileWriter;

	protected $testLogPath = null;

	function setUp() {
		parent::setUp();
	
		SS_Log::clear_writers(); // this test will break if existing writers are available!

		$this->testLogPath = BASE_PATH . '/SS_LogTest.log';
		$this->testEmailWriter = new SS_LogEmailWriter('sean@silverstripe.com');
		$this->testFileWriter = new SS_LogFileWriter($this->testLogPath);
		SS_Log::add_writer($this->testEmailWriter, SS_Log::ERR);
		SS_Log::add_writer($this->testFileWriter, SS_Log::WARN);
	}

	function tearDown() {
		parent::tearDown();

		unlink($this->testLogPath);

		// TODO Reinstate original writers
	}

	function testCreatesLogFileInFileWriter() {
		// Filewriter is constructed in setUp()
		$this->assertFileExists($this->testLogPath);
	}

	function testLogFormats() {
		// Testing on file writer, but the writer implementation shouldn't matter here

		SS_Log::log('As string', SS_Log::WARN);
		$this->assertContains(
			'As string', 
			file_get_contents($this->testLogPath),
			'Correctly logs messages as strings'
		);

		SS_Log::log(new Exception('As exception'), SS_Log::WARN);
		$this->assertContains(
			'As exception', 
			file_get_contents($this->testLogPath),
			'Correctly logs messages as exceptions'
		);

		$message = array(
			'errno' => null,
			'errstr' => 'As array',
			'errfile' => null,
			'errline' => null,
			'errcontext' => null
		);
		SS_Log::log($message, SS_Log::WARN);
		$this->assertContains(
			'As array', 
			file_get_contents($this->testLogPath),
			'Correctly logs messages as array data'
		);
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