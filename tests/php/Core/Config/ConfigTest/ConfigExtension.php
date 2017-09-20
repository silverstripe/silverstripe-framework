<?php

namespace SilverStripe\Core\Tests\Config\ConfigTest;

use SilverStripe\Core\Extension;

class ConfigExtension extends Extension
{
    private static $config_array = [
        'foo' => 'foo'
    ];

    private static $config_value = true;
}
