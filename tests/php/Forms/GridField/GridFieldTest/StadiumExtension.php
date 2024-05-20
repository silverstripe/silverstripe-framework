<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\Filters\FulltextFilter;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\StadiumExtension;

class StadiumExtension extends DataExtension implements TestOnly
{
    protected function updateSearchableFields(&$fields)
    {
        $fields['Type']['filter'] = new FulltextFilter();
    }
}
