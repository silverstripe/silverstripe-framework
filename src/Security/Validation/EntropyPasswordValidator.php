<?php

namespace SilverStripe\Security\Validation;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Validation\ConstraintValidator;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Member;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * Validates passwords based on entropy.
 */
class EntropyPasswordValidator extends PasswordValidator
{
    use Extensible;

    /**
     * The strength of a valid password.
     * See https://symfony.com/doc/current/reference/constraints/PasswordStrength.html#minscore
     */
    private static int $password_strength = PasswordStrength::STRENGTH_STRONG;

    public function validate(string $password, Member $member): ValidationResult
    {
        $minScore = static::config()->get('password_strength');
        $result = ConstraintValidator::validate($password, [new PasswordStrength(minScore: $minScore), new NotBlank()]);
        $result->combineAnd(parent::validate($password, $member));
        $this->extend('updateValidatePassword', $password, $member, $result, $this);
        return $result;
    }
}
