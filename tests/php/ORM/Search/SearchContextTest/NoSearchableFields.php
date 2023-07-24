<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;

class NoSearchableFields extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_NoSearchableFields';

    private static $db = [
        'Name' => 'Varchar',
        'Email' => 'Varchar',
        'HairColor' => 'Varchar',
        'EyeColor' => 'Varchar'
    ];

    private static $has_one = [
        'Customer' => Customer::class,
        'Image' => Image::class,
    ];

    private static $summary_fields = [
        'Name' => 'Custom Label',
        'Customer' => 'Customer',
        'Customer.FirstName' => 'Customer',
        'Image.CMSThumbnail' => 'Image',
        'Image.BackLinks' => 'Backlinks',
        'Image.BackLinks.Count' => 'Backlinks',
        'HairColor',
        'EyeColor',
        'ReturnsNull',
        'DynamicField'
    ];

    public function MyName()
    {
        return 'Class ' . $this->Name;
    }

    public function getDynamicField()
    {
        return 'dynamicfield';
    }

    public function ReturnsNull()
    {
        return null;
    }
}
