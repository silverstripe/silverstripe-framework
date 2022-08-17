<?php

namespace SilverStripe\Forms;

/**
 * Hidden field.
 */
class HiddenField extends FormField
{

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_HIDDEN;

    protected $inputType = 'hidden';

    /**
     * @param array $properties
     * @return string
     */
    public function FieldHolder($properties = []): SilverStripe\ORM\FieldType\DBHTMLText
    {
        return $this->Field($properties);
    }

    /**
     * @return static
     */
    public function performReadonlyTransformation(): SilverStripe\Forms\HiddenField
    {
        $clone = clone $this;

        $clone->setReadonly(true);

        return $clone;
    }

    /**
     * @return bool
     */
    public function IsHidden()
    {
        return true;
    }

    function SmallFieldHolder($properties = [])
    {
        return $this->FieldHolder($properties);
    }
}
