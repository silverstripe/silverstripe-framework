<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationInterface;

/**
 * Interface for fields e.g. a DBField or FormField, that can use FieldValidator's
 * Intended for use on classes that have the FieldValidationTrait applied
 */
interface FieldValidationInterface extends ValidationInterface
{
    public function getName(): string;

    public function getValue(): mixed;

    public function getValueForValidation(): mixed;
}
