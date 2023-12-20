<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\GridField\GridFieldVersionTag;

class GridFieldVersionTagTest extends SapphireTest
{
    protected static $fixture_file = 'GridFieldVersionTagTest.yml';

    protected static $extra_dataobjects = [
        Cheerleader::class,
        Team::class,
    ];

    protected static $required_extensions = [
        Cheerleader::class => [
            Versioned::class,
        ],
    ];

    public function testGetColumnContent()
    {
        $team = Team::get();
        $cheerleader = Cheerleader::get()->first();
        $gridField = new GridField('TestGridField', 'TestGridFields', $team);

        $columns = $gridField->getConfig()->getComponentByType(GridFieldVersionTag::class);
        $nameColumn = $columns->getColumnContent($gridField, $team->first(), 'Name');
        $this->assertEquals(
            $nameColumn,
            ' <span class="ss-gridfield-badge badge status-modified" title="Item has unpublished changes">Modified</span>'
        );

        $cheerleader->publishRecursive();
        $nameColumn = $columns->getColumnContent($gridField, $team->first(), 'Name');

        $this->assertEquals($nameColumn, '');
    }

    public function testAugmentColumns()
    {
        $team = Team::get();
        $cheerleader = Cheerleader::get()->first();
        $gridField = new GridField('TestGridField', 'TestGridFields', $team);

        $columns = $gridField->getConfig()->getComponentByType(GridFieldVersionTag::class);

        $columns->setVersionedLabelFields(['Title']);
        $column = $columns->getVersionedLabelFields();

        $this->assertEquals($column, ['Title']);

        $augmentColumns = ['Name', 'Title', 'ID'];

        $columns->augmentColumns($gridField, $augmentColumns);
        $nameColumn = $columns->getColumn();

        $this->assertEquals($nameColumn, 'Title');

        $columns->setVersionedLabelFields(['Non-Title']);
        $column = $columns->getVersionedLabelFields();

        $this->assertEquals($column, ['Non-Title']);

        $columns->augmentColumns($gridField, $augmentColumns);
        $nameColumn = $columns->getColumn();

        $this->assertNotEquals($nameColumn, 'Non-Title');
        $this->assertEquals($nameColumn, 'Title');
    }
}
