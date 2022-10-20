<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Dev\Deprecation;
use SilverStripe\Versioned\VersionedGridFieldState\VersionedGridFieldState;

if (!class_exists(VersionedGridFieldState::class)) {
    return;
}


/**
 * @deprecated 4.1.0 Use VersionedGridFieldState instead
 */
class GridFieldVersionedState extends VersionedGridFieldState
{
    public function __construct(array $versionedLabelFields = ['Name', 'Title'])
    {
        Deprecation::notice('4.1.0', 'Use VersionedGridFieldState instead', Deprecation::SCOPE_CLASS);
        parent::__construct($versionedLabelFields);
    }
}
