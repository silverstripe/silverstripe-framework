<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\Tab;

class TabTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testNameAndID(): void
    {
        // ID is set on instantiation based on the name
        $tab = new Tab('MyName _-()!@#$');
        $this->assertSame('MyName _-()!@#$', $tab->getName());
        $this->assertSame('MyName_-', $tab->ID());

        // Changing the name changes the ID
        $tab->setName('NewName');
        $this->assertSame('NewName', $tab->getName());
        $this->assertSame('NewName', $tab->ID());

        // If ID is explicitly set, changing the name doesn't override it
        $tab->setID('Custom-ID');
        $tab->setName('AnotherName');
        $this->assertSame('AnotherName', $tab->getName());
        $this->assertSame('Custom-ID', $tab->ID());
    }
}
