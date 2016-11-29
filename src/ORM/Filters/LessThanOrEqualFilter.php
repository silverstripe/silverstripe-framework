<?php

namespace SilverStripe\ORM\Filters;

/**
 * Selects numerical/date content less than or equal to the input
 *
 * Can be used by SearchContext and DataList->filter, eg;
 * Model::get()->filter("Field1:LessThanOrEqual", $value);
 */
class LessThanOrEqualFilter extends ComparisonFilter
{

    protected function getOperator()
    {
        return "<=";
    }

    protected function getInverseOperator()
    {
        return ">";
    }
}
