<?php

namespace SilverStripe\Security;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ValidationResult;

/**
 * This class represents a validator for member passwords.
 *
 * <code>
 * $pwdVal = new PasswordValidator();
 * $pwdValidator->setMinLength(7);
 * $pwdValidator->checkHistoricalPasswords(6);
 * $pwdValidator->setMinTestScore(3);
 * $pwdValidator->setTestNames(array("lowercase", "uppercase", "digits", "punctuation"));
 *
 * Member::set_password_validator($pwdValidator);
 * </code>
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
     * @var int
     */
    private static $min_length = null;

    /**
     * @config
     * @var int
     */
    private static $min_test_score = null;

    /**
     * @config
     * @var int
     */
    private static $historic_count = null;

    /**
     * @var int
     */
    protected $minLength = null;

    /**
     * @var int
     */
    protected $minScore = null;

    /**
     * @var string[]
     */
    protected $testNames = null;

    /**
     * @var int
     */
    protected $historicalPasswordCount = null;

    /**
     * @return int
     */
    public function getMinLength()
    {
        if ($this->minLength !== null) {
            return $this->minLength;
        }
        return $this->config()->get('min_length');
    }

    /**
     * @param int $minLength
     * @return $this
     */
    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;
        return $this;
    }

    /**
     * @return integer
     */
    public function getMinTestScore()
    {
        if ($this->minScore !== null) {
            return $this->minScore;
        }
        return $this->config()->get('min_test_score');
    }

    /**
     * @param int $minScore
     * @return $this
     */
    public function setMinTestScore($minScore)
    {
        $this->minScore = $minScore;
        return $this;
    }

    /**
     * Gets the list of tests to use for this validator
     *
     * @return string[]
     */
    public function getTestNames()
    {
        if ($this->testNames !== null) {
            return $this->testNames;
        }
        return array_keys(array_filter($this->getTests() ?? []));
    }

    /**
     * Set list of tests to use for this validator
     *
     * @param string[] $testNames
     * @return $this
     */
    public function setTestNames($testNames)
    {
        $this->testNames = $testNames;
        return $this;
    }

    /**
     * @return int
     */
    public function getHistoricCount()
    {
        if ($this->historicalPasswordCount !== null) {
            return $this->historicalPasswordCount;
        }
        return $this->config()->get('historic_count');
    }

    /**
     * @param int $count
     * @return $this
     */
    public function setHistoricCount($count)
    {
        $this->historicalPasswordCount = $count;
        return $this;
    }

    /**
     * Gets all possible tests
     *
     * @return array
     */
    public function getTests()
    {
        return $this->config()->get('character_strength_tests');
    }

    /**
     * @param string $password
     * @param Member $member
     * @return ValidationResult
     */
    public function validate($password, $member)
    {
        $valid = ValidationResult::create();

        $minLength = $this->getMinLength();
        if ($minLength && strlen($password ?? '') < $minLength) {
            $error = _t(
                __CLASS__ . '.TOOSHORT',
                'Password is too short, it must be {minimum} or more characters long',
                ['minimum' => $minLength]
            );

            $valid->addError($error, 'bad', 'TOO_SHORT');
        }

        $minTestScore = $this->getMinTestScore();
        if ($minTestScore) {
            $missedTests = [];
            $testNames = $this->getTestNames();
            $tests = $this->getTests();

            foreach ($testNames as $name) {
                if (preg_match($tests[$name] ?? '', $password ?? '')) {
                    continue;
                }
                $missedTests[] = _t(
                    __CLASS__ . '.STRENGTHTEST' . strtoupper($name ?? ''),
                    $name,
                    'The user needs to add this to their password for more complexity'
                );
            }

            $score = count($testNames ?? []) - count($missedTests ?? []);
            if ($missedTests && $score < $minTestScore) {
                $error = _t(
                    __CLASS__ . '.LOWCHARSTRENGTH',
                    'Please increase password strength by adding some of the following characters: {chars}',
                    ['chars' => implode(', ', $missedTests)]
                );
                $valid->addError($error, 'bad', 'LOW_CHARACTER_STRENGTH');
            }
        }

        $historicCount = $this->getHistoricCount();
        if ($historicCount) {
            $previousPasswords = MemberPassword::get()
                ->where(['"MemberPassword"."MemberID"' => $member->ID])
                ->sort('"Created" DESC, "ID" DESC')
                ->limit($historicCount);
            foreach ($previousPasswords as $previousPassword) {
                if ($previousPassword->checkPassword($password)) {
                    $error = _t(
                        __CLASS__ . '.PREVPASSWORD',
                        'You\'ve already used that password in the past, please choose a new password'
                    );
                    $valid->addError($error, 'bad', 'PREVIOUS_PASSWORD');
                    break;
                }
            }
        }

        $this->extend('updateValidatePassword', $password, $member, $valid, $this);

        return $valid;
    }
}
