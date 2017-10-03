<?php
namespace SilverStripe\Forms\GridField;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Convert;

class GridFieldVersionedState implements GridField_ColumnProvider
{
    protected $column = null;

    protected $versionedLabelFields = ['Name', 'Title'];

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
        $model = $gridField->getModelClass();
        $isModelVersioned = $model::has_extension(Versioned::class);

        if(!$isModelVersioned) {
            return;
        }

        $matchedVersionedFields = array_intersect(
            $columns,
            $this->versionedLabelFields
        );

        if (count($matchedVersionedFields) > 0) {
            $this->column = array_values($matchedVersionedFields)[0];
        }
        // Use first column
        else if ($columns) {
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
        return [$this->column];
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
        $flags = $record->getStatusFlags();
        foreach ($flags as $class => $data) {
            if (is_string($data)) {
                $data = array('text' => $data);
            }
            $flagContent .= sprintf(
                "<span class=\"ss-gridfield-badge badge %s\"%s>%s</span>",
                'status-' . Convert::raw2xml($class),
                (isset($data['title'])) ? sprintf(' title=\\"%s\\"', Convert::raw2xml($data['title'])) : '',
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
        return [ 'data-contains-version-state' => true ];
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
}
