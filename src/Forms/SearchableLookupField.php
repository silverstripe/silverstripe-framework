<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

/**
 * Read-only complement of {@link SearchableDropdownField} and {@link SearchableMultiDropdownField}.
 *
 * Shows the "human value" of the SearchableDropdownField for the currently selected
 * values.
 */
class SearchableLookupField extends LookupField
{
    private ?DataList $sourceList = null;

    /**
     * To retain compatibility with ancestor getSource() this returns an array of only the selected values
     */
    public function getSource(): array
    {
        $values = $this->getValueArray();
        if (empty($values) || $this->sourceList === null) {
            $selectedValuesList = ArrayList::create();
        } else {
            $selectedValuesList = $this->sourceList->filterAny(['ID' => $values]);
        }
        return $this->getListMap($selectedValuesList);
    }

    /**
     * @param mixed $source
     */
    public function setSource($source): static
    {
        // Setting to $this->sourceList instead of $this->source because SelectField.source
        // docblock type is array|ArrayAccess i.e. does not allow DataList
        if ($source instanceof DataList) {
            $this->sourceList = $source;
        } else {
            $this->sourceList = null;
        }
        return $this;
    }
}
