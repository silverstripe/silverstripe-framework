<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\HTML;

/**
 * Represents a number of fields which are selectable by a radio
 * button that appears at the beginning of each item.  Using CSS, you can
 * configure the field to only display its contents if the corresponding radio
 * button is selected. Each item is defined through {@link SelectionGroup_Item}.
 *
 * @example <code>
 * $items = [
 *  new SelectionGroup_Item(
 *      'one',
 *      new LiteralField('one', 'one view'),
 *      'one title'
 *  ),
 *  new SelectionGroup_Item(
 *      'two',
 *      new LiteralField('two', 'two view'),
 *      'two title'
 *  ),
 * ];
 * $field = new SelectionGroup('MyGroup', $items);
 * </code>
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 */
class SelectionGroup extends CompositeField
{

    /**
     * Create a new selection group.
     *
     * @param string $name The field name of the selection group.
     * @param array $items The list of {@link SelectionGroup_Item}
     * @param mixed $value
     */
    public function __construct($name, $items, $value = null)
    {
        if ($value !== null) {
            $this->setValue($value);
        }

        $selectionItems = [];

        foreach ($items as $key => $item) {
            if ($item instanceof SelectionGroup_Item) {
                $selectionItems[] = $item;
            } else {
                // Convert legacy format
                if (strpos($key ?? '', '//') !== false) {
                    list($key,$title) = explode('//', $key ?? '', 2);
                } else {
                    $title = null;
                }
                $selectionItems[] = new SelectionGroup_Item($key, $item, $title);
            }
        }

        parent::__construct($selectionItems);

        $this->setName($name);
    }

    public function FieldSet()
    {
        return $this->FieldList();
    }

    public function FieldList()
    {
        $items = parent::FieldList()->toArray();
        $count = 0;
        $newItems = [];

        /** @var SelectionGroup_Item $item */
        foreach ($items as $item) {
            if ($this->value == $item->getValue()) {
                $firstSelected = true;
                $checked = true;
            } else {
                $firstSelected = false;
                $checked = false;
            }

            $itemID = $this->ID() . '_' . (++$count);
            $extra = [
                "RadioButton" => DBField::create_field('HTMLFragment', HTML::createTag(
                    'input',
                    [
                        'class' => 'selector',
                        'type' => 'radio',
                        'id' => $itemID,
                        'name' => $this->name,
                        'value' => $item->getValue(),
                        'checked' => $checked,
                        'disabled' => $item->isDisabled()
                    ]
                )),
                "RadioLabel" => $item->getTitle(),
                "Selected" => $firstSelected,
            ];
            $newItems[] = $item->customise($extra);
        }

        return new ArrayList($newItems);
    }

    public function hasData()
    {
        return true;
    }

    public function FieldHolder($properties = [])
    {
        return parent::FieldHolder($properties);
    }
}
