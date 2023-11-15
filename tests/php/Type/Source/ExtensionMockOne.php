<?php

namespace SilverStripe\Type\Tests\Source;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * @extends Extension<ExtensibleMockOne|static>
 */
class ExtensionMockOne extends Extension implements TestOnly
{
}
