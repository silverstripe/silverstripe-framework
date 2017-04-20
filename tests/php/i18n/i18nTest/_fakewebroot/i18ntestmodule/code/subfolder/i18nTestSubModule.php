<?php

use SilverStripe\Core\Object;

class i18nTestSubModule extends Object
{
    public function __construct()
    {
        _t('i18nTestModule.OTHERENTITY', 'Other Entity');

        parent::__construct();
    }
}
