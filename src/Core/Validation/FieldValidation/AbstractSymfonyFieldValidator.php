<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\ConstraintValidator;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;

/**
 * Abstract class for validators that use Symfony constraints
 */
abstract class AbstractSymfonyFieldValidator extends StringFieldValidator
{
    protected function validateValue(): ValidationResult
    {
        $result = parent::validateValue();
        if (!$result->isValid()) {
            return $result;
        }
        $constraintClass = $this->getConstraintClass();
        $args = [
            ...$this->getContraintNamedArgs(),
            'message' => $this->getMessage(),
        ];
        $constraint = new $constraintClass(...$args);
        $validationResult = ConstraintValidator::validate($this->value, $constraint, $this->name);
        return $result->combineAnd($validationResult);
    }

    /**
     * The symfony constraint class to use
     */
    abstract protected function getConstraintClass(): string;

    /**
     * The named args to pass to the constraint
     * Defined named args as assoc array keys
     */
    protected function getContraintNamedArgs(): array
    {
        return [];
    }

    /**
     * The message to use when the value is invalid
     */
    abstract protected function getMessage(): string;
}
