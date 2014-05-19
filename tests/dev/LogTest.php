<?php

require_once 'Zend/Log/Writer/Abstract.php';

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

		SS_Log::clear_writers();
	}

	function tearDown() {
		parent::tearDown();

		SS_Log::clear_writers();
	}

	function testLogFormats() {
		// TODO Use dummy logger
		$logPath = '../test.log';
		$testFileWriter = new SS_LogFileWriter($logPath);
		SS_Log::add_writer($testFileWriter, SS_Log::WARN);

		SS_Log::log('As string', SS_Log::WARN);
		$this->assertContains(
			'As string', 
			file_get_contents($logPath),
			'Correctly logs messages as strings'
		);

		SS_Log::log(new Exception('As exception'), SS_Log::WARN);
		$this->assertContains(
			'As exception', 
			file_get_contents($logPath),
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
			file_get_contents($logPath),
			'Correctly logs messages as array data'
		);
	}

	function testExistingWriter() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		$testFileWriter = new SS_LogFileWriter('../test.log');
		SS_Log::add_writer($testEmailWriter, SS_Log::ERR);
		SS_Log::add_writer($testFileWriter, SS_Log::WARN);

		$writers = SS_Log::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(2, count($writers));
	}

	function testRemoveWriter() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		$testFileWriter = new SS_LogFileWriter('../test.log');
		SS_Log::add_writer($testEmailWriter, SS_Log::ERR);
		SS_Log::add_writer($testFileWriter, SS_Log::WARN);

		SS_Log::remove_writer($testEmailWriter);
		$writers = SS_Log::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(1, count($writers));

		SS_Log::remove_writer($testFileWriter);
		$writers = SS_Log::get_writers();
		$this->assertType('array', $writers);
		$this->assertEquals(0, count($writers));
	}

	function testEmailWriter() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		SS_Log::add_writer($testEmailWriter, SS_Log::ERR);

		SS_Log::log('Email test', SS_LOG::ERR, array('my-string' => 'test', 'my-array' => array('one' => 1)));
		$this->assertEmailSent('test@test.com');
		$email = $this->findEmail('test@test.com');
		$parser = new CSSContentParser($email['htmlContent']);
		$extras = $parser->getBySelector('table.extras');
		$extraRows = $extras[0]->tr;
		$this->assertContains('my-string', $extraRows[count($extraRows)-2]->td[0]->asXML(), 'Contains extra data key');
		$this->assertContains('test', $extraRows[count($extraRows)-2]->td[1]->asXML(), 'Contains extra data value');
		$this->assertContains('my-array', $extraRows[count($extraRows)-1]->td[0]->asXML(), 'Contains extra data key');
		$this->assertContains(
			"array('one'=&gt;1,)", 
			str_replace(array("\r", "\n", " ", '<br/>'), '', $extraRows[count($extraRows)-1]->td[1]->asXML()), 
			'Serializes arrays correctly'
		);
	}

	function testAddEventItemCallback() {
		$writer = new SS_LogTest_Writer();
		$logger = new SS_ZendLog();
		$logger->addEventItemCallback('test', function() {
			return array('extrakey' => 'extraval');
		});
		$logger->addWriter($writer);
		$logger->log('test event', Zend_Log::DEBUG, array('paramkey' => 'paramval'));
		$eventData = $writer->events[0];
		$this->assertArrayHasKey('paramkey', $eventData);
		$this->assertEquals('paramval', $eventData['paramkey']);
		$this->assertArrayHasKey('extrakey', $eventData);
		$this->assertEquals('extraval', $eventData['extrakey']);
	}

}

class SS_LogTest_Writer extends Zend_Log_Writer_Abstract implements TestOnly {
	public $events;

	public static function factory($config) {
		return new SS_LogTest_Writer();
	}

	protected function _write($event) {
		$this->events[] = $event;
	}
}