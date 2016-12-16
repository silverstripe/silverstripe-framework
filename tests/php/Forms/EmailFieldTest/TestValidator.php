<?php

namespace SilverStripe\Forms\Tests\EmailFieldTest;

use Exception;
use SilverStripe\Forms\Validator;
use SilverStripe\ORM\ValidationResult;

class TestValidator extends Validator
{
    public function validationError(
        $fieldName,
        $message,
        $messageType = ValidationResult::TYPE_ERROR,
        $cast = ValidationResult::CAST_TEXT
    ) {
        throw new Exception($message);
    }

    public function javascript()
    {
    }

    public function php($data)
    {
    }
}
