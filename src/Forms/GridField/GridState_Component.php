<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Core\Injector\Injectable;

/**
 * @see GridState
 */
class GridState_Component implements GridField_HTMLProvider
{
    use Injectable;

    public function getHTMLFragments($gridField)
    {
        return [
            'before' => $gridField->getState(false)->Field()
        ];
    }
}
