<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\View\ViewableData;

/**
 * GridField action menu item interface, this provides data so the action
 * will be included if there is a {@see GridField_ActionMenu}
 */
interface GridField_ActionMenuItem extends GridFieldComponent
{
    /**
     * Default group name
     */
    const DEFAULT_GROUP = 'Default';

    /**
     * Gets the title for this menu item
     *
     * @see {@link GridField_ActionMenu->getColumnContent()}
     *
     * @param GridField $gridField
     * @param ViewableData $record
     *
     * @return string $title
     */
    public function getTitle($gridField, $record, $columnName);

    /**
     * Gets any extra data that could go in to the schema that the menu generates
     *
     * @see {@link GridField_ActionMenu->getColumnContent()}
     *
     * @param GridField $gridField
     * @param ViewableData $record
     *
     * @return array $data
     */
    public function getExtraData($gridField, $record, $columnName);

    /**
     * Gets the group this menu item will belong to. A null value should indicate
     * the button should not display.
     *
     * @see {@link GridField_ActionMenu->getColumnContent()}
     *
     * @param GridField $gridField
     * @param ViewableData $record
     *
     * @return string|null $group
     */
    public function getGroup($gridField, $record, $columnName);
}
