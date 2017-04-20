<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * A button that allows a user to view readonly details of a record. This is
 * disabled by default and intended for use in readonly {@link GridField}
 * instances.
 */
class GridFieldViewButton implements GridField_ColumnProvider
{

    public function augmentColumns($field, &$cols)
    {
        if (!in_array('Actions', $cols)) {
            $cols[] = 'Actions';
        }
    }

    public function getColumnsHandled($field)
    {
        return array('Actions');
    }

    public function getColumnContent($field, $record, $col)
    {
        if (!$record->canView()) {
            return null;
        }
        $data = new ArrayData(array(
            'Link' => Controller::join_links($field->Link('item'), $record->ID, 'view')
        ));
        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return $data->renderWith($template);
    }

    public function getColumnAttributes($field, $record, $col)
    {
        return array('class' => 'grid-field__col-compact');
    }

    public function getColumnMetadata($gridField, $col)
    {
        return array('title' => null);
    }
}
