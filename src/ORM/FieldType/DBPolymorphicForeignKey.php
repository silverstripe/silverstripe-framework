<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Model\ModelData;

/**
 * A special ForeignKey class that handles relations with arbitrary class types
 */
class DBPolymorphicForeignKey extends DBComposite
{
    private static bool $index = true;

    private static array $composite_db = [
        'ID' => 'Int',
        'Class' => "DBClassName('" . DataObject::class . "', ['index' => false])"
    ];

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
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
    public function getClassValue(): ?string
    {
        return $this->getField('Class');
    }

    /**
     * Set the value of the "Class" this key points to
     *
     * @param string $value Name of a subclass of DataObject
     */
    public function setClassValue(string $value, bool $markChanged = true)
    {
        $this->setField('Class', $value, $markChanged);
    }

    /**
     * Gets the value of the "ID" this key points to
     */
    public function getIDValue(): ?int
    {
        return $this->getField('ID');
    }

    /**
     * Sets the value of the "ID" this key points to
     */
    public function setIDValue(int $value, bool $markChanged = true)
    {
        $this->setField('ID', $value, $markChanged);
    }

    public function setValue(mixed $value, null|array|ModelData $record = null, bool $markChanged = true): static
    {
        // Map dataobject value to array
        if ($value instanceof DataObject) {
            $value = [
                'ID' => $value->ID,
                'Class' => get_class($value),
            ];
        }

        return parent::setValue($value, $record, $markChanged);
    }

    public function getValue(): ?DataObject
    {
        $id = $this->getIDValue();
        $class = $this->getClassValue();
        if ($id && $class && is_subclass_of($class, DataObject::class)) {
            return DataObject::get_by_id($class, $id);
        }
        return null;
    }
}
