<?php

namespace SilverStripe\Forms\GridField;

/**
 * @see GridState
 */
class GridState_Component extends AbstractGridFieldComponent implements GridField_HTMLProvider
{

    public function getHTMLFragments(SilverStripe\Forms\GridField\GridField $gridField): array
    {
        return [
            'before' => $gridField->getState(false)->Field()
        ];
    }
}
