<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;

class Component implements GridField_ColumnProvider, GridField_ActionProvider, TestOnly
{

    public function augmentColumns($gridField, &$columns)
    {
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        if ($columnName == 'Surname') {
            return 'shouldnotbestring';
        }
        return array('class' => 'css-class');
    }

    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'Surname') {
            return 'shouldnotbestring';
        } elseif ($columnName == 'FirstName') {
            return array();
        }
        return array('metadata' => 'istrue');
    }

    /**
     * @skipUpgrade
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return array('Email', 'Surname', 'FirstName');
    }

    public function getActions($gridField)
    {
        return array('jump');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        return 'handledAction is executed';
    }
}
