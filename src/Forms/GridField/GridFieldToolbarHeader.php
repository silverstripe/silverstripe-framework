<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\View\SSViewer;

/**
 * Adding this class to a {@link GridFieldConfig} of a {@link GridField} adds
 * a header title to that field.
 *
 * The header serves to display the name of the data the GridField is showing.
 */
class GridFieldToolbarHeader extends AbstractGridFieldComponent implements GridField_HTMLProvider
{

    /**
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $templates = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return [
            'header' => $gridField->renderWith($templates)
        ];
    }
}
