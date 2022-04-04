<?php

namespace SilverStripe\Forms\GridField;

/**
 * @see GridState
 */
class GridState_Component extends AbstractGridFieldComponent implements GridField_HTMLProvider
{

    public function getHTMLFragments($gridField)
    {
        return [
            'before' => $gridField->getState(false)->Field()
        ];
    }
}
