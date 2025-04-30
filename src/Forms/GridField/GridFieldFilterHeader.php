<?php

namespace SilverStripe\Forms\GridField;

use LogicException;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Schema\FormSchema;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\ORM\Search\BasicSearchContext;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * GridFieldFilterHeader alters the {@link GridField} with some filtering
 * fields in the header of each column.
 *
 * @see GridField
 */
class GridFieldFilterHeader extends AbstractGridFieldComponent implements GridField_URLHandler, GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider, GridField_StateProvider
{
    /**
     * See {@link setThrowExceptionOnBadDataType()}
     *
     * @var bool
     * @deprecated 5.2.0 Will be removed without equivalent functionality in a future major release
     */
    protected $throwExceptionOnBadDataType = true;

    /**
     * @var SearchContext
     */
    protected $searchContext = null;

    /**
     * @var Form
     */
    protected $searchForm = null;

    /**
     * The name of the default search field
     * @var string|null
     */
    protected ?string $searchField = null;

    private string $placeHolderText = '';

    /**
     * @inheritDoc
     */
    public function getURLHandlers($gridField)
    {
        return [
            'GET schema/SearchForm' => 'getSearchFormSchema'
        ];
    }

    /**
     * Determine what happens when this component is used with a list that isn't {@link SS_Filterable}.
     *
     *  - true: An exception is thrown
     *  - false: This component will be ignored - it won't make any changes to the GridField.
     *
     * By default, this is set to true so that it's clearer what's happening, but the predefined
     * {@link GridFieldConfig} subclasses set this to false for flexibility.
     *
     * @param bool $throwExceptionOnBadDataType
     * @deprecated 5.2.0 Will be removed without equivalent functionality in a future major release
     */
    public function setThrowExceptionOnBadDataType($throwExceptionOnBadDataType)
    {
        Deprecation::notice('5.2.0', 'Will be removed without equivalent functionality in a future major release');
        $this->throwExceptionOnBadDataType = $throwExceptionOnBadDataType;
    }

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     * @deprecated 5.2.0 Will be removed without equivalent functionality in a future major release
     */
    public function getThrowExceptionOnBadDataType()
    {
        Deprecation::notice('5.2.0', 'Will be removed without equivalent functionality in a future major release');
        return $this->throwExceptionOnBadDataType;
    }

    public function getSearchField(): ?string
    {
        return $this->searchField;
    }

    public function setSearchField(string $field): GridFieldFilterHeader
    {
        $this->searchField = $field;
        return $this;
    }

    /**
     * Check that this dataList is of the right data type.
     * Returns false if it's a bad data type, and if appropriate, throws an exception.
     *
     * @param SS_List $dataList
     * @return bool
     */
    protected function checkDataType($dataList)
    {
        if ($dataList instanceof Filterable) {
            return true;
        } else {
            // This will be changed to always throw an exception in a future major release.
            if ($this->throwExceptionOnBadDataType) {
                throw new LogicException(
                    static::class . " expects an SS_Filterable list to be passed to the GridField."
                );
            }
            return false;
        }
    }

    /**
     * If the GridField has a filterable datalist, return an array of actions
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return [];
        }

        return ['filter', 'reset'];
    }

    /**
     * If the GridField has a filterable datalist, return an array of actions
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param array $data
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return;
        }

        $state = $this->getState($gridField);
        $state->Columns = [];

        if ($actionName === 'filter') {
            if (isset($data['filter'][$gridField->getName()])) {
                foreach ($data['filter'][$gridField->getName()] as $key => $filter) {
                    $state->Columns->$key = $filter;
                }
            }
        }
    }

    /**
     * Extract state data from the parent gridfield
     * @param GridField $gridField
     * @return GridState_Data
     */
    private function getState(GridField $gridField): GridState_Data
    {
        return $gridField->State->GridFieldFilterHeader;
    }

    public function initDefaultState(GridState_Data $data): void
    {
        $data->GridFieldFilterHeader->initDefaults(['Columns' => []]);
    }

    /**
     * @inheritDoc
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        if (!$this->checkDataType($dataList)) {
            return $dataList;
        }

        /** @var array $filterArguments */
        $filterArguments = $this->getState($gridField)->Columns->toArray();
        if (empty($filterArguments)) {
            return $dataList;
        }

        $dataListClone = clone($dataList);
        $results = $this->getSearchContext($gridField)
            ->getQuery($filterArguments, false, null, $dataListClone);

        return $results;
    }

    /**
     * Returns whether this {@link GridField} has any columns to filter on at all
     *
     * @param GridField $gridField
     * @return boolean
     */
    public function canFilterAnyColumns($gridField)
    {
        $list = $gridField->getList();
        if (!($list instanceof Filterable) || !$this->checkDataType($list)) {
            return false;
        }
        $modelClass = $gridField->getModelClass();
        $singleton = singleton($modelClass);
        if (ClassInfo::hasMethod($singleton, 'summaryFields')
            && ClassInfo::hasMethod($singleton, 'searchableFields')
        ) {
            // note: searchableFields() will return summary_fields if there are no searchable_fields on the model
            $searchableFields = array_keys($singleton->searchableFields());
            $summaryFields = array_keys($singleton->summaryFields());
            sort($searchableFields);
            sort($summaryFields);
            // searchable_fields has been explictily defined i.e. searchableFields() is not falling back to summary_fields
            if (!empty($searchableFields) && ($searchableFields !== $summaryFields)) {
                return true;
            }
            // we have fallen back to summary_fields, check they are filterable
            foreach ($searchableFields as $searchableField) {
                if ($list->canFilterBy($searchableField)) {
                    return true;
                }
            }
        } else {
            // Allows non-DataObject classes to be used with this component
            $columns = $gridField->getColumns();
            foreach ($columns as $columnField) {
                $metadata = $gridField->getColumnMetadata($columnField);
                $title = $metadata['title'];

                if ($title && $list->canFilterBy($columnField)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the text to be used as a placeholder in the search field.
     * If blank, the placeholder will be generated based on the class held in the GridField.
     */
    public function getPlaceHolderText(): string
    {
        return $this->placeHolderText;
    }

    /**
     * Set the text to be used as a placeholder in the search field.
     * If blank, this text will be generated based on the class held in the GridField.
     */
    public function setPlaceHolderText(string $placeHolderText): static
    {
        $this->placeHolderText = $placeHolderText;
        return $this;
    }

    /**
     * Generate a search context based on the model class of the of the GridField
     *
     * @param GridField $gridfield
     * @return SearchContext
     */
    public function getSearchContext(GridField $gridField)
    {
        if (!$this->searchContext) {
            $modelClass = $gridField->getModelClass();
            $singleton = singleton($modelClass);
            if (!$singleton->hasMethod('getDefaultSearchContext')) {
                throw new LogicException(
                    'Cannot dynamically instantiate SearchContext. Pass the SearchContext to setSearchContext()'
                    . " or implement a getDefaultSearchContext() method on $modelClass"
                );
            }

            $list = $gridField->getList();
            $searchContext = $singleton->getDefaultSearchContext();

            // In case we are working with a list not backed by the database we need to convert the search context into a BasicSearchContext
            // This is because the scaffolded filters use the ORM for data searching
            if (!$list instanceof DataList) {
                $searchContext = $this->getBasicSearchContext($gridField, $searchContext);
            }

            $this->searchContext = $searchContext;
        }

        return $this->searchContext;
    }

    /**
     * Sets a specific SearchContext instance for this component to use, instead of the default
     * context provided by the ModelClass.
     */
    public function setSearchContext(SearchContext $context): static
    {
        $this->searchContext = $context;
        return $this;
    }

    /**
     * Returns the search field schema for the component
     *
     * @param GridField $gridfield
     * @return string
     * @deprecated 5.4.0 Will be replaced with SilverStripe\ORM\Search\SearchContextForm::getSchemaData() in a future major release
     */
    public function getSearchFieldSchema(GridField $gridField)
    {
        Deprecation::noticeWithNoReplacment(
            '5.4.0',
            'Will be replaced with SilverStripe\ORM\Search\SearchContextForm::getSchemaData() in a future major release'
        );
        $schemaUrl = Controller::join_links($gridField->Link(), 'schema/SearchForm');
        $inst = singleton($gridField->getModelClass());
        $context = $this->getSearchContext($gridField);
        $params = $gridField->getRequest()->postVar('filter') ?: [];
        if (array_key_exists($gridField->getName(), $params ?? [])) {
            $params = $params[$gridField->getName()];
        }
        if ($context->getSearchParams()) {
            $params = array_merge($context->getSearchParams(), $params);
        }
        $context->setSearchParams($params);

        $searchField = $this->getSearchField() ?: $inst->config()->get('general_search_field');
        if (!$searchField) {
            $searchField = $context->getSearchFields()->first();
            $searchField = $searchField && property_exists($searchField, 'name') ? $searchField->name : null;
        }

        // Prefix "Search__" onto the filters to match the field names in the actual form
        $filters = $context->getSearchParams();
        if (!empty($filters)) {
            $filters = array_combine(array_map(function ($key) {
                return 'Search__' . $key;
            }, array_keys($filters ?? [])), $filters ?? []);
        }

        $searchAction = GridField_FormAction::create($gridField, 'filter', false, 'filter', null);
        $clearAction = GridField_FormAction::create($gridField, 'reset', false, 'reset', null);
        $schema = [
            'formSchemaUrl' => $schemaUrl,
            'name' => $searchField,
            'placeholder' => $this->getPlaceHolder($inst),
            'filters' => $filters ?: new \stdClass, // stdClass maps to empty json object '{}'
            'gridfield' => $gridField->getName(),
            'searchAction' => $searchAction->getAttribute('name'),
            'searchActionState' => $searchAction->getAttribute('data-action-state'),
            'clearAction' => $clearAction->getAttribute('name'),
            'clearActionState' => $clearAction->getAttribute('data-action-state'),
        ];

        return json_encode($schema);
    }

    /**
     * Returns the search form for the component
     *
     * @param GridField $gridField
     * @return Form|null
     */
    public function getSearchForm(GridField $gridField)
    {
        $searchContext = $this->getSearchContext($gridField);
        $searchFields = $searchContext->getSearchFields();

        if ($searchFields->count() === 0) {
            return null;
        }

        if ($this->searchForm) {
            return $this->searchForm;
        }

        $this->addSearchPrefixToFields($searchFields);

        // Update field titles to match column titles
        $columns = $gridField->getColumns();
        foreach ($columns as $columnField) {
            $metadata = $gridField->getColumnMetadata($columnField);
            // Get the field name, without any modifications
            $name = explode('.', $columnField ?? '');
            $title = $metadata['title'];
            $field = $searchFields->fieldByName($name[0]);

            if ($field) {
                $field->setTitle($title);
            }
        }

        $this->updateFieldClasses($searchFields);

        $name = $this->getTitle(singleton($gridField->getModelClass()));

        $this->searchForm = $form = new Form(
            $gridField,
            $name . "SearchForm",
            $searchFields,
            new FieldList()
        );

        $form->setFormMethod('get');
        $form->setFormAction($gridField->Link());
        $form->addExtraClass('cms-search-form form--no-dividers');
        $form->disableSecurityToken(); // This form is not tied to session so we disable this
        $form->loadDataFrom($searchContext->getSearchParams());

        return $this->searchForm;
    }

    /**
     * Returns the search form schema for the component
     *
     * @param GridField $gridfield
     * @return HTTPResponse
     * @deprecated 5.4.0 Will be replaced with SilverStripe\Forms\FormRequestHandler::getSchema() in a future major release
     */
    public function getSearchFormSchema(GridField $gridField)
    {
        Deprecation::noticeWithNoReplacment(
            '5.4.0',
            'Will be replaced with SilverStripe\Forms\FormRequestHandler::getSchema() in a future major release'
        );
        $form = $this->getSearchForm($gridField);

        // If there are no filterable fields, return a 400 response
        if (!$form) {
            return new HTTPResponse(_t(__CLASS__ . '.SearchFormFaliure', 'No search form could be generated'), 400);
        }

        $parts = $gridField->getRequest()->getHeader(FormSchema::SCHEMA_HEADER);
        $schemaID = $gridField->getRequest()->getURL();
        $data = FormSchema::singleton()
            ->getMultipartSchema($parts, $schemaID, $form);

        $response = new HTTPResponse(json_encode($data));
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Either returns the legacy filter header or the search button and field
     *
     * @param GridField $gridField
     * @return array|null
     */
    public function getHTMLFragments($gridField)
    {
        $forTemplate = new ArrayData([]);

        if (!$this->canFilterAnyColumns($gridField)) {
            return null;
        }

        $fieldSchema = $this->getSearchFieldSchema($gridField);
        $forTemplate->SearchFieldSchema = $fieldSchema;
        $searchTemplates = SSViewer::get_templates_by_class($this, '_Search', __CLASS__);
        return [
            'before' => $forTemplate->renderWith($searchTemplates),
            'buttons-before-right' => sprintf(
                '<button type="button" name="showFilter" aria-label="%s" title="%s"' .
                ' class="btn btn-secondary font-icon-search btn--no-text btn--icon-large grid-field__filter-open"></button>',
                _t('SilverStripe\\Forms\\GridField\\GridField.OpenFilter', "Open search and filter"),
                _t('SilverStripe\\Forms\\GridField\\GridField.OpenFilter', "Open search and filter")
            )
        ];
    }

    /**
     * Get the text that will be used as a placeholder in the search field.
     *
     * @param object $obj An instance of the class that will be searched against.
     * If getPlaceHolderText is empty, this object will be used to build the placeholder
     * e.g. 'Search "My Data Object"'
     */
    private function getPlaceHolder(object $obj): string
    {
        $placeholder = $this->getPlaceHolderText();
        if (!empty($placeholder)) {
            return $placeholder;
        }
        if ($obj) {
            return _t(__CLASS__ . '.Search', 'Search "{name}"', ['name' => $this->getTitle($obj)]);
        }
        return _t(__CLASS__ . '.Search_Default', 'Search');
    }

    private function getTitle(object $inst): string
    {
        if (ClassInfo::hasMethod($inst, 'i18n_plural_name')) {
            return $inst->i18n_plural_name();
        }

        return ClassInfo::shortName($inst);
    }

    /**
     * Transform search context into BasicSearchContext (preserves all relevant search settings)
     */
    private function getBasicSearchContext(GridField $gridField, SearchContext $searchContext): BasicSearchContext
    {
        // Retrieve filters settings as these can be carried over as is
        $defaultSearchFields = $searchContext->getSearchFields();
        $defaultFilters = $searchContext->getFilters();
        $list = $gridField->getList();

        // Carry over any search form settings
        $basicSearchContext = BasicSearchContext::create($gridField->getModelClass());
        $basicSearchContext->setFields($defaultSearchFields);

        // Carry over filter configuration (make changes to filter classes so they work with this list)
        foreach ($defaultFilters as $defaultFilter) {
            $fieldFilter = PartialMatchFilter::create(
                // Use name instead of full name as this plain filter doesn't understand relations
                $defaultFilter->getName(),
                $defaultFilter->getValue(),
                $defaultFilter->getModifiers(),
            );
            $basicSearchContext->addFilter($fieldFilter);
        }

        return $basicSearchContext;
    }

    /*
     * Append a prefix to search field names to prevent conflicts with other fields in the search form
     */
    private function addSearchPrefixToFields(FieldList $fields): void
    {
        foreach ($fields as $field) {
            $field->setName('Search__' . $field->getName());
            if ($field instanceof CompositeField) {
                $this->addSearchPrefixToFields($field->getChildren());
            }
        }
    }

    /**
     * Update CSS classes for form fields, including nested inside composite fields
     */
    private function updateFieldClasses(FieldList $fields): void
    {
        foreach ($fields as $field) {
            $field->addExtraClass('stacked no-change-track');
            if ($field instanceof CompositeField) {
                $this->updateFieldClasses($field->getChildren());
            }
        }
    }
}
