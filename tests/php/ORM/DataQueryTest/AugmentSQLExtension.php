<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Resettable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Queries\SQLSelect;

class AugmentSQLExtension extends Extension implements TestOnly, Resettable
{
    // Not allowed to use callable type for properties
    private static mixed $augmentCallback = null;

    public static function setAugmentCallback(?callable $augmentCallback): void
    {
        AugmentSQLExtension::$augmentCallback = $augmentCallback;
    }

    public static function reset()
    {
        AugmentSQLExtension::$augmentCallback = null;
    }

    protected function augmentSQL(SQLSelect $select, DataQuery $query): void
    {
        if (AugmentSQLExtension::$augmentCallback !== null) {
            call_user_func_array(AugmentSQLExtension::$augmentCallback, [$select, $query]);
        }
    }
}
