<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\Tests\DataExtensionTest\CMSFieldsChild;

/**
 * Third level test class, testing that beforeExtendingCMSFields can be nested
 */
class CMSFieldsGrandChild extends CMSFieldsChild implements TestOnly
{
    private static $table_name = 'DataExtensionTest_CMSFieldsGrandChild';

    private static $db = array(
        'GrandchildField' => 'Varchar(255)'
    );

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(
            function (FieldList $fields) {
                // Remove field from parent's beforeExtendingCMSFields
                $fields->removeByName('ChildFieldBeforeExtension', true);

                // Adds own pre-extension field
                $fields->addFieldToTab('Root.Test', new TextField('GrandchildFieldBeforeExtension'));
            }
        );

        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Test', new TextField('GrandchildField'));
        return $fields;
    }
}
