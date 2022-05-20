<?php

namespace SilverStripe\ORM\Filters;

use SilverStripe\ORM\DataQuery;

/**
 * Incomplete.
 *
 * @todo add to tests
 */
class WithinRangeFilter extends SearchFilter
{

    private $min;
    private $max;

    public function setMin($min)
    {
        $this->min = $min;
    }

    public function setMax($max)
    {
        $this->max = $max;
    }

    protected function applyOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $predicate = sprintf('%1$s >= ? AND %1$s <= ?', $this->getDbName());
        return $query->where([
            $predicate => [
                $this->min,
                $this->max
            ]
        ]);
    }

    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $predicate = sprintf('%1$s < ? OR %1$s > ?', $this->getDbName());
        return $query->where([
            $predicate => [
                $this->min,
                $this->max
            ]
        ]);
    }
}
