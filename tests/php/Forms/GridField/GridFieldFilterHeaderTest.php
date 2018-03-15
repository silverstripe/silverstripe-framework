<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\CheerleaderHat;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\Team;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\TeamGroup;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\Mom;
use SilverStripe\ORM\DataList;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField;

class GridFieldFilterHeaderTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = 'GridFieldSortableHeaderTest.yml';

    /**
     * @var array
     */
    protected static $extra_dataobjects = array(
        Team::class,
        TeamGroup::class,
        Cheerleader::class,
        CheerleaderHat::class,
        Mom::class,
    );

    /**
     * @skipUpgrade
     */
    public function testRenderFilterdHeaderStandard()
    {
        $list = new DataList(Team::class);
        $config = new GridFieldConfig_RecordEditor();
        $form = new Form(null, 'Form', new FieldList(), new FieldList());
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        $gridField->setForm($form);

        /** @var $compontent GridFieldFilterHeader */
        $compontent = $gridField->getConfig()->getComponentByType(GridFieldFilterHeader::class);
        $htmlFragment = $compontent->getHTMLFragments($gridField);

        $this->assertContains(
            '<input type="text" name="filter[testfield][Name]"'
            . ' class="text grid-field__sort-field no-change-track form-group--no-label"'
            . ' id="filter_testfield_Name" placeholder="Filter by Name" />',
            $htmlFragment['header']
        );

        $this->assertNotContains(
            '<input type="text" name="filter[testfield][City]"'
            . ' class="text grid-field__sort-field no-change-track form-group--no-label"'
            . ' id="filter_testfield_City" placeholder="Filter by City" />',
            $htmlFragment['header']
        );
    }

    /**
     * @skipUpgrade
     */
    public function testRenderFilterHeaderUsingAliasFields()
    {
        $list = new DataList(Team::class);
        $config = new GridFieldConfig_RecordEditor();
        $form = new Form(null, 'Form', new FieldList(), new FieldList());
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        $gridField->setForm($form);

        /** @var $compontent GridFieldFilterHeader */
        $compontent = $gridField->getConfig()->getComponentByType(GridFieldFilterHeader::class);
        $compontent->setAliasFields([
            'City.Initial' => 'City',
        ]);
        $htmlFragment = $compontent->getHTMLFragments($gridField);

        $this->assertContains(
            '<input type="text" name="filter[testfield][City]"'
            . ' class="text grid-field__sort-field no-change-track form-group--no-label"'
            . ' id="filter_testfield_City" placeholder="Filter by City" />',
            $htmlFragment['header']
        );
    }

    /**
     * @skipUpgrade
     */
    public function testRenderFilterHeaderUsingOmitFields()
    {
        $list = new DataList(Team::class);
        $config = new GridFieldConfig_RecordEditor();
        $form = new Form(null, 'Form', new FieldList(), new FieldList());
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        $gridField->setForm($form);

        /** @var $compontent GridFieldFilterHeader */
        $compontent = $gridField->getConfig()->getComponentByType(GridFieldFilterHeader::class);
        $compontent->setOmittedFields(['Name']);
        $htmlFragment = $compontent->getHTMLFragments($gridField);

        $this->assertNotContains(
            '<input type="text" name="filter[testfield][Name]"'
            . ' class="text grid-field__sort-field no-change-track form-group--no-label"'
            . ' id="filter_testfield_Name" placeholder="Filter by Name" />',
            $htmlFragment['header']
        );
    }

    /**
     * @skipUpgrade
     */
    public function testRenderFilterHeaderWithCustomFields()
    {
        $list = new DataList(Team::class);
        $config = new GridFieldConfig_RecordEditor();
        $form = new Form(null, 'Form', new FieldList(), new FieldList());
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        $gridField->setForm($form);

        /** @var $compontent GridFieldFilterHeader */
        $compontent = $gridField->getConfig()->getComponentByType(GridFieldFilterHeader::class);
        $compontent
            ->setAliasFields([
                'City.Initial' => 'City',
            ])
            ->setCustomFields([
            'Name' => DropdownField::create('', '', ['Name1' => 'Name1', 'Name2' => 'Name2']),
            'City' => DropdownField::create('', '', ['City' => 'City1', 'City2' => 'City2']),
        ]);

        $htmlFragment = $compontent->getHTMLFragments($gridField);

        $this->assertContains(
            '<select name="filter[testfield][Name]" '
            . 'class="dropdown grid-field__sort-field no-change-track form-group--no-label"'
            . ' id="filter_testfield_Name" placeholder="Filter by Name">',
            $htmlFragment['header']
        );

        $this->assertContains(
            '<select name="filter[testfield][City]" '
            . 'class="dropdown grid-field__sort-field no-change-track form-group--no-label"'
            . ' id="filter_testfield_City" placeholder="Filter by City">',
            $htmlFragment['header']
        );
    }
}
