<?php

namespace SilverStripe\Security\Validation;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * Abstract validator with functionality for checking for reusing old passwords.
 */
abstract class PasswordValidator
{
    use Injectable;
    use Configurable;

    /**
     * Default number of previous passwords to check for a reusing old passwords.
     */
    private static int $historic_count = 6;

    protected ?int $historicalPasswordCount = null;

    public function validate(string $password, Member $member): ValidationResult
    {
        $result = ValidationResult::create();

        $historicCount = $this->getHistoricCount();
        if ($historicCount) {
            $idColumn = DataObject::getSchema()->sqlColumnForField(MemberPassword::class, 'MemberID');
            $previousPasswords = MemberPassword::get()
                ->where([$idColumn => $member->ID])
                ->sort(['Created' => 'DESC', 'ID' => 'DESC'])
                ->limit($historicCount);
            foreach ($previousPasswords as $previousPassword) {
                if ($previousPassword->checkPassword($password)) {
                    $error = _t(
                        PasswordValidator::class . '.PREVPASSWORD',
                        'You\'ve already used that password in the past, please choose a new password'
                    );
                    $result->addError($error, 'bad', 'PREVIOUS_PASSWORD');
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get the number of previous passwords to check for a reusing old passwords.
     */
    public function getHistoricCount(): int
    {
        if ($this->historicalPasswordCount !== null) {
            return $this->historicalPasswordCount;
        }
        return $this->config()->get('historic_count') ?? 0;
    }

    /**
     * Set the number of previous passwords to check for a reusing old passwords.
     */
    public function setHistoricCount(int $count): static
    {
        $this->historicalPasswordCount = $count;
        return $this;
    }

    /**
     * Get the required strength of a password based on the consts in
     * Symfony\Component\Validator\Constraints\PasswordStrength
     * Default return -1 for validators that do not support this
     *
     */
    public function getRequiredStrength(): int
    {
        return -1;
    }

    /**
     * Check if this validator can evaluate password strength.
     */
    public function canEvaluateStrength(): bool
    {
        return false;
    }

    /**
     * Evaluate the strength of a password based on the consts in
     * Symfony\Component\Validator\Constraints\PasswordStrength
     * Default return -1 for validators that do not support this
     */
    public function evaluateStrength(string $password): int
    {
        return -1;
    }

    /**
     * Textual representation of an evaluated password strength
     */
    public static function getStrengthLevel(int $strength): string
    {
        return match ($strength) {
            PasswordStrength::STRENGTH_VERY_WEAK => _t(
                PasswordValidator::class . '.VERYWEAK',
                'very weak'
            ),
            PasswordStrength::STRENGTH_WEAK => _t(
                PasswordValidator::class . '.WEAK',
                'weak'
            ),
            PasswordStrength::STRENGTH_MEDIUM => _t(
                PasswordValidator::class . '.MEDIUM',
                'medium'
            ),
            PasswordStrength::STRENGTH_STRONG => _t(
                PasswordValidator::class . '.STRONG',
                'strong'
            ),
            PasswordStrength::STRENGTH_VERY_STRONG => _t(
                PasswordValidator::class . '.VERYSTRONG',
                'very strong'
            ),
            default => '',
        };
    }
}
