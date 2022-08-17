<?php

namespace SilverStripe\Forms\HTMLEditor;

use SilverStripe\Forms\HTMLReadonlyField;

/**
 * Readonly version of an {@link HTMLEditorField}.
 */
class HTMLEditorField_Readonly extends HTMLReadonlyField
{
    private static $casting = [
        'Value' => 'HTMLText',
    ];

    public function Type(): string
    {
        return 'htmleditorfield readonly';
    }
}
