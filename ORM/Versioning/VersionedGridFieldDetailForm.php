<?php

namespace SilverStripe\ORM\Versioning;

use Extension;

/**
 * Extends {@see GridFieldDetailForm}
 */
class VersionedGridFieldDetailForm extends Extension {
    public function updateItemRequestClass(&$class, $gridField, $record, $requestHandler) {
        // Conditionally use a versioned item handler
        if($record && $record->has_extension('SilverStripe\ORM\Versioning\Versioned')) {
            $class = 'SilverStripe\ORM\Versioning\VersionedGridFieldItemRequest';
        }
    }
}
