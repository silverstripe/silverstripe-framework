<?php

namespace SilverStripe\Forms;

class TreeDropdownField_Readonly extends TreeDropdownField
{
    protected $readonly = true;

    public function Field($properties = [])
    {
        $fieldName = $this->getTitleField();
        if ($this->value) {
            $keyObj = $this->objectForKey($this->value);
            $title = $keyObj ? $keyObj->$fieldName : '';
            $source = [$this->value => $title];
        } else {
            // Using null as an array offset is deprecated in PHP 8.5. An empty
            // source renders the same "(none)" placeholder LookupField produced
            // for the previous [null => null] source.
            $source = [];
        }
        $field = LookupField::create($this->name, $this->title, $source);
        $field->setValue($this->value);
        $field->setForm($this->form);
        return $field->Field();
    }
}
