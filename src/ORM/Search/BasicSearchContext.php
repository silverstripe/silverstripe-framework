<?php

namespace SilverStripe\ORM\Search;

use InvalidArgumentException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\Sortable;

/**
 * A SearchContext that can be used with non-ORM data.
 * This class isn't guaranteed to respect the full searchable fields spec defined on DataObject classes.
 */
class BasicSearchContext extends SearchContext
{
    use Configurable;

    /**
     * Name of the field which, if included in search forms passed to this object, will be used
     * to search across all searchable fields.
     */
    private static $general_search_field_name = 'q';

    /**
     * Returns a list which has been limited, sorted, and filtered by the given parameters.
     *
     * @param array $searchParams Map of search criteria, mostly taken from $_REQUEST.
     *  If a filter is applied to a relationship in dot notation,
     *  the parameter name should have the dots replaced with double underscores,
     *  for example "Comments__Name" instead of the filter name "Comments.Name".
     * @param array|bool|string $sort Field to sort on.
     * @param array|null|string $limit
     * @param Filterable&Sortable&Limitable $existingQuery
     */
    public function getQuery($searchParams, $sort = false, $limit = false, $existingQuery = null): Filterable&Sortable&Limitable
    {
        if (!$existingQuery || !($existingQuery instanceof Filterable) || !($existingQuery instanceof Sortable) || !($existingQuery instanceof Limitable)) {
            throw new InvalidArgumentException('getQuery requires a pre-existing filterable/sortable/limitable list to be passed as $existingQuery.');
        }

        if ((count(func_get_args()) >= 3) && (!in_array(gettype($limit), ['array', 'NULL', 'string']))) {
            Deprecation::notice(
                '5.1.0',
                '$limit should be type of array|string|null'
            );
            $limit = null;
        }

        $searchParams = $this->applySearchFilters($this->normaliseSearchParams($searchParams));
        $result = $this->applyGeneralSearchField($searchParams, $existingQuery);

        // Filter the list by the requested filters.
        if (!empty($searchParams)) {
            $result = $result->filter($searchParams);
        }

        // Only sort if a sort value is provided - sort by "false" just means use the existing sort.
        if ($sort) {
            $result = $result->sort($sort);
        }

        // Limit must be last so that ArrayList results don't have an applied limit before they can be filtered/sorted.
        $result = $result->limit($limit);

        return $result;
    }

    private function normaliseSearchParams(array $searchParams): array
    {
        $normalised = [];
        foreach ($searchParams as $field => $searchTerm) {
            if ($this->clearEmptySearchFields($searchTerm)) {
                $normalised[str_replace('__', '.', $field)] = $searchTerm;
            }
        }
        return $normalised;
    }

    private function applySearchFilters(array $searchParams): array
    {
        $applied = [];
        foreach ($searchParams as $fieldName => $searchTerm) {
            // Ignore the general search field - we'll deal with that in a special way.
            if ($fieldName === static::config()->get('general_search_field_name')) {
                $applied[$fieldName] = $searchTerm;
                continue;
            }
            $filterTerm = $this->getFilterTerm($fieldName);
            $applied["{$fieldName}:{$filterTerm}"] = $searchTerm;
        }
        return $applied;
    }

    private function applyGeneralSearchField(array &$searchParams, Filterable $existingQuery): Filterable
    {
        $generalFieldName = static::config()->get('general_search_field_name');
        if (array_key_exists($generalFieldName, $searchParams)) {
            $searchTerm = $searchParams[$generalFieldName];
            if (Config::inst()->get($this->modelClass, 'general_search_split_terms') !== false) {
                $searchTerm = explode(' ', $searchTerm);
            }
            $generalFilter = [];
            foreach ($this->getSearchFields()->dataFieldNames() as $fieldName) {
                if ($fieldName === $generalFieldName) {
                    continue;
                }
                if (!$this->getCanGeneralSearch($fieldName)) {
                    continue;
                }
                $filterTerm = $this->getGeneralSearchFilterTerm($fieldName);
                $generalFilter["{$fieldName}:{$filterTerm}"] = $searchTerm;
            }
            $result = $existingQuery->filterAny($generalFilter);
            unset($searchParams[$generalFieldName]);
        }

        return $result ?? $existingQuery;
    }

    private function getCanGeneralSearch(string $fieldName): bool
    {
        $singleton = singleton($this->modelClass);

        // Allowed if we're dealing with arbitrary data.
        if (!ClassInfo::hasMethod($singleton, 'searchableFields')) {
            return true;
        }

        $fields = $singleton->searchableFields();

        // Not allowed if the field isn't searchable.
        if (!isset($fields[$fieldName])) {
            return false;
        }

        // Allowed if 'general' isn't part of the spec, or is explicitly truthy.
        return !isset($fields[$fieldName]['general']) || $fields[$fieldName]['general'];
    }

    /**
     * Get the search filter for the given fieldname when searched from the general search field.
     */
    private function getGeneralSearchFilterTerm(string $fieldName): string
    {
        $filterClass = Config::inst()->get($this->modelClass, 'general_search_field_filter');
        if ($filterClass) {
            return $this->getTermFromFilter(Injector::inst()->create($filterClass, $fieldName));
        }

        if ($filterClass === '') {
            return $this->getFilterTerm($fieldName);
        }

        return 'PartialMatch:nocase';
    }

    private function getFilterTerm(string $fieldName): string
    {
        $filter = $this->getFilter($fieldName) ?? PartialMatchFilter::create($fieldName);
        return $this->getTermFromFilter($filter);
    }

    private function getTermFromFilter(SearchFilter $filter): string
    {
        $modifiers = $filter->getModifiers() ?? [];

        // Get the string used to refer to the filter, e.g. "PartialMatch"
        // Ask the injector for it first - but for any not defined there, fall back to string manipulation.
        $filterTerm = Injector::inst()->getServiceName(get_class($filter));
        if (!$filterTerm) {
            $filterTerm = preg_replace('/Filter$/', '', ClassInfo::shortName($filter));
        }

        // Add modifiers to filter
        foreach ($modifiers as $modifier) {
            $filterTerm .= ":{$modifier}";
        }

        return $filterTerm;
    }
}
