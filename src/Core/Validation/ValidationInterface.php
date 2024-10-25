<?php

namespace SilverStripe\Core\Validation;

use SilverStripe\Core\Validation\ValidationResult;

/**
 * Interface for classes that can be validated.
 */
interface ValidationInterface
{
    public function validate(): ValidationResult;
}
