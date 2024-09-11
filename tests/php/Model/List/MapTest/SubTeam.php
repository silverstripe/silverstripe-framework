<?php

namespace SilverStripe\Model\Tests\List\MapTest;

use SilverStripe\Dev\TestOnly;

class SubTeam extends Team implements TestOnly
{
    private static $table_name = 'MapTest_SubTeam';

    private static $has_one = [
        "ParentTeam" => Team::class,
    ];
}
