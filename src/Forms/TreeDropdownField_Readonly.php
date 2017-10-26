<?php

namespace SilverStripe\Forms;

class TreeDropdownField_Readonly extends TreeDropdownField
{
    protected $readonly = true;

    public function Field($properties = array())
    {
        $fieldName = $this->getTitleField();
        if ($this->value) {
            $keyObj = $this->objectForKey($this->value);
            $title = $keyObj ? $keyObj->$fieldName : '';
        } else {
            $title = null;
        }

        $source = [ $this->value => $title ];
        $field = new LookupField($this->name, $this->title, $source);
        $field->setValue($this->value);
        $field->setForm($this->form);
        return $field->Field();
    }
}
