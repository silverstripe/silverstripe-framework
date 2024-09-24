<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Model\ModelFields\StringModelField;
use SilverStripe\ORM\FieldType\DBFieldTrait;

/**
 * An abstract base class for the string field types (i.e. Varchar and Text)
 */
abstract class DBString extends StringModelField implements DBField
{
    use DBFieldTrait;

    /**
     * Set the default value for "nullify empty"
     *
     * {@inheritDoc}
     */
    public function __construct($name = null, $options = [])
    {
        $this->options['nullifyEmpty'] = true;
        parent::__construct($name, $options);
    }

    /**
     * Update the optional parameters for this field.
     *
     * The options allowed are:
     *   <ul><li>"nullifyEmpty"
     *       This is a boolean flag.
     *       True (the default) means that empty strings are automatically converted to nulls to be stored in
     *       the database. Set it to false to ensure that nulls and empty strings are kept intact in the database.
     *   </li></ul>
     */
    public function setOptions(array $options = []): static
    {
        parent::setOptions($options);

        if (array_key_exists('nullifyEmpty', $options ?? [])) {
            $this->options['nullifyEmpty'] = (bool) $options['nullifyEmpty'];
        }
        if (array_key_exists('default', $options ?? [])) {
            $this->setDefaultValue($options['default']);
        }

        return $this;
    }

    /**
     * Set whether this field stores empty strings rather than converting
     * them to null.
     *
     * @param $value boolean True if empty strings are to be converted to null
     * @return $this
     */
    public function setNullifyEmpty(bool $value): static
    {
        $this->options['nullifyEmpty'] = $value;
        return $this;
    }

    /**
     * Get whether this field stores empty strings rather than converting
     * them to null
     *
     * @return boolean True if empty strings are to be converted to null
     */
    public function getNullifyEmpty(): bool
    {
        return !empty($this->options['nullifyEmpty']);
    }

    public function exists(): bool
    {
        $value = $this->RAW();
        // All truthy values and non-empty strings exist ('0' but not (int)0)
        return $value || (is_string($value) && strlen($value ?? ''));
    }

    public function prepValueForDB(mixed $value): array|string|null
    {
        // Cast non-empty value
        if (is_scalar($value) && strlen($value ?? '')) {
            return (string)$value;
        }

        // Return "empty" value
        if ($this->getNullifyEmpty() || $value === null) {
            return null;
        }
        return '';
    }
}
