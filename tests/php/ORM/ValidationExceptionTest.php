<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Dev\SapphireTest;

class ValidationExceptionTest extends SapphireTest
{
    /**
     * Test that ValidationResult object can correctly populate a ValidationException
     */
    public function testCreateFromValidationResult()
    {
        $result = new ValidationResult();
        $result->addError('Not a valid result');

        $exception = new ValidationException($result);

        $this->assertEquals(0, $exception->getCode());
        $this->assertEquals('Not a valid result', $exception->getMessage());
        $this->assertFalse($exception->getResult()->isValid());
        $this->assertContains(
            [
            'message' => 'Not a valid result',
            'messageCast' => ValidationResult::CAST_TEXT,
            'messageType' => ValidationResult::TYPE_ERROR,
            'fieldName' => null,
            ],
            $exception->getResult()->getMessages()
        );
    }

    /**
     * Test that ValidationResult object with multiple errors can correctly
     * populate a ValidationException
     */
    public function testCreateFromComplexValidationResult()
    {
        $result = new ValidationResult();
        $result
            ->addError('Invalid type')
            ->addError('Out of kiwis');
        $exception = new ValidationException($result);

        $this->assertEquals(0, $exception->getCode());
        $this->assertEquals('Invalid type', $exception->getMessage());
        $this->assertEquals(false, $exception->getResult()->isValid());
        $this->assertContains(
            [
            'message' => 'Invalid type',
            'messageCast' => ValidationResult::CAST_TEXT,
            'messageType' => ValidationResult::TYPE_ERROR,
            'fieldName' => null,
            ],
            $exception->getResult()->getMessages()
        );
        $this->assertContains(
            [
            'message' => 'Out of kiwis',
            'messageCast' => ValidationResult::CAST_TEXT,
            'messageType' => ValidationResult::TYPE_ERROR,
            'fieldName' => null,
            ],
            $exception->getResult()->getMessages()
        );
    }

    /**
     * Test that a ValidationException created with no contained ValidationResult
     * will correctly populate itself with an inferred version
     */
    public function testCreateFromMessage()
    {
        $exception = new ValidationException('Error inferred from message', E_USER_ERROR);

        $this->assertEquals(E_USER_ERROR, $exception->getCode());
        $this->assertEquals('Error inferred from message', $exception->getMessage());
        $this->assertFalse($exception->getResult()->isValid());
        $this->assertContains(
            [
            'message' => 'Error inferred from message',
            'messageCast' => ValidationResult::CAST_TEXT,
            'messageType' => ValidationResult::TYPE_ERROR,
            'fieldName' => null,
            ],
            $exception->getResult()->getMessages()
        );
    }

    /**
     * Test that ValidationException can be created with both a ValidationResult
     * and a custom message
     */
    public function testCreateWithComplexValidationResultAndMessage()
    {
        $result = new ValidationResult();
        $result->addError('A spork is not a knife')
            ->addError('A knife is not a back scratcher');
        $exception = new ValidationException($result, E_USER_WARNING);

        $this->assertEquals(E_USER_WARNING, $exception->getCode());
        $this->assertEquals('A spork is not a knife', $exception->getMessage());
        $this->assertEquals(false, $exception->getResult()->isValid());
        $this->assertContains(
            [
            'message' => 'A spork is not a knife',
            'messageCast' => ValidationResult::CAST_TEXT,
            'messageType' => ValidationResult::TYPE_ERROR,
            'fieldName' => null,
            ],
            $exception->getResult()->getMessages()
        );
        $this->assertContains(
            [
            'message' => 'A knife is not a back scratcher',
            'messageCast' => ValidationResult::CAST_TEXT,
            'messageType' => ValidationResult::TYPE_ERROR,
            'fieldName' => null,
            ],
            $exception->getResult()->getMessages()
        );
    }

    /**
     * Test combining validation results together
     */
    public function testCombineResults()
    {
        $result = new ValidationResult();
        $anotherresult = new ValidationResult();
        $yetanotherresult = new ValidationResult();
        $anotherresult->addError("Eat with your mouth closed", 'bad', "EATING101");
        $yetanotherresult->addError("You didn't wash your hands", 'bad', "BECLEAN", false);

        $this->assertTrue($result->isValid());
        $this->assertFalse($anotherresult->isValid());
        $this->assertFalse($yetanotherresult->isValid());

        $result->combineAnd($anotherresult)
            ->combineAnd($yetanotherresult);
        $this->assertFalse($result->isValid());
        $this->assertEquals(
            [
                'EATING101' => [
                    'message' => 'Eat with your mouth closed',
                    'messageType' => 'bad',
                    'messageCast' => ValidationResult::CAST_TEXT,
                    'fieldName' => null,
                ],
                'BECLEAN' => [
                    'message' => 'You didn\'t wash your hands',
                    'messageType' => 'bad',
                    'messageCast' => ValidationResult::CAST_HTML,
                    'fieldName' => null,
                ],
            ],
            $result->getMessages()
        );
    }

    /**
     * Test that a ValidationException created with no contained ValidationResult
     * will correctly populate itself with an inferred version
     */
    public function testValidationResultAddMethods()
    {
        $result = new ValidationResult();
        $result->addMessage('A spork is not a knife', 'bad');
        $result->addError('A knife is not a back scratcher');
        $result->addFieldMessage('Title', 'Title is good', 'good');
        $result->addFieldError('Content', 'Content is bad', 'bad');


        $this->assertEquals(
            [
            [
                'fieldName' => null,
                'message' => 'A spork is not a knife',
                'messageType' => 'bad',
                'messageCast' => ValidationResult::CAST_TEXT,
            ],
            [
                'fieldName' => null,
                'message' => 'A knife is not a back scratcher',
                'messageType' => 'error',
                'messageCast' => ValidationResult::CAST_TEXT,
            ],
            [
                'fieldName' => 'Title',
                'message' => 'Title is good',
                'messageType' => 'good',
                'messageCast' => ValidationResult::CAST_TEXT,
            ],
            [
                'fieldName' => 'Content',
                'message' => 'Content is bad',
                'messageType' => 'bad',
                'messageCast' => ValidationResult::CAST_TEXT,
            ]
            ],
            $result->getMessages()
        );
    }
}
