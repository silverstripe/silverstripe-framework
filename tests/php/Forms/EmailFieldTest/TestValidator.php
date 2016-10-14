<?php

namespace SilverStripe\Forms\Tests\EmailFieldTest;

use Exception;
use SilverStripe\Forms\Validator;

class TestValidator extends Validator
{
	public function validationError($fieldName, $message, $messageType = '')
	{
		throw new Exception($message);
	}

	public function javascript()
	{
	}

	public function php($data)
	{
	}
}
