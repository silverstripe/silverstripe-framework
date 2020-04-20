<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

/**
 * @skipUpgrade
 */
class PlayerExtension extends DataExtension implements TestOnly
{

    public static function get_extra_config($class = null, $extensionClass = null, $args = null)
    {
        $config = [];

        // Only add these extensions if the $class is set to DataExtensionTest_Player, to
        // test that the argument works.
        if (strcasecmp($class, Player::class) === 0) {
            $config['db'] = [
                'Address' => 'Text',
                'DateBirth' => 'Date',
                'Status' => "Enum('Shooter,Goalie')"
            ];
            $config['defaults'] = [
                'Status' => 'Goalie'
            ];
        }

        return $config;
    }
}
