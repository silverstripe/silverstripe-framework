<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Extension;

class ArticleExtension extends Extension implements TestOnly
{
    private static $db = [
        'ExtendedField' => 'Varchar'
    ];

    protected function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Main',
            new TextField('AddedExtensionField')
        );
    }
}
