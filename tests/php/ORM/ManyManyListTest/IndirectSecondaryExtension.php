<?php

namespace SilverStripe\ORM\Tests\ManyManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ManyManyList;

/**
 * An extension that is applied to ManyManyListTest_Secondary that
 * implements the other side of the many-many relationship.
 *
 * @method ManyManyList Primary()
 */
class IndirectSecondaryExtension extends DataExtension implements TestOnly
{
    private static $db = array(
        'Title' => 'Varchar(255)'
    );

    private static $belongs_many_many = array(
        'Primary' => IndirectPrimary::class
    );
}
