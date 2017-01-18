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
                'one' => 'An item',
                'other' => '{count} items',
            ],
            'Level1.BoolTest' => 'True',
            'Level1.FlagTest' => 'No',
            'Level1.TextTest' => 'Maybe',
            'TopLevel' => 'The Top',
        ];
        $yaml = <<<YAML
de:
  Level1:
    Level2.EntityName: Text
    OtherEntityName: 'Other Text'
    Plurals:
      one: 'An item'
      other: '{count} items'
    BoolTest: 'True'
    FlagTest: 'No'
    TextTest: Maybe
  TopLevel: 'The Top'

YAML;
        $this->assertEquals($yaml, Convert::nl2os($writer->getYaml($entities, 'de')));
    }
}
