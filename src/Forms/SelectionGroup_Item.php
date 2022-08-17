<?php

namespace SilverStripe\Forms;

class SelectionGroup_Item extends CompositeField
{

    /**
     * @var String
     */
    protected $value;

    /**
     * @var String
     */
    protected $title;

    /**
     * @param string $value Form field identifier
     * @param FormField|array $fields Contents of the option
     * @param string $title Title to show for the radio button option
     */
    function __construct(string $value, SilverStripe\Forms\TreeDropdownField $fields = null, string $title = null): void
    {
        $this->setValue($value);
        if ($fields && !is_array($fields)) {
            $fields = [$fields];
        }

        parent::__construct($fields);

        $this->setTitle($title ?: $value);
    }

    function getTitle(): string
    {
        return $this->title;
    }

    function setTitle(string $title): SilverStripe\Forms\SelectionGroup_Item
    {
        $this->title = $title;
        return $this;
    }

    function getValue(): string
    {
        return $this->value;
    }

    function setValue(string $Value, $data = null): SilverStripe\Forms\SelectionGroup_Item
    {
        $this->value = $Value;
        return $this;
    }
}
