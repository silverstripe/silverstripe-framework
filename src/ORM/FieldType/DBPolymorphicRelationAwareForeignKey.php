<?php

namespace SilverStripe\ORM\FieldType;

use InvalidArgumentException;
use SilverStripe\ORM\DataObject;

/**
 * A special polymorphic ForeignKey class that allows a single has_one relation to map to multiple has_many relations
 */
class DBPolymorphicRelationAwareForeignKey extends DBPolymorphicForeignKey
{
    private static array $composite_db = [
        'Relation' => 'Varchar',
    ];

    /**
     * Get the value of the "Relation" this key points to
     *
     * @return string Name of the has_many relation being stored
     */
    public function getRelationValue(): string
    {
        return $this->getField('Relation');
    }

    /**
     * Set the value of the "Relation" this key points to
     *
     * @param string $value Name of the has_many relation being stored
     * @param bool $markChanged Mark this field as changed?
     */
    public function setRelationValue(string $value, bool $markChanged = true): static
    {
        $this->setField('Relation', $value, $markChanged);
        return $this;
    }
}
