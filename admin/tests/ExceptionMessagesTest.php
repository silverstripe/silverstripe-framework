<?php

/**
 * @package framework
 * @subpackage tests
 */
class ExceptionMessagesTest extends FunctionalTest {
	public function testInvalidBatchActionExceptionMessage() {
		$batchActionHandler = new CMSBatchActionHandler(new Controller(), 'test', 'SiteTree');

		Config::inst()->update('CMSBatchActionHandler', 'batch_actions', array(
			'foo' => array(
				'invalid' => 'definition',
			),
		));

		$throws = false;

		try {
			$reflection = new ReflectionClass('CMSBatchActionHandler');

			$method = $reflection->getMethod('actionByName');
			$method->setAccessible(true);
			$method->invokeArgs($batchActionHandler, array('foo'));
		}
		catch (InvalidArgumentException $e) {
			$throws = true;
			$this->assertContains('There seems to be an error', $e->getMessage());
		}

		$this->assertTrue($throws);
	}
}
