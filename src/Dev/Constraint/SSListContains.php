<?php

namespace SilverStripe\Dev\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SilverStripe\Dev\SSListExporter;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;

/**
 * Constraint for checking if a SS_List contains items matching the given
 * key-value pairs.
 */
class SSListContains extends Constraint implements TestOnly
{
    /**
     * @var array
     */
    protected $matches = [];

    protected SSListExporter $exporter;

    /**
     * Check if the list has left over items that don't match
     *
     * @var bool
     */
    protected $hasLeftoverItems = false;

    public function __construct(array $matches)
    {
        $this->exporter = new SSListExporter();

        $this->matches = $matches;
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
            $this->checkIfItemEvaluatesRemainingMatches($item);
        }

        //we have remaining matches?
        if (count($this->matches ?? []) !== 0) {
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

    protected function checkIfItemEvaluatesRemainingMatches(ViewableData $item): bool
    {
        $success = false;
        foreach ($this->matches as $key => $match) {
            $constraint = new ViewableDataContains($match);

            if ($constraint->evaluate($item, '', true)) {
                $success = true;
                unset($this->matches[$key]);
                break;
            }
        }

        return $success;
    }

    /**
     * Returns a string representation of the object.
     */
    public function toString(): string
    {
        $matchToString = function ($key, $value) {
            return ' "' . $key . '" is "' . $value . '"';
        };

        $matchesToString = function ($matches) use ($matchToString) {
            $matchesAsString = implode(' and ', array_map(
                $matchToString,
                array_keys($matches ?? []),
                array_values($matches ?? [])
            ));

            return '(' . $matchesAsString . ')';
        };

        $allMatchesAsString = implode(
            "\n or ",
            array_map($matchesToString, $this->matches ?? [])
        );


        return $this->getStubForToString() . $allMatchesAsString;
    }

    protected function getStubForToString(): string
    {
        return ' contains an item matching ';
    }
}
