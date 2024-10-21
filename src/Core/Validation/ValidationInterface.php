<?php

namespace SilverStripe\Core\Validation;

use SilverStripe\Core\Validation\ValidationResult;

interface ValidationInterface
{
    public function validate(): ValidationResult;
}
