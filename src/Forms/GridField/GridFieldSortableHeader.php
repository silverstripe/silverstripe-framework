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
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;

/**
 * GridFieldSortableHeader adds column headers to a {@link GridField} that can
 * also sort the columns.
 *
 * @see GridField
 */
class GridFieldSortableHeader extends AbstractGridFieldComponent implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider, GridField_StateProvider
{

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     *
     * @var bool
     * @deprecated 5.2.0 Will be removed without equivalent functionality
     */
    protected $throwExceptionOnBadDataType = true;

    /**
     * @var array
     */
    public $fieldSorting = [];

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
     * @deprecated 5.2.0 Will be removed without equivalent functionality
     */
    public function setThrowExceptionOnBadDataType($throwExceptionOnBadDataType)
    {
        Deprecation::notice('5.2.0', 'Will be removed without equivalent functionality');
        $this->throwExceptionOnBadDataType = $throwExceptionOnBadDataType;
        return $this;
    }

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     *
     * @return bool
     * @deprecated 5.2.0 Will be removed without equivalent functionality
     */
    public function getThrowExceptionOnBadDataType()
    {
        Deprecation::notice('5.2.0', 'Will be removed without equivalent functionality');
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
            // This will be changed to always throw an exception in a future major release.
            if ($this->throwExceptionOnBadDataType) {
                throw new LogicException(
                    static::class . " expects an SS_Sortable list to be passed to the GridField."
                );
            }
            return false;
        }
    }

    /**
     * Specify sorting with fieldname as the key, and actual fieldname to sort as value.
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
        $forTemplate = new ArrayData([]);
        $forTemplate->Fields = new ArrayList;

        $state = $this->getState($gridField);
        $columns = $gridField->getColumns();
        $currentColumn = 0;

        $schema = DataObject::getSchema();
        foreach ($columns as $columnField) {
            $currentColumn++;
            $metadata = $gridField->getColumnMetadata($columnField);
            $fieldName = str_replace('.', '-', $columnField ?? '');
            $title = $metadata['title'];

            if (isset($this->fieldSorting[$columnField]) && $this->fieldSorting[$columnField]) {
                $columnField = $this->fieldSorting[$columnField];
            }

            $allowSort = ($title && $list->canSortBy($columnField));

            if (!$allowSort && strpos($columnField ?? '', '.') !== false) {
                // we have a relation column with dot notation
                // @see DataObject::relField for approximation
                $parts = explode('.', $columnField ?? '');
                $tmpItem = singleton($list->dataClass());
                for ($idx = 0; $idx < sizeof($parts ?? []); $idx++) {
                    $methodName = $parts[$idx];
                    if ($tmpItem instanceof SS_List) {
                        // It's impossible to sort on a HasManyList/ManyManyList
                        break;
                    } elseif ($tmpItem && ClassInfo::hasMethod($tmpItem, $methodName)) {
                        // The part is a relation name, so get the object/list from it
                        $tmpItem = $tmpItem->$methodName();
                    } elseif ($tmpItem instanceof DataObject
                        && $schema->fieldSpec($tmpItem, $methodName, DataObjectSchema::DB_ONLY)
                    ) {
                        // Else, if we've found a database field at the end of the chain, we can sort on it.
                        // If a method is applied further to this field (E.g. 'Cost.Currency') then don't try to sort.
                        $allowSort = $idx === sizeof($parts ?? []) - 1;
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
                    ['SortColumn' => $columnField]
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
                $field = new LiteralField($fieldName, '<span class="non-sortable">' . $title . '</span>');
            }
            $forTemplate->Fields->push($field);
        }

        $template = SSViewer::get_templates_by_class($this, '_Row', __CLASS__);
        return [
            'header' => $forTemplate->renderWith($template),
        ];
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

        return ['sortasc', 'sortdesc'];
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return;
        }

        $state = $this->getState($gridField);
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
     * @param SS_List&Sortable $dataList
     * @return SS_List
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        if (!$this->checkDataType($dataList)) {
            return $dataList;
        }

        $state = $this->getState($gridField);
        if ($state->SortColumn == "") {
            return $dataList;
        }

        // Prevent SQL Injection by validating that SortColumn exists
        $columns = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $fields = $columns->getDisplayFields($gridField);
        if (!array_key_exists($state->SortColumn, $fields) &&
            !in_array($state->SortColumn, $this->getFieldSorting())
        ) {
            throw new LogicException('Invalid SortColumn: ' . $state->SortColumn);
        }

        return $dataList->sort($state->SortColumn, $state->SortDirection('asc'));
    }

    /**
     * Extract state data from the parent gridfield
     * @param GridField $gridField
     * @return GridState_Data
     */
    private function getState(GridField $gridField): GridState_Data
    {
        return $gridField->State->GridFieldSortableHeader;
    }

    public function initDefaultState(GridState_Data $data): void
    {
        $data->GridFieldSortableHeader->initDefaults(['SortColumn' => null, 'SortDirection' => 'asc']);
    }
}
