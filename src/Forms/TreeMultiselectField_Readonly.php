<?php

namespace SilverStripe\Forms;

class TreeMultiselectField_Readonly extends TreeMultiselectField
{

    protected $readonly = true;

    public function Field($properties = [])
    {
        // Build list of titles
        $titleField = $this->getTitleField();
        $items = $this->getItems();
        $titleArray = [];
        foreach ($items as $item) {
            $titleArray[] = $item->$titleField;
        }
        $titleList = implode(", ", $titleArray);

        // Build list of values
        $itemIDs = [];
        foreach ($items as $item) {
            $itemIDs[] = $item->ID;
        }
        $itemIDsList = implode(",", $itemIDs);

        // Readonly field for display
        $field = new ReadonlyField($this->name . '_ReadonlyValue', $this->title);
        $field->setValue($titleList);
        $field->setForm($this->form);

        // Store values to hidden field
        $valueField = new HiddenField($this->name);
        $valueField->setValue($itemIDsList);
        $valueField->setForm($this->form);

        return $field->Field() . $valueField->Field();
    }
}
