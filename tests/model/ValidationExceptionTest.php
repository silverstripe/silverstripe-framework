<?php

/**
 * @package framework
 * @subpackage Testing
 */
class ValidationExceptionTest extends SapphireTest 
{
	/**
	 * Test that ValidationResult object can correctly populate a ValidationException
	 */
	public function testCreateFromValidationResult() {
		
		$result = new ValidationResult(false, 'Not a valid result');
		$exception = new ValidationException($result);
		
		$this->assertEquals(0, $exception->getCode());
		$this->assertEquals('Not a valid result', $exception->getMessage());
		$this->assertEquals(false, $exception->getResult()->valid());
		$this->assertEquals('Not a valid result', $exception->getResult()->message());
		
	}
	
	/**
	 * Test that ValidationResult object with multiple errors can correctly 
	 * populate a ValidationException
	 */
	public function testCreateFromComplexValidationResult() {
		$result = new ValidationResult();
		$result->error('Invalid type');
		$result->error('Out of kiwis');
		$exception = new ValidationException($result);
		
		$this->assertEquals(0, $exception->getCode());
		$this->assertEquals('Invalid type; Out of kiwis', $exception->getMessage());
		$this->assertEquals(false, $exception->getResult()->valid());
		$this->assertEquals('Invalid type; Out of kiwis', $exception->getResult()->message());
	}
	
	/**
	 * Test that a ValidationException created with no contained ValidationResult
	 * will correctly populate itself with an inferred version
	 */
	public function testCreateFromMessage() {
		$exception = new ValidationException('Error inferred from message', E_USER_ERROR);
		
		$this->assertEquals(E_USER_ERROR, $exception->getCode());
		$this->assertEquals('Error inferred from message', $exception->getMessage());
		$this->assertEquals(false, $exception->getResult()->valid());
		$this->assertEquals('Error inferred from message', $exception->getResult()->message());
	}
	
	/**
	 * Test that ValidationException can be created with both a ValidationResult
	 * and a custom message
	 */
	public function testCreateWithValidationResultAndMessage() {
		$result = new ValidationResult(false, 'Incorrect placement of cutlery');
		$exception = new ValidationException($result, 'An error has occurred', E_USER_WARNING);
		
		$this->assertEquals(E_USER_WARNING, $exception->getCode());
		$this->assertEquals('An error has occurred', $exception->getMessage());
		$this->assertEquals(false, $exception->getResult()->valid());
		$this->assertEquals('Incorrect placement of cutlery', $exception->getResult()->message());
	}
	
	
	/**
	 * Test that ValidationException can be created with both a ValidationResult
	 * and a custom message
	 */
	public function testCreateWithComplexValidationResultAndMessage() {
		$result = new ValidationResult();
		$result->error('A spork is not a knife');
		$result->error('A knife is not a scratcher');
		$exception = new ValidationException($result, 'An error has occurred', E_USER_WARNING);
		
		$this->assertEquals(E_USER_WARNING, $exception->getCode());
		$this->assertEquals('An error has occurred', $exception->getMessage());
		$this->assertEquals(false, $exception->getResult()->valid());
		$this->assertEquals('A spork is not a knife; A knife is not a scratcher', $exception->getResult()->message());
	}

	/**
	 * Test that a ValidationException created with no contained ValidationResult
	 * will correctly populate itself with an inferred version
	 */
	public function testCreateForField() {
		$exception = ValidationException::create_for_field('Content', 'Content is required');

		$this->assertEquals('Content is required', $exception->getMessage());
		$this->assertEquals(false, $exception->getResult()->valid());

		$this->assertEquals(array(
			'Content' => array(
				'message' => 'Content is required',
				'messageType' => 'bad',
			),
		), $exception->getResult()->fieldErrors());
	}

	/**
	 * Test that a ValidationException created with no contained ValidationResult
	 * will correctly populate itself with an inferred version
	 */
	public function testValidationResultAddMethods() {
		$result = new ValidationResult();
		$result->addMessage('A spork is not a knife', 'bad');
		$result->addError('A knife is not a back scratcher');
		$result->addFieldMessage('Title', 'Title is good', 'good');
		$result->addFieldError('Content', 'Content is bad');
		

		$this->assertEquals(array(
			'Title' => array(
				'message' => 'Title is good',
				'messageType' => 'good'
			),
			'Content' => array(
				'message' => 'Content is bad',
				'messageType' => 'bad'
			)
		), $result->fieldErrors());

		$this->assertEquals('A spork is not a knife; A knife is not a back scratcher', $result->overallMessage());

		$exception = ValidationException::create_for_field('Content', 'Content is required');
	}

}