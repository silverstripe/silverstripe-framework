<?php

namespace SilverStripe\i18n\Tests\i18nTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

class TestDataObject extends DataObject implements TestOnly
{
    private static $table_name = 'i18nTest_TestDataObject';

    private static $db = [
        'MyProperty' => 'Varchar',
        'MyUntranslatedProperty' => 'Text'
    ];

    private static $has_one = [
        'HasOneRelation' => Member::class
    ];

    private static $has_many = [
        'HasManyRelation' => Member::class
    ];

    private static $many_many = [
        'ManyManyRelation' => Member::class
    ];

    /**
     * @param bool $includerelations a boolean value to indicate if the labels returned include relation fields
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['MyProperty'] = _t(__CLASS__ . '.MyProperty', 'My Property');

        return $labels;
    }
}
