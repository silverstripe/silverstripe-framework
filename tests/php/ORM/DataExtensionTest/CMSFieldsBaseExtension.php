<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

/**
 * Extension to top level test class, tests that updateCMSFields work
 */
class CMSFieldsBaseExtension extends DataExtension implements TestOnly
{
    private static $db = array(
        'ExtendedFieldKeep' => 'Varchar(255)',
        'ExtendedFieldRemove' => 'Varchar(255)'
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Test', new TextField('ExtendedFieldRemove'));
        $fields->addFieldToTab('Root.Test', new TextField('ExtendedFieldKeep'));

        if ($childField = $fields->dataFieldByName('ChildFieldBeforeExtension')) {
            $childField->setTitle('ChildFieldBeforeExtension: Modified Title');
        }

        if ($grandchildField = $fields->dataFieldByName('GrandchildFieldBeforeExtension')) {
            $grandchildField->setTitle('GrandchildFieldBeforeExtension: Modified Title');
        }
    }
}
