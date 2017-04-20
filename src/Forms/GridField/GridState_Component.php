<?php

namespace SilverStripe\Forms\GridField;

/**
 * @see GridState
 */
class GridState_Component implements GridField_HTMLProvider
{

    public function getHTMLFragments($gridField)
    {
        return array(
            'before' => $gridField->getState(false)->Field()
        );
    }
}
