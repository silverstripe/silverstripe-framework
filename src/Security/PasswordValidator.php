<?php

namespace SilverStripe\Security;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\ValidationResult;

/**
 * This class represents a validator for member passwords.
 */
class PasswordValidator
{
    use Injectable;
    use Configurable;
    use Extensible;

    /**
     * @config
     * @var array
     */
    private static $character_strength_tests = [
        'lowercase' => '/[a-z]/',
        'uppercase' => '/[A-Z]/',
        'digits' => '/[0-9]/',
        'punctuation' => '/[^A-Za-z0-9]/',
    ];

    /**
     * @config
     * @var integer
     */
    private static $min_length = 3;

    /**
     * @config
     * @var integer
     */
    private static $min_test_score = null;

    /**
     * @config
     * @var integer
     */
    private static $historic_count = 6;

    /**
     * @deprecated 5.0
     * Minimum password length
     *
     * @param int $minLength
     * @return $this
     */
    public function minLength($minLength)
    {
        Deprecation::notice('5.0', 'Use ->config()->set(\'min_length\', $value) instead.');
        $this->config()->set('min_length', $minLength);
        return $this;
    }

    /**
     * @deprecated 5.0
     * Check the character strength of the password.
     *
     * Eg: $this->characterStrength(3, array("lowercase", "uppercase", "digits", "punctuation"))
     *
     * @param int $minScore The minimum number of character tests that must pass
     * @param array $testNames The names of the tests to perform
     * @return $this
     */
    public function characterStrength($minScore, $testNames = null)
    {

        Deprecation::notice(
            '5.0',
            'Use ->config()->set(\'min_test_score\', $value) and '.
            '->config()->set(\'character_strength_tests\', $value) instead.'
        );
        $this->config()->set('min_test_score', $minScore);
        return $this;
    }

    /**
     * @deprecated 5.0
     * Check a number of previous passwords that the user has used, and don't let them change to that.
     *
     * @param int $count
     * @return $this
     */
    public function checkHistoricalPasswords($count)
    {
        Deprecation::notice('5.0', 'Use ->config()->set(\'historic_count\', $value) instead.');
        $this->config()->set('historic_count', $count);
        return $this;
    }

    /**
     * @return integer
     */
    public function getMinLength()
    {
        return $this->config()->get('min_length');
    }

    /**
     * @return integer
     */
    public function getMinTestScore()
    {
        return $this->config()->get('min_test_score');
    }
    /**
     * @return integer
     */
    public function getHistoricCount()
    {
        return $this->config()->get('historic_count');
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return $this->config()->get('character_strength_tests');
    }


    /**
     * @return array
     */
    public function getTestNames()
    {
        return array_keys(array_filter($this->getTests()));
    }

    /**
     * @param String $password
     * @param Member $member
     * @return ValidationResult
     */
    public function validate($password, $member)
    {
        $valid = ValidationResult::create();

        $minLength = $this->getMinLength();
        if ($minLength && strlen($password) < $minLength) {
            $error = _t(
                'SilverStripe\\Security\\PasswordValidator.TOOSHORT',
                'Password is too short, it must be {minimum} or more characters long',
                ['minimum' => $this->minLength]
            );

            $valid->addError($error, 'bad', 'TOO_SHORT');
        }

        $minTestScore = $this->getMinTestScore();
        if ($minTestScore) {
            $missedTests = [];
            $testNames = $this->getTestNames();
            $tests = $this->getTests();

            foreach ($testNames as $name) {
                if (preg_match($tests[$name], $password)) {
                    continue;
                }
                $missedTests[] = _t(
                    'SilverStripe\\Security\\PasswordValidator.STRENGTHTEST' . strtoupper($name),
                    $name,
                    'The user needs to add this to their password for more complexity'
                );
            }

            $score = count($this->testNames) - count($missedTests);
            if ($score < $minTestScore) {
                $error = _t(
                    'SilverStripe\\Security\\PasswordValidator.LOWCHARSTRENGTH',
                    'Please increase password strength by adding some of the following characters: {chars}',
                    ['chars' => implode(', ', $missedTests)]
                );
                $valid->addError($error, 'bad', 'LOW_CHARACTER_STRENGTH');
            }
        }

        $historicCount = $this->getHistoricCount();
        if ($historicCount) {
            $previousPasswords = MemberPassword::get()
                ->where(array('"MemberPassword"."MemberID"' => $member->ID))
                ->sort('"Created" DESC, "ID" DESC')
                ->limit($historicCount);
            /** @var MemberPassword $previousPassword */
            foreach ($previousPasswords as $previousPassword) {
                if ($previousPassword->checkPassword($password)) {
                    $error =  _t(
                        'SilverStripe\\Security\\PasswordValidator.PREVPASSWORD',
                        'You\'ve already used that password in the past, please choose a new password'
                    );
                    $valid->addError($error, 'bad', 'PREVIOUS_PASSWORD');
                    break;
                }
            }
        }

        $this->extend('updateValidatePassword', $password, $member, $valid);

        return $valid;
    }
}
