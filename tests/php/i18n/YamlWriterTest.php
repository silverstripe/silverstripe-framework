<?php

namespace SilverStripe\i18n\Tests;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\Messages\YamlWriter;

class YamlWriterTest extends SapphireTest
{
    public function testYamlWriter()
    {
        $writer = new YamlWriter();
        $entities = [
            'Level1.Level2.EntityName' => 'Text',
            'Level1.OtherEntityName' => 'Other Text',
            'Level1.Plurals' => [
                'context' => 'Some ignored context',
                'one' => 'An item',
                'other' => '{count} items',
            ],
            'Level1.PluralString1' => 'An item|{count} items',
            'Level1.PluralString2' => [
                'context' => 'Another ignored context',
                'default' => 'An item|{count} items',
            ],
            // Some near-false-positives for plurals
            'Level1.NotPlural1' => 'Not a plural|string', // no count
            'Level1.NotPlural2' => 'Not|a|plural|string{count}', // unexpected number
            'Level1.NotPlural3' => 'Not a plural string {count}', // no pipe
            'Level1.BoolTest' => 'True',
            'Level1.FlagTest' => 'No',
            'Level1.TextTest' => 'Maybe',
            'Template.ss.Key' => 'Template var',
            'TopLevel' => 'The Top',
        ];
        $yaml = <<<YAML
de:
  Level1:
    BoolTest: 'True'
    FlagTest: 'No'
    Level2.EntityName: Text
    NotPlural1: 'Not a plural|string'
    NotPlural2: 'Not|a|plural|string{count}'
    NotPlural3: 'Not a plural string {count}'
    OtherEntityName: 'Other Text'
    PluralString1:
      one: 'An item'
      other: '{count} items'
    PluralString2:
      one: 'An item'
      other: '{count} items'
    Plurals:
      one: 'An item'
      other: '{count} items'
    TextTest: Maybe
  Template.ss:
    Key: 'Template var'
  TopLevel: 'The Top'

YAML;
        $this->assertEquals($yaml, Convert::nl2os($writer->getYaml($entities, 'de')));
    }
}
