<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Extension as CoreExtension;
use SilverStripe\Dev\TestOnly;

class Extending_Extension extends CoreExtension implements TestOnly
{
    public function updateResult(&$first, &$second, &$third)
    {
        // Extension should be invoked third
        if ($first === 11 && $second === 12 && $third == 13) {
            $first = 21;
            $second = 22;
            $third = 23;
            return 'extension';
        }
        return 'extension-error';
    }
}
