<?php

/**
 * Extends {@see GridFieldDetailForm}
 */
class VersionedGridFieldDetailForm extends Extension {
    public function updateItemRequestClass(&$class, $gridField, $record, $requestHandler) {
        // Conditionally use a versioned item handler
        if($record && $record->has_extension('Versioned')) {
            $class = 'VersionedGridFieldItemRequest';
        }
    }
}
