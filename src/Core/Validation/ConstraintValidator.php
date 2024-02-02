<?php

namespace SilverStripe\Core\Validation;

use InvalidArgumentException;
use SilverStripe\ORM\ValidationResult;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;

/**
 * Helper class to abstract away wiring up symfony/validator and getting ValidationResult from validating
 * symfony validator constraints.
 */
class ConstraintValidator
{
    /**
     * Validate a value by a constraint
     *
     * @param Constraint|Constraint[] $constraints a constraint or array of constraints to validate against
     * @param string $fieldName The field name the value relates to, if relevant
     */
    public static function validate(mixed $value, Constraint|array $constraints, string $fieldName = ''): ValidationResult
    {
        if (is_array($constraints) && empty($constraints)) {
            throw new InvalidArgumentException('$constraints must not be an empty array');
        }

        // Perform validation
        $validator = Validation::createValidator();
        $violations = $validator->validate($value, $constraints);

        // Convert value to ValidationResult
        $result = ValidationResult::create();
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            if ($fieldName) {
                $result->addFieldError($fieldName, $violation->getMessage());
            } else {
                $result->addError($violation->getMessage());
            }
        }

        return $result;
    }
}
