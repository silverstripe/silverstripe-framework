<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use LogicException;

/**
 * GridFieldFilterHeader alters the {@link GridField} with some filtering
 * fields in the header of each column.
 *
 * @see GridField
 */
class GridFieldFilterHeader implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider
{

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     *
     * @var bool
     */
    protected $throwExceptionOnBadDataType = true;

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

    public function getHTMLFragments($gridField)
    {
        $list = $gridField->getList();
        if (!$this->checkDataType($list)) {
            return null;
        }

        /** @var Filterable $list */
        $forTemplate = new ArrayData(array());
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

            $forTemplate->Fields->push($fields);
        }

        if (!$canFilter) {
            return null;
        }

        $templates = SSViewer::get_templates_by_class($this, '_Row', __CLASS__);
        return array(
            'header' => $forTemplate->renderWith($templates),
        );
    }
}
