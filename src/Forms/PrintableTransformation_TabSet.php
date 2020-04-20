<?php

namespace SilverStripe\Forms;

/**
 * Class representing printable tabsets
 */
class PrintableTransformation_TabSet extends TabSet
{
    /**
     * @param array $tabs
     */
    public function __construct($tabs)
    {
        $this->children = $tabs;
        CompositeField::__construct($tabs);
    }

    public function FieldHolder($properties = [])
    {
        // This gives us support for sub-tabs.
        $tag = $this->getTabSet() ? 'h2>' : 'h1>';
        $retVal = '';
        foreach ($this->getChildren() as $tab) {
            $retVal .= "<$tag" . $tab->Title() . "</$tag\n";
            $retVal .= $tab->FieldHolder();
        }
        return $retVal;
    }
}
