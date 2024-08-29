<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\Filters\FulltextFilter;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\StadiumExtension;

class StadiumExtension extends Extension implements TestOnly
{
    protected function updateSearchableFields(&$fields)
    {
        $fields['Type']['filter'] = new FulltextFilter();
    }
}
