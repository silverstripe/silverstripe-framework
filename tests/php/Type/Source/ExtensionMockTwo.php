<?php

namespace SilverStripe\Type\Tests\Source;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

/**
 * @extends DataExtension<ExtensibleMockOne|ExtensibleMockTwo|static>
 */
class ExtensionMockTwo extends DataExtension implements TestOnly
{
}
