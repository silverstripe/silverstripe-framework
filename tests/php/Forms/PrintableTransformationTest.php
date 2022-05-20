<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\PrintableTransformation;
use SilverStripe\Forms\PrintableTransformation_TabSet;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;

class PrintableTransformationTest extends SapphireTest
{
    public function testTransformTabSet()
    {
        $tab1 = new Tab('Main');
        $tab2 = new Tab('Settings');
        $tabSet = new TabSet('Root', 'Root', $tab1, $tab2);

        $transformation = new PrintableTransformation();
        $result = $transformation->transformTabSet($tabSet);

        $this->assertInstanceOf(PrintableTransformation_TabSet::class, $result);
        $this->assertSame('Root', $result->Title());
    }
}
