<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationInterface;

interface FieldValidationInterface extends ValidationInterface
{
    public function getName(): string;

    public function getValue(): mixed;

    public function getValueForValidation(): mixed;
}
