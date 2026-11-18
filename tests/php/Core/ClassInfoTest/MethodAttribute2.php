<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

use Attribute;
use SilverStripe\Core\Attributes\OwnerAware;
use SilverStripe\Core\Attributes\HasOwner;
use SilverStripe\Dev\TestOnly;

#[Attribute(Attribute::TARGET_METHOD)]
class MethodAttribute2 implements OwnerAware, TestOnly, AttributeInterface
{
    use HasOwner;

    public function __construct(public readonly string $name)
    {
    }
}
