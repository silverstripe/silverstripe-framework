<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

/**
 * Base class for CMS fields
 */
class CMSFieldsBase extends DataObject implements TestOnly
{

    private static $table_name = 'DataExtensionTest_CMSFieldsBase';

    private static $db = array(
        'PageField' => 'Varchar(255)'
    );

    private static $extensions = array(
        CMSFieldsBaseExtension::class
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Test', new TextField('PageField'));
        return $fields;
    }
}
