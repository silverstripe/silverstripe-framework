<?php

namespace SilverStripe\Cli\CommandLoader;

use SilverStripe\Dev\DevelopmentAdmin;

/**
 * Get commands for the controllers registered in DevelopmentAdmin
 */
class DevCommandLoader extends PolyCommandLoader
{
    protected function getCommands(): array
    {
        return DevelopmentAdmin::singleton()->getCommands();
    }
}
