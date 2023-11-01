<?php

namespace SilverStripe\ORM\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBPrimaryKey;
use SilverStripe\ORM\FieldType\DBForeignKey;
use SilverStripe\ORM\DataList;

/**
 * Selects textual content with an exact match between columnname and keyword.
 *
 */
class ExactMatchFilter extends SearchFilter
{

    public function matches(mixed $objectValue): bool
    {
        $isCaseSensitive = $this->getCaseSensitive();
        if ($isCaseSensitive === null) {
            $isCaseSensitive = $this->getCaseSensitiveByCollation();
        }
        $caseSensitive = $isCaseSensitive ? '' : 'i';
        $negated = in_array('not', $this->getModifiers());

        // Can't just cast to array, because that will convert null into an empty array
        $filterValues = $this->getValue();
        if (!is_array($filterValues)) {
            $filterValues = [$filterValues];
        }

        // This is essentially a in_array($objectValue, $filterValues) check, with some special handling.
        $hasMatch = false;
        foreach ($filterValues as $filterValue) {
            if (is_string($filterValue) && is_string($objectValue)) {
                $regexSafeFilterValue = preg_quote($filterValue, '/');
                $doesMatch = preg_match('/^' . $regexSafeFilterValue . '$/u' . $caseSensitive, $objectValue);
            } elseif ($filterValue === null || $objectValue === null) {
                $doesMatch = $filterValue === $objectValue;
            } else {
                // case sensitivity is meaningless if one or both values aren't strings,
                // so fall back to a loose equivalency comparison.
                $doesMatch = $filterValue == $objectValue;
            }
            // Any match is a match
            if ($doesMatch) {
                $hasMatch = true;
                break;
            }
        }

        // Respect "not" modifier.
        if ($negated) {
            $hasMatch = !$hasMatch;
        }

        return $hasMatch;
    }

    public function getSupportedModifiers()
    {
        return ['not', 'nocase', 'case'];
    }

    /**
     * Applies an exact match (equals) on a field value.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    protected function applyOne(DataQuery $query)
    {
        return $this->oneFilter($query, true);
    }

    /**
     * Excludes an exact match (equals) on a field value.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    protected function excludeOne(DataQuery $query)
    {
        return $this->oneFilter($query, false);
    }

    /**
     * Applies a single match, either as inclusive or exclusive
     *
     * @param DataQuery $query
     * @param bool $inclusive True if this is inclusive, or false if exclusive
     * @return DataQuery
     */
    protected function oneFilter(DataQuery $query, $inclusive)
    {
        $this->model = $query->applyRelation($this->relation);
        $field = $this->getDbName();
        $value = $this->getValue();

        // Null comparison check
        if ($value === null) {
            $where = DB::get_conn()->nullCheckClause($field, $inclusive);
            return $query->where($where);
        }

        // Value comparison check
        $where = DB::get_conn()->comparisonClause(
            $field,
            null,
            true, // exact?
            !$inclusive, // negate?
            $this->getCaseSensitive(),
            true
        );
        // for != clauses include IS NULL values, since they would otherwise be excluded
        if (!$inclusive) {
            $nullClause = DB::get_conn()->nullCheckClause($field, true);
            $where .= " OR {$nullClause}";
        }

        $clause = [$where => $value];

        return $this->aggregate ?
            $this->applyAggregate($query, $clause) :
            $query->where($clause);
    }

    /**
     * Applies an exact match (equals) on a field value against multiple
     * possible values.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    protected function applyMany(DataQuery $query)
    {
        return $this->manyFilter($query, true);
    }

    /**
     * Excludes an exact match (equals) on a field value against multiple
     * possible values.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    protected function excludeMany(DataQuery $query)
    {
        return $this->manyFilter($query, false);
    }

    /**
     * Applies matches for several values, either as inclusive or exclusive
     *
     * @param DataQuery $query
     * @param bool $inclusive True if this is inclusive, or false if exclusive
     * @return DataQuery
     */
    protected function manyFilter(DataQuery $query, $inclusive)
    {
        $this->model = $query->applyRelation($this->relation);
        $caseSensitive = $this->getCaseSensitive();

        // Check values for null
        $field = $this->getDbName();
        $values = $this->getValue();
        if (empty($values)) {
            throw new \InvalidArgumentException("Cannot filter {$field} against an empty set");
        }
        $hasNull = in_array(null, $values ?? [], true);
        if ($hasNull) {
            $values = array_filter($values ?? [], function ($value) {
                return $value !== null;
            });
        }

        $connective = '';
        $setValuesToEmpty = false;
        if (empty($values)) {
            $predicate = '';
        } elseif ($caseSensitive === null) {
            // For queries using the default collation (no explicit case) we can use the WHERE .. NOT IN .. syntax,
            // providing simpler SQL than many WHERE .. AND .. fragments.
            $column = $this->getDbName();
            $usePlaceholders = $this->usePlaceholders($column, $values);
            if ($usePlaceholders) {
                $in = DB::placeholders($values);
            } else {
                // explicitly including space after comma to match the default for DB::placeholders
                $in = implode(', ', $values);
                $setValuesToEmpty = true;
            }
            if ($inclusive) {
                $predicate = "$column IN ($in)";
            } else {
                $predicate = "$column NOT IN ($in)";
            }
        } else {
            // Generate reusable comparison clause
            $comparisonClause = DB::get_conn()->comparisonClause(
                $this->getDbName(),
                null,
                true, // exact?
                !$inclusive, // negate?
                $this->getCaseSensitive(),
                true
            );
            $count = count($values ?? []);
            if ($count > 1) {
                $connective = $inclusive ? ' OR ' : ' AND ';
                $conditions = array_fill(0, $count ?? 0, $comparisonClause);
                $predicate = implode($connective ?? '', $conditions);
            } else {
                $predicate = $comparisonClause;
            }
        }

        // Always check for null when doing exclusive checks (either AND IS NOT NULL / OR IS NULL)
        // or when including the null value explicitly (OR IS NULL)
        if ($hasNull || !$inclusive) {
            // If excluding values which don't include null, or including
            // values which include null, we should do an `OR IS NULL`.
            // Otherwise we are excluding values that do include null, so `AND IS NOT NULL`.
            // Simplified from (!$inclusive && !$hasNull) || ($inclusive && $hasNull);
            $isNull = !$hasNull || $inclusive;
            $nullCondition = DB::get_conn()->nullCheckClause($field, $isNull);

            // Determine merge strategy
            if (empty($predicate)) {
                $predicate = $nullCondition;
            } else {
                // Merge null condition with predicate
                if ($isNull) {
                    $nullCondition = " OR {$nullCondition}";
                } else {
                    $nullCondition = " AND {$nullCondition}";
                }
                // If current predicate connective doesn't match the same as the null connective
                // make sure to group the prior condition
                if ($connective && (($connective === ' OR ') !== $isNull)) {
                    $predicate = "({$predicate})";
                }
                $predicate .= $nullCondition;
            }
        }

        $vals = $setValuesToEmpty ? [] : $values;
        $clause = [$predicate => $vals];

        return $this->aggregate ?
            $this->applyAggregate($query, $clause) :
            $query->where($clause);
    }

    public function isEmpty()
    {
        return $this->getValue() === [] || $this->getValue() === null || $this->getValue() === '';
    }

    /**
     * Determine if we should use placeholders for the given column and values
     * Current rules are to use placeholders for all values, unless all of these are true:
     * - The column is a DBPrimaryKey or DBForeignKey
     * - The values being filtered are all either integers or valid integer strings
     * - Using placeholders for integer ids has been configured off
     *
     * Putting IDs directly into a where clause instead of using placeholders was measured to be significantly
     * faster when querying a large number of IDs e.g. over 1000
     */
    private function usePlaceholders(string $column, array $values): bool
    {
        if (DataList::config()->get('use_placeholders_for_integer_ids')) {
            return true;
        }
        // Ensure that the $column was created in the "Table"."Column" format
        // by DataObjectSchema::sqlColumnForField() after first being called by SearchFilter::getDbName()
        // earlier on in manyFilter(). Do this check to ensure that we're be safe and only not using
        // placeholders in scenarios where we intend to not use them.
        if (!preg_match('#^"(.+)"."(.+)"$#', $column, $matches)) {
            return true;
        }
        $col = $matches[2];
        // Check if column is Primary or Foreign key, if it's not then we use placeholders
        $schema = DataObject::getSchema();
        $fieldSpec = $schema->fieldSpec($this->model, $col);
        $fieldObj = Injector::inst()->create($fieldSpec, $col);
        if (!is_a($fieldObj, DBPrimaryKey::class) && !is_a($fieldObj, DBForeignKey::class)) {
            return true;
        }
        // Validate that we're only using int ID's for the values
        // We need to do this to protect against SQL injection
        foreach ($values as $value) {
            if (!ctype_digit((string) $value) || $value != (int) $value) {
                return true;
            }
        }
        return false;
    }
}
