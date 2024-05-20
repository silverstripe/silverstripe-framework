<?php

namespace SilverStripe\Tests\ORM\UniqueKey;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class ExtraKeysExtension extends Extension implements TestOnly
{
    protected function cacheKeyComponent(): string
    {
        return 'extra-key';
    }
}
