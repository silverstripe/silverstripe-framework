<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\View\ViewableData;

/**
 * A component which is used to handle when a {@link GridField} is saved into
 * a record.
 */
interface GridField_SaveHandler extends GridFieldComponent
{

    /**
     * Called when a grid field is saved - i.e. the form is submitted.
     *
     * @param GridField $grid
     * @param DataObjectInterface&ViewableData $record
     */
    public function handleSave(GridField $grid, DataObjectInterface $record);
}
