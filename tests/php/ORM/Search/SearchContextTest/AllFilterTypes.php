<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class AllFilterTypes extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_AllFilterTypes';

    private static $db = array(
        'ExactMatch' => 'Varchar',
        'PartialMatch' => 'Varchar',
        'SubstringMatch' => 'Varchar',
        'CollectionMatch' => 'Varchar',
        'StartsWith' => 'Varchar',
        'EndsWith' => 'Varchar',
        'HiddenValue' => 'Varchar',
        'FulltextField' => 'Text',
    );

    private static $searchable_fields = array(
        'ExactMatch' => 'ExactMatchFilter',
        'PartialMatch' => 'PartialMatchFilter',
        'CollectionMatch' => 'ExactMatchFilter',
        'StartsWith' => 'StartsWithFilter',
        'EndsWith' => 'EndsWithFilter',
        'FulltextField' => 'FulltextFilter',
    );
}
