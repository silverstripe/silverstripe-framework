<?php

namespace SilverStripe\Dev\Constraint;

use PHPUnit_Framework_ExpectationFailedException;
use SilverStripe\Dev\SSListExporter;
use SilverStripe\View\ViewableData;

/**
 * Constraint for checking if a SS_List contains only items matching the given
 * key-value pairs.  Each match must correspond to 1 distinct record.
 *
 * @todo can this be solved more elegantly using a Comparator?
 *
 * Class SSListContainsOnly
 * @package SilverStripe\Dev\Constraint
 */
class SSListContainsOnly extends SSListContains
{

    private $item_not_matching = false;

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
     * @param mixed $other Value or object to evaluate.
     * @param string $description Additional information about the test
     * @param bool $returnResult Whether to return a result or throw an exception
     *
     * @return mixed
     *
     * @throws PHPUnit_Framework_ExpectationFailedException
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        $success = true;

        foreach ($other as $item) {
            if (!$this->checkIfItemEvaltuatesRemainingMatches($item)) {
                $this->item_not_matching = true;
                $success = false;
                break;
            }
        }

        //we have remaining matches?
        if (!$this->item_not_matching && count($this->matches) !== 0) {
            $success = false;
            $this->has_leftover_items = true;
        }

        if ($returnResult) {
            return $success;
        }

        if (!$success) {
            $this->fail($other, $description);
        }
    }

    protected function getStubForToString() {
        $this->item_not_matching
            ? parent::getStubForToString()
            : " contained only the given items, the following items were left over:\n";
    }
}
