<?php

namespace SilverStripe\ORM;

use SilverStripe\ORM\Queries\SQLConditionGroup;
use SilverStripe\ORM\Queries\SQLSelect;
use InvalidArgumentException;
use LogicException;

/**
 * Represents a subgroup inside a WHERE clause in a {@link DataQuery}
 *
 * Stores the clauses for the subgroup inside a specific {@link SQLSelect} object.
 * All non-where methods call their DataQuery versions, which uses the base
 * query object.
 */
class DataQuery_SubGroup extends DataQuery implements SQLConditionGroup
{
    private string $clause;

    /**
     * @var SQLSelect
     */
    protected $whereQuery;

    /**
     * @var SQLSelect
     */
    protected $havingQuery;

    /**
     * @param DataQuery $base
     * @param string $connective
     * @param string $clause
     */
    public function __construct(DataQuery $base, $connective)
    {
        // using func_get_args to add a 3rd param while retaining BC
        // @deprecated - add a 3rd param for CMS 6 - string $clause = 'WHERE'
        $clause = 'WHERE';
        $args = func_get_args();
        if (count($args) > 2) {
            $clause = $args[2];
        }
        parent::__construct($base->dataClass);
        $this->query = $base->query;
        $this->clause = strtoupper($clause);
        if ($this->clause === 'WHERE') {
            $this->whereQuery = new SQLSelect();
            $this->whereQuery->setConnective($connective);
            $base->where($this);
        } elseif ($this->clause === 'HAVING') {
            $this->havingQuery = new SQLSelect();
            $this->havingQuery->setConnective($connective);
            $base->having($this);
        } else {
            throw new InvalidArgumentException('$clause must be either WHERE or HAVING');
        }
    }

    public function where($filter)
    {
        if ($this->clause === 'HAVING') {
            throw new LogicException('Cannot call where() when clause is set to HAVING');
        }
        if ($filter && $this->whereQuery) {
            $this->whereQuery->addWhere($filter);
        }

        return $this;
    }

    public function whereAny($filter)
    {
        if ($this->clause === 'HAVING') {
            throw new LogicException('Cannot call whereAny() when clause is set to HAVING');
        }
        if ($filter && $this->whereQuery) {
            $this->whereQuery->addWhereAny($filter);
        }

        return $this;
    }

    public function having($filter)
    {
        if ($this->clause === 'WHERE') {
            throw new LogicException('Cannot call having() when clause is set to WHERE');
        }
        if ($filter && $this->havingQuery) {
            $this->havingQuery->addHaving($filter);
        }

        return $this;
    }

    public function conditionSQL(&$parameters)
    {
        $parameters = [];

        if ($this->clause === 'WHERE') {
            $where = $this->whereQuery->getWhere();
            if (!empty($where)) {
                $sql = DB::get_conn()->getQueryBuilder()->buildWhereFragment($this->whereQuery, $parameters);
                return preg_replace('/^\s*WHERE\s*/i', '', $sql ?? '');
            }
        } elseif ($this->clause === 'HAVING') {
            $having = $this->havingQuery->getHaving();
            if (!empty($having)) {
                $sql = DB::get_conn()->getQueryBuilder()->buildHavingFragment($this->havingQuery, $parameters);
                return preg_replace('/^\s*HAVING\s*/i', '', $sql ?? '');
            }
        }

        return null;
    }
}
