<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\Tests\DataExtensionTest\CMSFieldsChild;

/**
 * Third level test class, testing that beforeExtendingCMSFields and afterExtendingCMSFields can be nested
 */
class CMSFieldsGrandChild extends CMSFieldsChild implements TestOnly
{
    private static $table_name = 'DataExtensionTest_CMSFieldsGrandChild';

    private static $db = [
        'GrandchildField' => 'Varchar(255)'
    ];

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

        $this->afterUpdateCMSFields(
            function (FieldList $fields) {
                // Remove field from parent's afterExtendingCMSFields
                $fields->removeByName('ChildFieldAfterExtension', true);

                // Adds own post-extension field
                $fields->addFieldToTab(
                    'Root.Test',
                    new TextField('GrandchildFieldAfterExtension', 'GrandchildFieldAfterExtension')
                );
            }
        );

        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Test', new TextField('GrandchildField'));
        return $fields;
    }
}
