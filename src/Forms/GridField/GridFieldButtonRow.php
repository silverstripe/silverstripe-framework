<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * Adding this class to a {@link GridFieldConfig} of a {@link GridField} adds
 * a button row to that field.
 *
 * The button row provides a space for actions on this grid.
 *
 * This row provides two new HTML fragment spaces: 'toolbar-header-left' and
 * 'toolbar-header-right'.
 */
class GridFieldButtonRow extends AbstractGridFieldComponent implements GridField_HTMLProvider
{

    protected $targetFragment;

    public function __construct($targetFragment = 'before')
    {
        $this->targetFragment = $targetFragment;
    }

    public function getHTMLFragments($gridField)
    {
        $data = new ArrayData([
            "TargetFragmentName" => $this->targetFragment,
            "LeftFragment" => "\$DefineFragment(buttons-{$this->targetFragment}-left)",
            "RightFragment" => "\$DefineFragment(buttons-{$this->targetFragment}-right)",
        ]);

        $templates = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return [
            $this->targetFragment => $data->renderWith($templates)
        ];
    }
}
