<?php

namespace SilverStripe\Dev\Constraint;

use PHPUnit_Framework_Constraint;
use PHPUnit_Framework_ExpectationFailedException;
use PHPUnit_Util_InvalidArgumentHelper;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;
use SilverStripe\Dev\SapphireTest;

/* -------------------------------------------------
 *
 * This version of ViewableDataContains is for phpunit 9
 * The phpunit 5 version is lower down in this file
 * phpunit 6, 7 and 8 are not supported
 *
 * @see SilverStripe\Dev\SapphireTest
 *
 * -------------------------------------------------
 */

if (class_exists(Constraint::class)) {

    /**
     * Constraint for checking if a ViewableData (e.g. ArrayData or any DataObject) contains fields matching the given
     * key-value pairs.
     */
    // Ignore multiple classes in same file
    // @codingStandardsIgnoreStart
    class ViewableDataContains extends Constraint implements TestOnly
    {
        // @codingStandardsIgnoreEnd
        /**
         * @var array
         */
        private $match;

        /**
         * ViewableDataContains constructor.
         * @param array $match
         */
        public function __construct(array $match)
        {
            if (!is_array($match)) {
                throw SapphireTest::createInvalidArgumentException(
                    1,
                    'array'
                );
            }

            $this->match = $match;
        }

        /**
         * Evaluates the constraint for parameter $other
         *
         * If $returnResult is set to false (the default), an exception is thrown
         * in case of a failure. null is returned otherwise.
         *
         * If $returnResult is true, the result of the evaluation is returned as
         * a boolean value instead: true in case of success, false in case of a
         * failure.
         *
         * @param ViewableData $other Value or object to evaluate.
         * @param string $description Additional information about the test
         * @param bool $returnResult Whether to return a result or throw an exception
         *
         * @return null|bool
         *
         * @throws ExpectationFailedException
         */
        public function evaluate($other, $description = '', $returnResult = false): ?bool
        {
            $success = true;

            foreach ($this->match as $fieldName => $value) {
                if ($other->$fieldName != $value) {
                    $success = false;
                    break;
                }
            }

            if ($returnResult) {
                return $success;
            }

            if (!$success) {
                $this->fail($other, $description);
            }

            return null;
        }


        /**
         * Returns a string representation of the object.
         *
         * @todo: add representation for more than one match
         *
         * @return string
         */
        public function toString(): string
        {
            return 'contains only Objects where "' . key($this->match ?? []) . '" is "' . current($this->match ?? []) . '"';
        }
    }
}

/* -------------------------------------------------
 *
 * This version of ViewableDataContains is for phpunit 5
 * The phpunit 9 version is at the top of this file
 *
 * -------------------------------------------------
 */

if (!class_exists(PHPUnit_Framework_Constraint::class)) {
    return;
}

/**
 * Constraint for checking if a ViewableData (e.g. ArrayData or any DataObject) contains fields matching the given
 * key-value pairs.
 */
// Ignore multiple classes in same file
// @codingStandardsIgnoreStart
class ViewableDataContains extends PHPUnit_Framework_Constraint implements TestOnly
{
    // @codingStandardsIgnoreEnd
    /**
     * @var array
     */
    private $match;

    /**
     * ViewableDataContains constructor.
     * @param array $match
     */
    public function __construct($match)
    {
        Deprecation::notice('5.0.0', 'This class will be removed in CMS 5', Deprecation::SCOPE_CLASS);
        parent::__construct();
        if (!is_array($match)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'array'
            );
        }

        $this->match = $match;
    }

    /**
     * Evaluates the constraint for parameter $other
     *
     * If $returnResult is set to false (the default), an exception is thrown
     * in case of a failure. null is returned otherwise.
     *
     * If $returnResult is true, the result of the evaluation is returned as
     * a boolean value instead: true in case of success, false in case of a
     * failure.
     *
     * @param ViewableData $other Value or object to evaluate.
     * @param string $description Additional information about the test
     * @param bool $returnResult Whether to return a result or throw an exception
     *
     * @return null|bool
     *
     * @throws PHPUnit_Framework_ExpectationFailedException
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        $success = true;

        foreach ($this->match as $fieldName => $value) {
            if ($other->$fieldName != $value) {
                $success = false;
                break;
            }
        }

        if ($returnResult) {
            return $success;
        }

        if (!$success) {
            $this->fail($other, $description);
        }

        return null;
    }

    /**
     * Returns a string representation of the object.
     *
     * @todo: add representation for more than one match
     *
     * @return string
     */
    public function toString()
    {
        return 'contains only Objects where "' . key($this->match ?? []) . '" is "' . current($this->match ?? []) . '"';
    }
}
