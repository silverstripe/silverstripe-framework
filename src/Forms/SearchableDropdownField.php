<?php

namespace SilverStripe\Forms;

use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataList;

class SearchableDropdownField extends DropdownField
{
    use SearchableDropdownTrait;

    // This needs to be defined on the class, not the trait, otherwise there is a PHP error
    protected $schemaComponent = 'SearchableDropdownField';

    public function __construct(
        string $name,
        ?string $title = null,
        ?DataList $source = null,
        mixed $value = null,
        string $labelField = 'Title'
    ) {
        parent::__construct($name, $title, $source, $value);
        $this->setLabelField($labelField);
        $this->addExtraClass('ss-searchable-dropdown-field');
        $this->setHasEmptyDefault(true);
    }

    /**
     * @param string $string
     * @return $this
     *
     * @deprecated 5.2.0 Use setPlaceholder() instead
     */
    public function setEmptyString($string)
    {
        Deprecation::notice('5.2.0', 'Use setPlaceholder() instead');
        return parent::setEmptyString($string);
    }
}
