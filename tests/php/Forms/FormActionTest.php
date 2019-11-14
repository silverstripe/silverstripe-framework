<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FormAction;

class FormActionTest extends SapphireTest
{

    public function testGetField()
    {
        $formAction = new FormAction('test');
        $this->assertContains('type="submit"', $formAction->getAttributesHTML());

        $formAction->setAttribute('src', 'file.png');
        $this->assertContains('type="image"', $formAction->getAttributesHTML());
    }

    public function testGetTitle()
    {
        // Test that description overrides title attribute, but doesn't prevent it from
        // working if blank.
        $action = new FormAction('test');
        $action->setAttribute('title', 'this is the title');
        $this->assertEquals('this is the title', $action->getAttribute('title'));
        $action->setDescription('this is a better title');
        $this->assertEquals('this is a better title', $action->getAttribute('title'));
        $action->setDescription(null);
        $this->assertEquals('this is the title', $action->getAttribute('title'));
    }
}
