<?php

namespace SilverStripe\ORM\Filters;

use BadMethodCallException;
use SilverStripe\ORM\DataQuery;

/**
 * Base class for creating comparison filters, eg; greater than, less than, greater than or equal, etc
 *
 * If you extend this abstract class, you must implement getOperator() and and getInverseOperator
 *
 * getOperator() should return a string operator that will be applied to the filter,
 * eg; if getOperator() returns "<" then this will be a LessThan filter
 *
 * getInverseOperator() should return a string operator that evaluates the inverse of getOperator(),
 * eg; if getOperator() returns "<", then the inverse should be ">=
 */
abstract class ComparisonFilter extends SearchFilter
{

    /**
     * Should return an operator to be used for comparisons
     *
     * @return string Operator
     */
    abstract protected function getOperator();

    /**
     * Should return an inverse operator to be used for comparisons
     *
     * @return string Inverse operator
     */
    abstract protected function getInverseOperator();

    public function matches(mixed $toMatch): bool
    {
        // Match how MySQL handles null
        if ($toMatch === null) {
            return false;
        }

        $negated = in_array('not', $this->getModifiers());
        $fieldMatches = false;

        // can't just cast to array, because that will convert null into an empty array
        $values = $this->getValue();
        if (!is_array($values)) {
            $values = [$values];
        }

        foreach ($values as $value) {
            // Match how MySQL compares against null filters
            if ($value === null) {
                if ($this->isNumericNotString($toMatch)) {
                    $value = 0;
                } else {
                    // Nothing else matches a null value, even when negated
                    continue;
                }
            }
            // Match how MySQL compares native numbers with strings
            if ($this->isNumericNotString($value) && is_string($toMatch)) {
                $toMatch = $this->coerceStringToNumber($toMatch);
            }
            if ($this->isNumericNotString($toMatch) && is_string($value)) {
                $value = $this->coerceStringToNumber($value);
            }
            // Match how MySQL compares ints with floats
            if (is_int($toMatch) && is_float($value)) {
                $value = (int) $value;
            }

            $doesMatch = $this->match($toMatch, $value);

            // Respect "not" modifier.
            if ($negated) {
                $doesMatch = !$doesMatch;
            }
            // If any value matches, then we consider the field to have matched.
            if ($doesMatch) {
                $fieldMatches = true;
                break;
            }
        }

        return $fieldMatches;
    }

    protected function match(int|float|string|null $objectValue, int|float|string|null $filterValue): bool
    {
        // We can't add an abstract method but we want to enforce the method signature for any subclasses
        // which do implement this - therefore, throw an exception by default.
        $actualClass = get_class($this);
        throw new BadMethodCallException("matches is not implemented on $actualClass");
    }

    /**
     * Applies a comparison filter to the query
     * Handles SQL escaping for both numeric and string values
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    protected function applyOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);

        $predicate = sprintf("%s %s ?", $this->getDbName(), $this->getOperator());
        $clause = [$predicate => $this->getDbFormattedValue()];

        return $this->aggregate ?
            $this->applyAggregate($query, $clause) :
            $query->where($clause);
    }

    /**
     * Applies a exclusion(inverse) filter to the query
     * Handles SQL escaping for both numeric and string values
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);

        $predicate = sprintf("%s %s ?", $this->getDbName(), $this->getInverseOperator());
        $clause = [$predicate => $this->getDbFormattedValue()];

        return $this->aggregate ?
            $this->applyAggregate($query, $clause) :
            $query->where($clause);
    }

    public function isEmpty()
    {
        return $this->getValue() === [] || $this->getValue() === null || $this->getValue() === '';
    }
}
