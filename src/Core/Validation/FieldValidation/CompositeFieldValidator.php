<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use InvalidArgumentException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\FieldValidator;
use SilverStripe\Core\Validation\FieldValidation\FieldValidationInterface;

/**
 * A field validator that is composed of multiple other field validators
 */
class CompositeFieldValidator extends FieldValidator
{
    /**
     * @param mixed $value - an iterable list of FieldValidators
     */
    public function __construct(string $name, mixed $value)
    {
        parent::__construct($name, $value);
        if (!is_iterable($value)) {
            throw new InvalidArgumentException('Value must be iterable');
        }
        foreach ($value as $child) {
            if (!is_a($child, FieldValidationInterface::class)) {
                throw new InvalidArgumentException('Child is not a' . FieldValidationInterface::class);
            }
        }
    }

    protected function validateValue(): ValidationResult
    {
        $result = ValidationResult::create();
        foreach ($this->value as $child) {
            $result->combineAnd($child->validate());
        }
        return $result;
    }
}
