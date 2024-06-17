<?php

namespace SilverStripe\ORM\Search;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\SelectField;
use SilverStripe\Forms\CheckboxField;
use InvalidArgumentException;
use Exception;
use LogicException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\DataQuery;

/**
 * Manages searching of properties on one or more {@link DataObject}
 * types, based on a given set of input parameters.
 * SearchContext is intentionally decoupled from any controller-logic,
 * it just receives a set of search parameters and an object class it acts on.
 *
 * The default output of a SearchContext is either a {@link SQLSelect} object
 * for further refinement, or a {@link SS_List} that can be used to display
 * search results, e.g. in a {@link TableListField} instance.
 *
 * In case you need multiple contexts, consider namespacing your request parameters
 * by using {@link FieldList->namespace()} on the $fields constructor parameter.
 *
 * Each DataObject subclass can have multiple search contexts for different cases,
 * e.g. for a limited frontend search and a fully featured backend search.
 * By default, you can use {@link DataObject->getDefaultSearchContext()} which is automatically
 * scaffolded. It uses {@link DataObject::$searchable_fields} to determine which fields
 * to include.
 *
 * @see http://doc.silverstripe.com/doku.php?id=searchcontext
 *
 * @template T of object
 */
class SearchContext
{
    use Injectable;

    /**
     * DataObject subclass to which search parameters relate to.
     * Also determines as which object each result is provided.
     *
     * @var class-string<T>
     */
    protected $modelClass;

    /**
     * FormFields mapping to {@link DataObject::$db} properties
     * which are supposed to be searchable.
     *
     * @var FieldList
     */
    protected $fields;

    /**
     * Array of {@link SearchFilter} subclasses.
     *
     * @var SearchFilter[]
     */
    protected $filters;

    /**
     * Key/value pairs of search fields to search terms
     *
     * @var array
     */
    protected $searchParams = [];

    /**
     * A key value pair of values that should be searched for.
     * The keys should match the field names specified in {@link SearchContext::$fields}.
     * Usually these values come from a submitted searchform
     * in the form of a $_REQUEST object.
     * CAUTION: All values should be treated as insecure client input.
     *
     * @param class-string<T> $modelClass The base {@link DataObject} class that search properties related to.
     *                      Also used to generate a set of result objects based on this class.
     * @param FieldList $fields Optional. FormFields mapping to {@link DataObject::$db} properties
     *                      which are to be searched. Derived from modelclass using
     *                      {@link DataObject::scaffoldSearchFields()} if left blank.
     * @param array $filters Optional. Derived from modelclass if left blank
     */
    public function __construct($modelClass, $fields = null, $filters = null)
    {
        $this->modelClass = $modelClass;
        $this->fields = ($fields) ? $fields : new FieldList();
        $this->filters = ($filters) ? $filters : [];
    }

    /**
     * Returns scaffolded search fields for UI.
     *
     * @return FieldList
     */
    public function getSearchFields()
    {
        if ($this->fields?->exists()) {
            return $this->fields;
        }

        $singleton = singleton($this->modelClass);
        if (!$singleton->hasMethod('scaffoldSearchFields')) {
            throw new LogicException(
                'Cannot dynamically determine search fields. Pass the fields to setFields()'
                . " or implement a scaffoldSearchFields() method on {$this->modelClass}"
            );
        }
        return $singleton->scaffoldSearchFields();
    }

    protected function applyBaseTableFields()
    {
        $classes = ClassInfo::dataClassesFor($this->modelClass);
        $baseTable = DataObject::getSchema()->baseDataTable($this->modelClass);
        $fields = ["\"{$baseTable}\".*"];
        if ($this->modelClass != $classes[0]) {
            $fields[] = '"' . $classes[0] . '".*';
        }
        //$fields = array_keys($model->db());
        $fields[] = '"' . $classes[0] . '".\"ClassName\" AS "RecordClassName"';
        return $fields;
    }

    /**
     * Returns a SQL object representing the search context for the given
     * list of query parameters.
     *
     * @param array $searchParams Map of search criteria, mostly taken from $_REQUEST.
     *  If a filter is applied to a relationship in dot notation,
     *  the parameter name should have the dots replaced with double underscores,
     *  for example "Comments__Name" instead of the filter name "Comments.Name".
     * @param array|bool|string $sort Database column to sort on.
     *  Falls back to {@link DataObject::$default_sort} if not provided.
     * @param int|array|null $limit
     * @param DataList $existingQuery
     * @return DataList<T>
     * @throws Exception
     */
    public function getQuery($searchParams, $sort = false, $limit = false, $existingQuery = null)
    {
        if ((count(func_get_args()) >= 3) && (!in_array(gettype($limit), ['integer', 'array', 'NULL']))) {
            Deprecation::notice(
                '5.1.0',
                '$limit should be type of int|array|null'
            );
            $limit = null;
        }
        $this->setSearchParams($searchParams);
        $query = $this->prepareQuery($sort, $limit, $existingQuery);
        return $this->search($query);
    }

    /**
     * Perform a search on the passed DataList based on $this->searchParams.
     * @return DataList<T>
     */
    private function search(DataList $query): DataList
    {
        /** @var DataObject $modelObj */
        $modelObj = Injector::inst()->create($this->modelClass);
        $searchableFields = $modelObj->searchableFields();
        foreach ($this->searchParams as $searchField => $searchPhrase) {
            $searchField = str_replace('__', '.', $searchField ?? '');
            if ($searchField !== '' && $searchField === $modelObj->getGeneralSearchFieldName()) {
                $query = $this->generalFieldSearch($query, $searchableFields, $searchPhrase);
            } else {
                $query = $this->individualFieldSearch($query, $searchableFields, $searchField, $searchPhrase);
            }
        }
        return $query;
    }

    /**
     * Prepare the query to begin searching
     *
     * @param array|bool|string $sort Database column to sort on.
     * @param int|array|null $limit
     * @return DataList<T>
     */
    private function prepareQuery($sort, $limit, ?DataList $existingQuery): DataList
    {
        if ($limit === false) {
            $limit = null;
        }
        $query = null;
        if ($existingQuery) {
            if (!($existingQuery instanceof DataList)) {
                throw new InvalidArgumentException("existingQuery must be DataList");
            }
            if ($existingQuery->dataClass() != $this->modelClass) {
                throw new InvalidArgumentException("existingQuery's dataClass is " . $existingQuery->dataClass()
                    . ", $this->modelClass expected.");
            }
            $query = $existingQuery;
        } else {
            $query = DataList::create($this->modelClass);
        }

        if (is_array($limit)) {
            $query = $query->limit(
                isset($limit['limit']) ? $limit['limit'] : null,
                isset($limit['start']) ? $limit['start'] : null
            );
        } else {
            $query = $query->limit($limit);
        }
        if (!empty($sort) || is_null($sort)) {
            $query = $query->sort($sort);
        }
        return $query;
    }

    /**
     * Takes a search phrase or search term and searches for it across all searchable fields.
     *
     * @param string|array $searchPhrase
     */
    private function generalSearchAcrossFields($searchPhrase, DataQuery $subGroup, array $searchableFields): void
    {
        $formFields = $this->getSearchFields();
        foreach ($searchableFields as $field => $spec) {
            $formFieldName = str_replace('.', '__', $field);
            $filter = $this->getGeneralSearchFilter($this->modelClass, $field);
            // Only apply filter if the field is allowed to be general and is backed by a form field.
            // Otherwise we could be dealing with, for example, a DataObject which implements scaffoldSearchField
            // to provide some unexpected field name, where the below would result in a DatabaseException.
            if ((!isset($spec['general']) || $spec['general'])
                && ($formFields->fieldByName($formFieldName) || $formFields->dataFieldByName($formFieldName))
                && $filter !== null
            ) {
                $filter->setModel($this->modelClass);
                $filter->setValue($searchPhrase);
                $this->applyFilter($filter, $subGroup, $spec);
            }
        }
    }

    /**
     * Use the global general search for searching across multiple fields.
     *
     * @param string|array $searchPhrase
     * @return DataList<T>
     */
    private function generalFieldSearch(DataList $query, array $searchableFields, $searchPhrase): DataList
    {
        return $query->alterDataQuery(function (DataQuery $dataQuery) use ($searchableFields, $searchPhrase) {
            // If necessary, split search phrase into terms, then search across fields.
            if (Config::inst()->get($this->modelClass, 'general_search_split_terms')) {
                if (is_array($searchPhrase)) {
                    // Allow matches from ANY query in the array (i.e. return $obj where query1 matches OR query2 matches)
                    $dataQuery = $dataQuery->disjunctiveGroup();
                    foreach ($searchPhrase as $phrase) {
                        // where ((field1 LIKE %lorem% OR field2 LIKE %lorem%) AND (field1 LIKE %ipsum% OR field2 LIKE %ipsum%))
                        $generalSubGroup = $dataQuery->conjunctiveGroup();
                        foreach (explode(' ', $phrase) as $searchTerm) {
                            $this->generalSearchAcrossFields($searchTerm, $generalSubGroup->disjunctiveGroup(), $searchableFields);
                        }
                    }
                } else {
                    // where ((field1 LIKE %lorem% OR field2 LIKE %lorem%) AND (field1 LIKE %ipsum% OR field2 LIKE %ipsum%))
                    $generalSubGroup = $dataQuery->conjunctiveGroup();
                    foreach (explode(' ', $searchPhrase) as $searchTerm) {
                        $this->generalSearchAcrossFields($searchTerm, $generalSubGroup->disjunctiveGroup(), $searchableFields);
                    }
                }
            } else {
                // where (field1 LIKE %lorem ipsum% OR field2 LIKE %lorem ipsum%)
                $this->generalSearchAcrossFields($searchPhrase, $dataQuery->disjunctiveGroup(), $searchableFields);
            }
        });
    }

    /**
     * Get the search filter for the given fieldname when searched from the general search field.
     */
    private function getGeneralSearchFilter(string $modelClass, string $fieldName): ?SearchFilter
    {
        if ($filterClass = Config::inst()->get($modelClass, 'general_search_field_filter')) {
            return Injector::inst()->create($filterClass, $fieldName);
        }
        return $this->getFilter($fieldName);
    }

    /**
     * Search against a single field
     *
     * @param string|array $searchPhrase
     * @return DataList<T>
     */
    private function individualFieldSearch(DataList $query, array $searchableFields, string $searchField, $searchPhrase): DataList
    {
        $filter = $this->getFilter($searchField);
        if (!$filter) {
            return $query;
        }
        $filter->setModel($this->modelClass);
        $filter->setValue($searchPhrase);
        $searchableFieldSpec = $searchableFields[$searchField] ?? [];
        return $query->alterDataQuery(function ($dataQuery) use ($filter, $searchableFieldSpec) {
            $this->applyFilter($filter, $dataQuery, $searchableFieldSpec);
        });
    }

    /**
     * Apply a SearchFilter to a DataQuery for a given field's specifications
     */
    private function applyFilter(SearchFilter $filter, DataQuery $dataQuery, array $searchableFieldSpec): void
    {
        if ($filter->isEmpty()) {
            return;
        }
        if (isset($searchableFieldSpec['match_any'])) {
            $searchFields = $searchableFieldSpec['match_any'];
            $filterClass = get_class($filter);
            $value = $filter->getValue();
            $modifiers = $filter->getModifiers();
            $subGroup = $dataQuery->disjunctiveGroup();
            foreach ($searchFields as $matchField) {
                /** @var SearchFilter $filter */
                $filter = Injector::inst()->create($filterClass, $matchField, $value, $modifiers);
                $filter->apply($subGroup);
            }
        } else {
            $filter->apply($dataQuery);
        }
    }

    /**
     * Returns a result set from the given search parameters.
     *
     * @param array $searchParams
     * @param array|bool|string $sort
     * @param array|null|string $limit
     * @return DataList<T>
     * @throws Exception
     */
    public function getResults($searchParams, $sort = false, $limit = null)
    {
        $searchParams = array_filter((array)$searchParams, [$this, 'clearEmptySearchFields']);

        // getQuery actually returns a DataList
        return $this->getQuery($searchParams, $sort, $limit);
    }

    /**
     * Callback map function to filter fields with empty values from
     * being included in the search expression.
     *
     * @param mixed $value
     * @return boolean
     */
    public function clearEmptySearchFields($value)
    {
        return ($value != '');
    }

    /**
     * Accessor for the filter attached to a named field.
     *
     * @param string $name
     * @return SearchFilter|null
     */
    public function getFilter($name)
    {
        if (isset($this->filters[$name])) {
            return $this->filters[$name];
        } else {
            return null;
        }
    }

    /**
     * Get the map of filters in the current search context.
     *
     * @return SearchFilter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Overwrite the current search context filter map.
     *
     * @param SearchFilter[] $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * Adds a instance of {@link SearchFilter}.
     *
     * @param SearchFilter $filter
     */
    public function addFilter($filter)
    {
        $this->filters[$filter->getFullName()] = $filter;
    }

    /**
     * Removes a filter by name.
     *
     * @param string $name
     */
    public function removeFilterByName($name)
    {
        unset($this->filters[$name]);
    }

    /**
     * Get the list of searchable fields in the current search context.
     *
     * @return FieldList
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Apply a list of searchable fields to the current search context.
     *
     * @param FieldList $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * Adds a new {@link FormField} instance.
     *
     * @param FormField $field
     */
    public function addField($field)
    {
        $this->fields?->push($field);
    }

    /**
     * Removes an existing formfield instance by its name.
     *
     * @param string $fieldName
     */
    public function removeFieldByName($fieldName)
    {
        $this->fields?->removeByName($fieldName);
    }

    /**
     * Set search param values
     *
     * @param array|HTTPRequest $searchParams
     * @return $this
     */
    public function setSearchParams($searchParams)
    {
        // hack to work with $searchParams when it's an Object
        if ($searchParams instanceof HTTPRequest) {
            $this->searchParams = $searchParams->getVars();
        } else {
            $this->searchParams = $searchParams;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getSearchParams()
    {
        return $this->searchParams;
    }

    /**
     * Gets a list of what fields were searched and the values provided
     * for each field. Returns an ArrayList of ArrayData, suitable for
     * rendering on a template.
     *
     * @return ArrayList<ArrayData>
     */
    public function getSummary()
    {
        $list = ArrayList::create();
        foreach ($this->searchParams as $searchField => $searchValue) {
            if (empty($searchValue)) {
                continue;
            }
            $filter = $this->getFilter($searchField);
            if (!$filter) {
                continue;
            }

            $field = $this->fields?->fieldByName($filter->getFullName());
            if (!$field) {
                continue;
            }

            // For dropdowns, checkboxes, etc, get the value that was presented to the user
            // e.g. not an ID
            if ($field instanceof SelectField) {
                $source = $field->getSource();
                if (isset($source[$searchValue])) {
                    $searchValue = $source[$searchValue];
                }
            } else {
                // For checkboxes, it suffices to simply include the field in the list, since it's binary
                if ($field instanceof CheckboxField) {
                    $searchValue = null;
                }
            }

            $list->push(ArrayData::create([
                'Field' => $field->Title(),
                'Value' => $searchValue,
            ]));
        }

        return $list;
    }
}
