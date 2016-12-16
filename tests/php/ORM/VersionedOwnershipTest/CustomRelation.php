<?php

namespace SilverStripe\ORM\Tests\VersionedOwnershipTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * Object which is owned via a custom PHP method rather than DB relation
 *
 * @mixin Versioned
 */
class CustomRelation extends DataObject implements TestOnly
{
    private static $extensions = array(
        Versioned::class,
    );

    private static $table_name = 'VersionedOwnershipTest_CustomRelation';

    private static $db = array(
        'Title' => 'Varchar(255)',
    );

    private static $owned_by = array(
        'Pages'
    );

    /**
     * All pages with the same number. E.g. 'Page 1' owns 'Custom 1'
     *
     * @return DataList
     */
    public function Pages()
    {
        $title = str_replace('Custom', 'Page', $this->Title);
        return TestPage::get()->filter('Title', $title);
    }
}
