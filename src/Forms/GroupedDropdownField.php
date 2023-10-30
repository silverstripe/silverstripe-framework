<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

/**
 * Grouped dropdown, using optgroup tags.
 *
 * $source parameter (from DropdownField) must be a two dimensional array.
 * The first level of the array is used for the optgroup, and the second
 * level are the <options> for each group.
 *
 * Returns a select tag containing all the appropriate option tags, with
 * optgroup tags around the option tags as required.
 *
 * <b>Usage</b>
 *
 * <code>
 * new GroupedDropdownField(
 *    $name = "dropdown",
 *    $title = "Simple Grouped Dropdown",
 *    $source = array(
 *       "numbers" => array(
 *              "1" => "1",
 *              "2" => "2",
 *              "3" => "3",
 *              "4" => "4"
 *          ),
 *       "letters" => array(
 *              "1" => "A",
 *              "2" => "B",
 *              "3" => "C",
 *              "4" => "D",
 *              "5" => "E",
 *              "6" => "F"
 *          )
 *    )
 * )
 * </code>
 *
 * <b>Disabling individual items</b>
 *
 * Unlike the source, disabled items are specified in the same way as
 * normal DropdownFields, using a single value list. Don't pass in grouped
 * values here.
 *
 * <code>
 * // Disables first and third option in each group
 * $groupedDrDownField->setDisabledItems(array("1", "3"))
 * </code>
 */
class GroupedDropdownField extends DropdownField
{

    protected $schemaDataType = 'GroupedDropdownField';

    /**
     * Build a potentially nested fieldgroup
     *
     * @param mixed $valueOrGroup Value of item, or title of group
     * @param string|array $titleOrOptions Title of item, or options in grouip
     * @return ArrayData Data for this item
     */
    protected function getFieldOption($valueOrGroup, $titleOrOptions)
    {
        // Return flat option
        if (!is_array($titleOrOptions)) {
            return parent::getFieldOption($valueOrGroup, $titleOrOptions);
        }

        // Build children from options list
        $options = new ArrayList();
        foreach ($titleOrOptions as $childValue => $childTitle) {
            $options->push($this->getFieldOption($childValue, $childTitle));
        }

        return new ArrayData([
            'Title' => $valueOrGroup,
            'Options' => $options
        ]);
    }

    public function Type()
    {
        return 'groupeddropdown dropdown';
    }

    public function getSourceValues()
    {
        // Flatten values
        $values = [];
        $source = $this->getSource();
        array_walk_recursive(
            $source,
            // Function to extract value from array key
            function ($title, $value) use (&$values) {
                $values[] = $value;
            }
        );
        return $values;
    }

    /**
     * @return SingleLookupField
     */
    public function performReadonlyTransformation()
    {
        $field = parent::performReadonlyTransformation();
        $field->setSource(ArrayLib::flatten($this->getSource()));
        return $field;
    }
}
