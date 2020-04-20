<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\SelectionGroup_Item;
use SilverStripe\Forms\SelectionGroup;

class SelectionGroupTest extends SapphireTest
{
    public function testFieldHolder()
    {
        $items = [
            new SelectionGroup_Item(
                'one',
                new LiteralField('one', 'one view'),
                'one title'
            ),
            new SelectionGroup_Item(
                'two',
                new LiteralField('two', 'two view'),
                'two title'
            ),
        ];
        $field = new SelectionGroup('MyGroup', $items);
        $parser = new CSSContentParser($field->FieldHolder());
        $listEls = $parser->getBySelector('li');
        $listElOne = $listEls[0];
        $listElTwo = $listEls[1];

        $this->assertEquals('one', (string)$listElOne->label[0]->input[0]['value']);
        $this->assertEquals('two', (string)$listElTwo->label[0]->input[0]['value']);

        $this->assertEquals(' one title', (string)$listElOne->label[0]);
        $this->assertEquals(' two title', (string)$listElTwo->label[0]);

        $this->assertContains('one view', (string)$listElOne->div);
        $this->assertContains('two view', (string)$listElTwo->div);
    }

    public function testSelectedFieldHolder()
    {
        $items = [
            new SelectionGroup_Item(
                'one',
                new LiteralField('one', 'one view'),
                'one title'
            ),
            new SelectionGroup_Item(
                'two',
                new LiteralField('two', 'two view'),
                'two title'
            ),
        ];
        $field = new SelectionGroup('MyGroup', $items);
        $field->setValue('two');

        $parser = new CSSContentParser($field->FieldHolder());

        $listEls = $parser->getBySelector('li');
        $listElOne = $listEls[0];
        $listElTwo = $listEls[1];

        $this->assertEquals('one', (string)$listElOne->label[0]->input[0]['value']);
        $this->assertEquals('two', (string)$listElTwo->label[0]->input[0]['value']);
        $this->assertEquals('selected', (string)$listElTwo->attributes()->class);
    }

    public function testLegacyItemsFieldHolder()
    {
        $items = [
            'one' => new LiteralField('one', 'one view'),
            'two' => new LiteralField('two', 'two view'),
        ];
        $field = new SelectionGroup('MyGroup', $items);
        $parser = new CSSContentParser($field->FieldHolder());
        $listEls = $parser->getBySelector('li');
        $listElOne = $listEls[0];
        $listElTwo = $listEls[1];

        $this->assertEquals('one', (string)$listElOne->label[0]->input[0]['value']);
        $this->assertEquals('two', (string)$listElTwo->label[0]->input[0]['value']);

        $this->assertEquals(' one', (string)$listElOne->label[0]);
        $this->assertEquals(' two', (string)$listElTwo->label[0]);
    }

    public function testLegacyItemsFieldHolderWithTitle()
    {
        $items = [
            'one//one title' => new LiteralField('one', 'one view'),
            'two//two title' => new LiteralField('two', 'two view'),
        ];
        $field = new SelectionGroup('MyGroup', $items);
        $parser = new CSSContentParser($field->FieldHolder());
        $listEls = $parser->getBySelector('li');
        $listElOne = $listEls[0];
        $listElTwo = $listEls[1];

        $this->assertEquals('one', (string)$listElOne->label[0]->input[0]['value']);
        $this->assertEquals('two', (string)$listElTwo->label[0]->input[0]['value']);

        $this->assertEquals(' one title', (string)$listElOne->label[0]);
        $this->assertEquals(' two title', (string)$listElTwo->label[0]);
    }
}
