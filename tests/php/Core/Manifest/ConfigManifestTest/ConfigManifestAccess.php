<?php

namespace SilverStripe\Core\Tests\Manifest\ConfigManifestTest;

use SilverStripe\Core\Manifest\ConfigManifest;

class ConfigManifestAccess extends ConfigManifest
{
    public function relativeOrder($a, $b)
    {
        return parent::relativeOrder($a, $b);
    }
}
