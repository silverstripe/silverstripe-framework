<?php

namespace SilverStripe\ORM\Filters;

/**
 * Selects numerical/date content less than the input
 *
 * Can be used by SearchContext and DataList->filter, eg;
 * Model::get()->filter("Field1:LessThan", $value);
 */
class LessThanFilter extends ComparisonFilter
{
    protected function match(int|float|string|null $objectValue, int|float|string|null $filterValue): bool
    {
        if ($this->isNumericNotString($filterValue) && !is_numeric($objectValue)) {
            return true;
        }

        // Match how MySQL compares against non-string numeric values
        if ($this->isNumericNotString($objectValue) && $this->isNumericNotString($filterValue)) {
            return $objectValue < $filterValue;
        }

        // Match how MySQL compares strings and numeric strings
        $compared = strcasecmp($objectValue ?? '', $filterValue ?? '');
        return $compared < 0;
    }

    protected function getOperator()
    {
        return "<";
    }

    protected function getInverseOperator()
    {
        return ">=";
    }
}
