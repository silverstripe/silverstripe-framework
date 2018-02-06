<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Dev\Deprecation;
use SilverStripe\Versioned\VersionedGridFieldState\VersionedGridFieldState;

if (!class_exists(VersionedGridFieldState::class)) {
    return;
}


/**
 * @deprecated 4.1..5.0
 */
class GridFieldVersionedState extends VersionedGridFieldState
{
    public function __construct(array $versionedLabelFields = ['Name', 'Title'])
    {
        parent::__construct($versionedLabelFields);
        Deprecation::notice('5.0', 'Use ' . VersionedGridFieldState::class . ' instead');
    }
}
