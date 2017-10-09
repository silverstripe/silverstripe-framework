<?php

namespace SilverStripe\Dev\Constraint;

use PHPUnit_Framework_Constraint;
use PHPUnit_Framework_ExpectationFailedException;
use SilverStripe\Dev\SSListExporter;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;

if (!class_exists(PHPUnit_Framework_Constraint::class)) {
    return;
}

/**
 * Constraint for checking if a SS_List contains items matching the given
 * key-value pairs.
 */
class SSListContains extends PHPUnit_Framework_Constraint implements TestOnly
{
    /**
     * @var array
     */
    protected $matches = [];

    /**
     * Check if the list has left over items that don't match
     *
     * @var bool
     */
    protected $hasLeftoverItems = false;

    public function __construct($matches)
    {
        parent::__construct();
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
     * @return null|bool
     *
     * @throws PHPUnit_Framework_ExpectationFailedException
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        $success = true;

        foreach ($other as $item) {
            $this->checkIfItemEvaluatesRemainingMatches($item);
        }

        //we have remaining matches?
        if (count($this->matches) !== 0) {
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

    /**
     * @param ViewableData $item
     * @return bool
     */
    protected function checkIfItemEvaluatesRemainingMatches(ViewableData $item)
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
     *
     * @return string
     */
    public function toString()
    {
        $matchToString = function ($key, $value) {
            return ' "' . $key . '" is "' . $value . '"';
        };

        $matchesToString = function ($matches) use ($matchToString) {
            $matchesAsString = implode(' and ', array_map(
                $matchToString,
                array_keys($matches),
                array_values($matches)
            ));

            return '(' . $matchesAsString . ')';
        };

        $allMatchesAsString = implode(
            "\n or ",
            array_map($matchesToString, $this->matches)
        );


        return $this->getStubForToString() . $allMatchesAsString;
    }

    protected function getStubForToString()
    {
        return ' contains an item matching ';
    }
}
