<?php

namespace SilverStripe\Type\Tests\data;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Type\Tests\Source\ExtensibleMockOne;
use SilverStripe\Type\Tests\Source\ExtensibleMockTwo;
use SilverStripe\Type\Tests\Source\ExtensionMockOne;
use SilverStripe\Type\Tests\Source\ExtensionMockTwo;
use function PHPStan\Testing\assertType;

$extensionOne = Injector::inst()->get(ExtensionMockOne::class);
$extensionTwo = Injector::inst()->get(ExtensionMockTwo::class);

assertType(
    sprintf('%s|static(%s)', ExtensibleMockOne::class, ExtensionMockOne::class),
    $extensionOne->getOwner()
);

assertType(
    sprintf(
        '%s|%s|static(%s)',
        ExtensibleMockOne::class,
        ExtensibleMockTwo::class,
        ExtensionMockTwo::class
    ),
    $extensionTwo->getOwner()
);
