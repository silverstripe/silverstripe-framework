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
		$result->error('Invalid type')
				->error('Out of kiwis');
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
		$result->error('A spork is not a knife')
				->error('A knife is not a back scratcher');
		$exception = new ValidationException($result, 'An error has occurred', E_USER_WARNING);

		$this->assertEquals(E_USER_WARNING, $exception->getCode());
		$this->assertEquals('An error has occurred', $exception->getMessage());
		$this->assertEquals(false, $exception->getResult()->valid());
		$this->assertEquals('A spork is not a knife; A knife is not a back scratcher',
			$exception->getResult()->message());
	}

	/**
	 * Test combining validation results together
	 */
	public function testCombineResults(){
		$result = new ValidationResult();
		$anotherresult = new ValidationResult();
		$yetanotherresult = new ValidationResult();
		$anotherresult->error("Eat with your mouth closed", "EATING101");
		$yetanotherresult->error("You didn't wash your hands", "BECLEAN");

		$this->assertTrue($result->valid());
		$this->assertFalse($anotherresult->valid());
		$this->assertFalse($yetanotherresult->valid());

		$result->combineAnd($anotherresult)
				->combineAnd($yetanotherresult);
		$this->assertFalse($result->valid());
		$this->assertEquals(array(
			"EATING101" => "Eat with your mouth closed",
			"BECLEAN" => "You didn't wash your hands"
		),$result->messageList());
	}

}
