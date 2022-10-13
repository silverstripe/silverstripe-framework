<?php

namespace SilverStripe\Forms\GridField;

use LogicException;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Schema\FormSchema;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Filterable;
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
     */
    protected $throwExceptionOnBadDataType = true;

    /**
     * Indicates that this component should revert to displaying it's legacy
     * table header style rather than the react driven search box
     *
     * @deprecated 4.3.0:5.0.0 Will be removed in 5.0
     * @var bool
     */
    public $useLegacyFilterHeader = false;

    /**
     * Forces all filter components to revert to displaying the legacy
     * table header style rather than the react driven search box
     *
     * @deprecated 4.3.0:5.0.0 Will be removed in 5.0
     * @config
     * @var bool
     */
    private static $force_legacy = false;

    /**
     * @var \SilverStripe\ORM\Search\SearchContext
     */
    protected $searchContext = null;

    /**
     * @var Form
     */
    protected $searchForm = null;

    /**
     * @var callable
     * @deprecated 4.3.0:5.0.0 Will be removed in 5.0
     */
    protected $updateSearchContextCallback = null;

    /**
     * @var callable
     * @deprecated 4.3.0:5.0.0 Will be removed in 5.0
     */
    protected $updateSearchFormCallback = null;

    /**
     * The name of the default search field
     * @var string|null
     */
    protected ?string $searchField = null;

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
     * @param bool $useLegacy This will be removed in 5.0
     * @param callable|null $updateSearchContext This will be removed in 5.0
     * @param callable|null $updateSearchForm This will be removed in 5.0
     */
    public function __construct(
        $useLegacy = false,
        callable $updateSearchContext = null,
        callable $updateSearchForm = null
    ) {
        $this->useLegacyFilterHeader = Config::inst()->get(self::class, 'force_legacy') || $useLegacy;
        $this->updateSearchContextCallback = $updateSearchContext;
        $this->updateSearchFormCallback = $updateSearchForm;
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
     */
    public function setThrowExceptionOnBadDataType($throwExceptionOnBadDataType)
    {
        $this->throwExceptionOnBadDataType = $throwExceptionOnBadDataType;
    }

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     */
    public function getThrowExceptionOnBadDataType()
    {
        return $this->throwExceptionOnBadDataType;
    }

    public function getSearchField(): ?string
    {
        return $this->searchField;
    }

    public function setSearchField(string $field): self
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

        /** @var Filterable $dataList */
        /** @var array $filterArguments */
        $filterArguments = $this->getState($gridField)->Columns->toArray();
        if (empty($filterArguments)) {
            return $dataList;
        }

        $dataListClone = clone($dataList);
        $results = $this->getSearchContext($gridField)
            ->getQuery($filterArguments, false, false, $dataListClone);

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

        if (!$this->checkDataType($list)) {
            return false;
        }

        $columns = $gridField->getColumns();
        foreach ($columns as $columnField) {
            $metadata = $gridField->getColumnMetadata($columnField);
            $title = $metadata['title'];

            if ($title && $list->canFilterBy($columnField)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Generate a search context based on the model class of the of the GridField
     *
     * @param GridField $gridfield
     * @return \SilverStripe\ORM\Search\SearchContext
     */
    public function getSearchContext(GridField $gridField)
    {
        if (!$this->searchContext) {
            $this->searchContext = singleton($gridField->getModelClass())->getDefaultSearchContext();

            if ($this->updateSearchContextCallback) {
                call_user_func($this->updateSearchContextCallback, $this->searchContext);
            }
        }

        return $this->searchContext;
    }

    /**
     * Returns the search field schema for the component
     *
     * @param GridField $gridfield
     * @return string
     */
    public function getSearchFieldSchema(GridField $gridField)
    {
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

        $name = $gridField->Title ?: $inst->i18n_plural_name();

        // Prefix "Search__" onto the filters for the React component
        $filters = $context->getSearchParams();
        if (!$this->useLegacyFilterHeader && !empty($filters)) {
            $filters = array_combine(array_map(function ($key) {
                return 'Search__' . $key;
            }, array_keys($filters ?? [])), $filters ?? []);
        }

        $searchAction = GridField_FormAction::create($gridField, 'filter', false, 'filter', null);
        $clearAction = GridField_FormAction::create($gridField, 'reset', false, 'reset', null);
        $schema = [
            'formSchemaUrl' => $schemaUrl,
            'name' => $searchField,
            'placeholder' => _t(__CLASS__ . '.Search', 'Search "{name}"', ['name' => $name]),
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

        // Append a prefix to search field names to prevent conflicts with other fields in the search form
        foreach ($searchFields as $field) {
            $field->setName('Search__' . $field->getName());
        }

        $columns = $gridField->getColumns();

        // Update field titles to match column titles
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

        foreach ($searchFields->getIterator() as $field) {
            $field->addExtraClass('stacked no-change-track');
        }

        $name = $gridField->Title ?: singleton($gridField->getModelClass())->i18n_plural_name();

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

        if ($this->updateSearchFormCallback) {
            call_user_func($this->updateSearchFormCallback, $form);
        }

        return $this->searchForm;
    }

    /**
     * Returns the search form schema for the component
     *
     * @param GridField $gridfield
     * @return HTTPResponse
     */
    public function getSearchFormSchema(GridField $gridField)
    {
        $form = $this->getSearchForm($gridField);

        // If there are no filterable fields, return a 400 response
        if (!$form) {
            return new HTTPResponse(_t(__CLASS__ . '.SearchFormFaliure', 'No search form could be generated'), 400);
        }

        $parts = $gridField->getRequest()->getHeader(LeftAndMain::SCHEMA_HEADER);
        $schemaID = $gridField->getRequest()->getURL();
        $data = FormSchema::singleton()
            ->getMultipartSchema($parts, $schemaID, $form);

        $response = new HTTPResponse(json_encode($data));
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Generate fields for the legacy filter header row
     *
     * @deprecated 4.12.0 Use search field instead
     * @param GridField $gridfield
     * @return ArrayList|null
     */
    public function getLegacyFilterHeader(GridField $gridField)
    {
        Deprecation::notice('4.12.0', 'Use search field instead');

        $list = $gridField->getList();
        if (!$this->checkDataType($list)) {
            return null;
        }

        $columns = $gridField->getColumns();
        $filterArguments = $this->getState($gridField)->Columns->toArray();
        $currentColumn = 0;
        $canFilter = false;
        $fieldsList = new ArrayList();

        foreach ($columns as $columnField) {
            $currentColumn++;
            $metadata = $gridField->getColumnMetadata($columnField);
            $title = $metadata['title'];
            $fields = new FieldGroup();

            if ($title && $list->canFilterBy($columnField)) {
                $canFilter = true;

                $value = '';
                if (isset($filterArguments[$columnField])) {
                    $value = $filterArguments[$columnField];
                }
                $field = new TextField('filter[' . $gridField->getName() . '][' . $columnField . ']', '', $value);
                $field->addExtraClass('grid-field__sort-field');
                $field->addExtraClass('no-change-track');

                $field->setAttribute(
                    'placeholder',
                    _t('SilverStripe\\Forms\\GridField\\GridField.FilterBy', "Filter by ") . _t('SilverStripe\\Forms\\GridField\\GridField.' . $metadata['title'], $metadata['title'])
                );

                $fields->push($field);
                $fields->push(
                    GridField_FormAction::create($gridField, 'reset', false, 'reset', null)
                        ->addExtraClass('btn font-icon-cancel btn-secondary btn--no-text ss-gridfield-button-reset')
                        ->setAttribute('title', _t('SilverStripe\\Forms\\GridField\\GridField.ResetFilter', "Reset"))
                        ->setAttribute('id', 'action_reset_' . $gridField->getModelClass() . '_' . $columnField)
                );
            }

            if ($currentColumn == count($columns ?? [])) {
                $fields->push(
                    GridField_FormAction::create($gridField, 'filter', false, 'filter', null)
                        ->addExtraClass('btn font-icon-search btn--no-text btn--icon-large grid-field__filter-submit ss-gridfield-button-filter')
                        ->setAttribute('title', _t('SilverStripe\\Forms\\GridField\\GridField.Filter', 'Filter'))
                        ->setAttribute('id', 'action_filter_' . $gridField->getModelClass() . '_' . $columnField)
                );
                $fields->push(
                    GridField_FormAction::create($gridField, 'reset', false, 'reset', null)
                        ->addExtraClass('btn font-icon-cancel btn--no-text grid-field__filter-clear btn--icon-md ss-gridfield-button-close')
                        ->setAttribute('title', _t('SilverStripe\\Forms\\GridField\\GridField.ResetFilter', "Reset"))
                        ->setAttribute('id', 'action_reset_' . $gridField->getModelClass() . '_' . $columnField)
                );
                $fields->addExtraClass('grid-field__filter-buttons');
                $fields->addExtraClass('no-change-track');
            }

            $fieldsList->push($fields);
        }

        return $canFilter ? $fieldsList : null;
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

        if ($this->useLegacyFilterHeader) {
            $fieldsList = $this->getLegacyFilterHeader($gridField);
            $forTemplate->Fields = $fieldsList;
            $filterTemplates = SSViewer::get_templates_by_class($this, '_Row', __CLASS__);
            return ['header' => $forTemplate->renderWith($filterTemplates)];
        } else {
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
    }
}
