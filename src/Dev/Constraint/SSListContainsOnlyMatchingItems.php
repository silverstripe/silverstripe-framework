<?php

namespace SilverStripe\Dev\Constraint;

use PHPUnit_Framework_Constraint;
use PHPUnit_Framework_ExpectationFailedException;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SSListExporter;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\SS_List;

/* -------------------------------------------------
 *
 * This version of SSListContainsOnlyMatchingItems is for phpunit 9
 * The phpunit 5 version is lower down in this file
 * phpunit 6, 7 and 8 are not supported
 *
 * @see SilverStripe\Dev\SapphireTest
 *
 * -------------------------------------------------
 */

if (class_exists(Constraint::class)) {

    /**
     * Constraint for checking if every item in a SS_List matches a given match,
     * e.g. every Member has isActive set to true
     */
    // Ignore multiple classes in same file
    // @codingStandardsIgnoreStart
    class SSListContainsOnlyMatchingItems extends Constraint implements TestOnly
    {
        // @codingStandardsIgnoreEnd
        /**
         * @var array
         */
        private $match;

        /**
         * @var ViewableDataContains
         */
        private $constraint;

        public function __construct($match)
        {
            $this->exporter = new SSListExporter();

            $this->constraint = new ViewableDataContains($match);
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
         * @param SS_List $other Value or object to evaluate.
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

            foreach ($other as $item) {
                if (!$this->constraint->evaluate($item, '', true)) {
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
         * @return string
         */
        public function toString(): string
        {
            return 'contains only Objects where "' . key($this->match ?? []) . '" is "' . current($this->match ?? []) . '"';
        }
    }
}

if (!class_exists(PHPUnit_Framework_Constraint::class)) {
    return;
}

/* -------------------------------------------------
 *
 * This version of SSListContainsOnlyMatchingItems is for phpunit 5
 * The phpunit 9 version is at the top of this file
 *
 * -------------------------------------------------
 */

/**
 * Constraint for checking if every item in a SS_List matches a given match,
 * e.g. every Member has isActive set to true
 */
// Ignore multiple classes in same file
// @codingStandardsIgnoreStart
class SSListContainsOnlyMatchingItems extends PHPUnit_Framework_Constraint implements TestOnly
{
    // @codingStandardsIgnoreEnd
    /**
     * @var array
     */
    private $match;

    /**
     * @var ViewableDataContains
     */
    private $constraint;

    public function __construct($match)
    {
        Deprecation::notice('5.0.0', 'This class will be removed in CMS 5', Deprecation::SCOPE_CLASS);
        $this->exporter = new SSListExporter();

        $this->constraint = new ViewableDataContains($match);
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
     * @param SS_List $other Value or object to evaluate.
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

        foreach ($other as $item) {
            if (!$this->constraint->evaluate($item, '', true)) {
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
     * @return string
     */
    public function toString()
    {
        return 'contains only Objects where "' . key($this->match ?? []) . '" is "' . current($this->match ?? []) . '"';
    }
}
