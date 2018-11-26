<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\PrintableTransformation_TabSet;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;

class PrintableTransformationTabSetTest extends SapphireTest
{
    public function testFieldHolder()
    {
        $tabs = [
            new Tab('Main'),
            new Tab('Secondary'),
            $optionsTabSet = new TabSet(
                'Options',
                'Options',
                new Tab('Colours'),
                new Tab('Options')
            ),
        ];

        $transformationTabSet = new PrintableTransformation_TabSet($tabs);
        $result = $transformationTabSet->FieldHolder();

        $this->assertContains('<h1>Main</h1>', $result);
        $this->assertContains('<h1>Secondary</h1>', $result);

        $transformationTabSet->setTabSet($optionsTabSet);
        $result = $transformationTabSet->FieldHolder();

        $this->assertContains('<h2>Options</h2>', $result);
    }
}
