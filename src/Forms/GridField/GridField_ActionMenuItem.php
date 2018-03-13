<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\ORM\DataObject;

/**
 * @internal
 *
 * GridField action menu interface, this should only go in a {@see GridField_ActionMenu}
 */
interface GridField_ActionMenuItem extends GridFieldComponent
{
    /**
     * For submitting the gridfield
     */
    const SUBMIT = 'submit';

    /**
     * For just following the url link
     */
    const LINK = 'link';

    /**
     * Gets the title for this menu item
     *
     * @see {@link GridFieldActionMenu->getColumnContent()}
     *
     * @param GridField $gridField
     * @param DataObject $record
     *
     * @return string $title
     */
    public function getTitle($gridField, $record);

    /**
     * Gets the action url for this menu item
     *
     * @param $gridField
     * @param $record
     *
     * @return string $url
     */
    public function getUrl($gridField, $record);

    /**
     * Gets the type this menu item will behave as
     *
     * @param $gridField
     * @param $record
     *
     * @return string $type
     */
    public function getType($gridField, $record);

    /**
     * Gets any extra data that could go in to the schema that the menu generates
     *
     * @param $gridField
     * @param $record
     * @return array $data
     */
    public function getExtraData($gridField, $record);

    /**
     * Gets the group this menu item will belong to
     *
     * @param $gridField
     * @param $record
     *
     * @return string $group
     */
    public function getGroup($gridField, $record);
}
