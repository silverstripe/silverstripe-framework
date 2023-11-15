<?php

namespace SilverStripe\Type\Tests\data;

use SilverStripe\Assets\File;
use SilverStripe\Core\Injector\Injector;
use function PHPStan\Testing\assertType;

assertType(
    File::class,
    Injector::inst()->get(File::class)
);

assertType(
    File::class,
    singleton(File::class)
);

assertType(
    File::class,
    Injector::inst()->create(File::class)
);

assertType(
    File::class,
    Injector::inst()->createWithArgs(File::class, ['Name' => 'Foo'])
);
