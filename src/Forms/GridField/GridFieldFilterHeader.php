<?php

namespace SilverStripe\Forms\GridField;

use LogicException;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
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
class GridFieldFilterHeader implements GridField_URLHandler, GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider
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
     * @var bool
     */
    public $useLegacyFilterHeader = false;

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
     * @param bool $useLegacy
     */
    public function __construct($useLegacy = false)
    {
        $this->useLegacyFilterHeader = $useLegacy;
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

    /**
     * Check that this dataList is of the right data type.
     * Returns false if it's a bad data type, and if appropriate, throws an exception.
     *
     * @param SS_List $dataList
     * @return bool
     */
    protected function checkDataType(SS_List $dataList)
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
     * @return array
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return;
        }

        $state = $gridField->State->GridFieldFilterHeader;
        if ($actionName === 'filter') {
            if (isset($data['filter'][$gridField->getName()])) {
                foreach ($data['filter'][$gridField->getName()] as $key => $filter) {
                    $state->Columns->$key = $filter;
                }
            }
        } elseif ($actionName === 'reset') {
            $state->Columns = null;
        }
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
        /** @var GridState_Data $columns */
        $columns = $gridField->State->GridFieldFilterHeader->Columns(null);
        if (empty($columns)) {
            return $dataList;
        }

        $filterArguments = $columns->toArray();
        $dataListClone = clone($dataList);
        foreach ($filterArguments as $columnName => $value) {
            if ($dataList->canFilterBy($columnName) && $value) {
                $dataListClone = $dataListClone->filter($columnName . ':PartialMatch', $value);
            }
        }
        return $dataListClone;
    }

    /**
     * Returns whether this {@link GridField} has any columns to filter on at all
     *
     * @param GridField $gridField
     * @return boolean
     */
    public function canFilterAnyColumns(GridField $gridField)
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
        $context = singleton($gridField->getModelClass())->getDefaultSearchContext();

        return $context;
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

        $context = $this->getSearchContext($gridField);
        $params = $gridField->getRequest()->postVar('filter') ?: [];
        if (array_key_exists($gridField->getName(), $params)) {
            $params = $params[$gridField->getName()];
        }
        $context->setSearchParams($params);

        $searchField = $context->getSearchFields()->first();
        $searchField = $searchField && property_exists($searchField, 'name') ? $searchField->name : null;

        $name = $gridField->Title ?: singleton($gridField->getModelClass())->i18n_plural_name();

        $schema = [
            'formSchemaUrl' => $schemaUrl,
            'name' => $searchField,
            'placeholder' => sprintf('Search "%s"', $name),
            'filters' => $context->getSearchParams() ?: null,
            'gridfield' => $gridField->getName(),
            'searchAction' => GridField_FormAction::create($gridField, 'filter', false, 'filter', null)->getAttribute('name'),
            'clearAction' => GridField_FormAction::create($gridField, 'reset', false, 'reset', null)->getAttribute('name')
        ];

        return Convert::raw2json($schema);
    }

    /**
     * Returns the search form schema for the component
     *
     * @param GridField $gridfield
     * @return HTTPResponse
     */
    public function getSearchFormSchema(GridField $gridField)
    {
        $searchContext = $this->getSearchContext($gridField);
        $searchFields = $searchContext->getSearchFields();

        // If there are no filterable fields, return a 400 response
        if ($searchFields->count() === 0) {
            return new HTTPResponse(_t(__CLASS__ . '.SearchFormFaliure', 'No search form could be generated'), 400);
        }

        $columns = $gridField->getColumns();

        // Update field titles to match column titles
        foreach ($columns as $columnField) {
            $metadata = $gridField->getColumnMetadata($columnField);
            // Get the field name, without any modifications
            $name = explode('.', $columnField);
            $title = $metadata['title'];
            $field = $searchFields->fieldByName($name[0]);

            if ($field) {
                $field->setTitle($title);
            }
        }

        foreach ($searchFields->getIterator() as $field) {
            $field->addExtraClass('stacked');
        }

        $form = new Form(
            $gridField,
            "SearchForm",
            $searchFields,
            new FieldList()
        );
        $form->setFormMethod('get');
        $form->setFormAction($gridField->Link());
        $form->addExtraClass('cms-search-form form--no-dividers');
        $form->disableSecurityToken(); // This form is not tied to session so we disable this
        $form->loadDataFrom($gridField->getRequest()->getVars());

        $parts = $gridField->getRequest()->getHeader(LeftAndMain::SCHEMA_HEADER);
        $schemaID = $gridField->getRequest()->getURL();
        $data = FormSchema::singleton()
            ->getMultipartSchema($parts, $schemaID, $form);

        $response = new HTTPResponse(Convert::raw2json($data));
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Generate fields for the legacy filter header row
     *
     * @param GridField $gridfield
     * @return ArrayList|null
     */
    public function getLegacyFilterHeader(GridField $gridField)
    {
        $list = $gridField->getList();
        if (!$this->checkDataType($list)) {
            return null;
        }

        $columns = $gridField->getColumns();
        $filterArguments = $gridField->State->GridFieldFilterHeader->Columns->toArray();
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

            if ($currentColumn == count($columns)) {
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
                'buttons-before-right' => '<button type="button" title="' ._t('SilverStripe\\Forms\\GridField\\GridField.OpenFilter', "Open search and filter") . '" name="showFilter" class="btn btn-secondary font-icon-search btn--no-text btn--icon-large grid-field__filter-open"></button>'
            ];
        }
    }
}
