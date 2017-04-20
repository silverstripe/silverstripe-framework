<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\Tests\DataExtensionTest\CMSFieldsBase;

/**
 * Second level test class.
 * Tests usage of beforeExtendingCMSFields
 */
class CMSFieldsChild extends CMSFieldsBase implements TestOnly
{
    private static $table_name = 'DataExtensionTest_CMSFieldsChild';

    private static $db = array(
        'ChildField' => 'Varchar(255)',
        'ChildFieldBeforeExtension' => 'Varchar(255)'
    );

    public function getCMSFields()
    {
        $this->beforeExtending(
            'updateCMSFields',
            function (FieldList $fields) {
                $fields->addFieldToTab('Root.Test', new TextField('ChildFieldBeforeExtension'));
            }
        );

        $this->afterExtending(
            'updateCMSFields',
            function (FieldList $fields) {
                $fields->removeByName('ExtendedFieldRemove', true);
            }
        );

        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Test', new TextField('ChildField'));
        return $fields;
    }
}
