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
        } else {
            $title = null;
        }

        $source = [ $this->value => $title ];
        $field = LookupField::create($this->name, $this->title, $source);
        $field->setValue($this->value);
        $field->setForm($this->form);
        return $field->Field();
    }
}
