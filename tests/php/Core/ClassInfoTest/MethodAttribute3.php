<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

use Attribute;
use SilverStripe\Core\Attributes\OwnerAware;
use SilverStripe\Core\Attributes\HasOwner;
use SilverStripe\Dev\TestOnly;

#[Attribute(Attribute::TARGET_METHOD)]
class MethodAttribute3 extends MethodAttribute2 implements TestOnly
{
}
