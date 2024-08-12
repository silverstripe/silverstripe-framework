<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;

class TabSetTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testChangeTabOrder(): void
    {
        $tabSet = new TabSet('Root');
        $fieldList = new FieldList([$tabSet]);
        $fieldList->findOrMakeTab('Root.Main');
        $fieldList->findOrMakeTab('Root.Next');
        $fieldList->findOrMakeTab('Root.More');
        $fieldList->findOrMakeTab('Root.Extra');
        $fieldList->addFieldToTab('Root', new TabSet('SubTabSet'));
        $fieldList->findOrMakeTab('Root.SubTabSet.Another');

        // Reorder tabs - intentionally leaving some alone, which will be added to the end.
        $tabSet->changeTabOrder([
            'SubTabSet',
            'More',
            'Main',
            'Non-Existent', // will be ignored
            'Another', // will be ignored
        ]);
        // Order is correct
        $this->assertSame(['SubTabSet', 'More', 'Main', 'Next', 'Extra'], $tabSet->getChildren()->column('Name'));
        // Sub-tab is still there
        $this->assertNotNull($fieldList->findTab('Root.SubTabSet.Another'));
    }
}
