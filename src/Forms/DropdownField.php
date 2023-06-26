<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Dropdown field, created from a select tag.
 *
 * <b>Setting a $has_one relation</b>
 *
 * Using here an example of an art gallery, with Exhibition pages,
 * each of which has a Gallery they belong to.  The Gallery class is also user-defined.
 * <code>
 *  static $has_one = array(
 *      'Gallery' => 'Gallery',
 *  );
 *
 *  public function getCMSFields() {
 *      $fields = parent::getCMSFields();
 *      $field = DropdownField::create('GalleryID', 'Gallery', Gallery::get()->map('ID', 'Title'))
 *          ->setEmptyString('(Select one)');
 *      $fields->addFieldToTab('Root.Content', $field, 'Content');
 * </code>
 *
 * As you see, you need to put "GalleryID", rather than "Gallery" here.
 *
 * <b>Populate with Array</b>
 *
 * Example model definition:
 * <code>
 * class MyObject extends DataObject {
 *   static $db = array(
 *     'Country' => "Varchar(100)"
 *   );
 * }
 * </code>
 *
 * Example instantiation:
 * <code>
 * DropdownField::create(
 *   'Country',
 *   'Country',
 *   array(
 *     'NZ' => 'New Zealand',
 *     'US' => 'United States',
 *     'GEM'=> 'Germany'
 *   )
 * );
 * </code>
 *
 * <b>Populate with Enum-Values</b>
 *
 * You can automatically create a map of possible values from an {@link Enum} database column.
 *
 * Example model definition:
 * <code>
 * class MyObject extends DataObject {
 *   static $db = array(
 *     'Country' => "Enum('New Zealand,United States,Germany','New Zealand')"
 *   );
 * }
 * </code>
 *
 * Field construction:
 * <code>
 * DropdownField::create(
 *   'Country',
 *   'Country',
 *   singleton('MyObject')->dbObject('Country')->enumValues()
 * );
 * </code>
 *
 * <b>Disabling individual items</b>
 *
 * Individual items can be disabled by feeding their array keys to setDisabledItems.
 *
 * <code>
 * $DrDownField->setDisabledItems( array( 'US', 'GEM' ) );
 * </code>
 *
 * @see CheckboxSetField for multiple selections through checkboxes instead.
 * @see ListboxField for a single <select> box (with single or multiple selections).
 * @see TreeDropdownField for a rich and customizeable UI that can visualize a tree of selectable elements
 */
class DropdownField extends SingleSelectField
{

    /**
     * Build a field option for template rendering
     *
     * @param mixed $value Value of the option
     * @param string $title Title of the option
     * @return ArrayData Field option
     */
    protected function getFieldOption($value, $title)
    {
        // Check selection
        $selected = $this->isSelectedValue($value, $this->Value());

        // Check disabled
        $disabled = false;
        if ($this->isDisabledValue($value) && $title != $this->getEmptyString()) {
            $disabled = 'disabled';
        }

        return new ArrayData([
            'Title' => (string)$title,
            'Value' => $value,
            'Selected' => $selected,
            'Disabled' => $disabled,
        ]);
    }

    /**
     * A required DropdownField must have a user selected attribute,
     * so require an empty default for a required field
     *
     * @return bool
     */
    public function getHasEmptyDefault()
    {
        return parent::getHasEmptyDefault() || $this->Required();
    }

    /**
     * Provides <option> list for the template
     * This allows for a faster rendering of dropdowns with lots of options
     *
     * @return string
     */
    private function renderOptionsHTML(): string
    {
        // Some methods only exists for single selects
        $source = $this->hasMethod('getSourceEmpty') ? $this->getSourceEmpty() : $this->getSource();
        $emptyString = $this->hasMethod('getEmptyString') ? $this->getEmptyString() : '';

        $currentValue = $this->Value();
        foreach ($source as $value => $title) {
            // It's an opt group, ignore since it has its own template
            if (is_array($title)) {
                continue;
            }
            $selected = '';
            if ($this->isSelectedValue($value, $currentValue)) {
                $selected = ' selected="selected"';
            }
            $disabled = '';
            if ($this->isDisabledValue($value) && $title != $emptyString) {
                $disabled = ' disabled="disabled"';
            }
            $item = '<option value="' . Convert::raw2xml($value) . '"' . $selected . $disabled . '>' . Convert::raw2xml($title) . '</option>';
            $options[] = $item;
        }

        return implode("\n", $options);
    }

    /**
     * @param array $properties
     * @return string
     */
    public function Field($properties = [])
    {
        $options = [];

        // Add all options
        foreach ($this->getSourceEmpty() as $value => $title) {
            $options[] = $this->getFieldOption($value, $title);
        }

        $properties = array_merge($properties, [
            'Options' => new ArrayList($options)
        ]);

        // Without this, changing the source will render the previous list due to cache
        $OptionsHTML = new DBHTMLText('Options');
        $OptionsHTML->setValue($this->renderOptionsHTML());
        $properties['OptionsHTML'] = $OptionsHTML;

        return parent::Field($properties);
    }
}
