<?php

namespace SilverStripe\i18n\Tests\i18nTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

class TestDataObject extends DataObject implements TestOnly
{

    private static $db = array(
        'MyProperty' => 'Varchar',
        'MyUntranslatedProperty' => 'Text'
    );

    private static $has_one = array(
        'HasOneRelation' => Member::class
    );

    private static $has_many = array(
        'HasManyRelation' => Member::class
    );

    private static $many_many = array(
        'ManyManyRelation' => Member::class
    );

    /**
     * @param bool $includerelations a boolean value to indicate if the labels returned include relation fields
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['MyProperty'] = _t('i18nTest_DataObject.MyProperty', 'My Property');

        return $labels;
    }
}
