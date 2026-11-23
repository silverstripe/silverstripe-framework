<?php

namespace SilverStripe\Dev\Tests\Command\UnloadableDataClassTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

// The manifest lists this class, but it never gets declared at runtime.
// The class must use the missing trait: if only the guard referenced it,
// PHP would bind the class at compile time and it would exist after all.
if (!trait_exists('SilverStripe\\Dev\\Tests\\Command\\UnloadableDataClassTest\\MissingTrait')) {
    return;
}

class GuardedThing extends DataObject implements TestOnly
{
    use MissingTrait;

    private static string $table_name = 'UnloadableDataClassTest_GuardedThing';
}
