<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

use Attribute;
use SilverStripe\Dev\TestOnly;

#[Attribute(Attribute::TARGET_PROPERTY)]
class PropertyAttribute3 extends PropertyAttribute2 implements TestOnly
{
}
