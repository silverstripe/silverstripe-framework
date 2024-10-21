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
    private static int $password_strength = PasswordStrength::STRENGTH_MEDIUM;

    public function validate(string $password, Member $member): ValidationResult
    {
        $minScore = static::config()->get('password_strength');
        $result = ConstraintValidator::validate($password, [new PasswordStrength(minScore: $minScore), new NotBlank()]);
        $result->combineAnd(parent::validate($password, $member));
        $this->extend('updateValidatePassword', $password, $member, $result, $this);
        return $result;
    }

    public function getRequiredStrength(): int
    {
        return static::config()->get('password_strength');
    }
    
    public function canEvaluateStrength(): bool
    {
        return true;
    }

    public function evaluateStrength(string $password): int
    {
        $strengths = [
            PasswordStrength::STRENGTH_WEAK,
            PasswordStrength::STRENGTH_MEDIUM,
            PasswordStrength::STRENGTH_STRONG,
            PasswordStrength::STRENGTH_VERY_STRONG,
        ];
        // STRENGTH_VERY_WEAK is not validatable, it's just the default value
        $lastPassedStrength = PasswordStrength::STRENGTH_VERY_WEAK;
        foreach ($strengths as $strength) {
            $result = ConstraintValidator::validate($password, new PasswordStrength(minScore: $strength));
            if ($result->isValid()) {
                $lastPassedStrength = $strength;
            } else {
                break;
            }
        }
        return $lastPassedStrength;
    }
}
