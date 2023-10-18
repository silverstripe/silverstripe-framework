<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\DataObject;

/**
 * A special ForeignKey class that handles relations with arbitrary class types
 */
class DBPolymorphicForeignKey extends DBComposite
{
    private static $index = true;

    private static $composite_db = [
        'ID' => 'Int',
        'Class' => "DBClassName('" . DataObject::class . "', ['index' => false])"
    ];

    public function scaffoldFormField($title = null, $params = null)
    {
        // Don't provide scaffolded form field generation - Scaffolding should be performed on
        // the has_many end, or set programmatically.
        return null;
    }

    /**
     * Get the value of the "Class" this key points to
     *
     * @return string Name of a subclass of DataObject
     */
    public function getClassValue()
    {
        return $this->getField('Class');
    }

    /**
     * Set the value of the "Class" this key points to
     *
     * @param string $value Name of a subclass of DataObject
     * @param boolean $markChanged Mark this field as changed?
     */
    public function setClassValue($value, $markChanged = true)
    {
        $this->setField('Class', $value, $markChanged);
    }

    /**
     * Gets the value of the "ID" this key points to
     *
     * @return integer
     */
    public function getIDValue()
    {
        return $this->getField('ID');
    }

    /**
     * Sets the value of the "ID" this key points to
     *
     * @param integer $value
     * @param boolean $markChanged Mark this field as changed?
     */
    public function setIDValue($value, $markChanged = true)
    {
        $this->setField('ID', $value, $markChanged);
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        // Map dataobject value to array
        if ($value instanceof DataObject) {
            $value = [
                'ID' => $value->ID,
                'Class' => get_class($value),
            ];
        }

        parent::setValue($value, $record, $markChanged);
    }

    public function getValue()
    {
        $id = $this->getIDValue();
        $class = $this->getClassValue();
        if ($id && $class && is_subclass_of($class, DataObject::class)) {
            return DataObject::get_by_id($class, $id);
        }
        return null;
    }
}
