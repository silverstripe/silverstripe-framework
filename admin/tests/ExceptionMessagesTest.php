<?php

class InvalidCMSBatchActionSubclass {

}

/**
 * @package framework
 * @subpackage tests
 */
class ExceptionMessagesTest extends FunctionalTest {
    public function testInvalidCMSBatchActionSubclassExceptionMessage() {
        $batchActionHandler = new CMSBatchActionHandler(new Controller(), 'test', 'SiteTree');

        $throws = false;

        try {
            $reflection = new ReflectionClass('CMSBatchActionHandler');

            $method = $reflection->getMethod('buildAction');
            $method->setAccessible(true);
            $method->invokeArgs($batchActionHandler, array('InvalidCMSBatchActionSubclass'));
        }
        catch (InvalidArgumentException $e) {
            $throws = true;
            $this->assertContains('InvalidCMSBatchActionSubclass is not a subclass of CMSBatchAction', $e->getMessage());
        }

        $this->assertTrue($throws);
    }
}
