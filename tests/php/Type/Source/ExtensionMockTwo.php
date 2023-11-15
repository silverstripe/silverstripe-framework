<?php

namespace SilverStripe\Type\Tests\Source;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * @extends Extension<ExtensibleMockOne|ExtensibleMockTwo|static>
 */
class ExtensionMockTwo extends Extension implements TestOnly
{
}
