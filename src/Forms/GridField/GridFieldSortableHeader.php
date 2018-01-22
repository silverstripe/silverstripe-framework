<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\Sortable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use LogicException;

/**
 * GridFieldSortableHeader adds column headers to a {@link GridField} that can
 * also sort the columns.
 *
 * @see GridField
 */
class GridFieldSortableHeader implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider
{

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     *
     * @var bool
     */
    protected $throwExceptionOnBadDataType = true;

    /**
     * @var array
     */
    public $fieldSorting = array();

    /**
     * Determine what happens when this component is used with a list that isn't {@link SS_Filterable}.
     *
     *  - true:  An exception is thrown
     *  - false: This component will be ignored - it won't make any changes to the GridField.
     *
     * By default, this is set to true so that it's clearer what's happening, but the predefined
     * {@link GridFieldConfig} subclasses set this to false for flexibility.
     *
     * @param bool $throwExceptionOnBadDataType
     * @return $this
     */
    public function setThrowExceptionOnBadDataType($throwExceptionOnBadDataType)
    {
        $this->throwExceptionOnBadDataType = $throwExceptionOnBadDataType;
        return $this;
    }

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     *
     * @return bool
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
        if ($dataList instanceof Sortable) {
            return true;
        } else {
            if ($this->throwExceptionOnBadDataType) {
                throw new LogicException(
                    static::class . " expects an SS_Sortable list to be passed to the GridField."
                );
            }
            return false;
        }
    }

    /**
     * Specify sortings with fieldname as the key, and actual fieldname to sort as value.
     * Example: array("MyCustomTitle"=>"Title", "MyCustomBooleanField" => "ActualBooleanField")
     *
     * @param array $sorting
     * @return $this
     */
    public function setFieldSorting($sorting)
    {
        $this->fieldSorting = $sorting;
        return $this;
    }

    /**
     * @return array
     */
    public function getFieldSorting()
    {
        return $this->fieldSorting;
    }

    /**
     * Returns the header row providing titles with sort buttons
     *
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $list = $gridField->getList();
        if (!$this->checkDataType($list)) {
            return null;
        }
        /** @var Sortable $list */
        $forTemplate = new ArrayData(array());
        $forTemplate->Fields = new ArrayList;

        $state = $gridField->State->GridFieldSortableHeader;
        $columns = $gridField->getColumns();
        $currentColumn = 0;

        $schema = DataObject::getSchema();
        foreach ($columns as $columnField) {
            $currentColumn++;
            $metadata = $gridField->getColumnMetadata($columnField);
            $fieldName = str_replace('.', '-', $columnField);
            $title = $metadata['title'];

            if (isset($this->fieldSorting[$columnField]) && $this->fieldSorting[$columnField]) {
                $columnField = $this->fieldSorting[$columnField];
            }

            $allowSort = ($title && $list->canSortBy($columnField));

            if (!$allowSort && strpos($columnField, '.') !== false) {
                // we have a relation column with dot notation
                // @see DataObject::relField for approximation
                $parts = explode('.', $columnField);
                $tmpItem = singleton($list->dataClass());
                for ($idx = 0; $idx < sizeof($parts); $idx++) {
                    $methodName = $parts[$idx];
                    if ($tmpItem instanceof SS_List) {
                        // It's impossible to sort on a HasManyList/ManyManyList
                        break;
                    } elseif (method_exists($tmpItem, 'hasMethod') && $tmpItem->hasMethod($methodName)) {
                        // The part is a relation name, so get the object/list from it
                        $tmpItem = $tmpItem->$methodName();
                    } elseif ($tmpItem instanceof DataObject
                        && $schema->fieldSpec($tmpItem, $methodName, DataObjectSchema::DB_ONLY)
                    ) {
                        // Else, if we've found a database field at the end of the chain, we can sort on it.
                        // If a method is applied further to this field (E.g. 'Cost.Currency') then don't try to sort.
                        $allowSort = $idx === sizeof($parts) - 1;
                        break;
                    } else {
                        // If neither method nor field, then unable to sort
                        break;
                    }
                }
            }

            if ($allowSort) {
                $dir = 'asc';
                if ($state->SortColumn(null) == $columnField && $state->SortDirection('asc') == 'asc') {
                    $dir = 'desc';
                }

                $field = GridField_FormAction::create(
                    $gridField,
                    'SetOrder' . $fieldName,
                    $title,
                    "sort$dir",
                    array('SortColumn' => $columnField)
                )->addExtraClass('grid-field__sort');

                if ($state->SortColumn(null) == $columnField) {
                    $field->addExtraClass('ss-gridfield-sorted');

                    if ($state->SortDirection('asc') == 'asc') {
                        $field->addExtraClass('ss-gridfield-sorted-asc');
                    } else {
                        $field->addExtraClass('ss-gridfield-sorted-desc');
                    }
                }
            } else {
                if ($currentColumn == count($columns)) {
                    $filter = $gridField->getConfig()->getComponentByType(GridFieldFilterHeader::class);

                    if ($filter && $filter->canFilterAnyColumns($gridField)) {
                        $field = new LiteralField(
                            $fieldName,
                            '<button type="button" name="showFilter" title="Open search and filter" class="btn btn-secondary font-icon-search btn--no-text btn--icon-large grid-field__filter-open"></button>'
                        );
                    } else {
                        $field = new LiteralField($fieldName, '<span class="non-sortable">' . $title . '</span>');
                    }
                } else {
                    $field = new LiteralField($fieldName, '<span class="non-sortable">' . $title . '</span>');
                }
            }
            $forTemplate->Fields->push($field);
        }

        $template = SSViewer::get_templates_by_class($this, '_Row', __CLASS__);
        return array(
            'header' => $forTemplate->renderWith($template),
        );
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

        return array('sortasc', 'sortdesc');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return;
        }

        $state = $gridField->State->GridFieldSortableHeader;
        switch ($actionName) {
            case 'sortasc':
                $state->SortColumn = $arguments['SortColumn'];
                $state->SortDirection = 'asc';
                break;

            case 'sortdesc':
                $state->SortColumn = $arguments['SortColumn'];
                $state->SortDirection = 'desc';
                break;
        }
    }

    /**
     * Returns the manipulated (sorted) DataList. Field names will simply add an
     * 'ORDER BY' clause, relation names will add appropriate joins to the
     * {@link DataQuery} first.
     *
     * @param GridField $gridField
     * @param SS_List $dataList
     * @return SS_List
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        if (!$this->checkDataType($dataList)) {
            return $dataList;
        }

        /** @var Sortable $dataList */
        $state = $gridField->State->GridFieldSortableHeader;
        if ($state->SortColumn == "") {
            return $dataList;
        }

        return $dataList->sort($state->SortColumn, $state->SortDirection('asc'));
    }
}
