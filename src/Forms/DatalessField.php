<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Abstract class for all fields without data.
 * Labels, headings and the like should extend from this.
 */
class DatalessField extends FormField
{

    /**
     * @var bool $allowHTML
     */
    protected $allowHTML;

    /**
     * function that returns whether this field contains data.
     * Always returns false.
     */
    public function hasData(): bool
    {
        return false;
    }

    public function getAttributes(): array
    {
        return array_merge(
            parent::getAttributes(),
            [
                'type' => 'hidden',
            ]
        );
    }

    /**
     * Returns the field's representation in the form.
     * For dataless fields, this defaults to $Field.
     *
     * @param array $properties
     * @return DBHTMLText
     */
    public function FieldHolder(array $properties = []): SilverStripe\ORM\FieldType\DBHTMLText|string
    {
        return $this->Field($properties);
    }

    /**
     * Returns the field's representation in a field group.
     * For dataless fields, this defaults to $Field.
     *
     * @param array $properties
     * @return DBHTMLText
     */
    public function SmallFieldHolder(array $properties = []): string
    {
        return $this->Field($properties);
    }

    /**
     * Returns a readonly version of this field
     */
    public function performReadonlyTransformation(): SilverStripe\Forms\HeaderField
    {
        $clone = clone $this;
        $clone->setReadonly(true);
        return $clone;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function setAllowHTML(bool $bool): SilverStripe\Forms\LiteralField
    {
        $this->allowHTML = $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowHTML()
    {
        return $this->allowHTML;
    }

    public function Type(): string
    {
        return 'readonly';
    }
}
