<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

class ArticleExtension extends DataExtension implements TestOnly
{
    private static $db = array(
        'ExtendedField' => 'Varchar'
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Main',
            new TextField('AddedExtensionField')
        );
    }
}
