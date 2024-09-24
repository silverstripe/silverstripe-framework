<?php

namespace SilverStripe\Security\Validation;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberPassword;

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
}
