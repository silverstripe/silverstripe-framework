<?php

namespace SilverStripe\Security\Validation;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Member;

/**
 * Validates passwords based on a set of regex rules about what the password must contain.
 *
 * <code>
 * $pwdVal = new PasswordValidator();
 * $pwdValidator->setMinLength(7);
 * $pwdValidator->setHistoricCount(6);
 * $pwdValidator->setMinTestScore(3);
 * $pwdValidator->setTestNames(array("lowercase", "uppercase", "digits", "punctuation"));
 *
 * Member::set_password_validator($pwdValidator);
 * </code>
 */
class RulesPasswordValidator extends PasswordValidator
{
    use Extensible;

    /**
     * Regex to test the password against. See min_test_score.
     */
    private static array $character_strength_tests = [
        'lowercase' => '/[a-z]/',
        'uppercase' => '/[A-Z]/',
        'digits' => '/[0-9]/',
        'punctuation' => '/[^A-Za-z0-9]/',
    ];

    /**
     * Default minimum number of characters for a valid password.
     */
    private static int $min_length = 8;

    /**
     * Default minimum test score for a valid password.
     * The test score is the number of character_strength_tests that the password matches.
     */
    private static int $min_test_score = 0;

    protected ?int $minLength = null;

    protected ?int $minScore = null;

    /**
     * @var string[]
     */
    protected ?array $testNames = null;

    /**
     * Get the minimum number of characters for a valid password.
     */
    public function getMinLength(): int
    {
        if ($this->minLength !== null) {
            return $this->minLength;
        }
        return $this->config()->get('min_length') ?? 0;
    }

    /**
     * Set the minimum number of characters for a valid password.
     */
    public function setMinLength(int $minLength): static
    {
        $this->minLength = $minLength;
        return $this;
    }

    /**
     * Get the minimum test score for a valid password.
     * The test score is the number of character_strength_tests that the password matches.
     */
    public function getMinTestScore(): int
    {
        if ($this->minScore !== null) {
            return $this->minScore;
        }
        return $this->config()->get('min_test_score') ?? 0;
    }

    /**
     * Set the minimum test score for a valid password.
     * The test score is the number of character_strength_tests that the password matches.
     */
    public function setMinTestScore(int $minScore): static
    {
        $this->minScore = $minScore;
        return $this;
    }

    /**
     * Gets the list of tests to use for this validator
     *
     * @return string[]
     */
    public function getTestNames(): array
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
     */
    public function setTestNames(array $testNames): static
    {
        $this->testNames = $testNames;
        return $this;
    }

    /**
     * Gets all possible tests
     */
    public function getTests(): array
    {
        return $this->config()->get('character_strength_tests');
    }

    public function validate(string $password, Member $member): ValidationResult
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
                /** @phpstan-ignore translation.key (we need the key to be dynamic here) */
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

        $valid->combineAnd(parent::validate($password, $member));

        $this->extend('updateValidatePassword', $password, $member, $valid, $this);

        return $valid;
    }
}
