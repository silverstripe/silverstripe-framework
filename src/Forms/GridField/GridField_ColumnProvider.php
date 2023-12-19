<?php
namespace SilverStripe\Forms\GridField;

use SilverStripe\ORM\DataObject;

/**
 * Add a new column to the table display body, or modify existing columns.
 *
 * Used once per record/row.
 */
interface GridField_ColumnProvider extends GridFieldComponent
{

    /**
     * Modify the list of columns displayed in the table.
     *
     * @see {@link GridFieldDataColumns->getDisplayFields()}
     * @see {@link GridFieldDataColumns}.
     *
     * @param GridField $gridField
     * @param array $columns List reference of all column names.
     */
    public function augmentColumns($gridField, &$columns);

    /**
     * Names of all columns which are affected by this component.
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField);

    /**
     * HTML for the column, content of the <td> element.
     *
     * @param  GridField $gridField
     * @param  ViewableData $record - Record displayed in this row
     * @param  string $columnName
     * @return string - HTML for the column. Return NULL to skip.
     */
    public function getColumnContent($gridField, $record, $columnName);

    /**
     * Attributes for the element containing the content returned by {@link getColumnContent()}.
     *
     * @param  GridField $gridField
     * @param  ViewableData $record displayed in this row
     * @param  string $columnName
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName);

    /**
     * Additional metadata about the column which can be used by other components,
     * e.g. to set a title for a search column header.
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array - Map of arbitrary metadata identifiers to their values.
     */
    public function getColumnMetadata($gridField, $columnName);
}
