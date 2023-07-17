<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class MixedHasManyEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'MixedHasManyEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'MixedManyManyEagerLoadObject' => MixedManyManyEagerLoadObject::class,
        'MixedHasOneEagerLoadObject' => MixedHasOneEagerLoadObject::class
    ];
}
