<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Core\Config\Config_MemCache;
use SilverStripe\Dev\TestOnly;

class ConfigTestMemCache extends Config_MemCache implements TestOnly
{
    public $cache;
    public $tags;
}
