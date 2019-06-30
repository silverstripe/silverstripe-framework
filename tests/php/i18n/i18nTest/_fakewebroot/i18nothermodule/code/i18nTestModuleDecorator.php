<?php declare(strict_types = 1);

use SilverStripe\ORM\DataExtension;

class i18nTestModuleExtension extends DataExtension
{

    public static $db = array(
        'MyExtraField' => 'Varchar'
    );
}
