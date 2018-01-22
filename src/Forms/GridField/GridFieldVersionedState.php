<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Convert;

/**
 * @todo Move to siverstripe/versioned module
 */
class GridFieldVersionedState implements GridField_ColumnProvider
{
    /**
     * Column name for versioned state
     *
     * @var string
     */
    protected $column = null;

    /**
     * Fields/columns to display version states. We can specifies more than one
     * field but states only show in the first column found.
     */
    protected $versionedLabelFields = ['Title'];

    public function __construct($versionedLabelFields = null)
    {
        if ($versionedLabelFields) {
            $this->versionedLabelFields = $versionedLabelFields;
        }
    }

    /**
     * Modify the list of columns displayed in the table.
     *
     * @see {@link GridFieldDataColumns->getDisplayFields()}
     * @see {@link GridFieldDataColumns}.
     *
     * @param GridField $gridField
     * @param array $columns List reference of all column names.
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!class_exists(Versioned::class)) {
            return;
        }

        $model = $gridField->getModelClass();
        $isModelVersioned = $model::has_extension(Versioned::class);
        if (!$isModelVersioned) {
            return;
        }

        $matchedVersionedFields = array_intersect(
            $columns,
            $this->versionedLabelFields
        );

        if (count($matchedVersionedFields) > 0) {
            $this->column = array_values($matchedVersionedFields)[0];
        } elseif ($columns) {
            // Use first column
            $this->column = $columns[0];
        }
    }

    /**
     * Names of all columns which are affected by this component.
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return $this->column ? [$this->column] : [];
    }

    /**
     * HTML for the column, content of the <td> element.
     *
     * @param  GridField $gridField
     * @param  DataObject $record - Record displayed in this row
     * @param  string $columnName
     * @return string - HTML for the column. Return NULL to skip.
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        $flagContent = '';
        $flags = $this->getStatusFlags($record);
        foreach ($flags as $class => $data) {
            if (is_string($data)) {
                $data = array('text' => $data);
            }
            $flagContent .= sprintf(
                " <span class=\"ss-gridfield-badge badge %s\"%s>%s</span>",
                'status-' . Convert::raw2xml($class),
                (isset($data['title'])) ? sprintf(' title="%s"', Convert::raw2xml($data['title'])) : '',
                Convert::raw2xml($data['text'])
            );
        }
        return $flagContent;
    }

    /**
     * Attributes for the element containing the content returned by {@link getColumnContent()}.
     *
     * @param  GridField $gridField
     * @param  DataObject $record displayed in this row
     * @param  string $columnName
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return [];
    }

    /**
     * Additional metadata about the column which can be used by other components,
     * e.g. to set a title for a search column header.
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array - Map of arbitrary metadata identifiers to their values.
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        return [];
    }


    /**
     * A flag provides the user with additional data about the current item
     * status, for example a "removed from draft" status. Each item can have
     * more than one status flag. Returns a map of a unique key to a
     * (localized) title for the flag. The unique key can be reused as a CSS
     * class.
     *
     * Example (simple):
     *
     * ```php
     *   "deletedonlive" => "Deleted"
     * ```
     *
     * Example (with optional title attribute):
     *
     * ```php
     *   "deletedonlive" => array(
     *      'text' => "Deleted",
     *      'title' => 'This page has been deleted'
     *   )
     * ```
     *
     * @param Versioned|DataObject $record - the record to check status for
     * @return array
     */
    protected function getStatusFlags($record)
    {
        if (!$record->hasExtension(Versioned::class)) {
            return [];
        }

        $flags = [];
        if ($record->isOnLiveOnly()) {
            $flags['removedfromdraft'] = array(
                'text' => _t(__CLASS__ . '.ONLIVEONLYSHORT', 'On live only'),
                'title' => _t(__CLASS__ . '.ONLIVEONLYSHORTHELP', 'Item is published, but has been deleted from draft'),
            );
        } elseif ($record->isArchived()) {
            $flags['archived'] = array(
                'text' => _t(__CLASS__ . '.ARCHIVEDPAGESHORT', 'Archived'),
                'title' => _t(__CLASS__ . '.ARCHIVEDPAGEHELP', 'Item is removed from draft and live'),
            );
        } elseif ($record->isOnDraftOnly()) {
            $flags['addedtodraft'] = array(
                'text' => _t(__CLASS__ . '.ADDEDTODRAFTSHORT', 'Draft'),
                'title' => _t(__CLASS__ . '.ADDEDTODRAFTHELP', "Item has not been published yet")
            );
        } elseif ($record->isModifiedOnDraft()) {
            $flags['modified'] = array(
                'text' => _t(__CLASS__ . '.MODIFIEDONDRAFTSHORT', 'Modified'),
                'title' => _t(__CLASS__ . '.MODIFIEDONDRAFTHELP', 'Item has unpublished changes'),
            );
        }

        return $flags;
    }
}
