<?php

class EmailFieldTest extends SapphireTest {

	/**
	 * Check the php validator for email addresses. We should be checking against RFC 5322 which defines email address
	 * syntax.
	 *
	 * @TODO
	 *   - double quotes around the local part (before @) is not supported
	 *   - special chars ! # $ % & ' * + - / = ? ^ _ ` { | } ~ are all valid in local part
	 *   - special chars ()[]\;:,<> are valid in the local part if the local part is in double quotes
	 *   - "." is valid in the local part as long as its not first or last char
	 * @return void
	 */
	function testEmailAddressSyntax() {
		$this->internalCheck("blah@blah.com", "Valid, simple", true);
		$this->internalCheck("mr.o'connor+on-toast@blah.com", "Valid, special chars", true);
		$this->internalCheck("", "Empty email", true);
		$this->internalCheck("invalid", "Invalid, simple", false);
		$this->internalCheck("invalid@name@domain.com", "Invalid, two @'s", false);
		$this->internalCheck("invalid@domain", "Invalid, domain too simple", false);
		$this->internalCheck("domain.but.no.user", "Invalid, no user part", false);
	}

	function internalCheck($email, $checkText, $expectSuccess) {
		$field = new EmailField("MyEmail");
		$field->setValue($email);

		$val = new EmailFieldTest_Validator();
		try {
			$field->validate($val);
			if (!$expectSuccess) $this->assertTrue(false, $checkText . " (/$email/ passed validation, but not expected to)");
		} catch (Exception $e) {
			if ($e instanceof PHPUnit_Framework_AssertionFailedError) throw $e; // re-throw assertion failure
			else if ($expectSuccess) $this->assertTrue(false, $checkText . ": " . $e->GetMessage() . " (/$email/ did not pass validation, but was expected to)");
		}
	}
}

class EmailFieldTest_Validator extends Validator {
	function validationError($fieldName, $message, $messageType='') {
		throw new Exception($message);
	}

	function javascript() {
	}

	function php($data) {
	}
}
