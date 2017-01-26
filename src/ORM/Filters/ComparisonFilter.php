<?php

namespace SilverStripe\ORM\Filters;

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
        return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
    }
}
