<?php

namespace SilverStripe\ORM\Filters;

use SilverStripe\Core\Injector\Injector;

trait SearchFilterable
{
    /**
     * Given a filter expression and value construct a {@see SearchFilter} instance
     *
     * @param string $filter E.g. `Name:ExactMatch:not`, `Name:ExactMatch`, `Name:not`, `Name`
     * @param mixed $value Value of the filter
     * @return SearchFilter
     */
    protected function createSearchFilter($filter, $value)
    {
        // Field name is always the first component
        $fieldArgs = explode(':', $filter);
        $fieldName = array_shift($fieldArgs);
        $default = 'DataListFilter.default';

        // Inspect type of second argument to determine context
        $secondArg = array_shift($fieldArgs);
        $modifiers = $fieldArgs;
        if (!$secondArg) {
            // Use default SearchFilter if none specified. E.g. `->filter(['Name' => $myname])`
            $filterServiceName = $default;
        } else {
            // The presence of a second argument is by default ambiguous; We need to query
            // Whether this is a valid modifier on the default filter, or a filter itself.
            /** @var SearchFilter $defaultFilterInstance */
            $defaultFilterInstance = Injector::inst()->get($default);
            if (in_array(strtolower($secondArg), $defaultFilterInstance->getSupportedModifiers() ?? [])) {
                // Treat second (and any subsequent) argument as modifiers, using default filter
                $filterServiceName = $default;
                array_unshift($modifiers, $secondArg);
            } else {
                // Second argument isn't a valid modifier, so assume is filter identifier
                $filterServiceName = "DataListFilter.{$secondArg}";
            }
        }

        // Build instance
        return Injector::inst()->create($filterServiceName, $fieldName, $value, $modifiers);
    }
}
