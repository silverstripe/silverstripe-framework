<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use Symfony\Component\Validator\Constraint;

/**
 * Interface for FieldValidators which validate using Symfony constraints
 */
interface SymfonyFieldValidatorInterface
{
    /**
     * Get the Symfony constraint to validate against
     * Can return either a single constraint or an array of constraints
     */
    public function getConstraint(): Constraint|array;
}
