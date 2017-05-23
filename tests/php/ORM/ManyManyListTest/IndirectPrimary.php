<?php

namespace SilverStripe\ORM\Tests\ManyManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * A data object that implements the primary side of a many_many (where the extra fields are
 * defined.) The many-many refers to ManyManyListTest_Secondary rather than ManyManyListTest_SecondarySub
 * by design, because we're trying to test that a subclass instance picks up the extra fields of it's parent.
 *
 * @method ManyManyList Secondary()
 */
class IndirectPrimary extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyListTest_IndirectPrimary';

    private static $db = array(
        'Title' => 'Varchar(255)'
    );

    private static $many_many = array(
        'Secondary' => Secondary::class
    );

    private static $many_many_extraFields = array(
        'Secondary' => array(
            'DocumentSort' => 'Int'
        )
    );
}
