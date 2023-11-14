<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\View\ViewableData;

/**
 * Allows GridField_ActionMenuItem to act as a link
 */
interface GridField_ActionMenuLink extends GridField_ActionMenuItem
{
    /**
     * Gets the action url for this menu item
     *
     * @see {@link GridField_ActionMenu->getColumnContent()}
     *
     * @param GridField $gridField
     * @param ViewableData $record
     *
     * @return string $url
     */
    public function getUrl($gridField, $record, $columnName);
}
