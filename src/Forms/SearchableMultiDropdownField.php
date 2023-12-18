<?php

namespace SilverStripe\Forms;

use SilverStripe\Forms\MultiSelectField;
use SilverStripe\Forms\SearchableDropdownTrait;
use SilverStripe\ORM\DataList;

class SearchableMultiDropdownField extends MultiSelectField
{
    use SearchableDropdownTrait;

    // This needs to be defined on the class, not the trait, or else a there is a PHP error
    protected $schemaComponent = 'SearchableDropdownField';

    public function __construct(
        string $name,
        ?string $title = null,
        ?DataList $source = null,
        $value = null,
        $labelField = 'Title'
    ) {
        parent::__construct($name, $title, $source, $value);
        $this->setLabelField($labelField);
        $this->addExtraClass('ss-searchable-dropdown-field');
        $this->setIsMultiple(true);
        $this->setIsClearable(true);
    }
}
