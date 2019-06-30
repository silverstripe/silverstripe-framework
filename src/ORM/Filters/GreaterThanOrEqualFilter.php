<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Filters;

/**
 * Selects numerical/date content greater than or equal to the input
 *
 * Can be used by SearchContext and DataList->filter, eg;
 * Model::get()->filter("Field1:GreaterThanOrEqual", $value);
 */
class GreaterThanOrEqualFilter extends ComparisonFilter
{

    protected function getOperator()
    {
        return ">=";
    }

    protected function getInverseOperator()
    {
        return "<";
    }
}
