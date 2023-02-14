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
 *
 * This manipulation is done after the canView check - this is useful for things
 * like pagination.
 */
interface GridField_PostFilterDataManipulator extends GridFieldComponent
{
    /**
     * Manipulate the {@link SS_List} as needed by this grid modifier after filtering with a canView check.
     */
    public function getManipulatedDataPostFilter(GridField $gridField, SS_List $dataList): SS_List;
}
