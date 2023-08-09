<?php

namespace SilverStripe\ORM\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use InvalidArgumentException;

/**
 * Matches textual content with a LIKE '%keyword%' construct.
 */
class PartialMatchFilter extends SearchFilter
{
    protected static $matchesStartsWith = false;
    protected static $matchesEndsWith = false;

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

    public function matches(mixed $toMatch): bool
    {
        $isCaseSensitive = $this->getCaseSensitive();
        if ($isCaseSensitive === null) {
            $isCaseSensitive = $this->getCaseSensitivityByCollation();
        }
        $caseSensitive = $isCaseSensitive ? '' : 'i';
        $negated = in_array('not', $this->getModifiers());
        $toMatchString = (string) $toMatch;
        $fieldMatches = false;

        // Match how MYSQL performs partial matches against null values
        if ($toMatch === null) {
            return false;
        }

        // can't just cast to array, because that will convert null into an empty array
        $values = $this->getValue();
        if (!is_array($values)) {
            $values = [$values];
        }

        foreach ($values as $value) {
            if (is_bool($toMatch)) {
                if (static::$matchesStartsWith || static::$matchesEndsWith) {
                    // Nothing "starts" or "ends" with a boolean value, so automatically fail those matches.
                    $doesMatch = false;
                } else {
                    // A partial boolean match should match truthy and falsy values.
                    $doesMatch = $toMatch == $value;
                }
            } else {
                $value = (string) $value;
                $regexSafeValue = preg_quote($value, '/');
                $start = static::$matchesStartsWith ? '^' : '';
                $end = static::$matchesEndsWith ? '$' : '';
                $doesMatch = preg_match('/' . $start . $regexSafeValue . $end . '/u' . $caseSensitive, $toMatchString);
            }
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
        $comparisonClause = DB::get_conn()->comparisonClause(
            $this->getDbName(),
            null,
            false, // exact?
            false, // negate?
            $this->getCaseSensitive(),
            true
        );

        $clause = [$comparisonClause => $this->getMatchPattern($this->getValue())];

        return $this->aggregate ?
            $this->applyAggregate($query, $clause) :
            $query->where($clause);
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
        $count = count($values ?? []);
        $predicate = implode(' AND ', array_fill(0, $count ?? 0, $comparisonClause));
        return $query->where([$predicate => $parameters]);
    }

    public function isEmpty()
    {
        return $this->getValue() === [] || $this->getValue() === null || $this->getValue() === '';
    }
}
