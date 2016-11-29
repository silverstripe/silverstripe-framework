<?php

namespace SilverStripe\ORM\Versioning;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObject;

/**
 * Extends {@see GridFieldDetailForm}
 */
class VersionedGridFieldDetailForm extends Extension
{

    /**
     * @param string $class
     * @param GridField $gridField
     * @param DataObject $record
     * @param RequestHandler $requestHandler
     */
    public function updateItemRequestClass(&$class, $gridField, $record, $requestHandler)
    {
        // Conditionally use a versioned item handler
        if ($record && $record->has_extension('SilverStripe\ORM\Versioning\Versioned')) {
            $class = 'SilverStripe\ORM\Versioning\VersionedGridFieldItemRequest';
        }
    }
}
