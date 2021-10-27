<?php

namespace SilverStripe\Dev\Constraint;

use PHPUnit_Framework_Constraint;
use PHPUnit_Framework_ExpectationFailedException;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\SS_List;

/* -------------------------------------------------
 *
 * This version of SSListContains is for both phpunit5 and phpunit 9 because it extends SSListContains
 * phpunit 6, 7 and 8 are not supported
 *
 * @see SilverStripe\Dev\SapphireTest
 *
 * -------------------------------------------------
 */
if (!class_exists(Constraint::class) && !class_exists(PHPUnit_Framework_Constraint::class)) {
    return;
}

/**
 * Constraint for checking if a SS_List contains only items matching the given
 * key-value pairs.  Each match must correspond to 1 distinct record.
 */
class SSListContainsOnly extends SSListContains implements TestOnly
{
    /**
     * Check if the test fails due to a not matching item
     *
     * @var bool
     */
    private $itemNotMatching = false;

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
     * @throws PHPUnit_Framework_ExpectationFailedException|ExpectationFailedException
     */
    public function evaluate($other, $description = '', $returnResult = false): ?bool
    {
        $success = true;

        foreach ($other as $item) {
            if (!$this->checkIfItemEvaluatesRemainingMatches($item)) {
                $this->itemNotMatching = true;
                $success = false;
                break;
            }
        }

        //we have remaining matches?
        if (!$this->itemNotMatching && count($this->matches) !== 0) {
            $success = false;
            $this->hasLeftoverItems = true;
        }

        if ($returnResult) {
            return $success;
        }

        if (!$success) {
            $this->fail($other, $description);
        }

        return null;
    }

    protected function getStubForToString(): string
    {
        return $this->itemNotMatching
            ? parent::getStubForToString()
            : " contained only the given items, the following items were left over:\n";
    }
}
