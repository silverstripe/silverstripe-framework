<?php

namespace SilverStripe\Type\Tests\Source;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Dev\TestOnly;

class ExtensibleMockOne implements TestOnly
{
    use Extensible;
    use Configurable;
}
