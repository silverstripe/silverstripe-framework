<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

use Attribute;
use SilverStripe\Core\Attributes\OwnerAware;
use SilverStripe\Core\Attributes\HasOwner;
use SilverStripe\Dev\TestOnly;

#[Attribute(Attribute::TARGET_CLASS)]
class ClassAttribute3 extends ClassAttribute2 implements TestOnly
{
}
