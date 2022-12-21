<?php

namespace SilverStripe\Dev\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SilverStripe\Dev\SSListExporter;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\SS_List;

/**
 * Constraint for checking if every item in a SS_List matches a given match,
 * e.g. every Member has isActive set to true
 */
class SSListContainsOnlyMatchingItems extends Constraint implements TestOnly
{
    /**
     * @var array
     */
    private $match;

    protected SSListExporter $exporter;

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
     */
    public function toString(): string
    {
        return 'contains only Objects where "' . key($this->match ?? []) . '" is "' . current($this->match ?? []) . '"';
    }
}
