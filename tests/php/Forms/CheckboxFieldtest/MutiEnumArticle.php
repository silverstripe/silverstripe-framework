<?php

namespace SilverStripe\Forms\Tests\CheckboxSetFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class MultiEnumArticle extends DataObject implements TestOnly
{
    private static $table_name = 'CheckboxSetFieldTest_MultiEnumArticle';

    private static $db = array(
        "Content" => "Text",
        "Colours" => "MultiEnum('Red,Blue,Green')",
    );
}
