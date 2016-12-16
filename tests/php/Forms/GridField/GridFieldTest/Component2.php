<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_DataManipulator;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\SS_List;

class Component2 implements GridField_DataManipulator, TestOnly
{
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        $dataList = clone $dataList;
        $dataList->merge(new ArrayList(array(7, 8, 9, 10, 11, 12)));
        return $dataList;
    }
}
