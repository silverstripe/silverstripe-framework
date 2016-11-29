<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\SS_List;

/**
 * Can modify the data list.
 *
 * For example, a paginating component can apply a limit, or a sorting
 * component can apply a sort.
 *
 * Generally, the data manipulator will make use of to {@link GridState}
 * variables to decide how to modify the {@link DataList}.
 */
interface GridField_DataManipulator extends GridFieldComponent
{

    /**
     * Manipulate the {@link DataList} as needed by this grid modifier.
     *
     * @param GridField $gridField
     * @param SS_List $dataList
     * @return DataList
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList);
}
