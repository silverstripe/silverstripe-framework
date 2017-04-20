<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\PopoverField;

class PopoverFieldTest extends SapphireTest
{
    public function testPopoverField()
    {
        // Test normal constructor
        $field = new PopoverField(
            'My Title',
            [
            new TextField('Name')
            ]
        );
        $field->setPopoverTitle('Popover Title');
        $this->assertEquals('My Title', $field->Title());
        $this->assertEquals('Popover Title', $field->getPopoverTitle());
        $this->assertEquals('Name', $field->getChildren()->first()->Title());

        // Test single array argument
        $field2 = new PopoverField([ new TextField('Other')]);
        $this->assertEmpty($field2->Title());
        $this->assertEquals('Other', $field2->getChildren()->first()->Title());

        // Test variable length constructor
        $field3 = new PopoverField('Field Title', new TextField('First'), new TextField('Second'));
        $this->assertEquals('Field Title', $field3->Title());
        $this->assertEquals('First', $field3->getChildren()->first()->Title());
        $this->assertEquals('Second', $field3->getChildren()->last()->Title());
    }
}
