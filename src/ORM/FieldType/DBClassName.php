<?php

namespace SilverStripe\ORM\FieldType;

/**
 * Represents a classname selector, which respects obsolete clasess.
 */
class DBClassName extends DBEnum
{
    use DBClassNameTrait;

    /**
     * Get the specifications which will be used to generate this column in the database.
     */
    public function getFieldSpec(): string|array
    {
        $spec = parent::getFieldSpec();
        $spec['parts']['character set'] = 'utf8';
        $spec['parts']['collate'] = 'utf8_general_ci';
        return $spec;
    }

    public function getDefault(): string
    {
        // Check for assigned default
        $default = parent::getDefault();
        if ($default) {
            return $default;
        }

        return $this->getDefaultClassName();
    }
}
