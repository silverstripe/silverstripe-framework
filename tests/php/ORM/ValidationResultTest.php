<?php


namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ValidationResult;

class ValidationResultTest extends SapphireTest
{
    public function testSerialise()
    {
        $result = new ValidationResult();
        $result->addError("Error", ValidationResult::TYPE_ERROR, null, ValidationResult::CAST_HTML);
        $result->addMessage("Message", ValidationResult::TYPE_GOOD);
        $serialised = serialize($result);

        /**
 * @var ValidationResult $result2
*/
        $result2 = unserialize($serialised);
        $this->assertEquals(
            [
            [
                'message' => 'Error',
                'fieldName' => null,
                'messageCast' => ValidationResult::CAST_HTML,
                'messageType' => ValidationResult::TYPE_ERROR,
            ],
            [
                'message' => 'Message',
                'fieldName' => null,
                'messageCast' => ValidationResult::CAST_TEXT,
                'messageType' => ValidationResult::TYPE_GOOD,
            ]
            ],
            $result2->getMessages()
        );
        $this->assertFalse($result2->isValid());
    }
}
