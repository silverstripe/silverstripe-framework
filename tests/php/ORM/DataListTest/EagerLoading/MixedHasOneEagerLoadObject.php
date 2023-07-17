<?php

namespace SilverStripe\ORM\Tests\DataListTest\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class MixedHasOneEagerLoadObject extends DataObject implements TestOnly
{
    private static $table_name = 'MixedHasOneEagerLoadObject';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        // this is used for a "four levels deep" test
        'FourthLevel' => MixedHasOneEagerLoadObject::class
    ];
}
