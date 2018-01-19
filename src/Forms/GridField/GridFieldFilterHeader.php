<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridState_Data;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use LogicException;

/**
 * GridFieldFilterHeader alters the {@link GridField} with some filtering
 * fields in the header of each column.
 *
 * filter header with customisable filter fields
 *
 * REQUIREMENTS:
 *
 * table header component needs to be present in the GridField (for example GridFieldSortableHeader)
 * the last column of the table needs to have a vacant header so the filter widget could be displayed there
 * for example you can't have the last column with sorting header and filter widget at the same time
 *
 * ALIAS FIELDS
 *
 * in the case that the column name of the table is different from matching field name via the summary_fields
 * a field alias needs to be specified
 *
 * configuration format:
 *
 * 'field_alias' => 'field_name'
 *
 * for example if summary fields looks like this:
 *
 * private static $summary_fields = [
 *   'getTitleSummary' => 'Summary Title',
 *   'City.Name' => 'City Name',
 *   'Expires.Nice' => 'Expires on',
 * ];
 *
 * alias fields need to be specified to match the summary fields mapping
 *
 * ->setAliasFields([
 *   'getTitleSummary' => 'Summary Title',
 *   'City.Name' => 'City Name',
 *   'Expires.Nice' => 'Expires on',
 * ])
 *
 * Field alias is always applied first so there is no need to use the original name in other configuration
 *
 * CUSTOM FIELDS
 *
 * TextField is used as a default field for filters however this may be too crude in some situations
 * Custom fields are used to override the default fields with any field type that is needed
 *
 * configuration format:
 *
 * 'field_name' => 'field_object'
 *
 * for example if we want to use Dropdown field configuration below has to be used:
 *
 * ->setCustomFields([
 *  'City' => DropdownField::create('', '', $cities),
 * ])
 *
 * Note that Title and Name of the field are left empty as they are not used (Title) or are auto-populated (Name)
 *
 * CUSTOM FILTERS
 *
 * Partial match filter is used by default, but in some situations this can't be used as other filters are required
 *
 * configuration format:
 *
 * 'field_name' => 'filter_specification' (Closure or string)
 *
 * This component comes with Partial filter, Exact filter and Relation filter to cover most common cases
 *
 * for example we may want to use Exact match filter as we are filtering by IDs
 *
 * ->setCustomFilters([
 *   'MediaType' => GridFieldFilterHeader::FILTER_EXACT,
 *   'City' => function (Filterable $list, $columnName, $value) {
 *     return $list->filter('CityID:ExactMatch', $value);
 *   },
 * ])
 *
 * custom filter can be either a string which identifies one of the filters that are available
 * or a custom Closure which is expected to filter the list
 * this is a great way to cover edge cases as the implementation of the filter is up to the developer
 *
 * OMITTED FIELDS
 *
 * filter fields are created for all columns of the table that are considered "filterable" by default
 * in some situations this behaviour in unwanted and we need an option to remove a filter field
 *
 * configuration format:
 *
 * 'field_name'
 *
 * ->setOmittedFields(['Expires'])
 *
 *
 * @package SilverStripe\Forms\GridField
 * @see GridField
 */
class GridFieldFilterHeader implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider
{
    const FILTER_PARTIAL = 'filter_partial';
    const FILTER_EXACT = 'filter_exact';
    const FILTER_RELATION = 'filter_relation';

    /**
     * Alias fields - some fields may need to use an alias, this is useful when custom function is used to created data
     * field alias is always applied first
     * for example "getSummary" or "Date.Nice"
     *
     * configuration format:
     *
     * 'field_alias' => 'field_name'
     *
     * @var array
     */
    protected $alias_fields = [];

    /**
     * Custom fields list - all custom fields are stored here
     *
     * configuration format:
     *
     * 'field_name' => 'field_object'
     *
     * @var array
     */
    protected $custom_fields = [];

    /**
     * Custom filters - custom filter callbacks can be specified per field
     *
     * configuration format:
     *
     * 'field_name' => 'filter_specification' (Closure or string)
     *
     * see 'applyPartialFilter', 'applyExactFilter' and 'applyRelationFilter' filter functions (available by default)
     * custom filter function can be specified to filter the list
     *
     * @var array
     */
    protected $custom_filters = [];

    /**
     * Omitted fields - these fields will be omitted when creating filters
     *
     * configuration format:
     *
     * 'field_name'
     *
     * @var array
     */
    protected $omitted_fields = [];

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     *
     * @var bool
     */
    protected $throwExceptionOnBadDataType = true;

    /**
     * @param string $field
     * @return bool
     */
    protected function isAliasField($field)
    {
        return array_key_exists($field, $this->alias_fields);
    }

    /**
     * @param string $field
     * @return mixed
     */
    protected function getCustomField($field)
    {
        if (array_key_exists($field, $this->custom_fields)) {
            return $this->custom_fields[$field];
        }

        return null;
    }

    /**
     * @param $field
     * @return bool
     */
    protected function hasFilter($field)
    {
        return array_key_exists($field, $this->custom_filters);
    }

    /**
     * @param GridField $gridField
     * @param string $name
     * @param string $value
     * @param bool $isCustom
     * @return FormField
     */
    protected function createField(GridField $gridField, $name, $value, $isCustom)
    {
        // custom field
        if ($isCustom) {
            /** @var $field FormField */
            $field = $this->getCustomField($name);

            $field->setName('filter[' . $gridField->getName() . '][' . $name . ']');

            return $field;
        }

        // default field
        return new TextField('filter[' . $gridField->getName() . '][' . $name . ']', '', $value);
    }

    /**
     * @param Filterable $list
     * @param $columnName
     * @param $value
     * @return Filterable
     */
    protected function applyPartialFilter(Filterable $list, $columnName, $value)
    {
        return $list->filter($columnName.':PartialMatch', $value);
    }

    /**
     * @param Filterable $list
     * @param $columnName
     * @param $value
     * @return Filterable
     */
    protected function applyExactFilter(Filterable $list, $columnName, $value)
    {
        return $list->filter($columnName.':ExactMatch', $value);
    }

    /**
     * @param DataList $list
     * @param $columnName
     * @param $value
     * @return Filterable
     */
    protected function applyRelationFilter(DataList $list, $columnName, $value)
    {
        $tableSeparator = DataObjectSchema::config()->uninherited('table_namespace_separator');

        $className = $list->dataClass();
        $tableName = DataObject::getSchema()->tableName($className);
        $relationTable = $tableName . $tableSeparator . $columnName;
        $relationClassName = singleton($className)->getRelationClass($columnName);
        $relationTableName = DataObject::getSchema()->tableName($relationClassName);

        return $list
            ->innerJoin($relationTable, $tableName . '.ID=' . $relationTable . '.' . $tableName . 'ID')
            ->filter($relationTableName . 'ID', $value);
    }

    /**
     * configuration format:
     *
     * 'field_alias' => 'field_name'
     *
     * @param array $fields
     * @return $this
     */
    public function setAliasFields(array $fields)
    {
        $this->alias_fields = $fields;

        return $this;
    }

    /**
     * configuration format:
     *
     * 'field_name' => 'field_object'
     *
     * @param array $fields
     * @return $this
     */
    public function setCustomFields(array $fields)
    {
        $this->custom_fields = $fields;

        return $this;
    }

    /**
     * configuration format:
     *
     * 'field_name' => 'filter_specification' (Closure or string)
     *
     * @param array $fields
     * @return $this
     */
    public function setCustomFilters(array $fields)
    {
        $this->custom_filters = $fields;

        return $this;
    }

    /**
     * configuration format:
     *
     * 'field_name'
     *
     * @param array $fields
     * @return $this
     */
    public function setOmittedFields(array $fields)
    {
        $this->omitted_fields = $fields;

        return $this;
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
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return [];
        }

        return array('filter', 'reset');
    }

    /**
     * @param GridField $gridField
     * @param $actionName
     * @param $arguments
     * @param $data
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
                    /** @var $customField FormField */
                    $customField = $this->getCustomField($key);

                    // custom field
                    if (!is_null($customField)) {
                        $customField->setValue($filter);
                    }

                    $state->Columns->$key = $filter;
                }
            }
        } elseif ($actionName === 'reset') {
            $state->Columns = null;

            // reset all custom fields
            foreach ($this->custom_fields as $field) {
                /** @var $field FormField */
                $field->setValue('');
            }
        }
    }

    /**
     * @param GridField $gridField
     * @param SS_List $dataList
     * @return SS_List
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

        /** @var $dataListClone DataList */
        $dataListClone = clone($dataList);
        foreach ($filterArguments as $columnName => $value) {
            $columnName = ($this->isAliasField($columnName)) ? $this->alias_fields[$columnName] : $columnName;

            if (($dataList->canFilterBy($columnName) || $this->hasFilter($columnName)) && $value) {
                if ($this->hasFilter($columnName)) {
                    // custom filter configuration is available
                    $filter = $this->custom_filters[$columnName];

                    if ($filter instanceof \Closure) {
                        // custom filter function
                        $dataListClone = $filter($dataListClone, $columnName, $value);
                    } elseif ($filter === static::FILTER_PARTIAL) {
                        // partial filter
                        $dataListClone = $this->applyPartialFilter($dataListClone, $columnName, $value);
                    } elseif ($filter === static::FILTER_EXACT) {
                        // exact filter
                        $dataListClone = $this->applyExactFilter($dataListClone, $columnName, $value);
                    } elseif ($filter === static::FILTER_RELATION) {
                        // relation filter
                        $dataListClone = $this->applyRelationFilter($dataListClone, $columnName, $value);
                    }
                } else {
                    // default filter
                    $dataListClone = $this->applyPartialFilter($dataListClone, $columnName, $value);
                }
            }
        }

        return $dataListClone;
    }

    /**
     * Returns whether this {@link GridField} has any columns to sort on at all.
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
     * @param GridField $gridField
     * @return array|null
     */
    public function getHTMLFragments($gridField)
    {
        $list = $gridField->getList();
        if (!$this->checkDataType($list)) {
            return null;
        }

        /** @var Filterable $list */
        $forTemplate = new ArrayData([]);
        $forTemplate->Fields = new ArrayList();

        $columns = $gridField->getColumns();
        $filterArguments = $gridField->State->GridFieldFilterHeader->Columns->toArray();
        $currentColumn = 0;
        $canFilter = false;

        foreach ($columns as $columnField) {
            $currentColumn++;
            $metadata = $gridField->getColumnMetadata($columnField);
            $title = $metadata['title'];
            $fields = new FieldGroup();

            $columnField = ($this->isAliasField($columnField)) ? $this->alias_fields[$columnField] : $columnField;
            $isCustomField = array_key_exists($columnField, $this->custom_fields);
            $isOmitted = in_array($columnField, $this->omitted_fields);

            if ($title && !$isOmitted && ($list->canFilterBy($columnField) || $isCustomField)) {
                $canFilter = true;

                $value = '';
                if (isset($filterArguments[$columnField])) {
                    $value = $filterArguments[$columnField];
                }
                $field = $this->createField($gridField, $columnField, $value, $isCustomField);
                $field->addExtraClass('grid-field__sort-field');
                $field->addExtraClass('no-change-track');

                // add placeholder attribute only if it's not provided already
                if (empty($field->getAttribute('placeholder'))) {
                    $field->setAttribute(
                        'placeholder',
                        _t('SilverStripe\\Forms\\GridField\\GridField.FilterBy', "Filter by ")
                        . _t('SilverStripe\\Forms\\GridField\\GridField.'.$metadata['title'], $metadata['title'])
                    );
                }

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

            $forTemplate->Fields->push($fields);
        }

        if (!$canFilter) {
            return null;
        }

        $templates = SSViewer::get_templates_by_class($this, '_Row', __CLASS__);

        return [
            'header' => $forTemplate->renderWith($templates),
        ];
    }
}
