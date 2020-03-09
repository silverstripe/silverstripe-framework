<?php

namespace SilverStripe\Tests\ORM\UniqueKey;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class ExtraKeysExtension extends Extension implements TestOnly
{
    public function cacheKeyComponent(): string
    {
        return 'extra-key';
    }
}
