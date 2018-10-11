<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\ORM\SS_List;

/**
 * Can modify the data list.
 *
 * For example, a paginating component can apply a limit, or a sorting
 * component can apply a sort.
 *
 * Generally, the data manipulator will make use of to {@link GridState}
 * variables to decide how to modify the {@link SS_List}.
 */
interface GridField_DataManipulator extends GridFieldComponent
{

    /**
     * Manipulate the {@link SS_List} as needed by this grid modifier.
     *
     * @param GridField $gridField
     * @param SS_List $dataList
     * @return SS_List
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList);
}
