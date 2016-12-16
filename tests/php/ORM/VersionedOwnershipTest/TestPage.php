<?php

namespace SilverStripe\ORM\Tests\VersionedOwnershipTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * Page which owns a lits of banners
 *
 * @mixin Versioned
 */
class TestPage extends DataObject implements TestOnly
{
    private static $extensions = array(
        Versioned::class,
    );

    private static $table_name = 'VersionedOwnershipTest_Page';

    private static $db = array(
        'Title' => 'Varchar(255)',
    );

    private static $many_many = array(
        'Banners' => Banner::class,
    );

    private static $owns = array(
        'Banners',
        'Custom'
    );

    /**
     * All custom objects with the same number. E.g. 'Page 1' owns 'Custom 1'
     *
     * @return DataList
     */
    public function Custom()
    {
        $title = str_replace('Page', 'Custom', $this->Title);
        return CustomRelation::get()->filter('Title', $title);
    }
}
