<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class i18nTestModule extends DataObject implements TestOnly
{
    private static $db = array(
        'MyField' => 'Varchar',
    );

    public function myMethod()
    {
        _t(
            'i18nTestModule.ENTITY',
            'Entity with "Double Quotes"',
            'Comment for entity'
        );
    }
}
class i18nTestModule_Addition
{
    public function myAdditionalMethod()
    {
        _t('i18nTestModule.ADDITION', 'Addition');
    }
}
