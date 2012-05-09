<?php
/**
 * @package framework
 * @subpackage tests
 */
class SS_LogTest extends SapphireTest {

	protected $testEmailWriter;

	function setUp() {
		parent::setUp();

		SS_Log::clear_writers();
	}

	function tearDown() {
		parent::tearDown();

		SS_Log::clear_writers();
	}

	function testExistingWriter() {
		$testEmailWriter = new SS_LogEmailWriter('test@test.com');
		$testFileWriter = new SS_LogFileWriter('../test.log');
		SS_Log::add_writer($testEmailWriter, SS_Log::ERR);
		SS_Log::add_writer($testFileWriter, SS_Log::WARN);

		$writers = SS_Log::get_writers();
		$this->assertEquals(2, count($writers));
	}

	function testRemoveWriter() {
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
			str_replace(array("\r", "\n", " "), '', $extraRows[count($extraRows)-1]->td[1]->asXML()), 
			'Serializes arrays correctly'
		);
	}

}
