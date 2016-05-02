<?php
/**
 * @package framework
 * @subpackage tests
 */
class SS_LogTest extends SapphireTest {

	protected $testEmailWriter;

	public function setUp() {
		parent::setUp();

		SS_Log::clear_writers();
	}

	public function tearDown() {
		parent::tearDown();

		SS_Log::clear_writers();
	}

	public function testExistingWriter() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		$testFileWriter = new SS_LogFileWriter('../test.log');
		SS_Log::add_writer($testEmailWriter, SS_Log::ERR);
		SS_Log::add_writer($testFileWriter, SS_Log::WARN);

		$writers = SS_Log::get_writers();
		$this->assertEquals(2, count($writers));
	}

	public function testRemoveWriter() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		$testFileWriter = new SS_LogFileWriter('../test.log');
		SS_Log::add_writer($testEmailWriter, SS_Log::ERR);
		SS_Log::add_writer($testFileWriter, SS_Log::WARN);

		SS_Log::remove_writer($testEmailWriter);
		$writers = SS_Log::get_writers();

		$this->assertEquals(1, count($writers));

		SS_Log::remove_writer($testFileWriter);
		$writers = SS_Log::get_writers();
		$this->assertEquals(0, count($writers));
	}

	public function testEmailWriter() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		SS_Log::add_writer($testEmailWriter, SS_Log::ERR);

		SS_Log::log('Email test', SS_Log::ERR, array('my-string' => 'test', 'my-array' => array('one' => 1)));
		$this->assertEmailSent('test@test.com');
		$email = $this->findEmail('test@test.com');
		$this->assertContains('[Error] Email test', $email['htmlContent']);
		$parser = new CSSContentParser($email['htmlContent']);
		$extras = $parser->getBySelector('table.extras');
		$extraRows = $extras[0]->tr;
		$this->assertContains('my-string', $extraRows[count($extraRows)-2]->td[0]->asXML(), 'Contains extra data key');
		$this->assertContains('test', $extraRows[count($extraRows)-2]->td[1]->asXML(), 'Contains extra data value');
		$this->assertContains('my-array', $extraRows[count($extraRows)-1]->td[0]->asXML(), 'Contains extra data key');
		$this->assertContains(
			"array('one'=&gt;1,)",
			str_replace(array("\r", "\n", " "), '', $extraRows[count($extraRows)-1]->td[1]->asXML()),
			'Serializes arrays correctly'
		);
	}

	public function testEmailWriterDebugPriority() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		SS_Log::add_writer($testEmailWriter, SS_Log::DEBUG);
		SS_Log::log('Test something', SS_Log::DEBUG, array('my-string' => 'test', 'my-array' => array('one' => 1)));
		$this->assertEmailSent('test@test.com');
		$email = $this->findEmail('test@test.com');
		$this->assertContains('[DEBUG] Test something', $email['htmlContent']);
	}

	public function testEmailWriterInfoPriority() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		SS_Log::add_writer($testEmailWriter, SS_Log::INFO);
		SS_Log::log('Test something', SS_Log::INFO, array('my-string' => 'test', 'my-array' => array('one' => 1)));
		$this->assertEmailSent('test@test.com');
		$email = $this->findEmail('test@test.com');
		$this->assertContains('[INFO] Test something', $email['htmlContent']);
	}

	protected function exceptionGeneratorThrower() {
		throw new Exception("thrown from SS_LogTest::testExceptionGeneratorTop");
	}

	protected function exceptionGenerator() {
		$this->exceptionGeneratorThrower();
	}

	public function testEmailException() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		SS_Log::add_writer($testEmailWriter, SS_Log::ERR);

		// Trigger exception handling mechanism
		try {
			$this->exceptionGenerator();
		} catch(Exception $exception) {
			// Mimics exceptionHandler, but without the exit(1)
			SS_Log::log(
				array(
					'errno' => E_USER_ERROR,
					'errstr' => ("Uncaught " . get_class($exception) . ": " . $exception->getMessage()),
					'errfile' => $exception->getFile(),
					'errline' => $exception->getLine(),
					'errcontext' => $exception->getTrace()
				),
				SS_Log::ERR
			);
		}

		// Ensure email is sent
		$this->assertEmailSent('test@test.com');

		// Begin parsing of email body
		$email = $this->findEmail('test@test.com');
		$parser = new CSSContentParser($email['htmlContent']);

		// Check that the first three lines of the stacktrace are correct
		$stacktrace = $parser->getByXpath('//body/div[1]/ul[1]');
		$this->assertContains('<b>SS_LogTest-&gt;exceptionGeneratorThrower()</b>', $stacktrace[0]->li[0]->asXML());
		$this->assertContains('<b>SS_LogTest-&gt;exceptionGenerator()</b>', $stacktrace[0]->li[1]->asXML());
		$this->assertContains('<b>SS_LogTest-&gt;testEmailException()</b>', $stacktrace[0]->li[2]->asXML());
	}

	public function testSubclassedLogger() {
		$this->assertTrue(SS_Log::get_logger() !== SS_LogTest_NewLogger::get_logger());
	}

}

class SS_LogTest_NewLogger extends SS_Log {
	protected static $logger;
}
