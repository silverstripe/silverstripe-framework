<?php

namespace SilverStripe\Type\Tests\Source;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Dev\TestOnly;

class ExtensibleMockTwo implements TestOnly
{
    use Extensible;
    use Configurable;
}
