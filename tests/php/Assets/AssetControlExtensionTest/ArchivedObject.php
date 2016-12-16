<?php

namespace SilverStripe\Assets\Tests\AssetControlExtensionTest;

use SilverStripe\Dev\TestOnly;

/**
 * Versioned object that always archives its assets
 */
class ArchivedObject extends VersionedObject implements TestOnly
{
    private static $keep_archived_assets = true;

    private static $table_name = 'AssetControlExtensionTest_ArchivedObject';
}
