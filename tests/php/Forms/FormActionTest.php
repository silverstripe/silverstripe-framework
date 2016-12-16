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
}
