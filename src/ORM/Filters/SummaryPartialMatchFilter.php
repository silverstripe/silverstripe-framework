<?php

namespace SilverStripe\ORM\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use InvalidArgumentException;
use SilverStripe\ORM\Queries\SQLConditionGroup;

/**
 * Matches textual content with a LIKE '%keyword%' construct.
 */
class SummaryPartialMatchFilter extends SearchFilter
{
    private $fieldlist = [];


    public function __construct($fullName = null, $value = false, array $modifiers = [], array $fieldlist=[])
    {
        parent::__construct($fullName, $value, $modifiers);
        $this->fieldlist = $fieldlist;
    }

    public function getSupportedModifiers()
    {
        return ['not', 'nocase', 'case'];
    }

    /**
     * Apply the match filter to the given variable value
     *
     * @param string $value The raw value
     * @return string
     */
    protected function getMatchPattern($value)
    {
        return "%$value%";
    }

    /**
     * Apply filter criteria to a SQL query.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        if ($this->aggregate) {
            throw new InvalidArgumentException(sprintf(
                'Aggregate functions can only be used with comparison filters. See %s',
                $this->fullName
            ));
        }

        return parent::apply($query);
    }

    protected function applyOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);

        $fields = ['CEO', 'Name', 'Category'];
        $value = $this->getValue();
        $value = trim(preg_replace('!\s+!', ' ', $value));
        $terms = explode(" ", $value);

        foreach ($terms as $term) {
            $clause = [];
            foreach ($this->fieldlist as $field) {
                $comparisonClause = DB::get_conn()->comparisonClause(
                    $field,
                    null,
                    false, // exact?
                    false, // negate?
                    $this->getCaseSensitive(),
                    true
                );
                $clause[$comparisonClause] = $this->getMatchPattern($term);
            }
            $query->whereAny($clause);
        }

        return $query;
    }

    protected function applyMany(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $whereClause = [];
        $comparisonClause = DB::get_conn()->comparisonClause(
            $this->getDbName(),
            null,
            false, // exact?
            false, // negate?
            $this->getCaseSensitive(),
            true
        );
        foreach ($this->getValue() as $value) {
            $whereClause[] = [$comparisonClause => $this->getMatchPattern($value)];
        }
        return $query->whereAny($whereClause);
    }

    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $comparisonClause = DB::get_conn()->comparisonClause(
            $this->getDbName(),
            null,
            false, // exact?
            true, // negate?
            $this->getCaseSensitive(),
            true
        );
        return $query->where([
            $comparisonClause => $this->getMatchPattern($this->getValue())
        ]);
    }

    protected function excludeMany(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $values = $this->getValue();
        $comparisonClause = DB::get_conn()->comparisonClause(
            $this->getDbName(),
            null,
            false, // exact?
            true, // negate?
            $this->getCaseSensitive(),
            true
        );
        $parameters = [];
        foreach ($values as $value) {
            $parameters[] = $this->getMatchPattern($value);
        }
        // Since query connective is ambiguous, use AND explicitly here
        $count = count($values);
        $predicate = implode(' AND ', array_fill(0, $count, $comparisonClause));
        return $query->where([$predicate => $parameters]);
    }

    public function isEmpty()
    {
        return $this->getValue() === [] || $this->getValue() === null || $this->getValue() === '';
    }
}
