<?php declare(strict_types = 1);

namespace SilverStripe\Forms\Tests\GridField\GridFieldConfigTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField_ActionMenuItem;
use SilverStripe\Forms\GridField\GridFieldEditButton;

class MyActionMenuItemComponent extends GridFieldEditButton implements TestOnly
{
    protected $shouldDisplay;

    public function __construct($shouldDisplay = true)
    {
        $this->shouldDisplay = $shouldDisplay;
    }

    public function getGroup($gridField, $record, $columnName)
    {
        return $this->shouldDisplay ? GridField_ActionMenuItem::DEFAULT_GROUP : null;
    }
}
