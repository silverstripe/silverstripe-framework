<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\CheerleaderHat;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\Team;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\TeamGroup;
use SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest\Mom;
use SilverStripe\ORM\DataList;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField;

class GridFieldSortableHeaderTest extends SapphireTest
{

    protected static $fixture_file = 'GridFieldSortableHeaderTest.yml';

    protected static $extra_dataobjects = array(
        Team::class,
        TeamGroup::class,
        Cheerleader::class,
        CheerleaderHat::class,
        Mom::class,
    );

    /**
     * Tests that the appropriate sortable headers are generated
     *
     * @skipUpgrade
     */
    public function testRenderHeaders()
    {

        // Generate sortable header and extract HTML
        $list = new DataList(Team::class);
        $config = new GridFieldConfig_RecordEditor();
        $form = new Form(null, 'Form', new FieldList(), new FieldList());
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        $gridField->setForm($form);
        $compontent = $gridField->getConfig()->getComponentByType(GridFieldSortableHeader::class);
        $htmlFragment = $compontent->getHTMLFragments($gridField);

        // Check that the output shows name and hat as sortable fields, but not city
        $this->assertContains('<span class="non-sortable">City</span>', $htmlFragment['header']);
        $this->assertContains(
            'value="Name" class="action grid-field__sort" id="action_SetOrderName"',
            $htmlFragment['header']
        );
        $this->assertContains(
            'value="Cheerleader Hat" class="action grid-field__sort" id="action_SetOrderCheerleader-Hat-Colour"',
            $htmlFragment['header']
        );

        // Check inverse of above
        $this->assertNotContains(
            'value="City" class="action grid-field__sort" id="action_SetOrderCity"',
            $htmlFragment['header']
        );
        $this->assertNotContains('<span class="non-sortable">Name</span>', $htmlFragment['header']);
        $this->assertNotContains('<span class="non-sortable">Cheerleader Hat</span>', $htmlFragment['header']);
    }

    public function testGetManipulatedData()
    {
        $list = new DataList(Team::class);
        $config = new GridFieldConfig_RecordEditor();
        $gridField = new GridField('testfield', 'testfield', $list, $config);

        // Test normal sorting
        $state = $gridField->State->GridFieldSortableHeader;
        $state->SortColumn = 'City';
        $state->SortDirection = 'asc';

        $compontent = $gridField->getConfig()->getComponentByType(GridFieldSortableHeader::class);
        $listA = $compontent->getManipulatedData($gridField, $list);

        $state->SortDirection = 'desc';
        $listB = $compontent->getManipulatedData($gridField, $list);

        $this->assertEquals(
            array('Auckland', 'Cologne', 'Melbourne', 'Wellington'),
            $listA->column('City')
        );
        $this->assertEquals(
            array('Wellington', 'Melbourne', 'Cologne', 'Auckland'),
            $listB->column('City')
        );

        // Test one relation 'deep'
        $state->SortColumn = 'Cheerleader.Name';
        $state->SortDirection = 'asc';
        $relationListA = $compontent->getManipulatedData($gridField, $list);

        $state->SortDirection = 'desc';
        $relationListB = $compontent->getManipulatedData($gridField, $list);

        $this->assertEquals(
            array('Wellington', 'Melbourne', 'Cologne', 'Auckland'),
            $relationListA->column('City')
        );
        $this->assertEquals(
            array('Auckland', 'Cologne', 'Melbourne', 'Wellington'),
            $relationListB->column('City')
        );

        // Test two relations 'deep'
        $state->SortColumn = 'Cheerleader.Hat.Colour';
        $state->SortDirection = 'asc';
        $relationListC = $compontent->getManipulatedData($gridField, $list);

        $state->SortDirection = 'desc';
        $relationListD = $compontent->getManipulatedData($gridField, $list);

        $this->assertEquals(
            array('Cologne', 'Auckland', 'Wellington', 'Melbourne'),
            $relationListC->column('City')
        );
        $this->assertEquals(
            array('Melbourne', 'Wellington', 'Auckland', 'Cologne'),
            $relationListD->column('City')
        );
    }

    /**
     * Test getManipulatedData on subclassed dataobjects
     */
    public function testInheritedGetManiplatedData()
    {
        $list = TeamGroup::get();
        $config = new GridFieldConfig_RecordEditor();
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        $state = $gridField->State->GridFieldSortableHeader;
        $component = $gridField->getConfig()->getComponentByType(GridFieldSortableHeader::class);

        // Test that inherited dataobjects will work correctly
        $state->SortColumn = 'Cheerleader.Hat.Colour';
        $state->SortDirection = 'asc';
        $relationListA = $component->getManipulatedData($gridField, $list);
        $relationListAsql = Convert::nl2os($relationListA->sql(), ' ');

        // Assert that all tables are joined properly
        $this->assertContains('FROM "GridFieldSortableHeaderTest_Team"', $relationListAsql);
        $this->assertContains(
            'LEFT JOIN "GridFieldSortableHeaderTest_TeamGroup" '
            . 'ON "GridFieldSortableHeaderTest_TeamGroup"."ID" = "GridFieldSortableHeaderTest_Team"."ID"',
            $relationListAsql
        );
        $this->assertContains(
            'LEFT JOIN "GridFieldSortableHeaderTest_Cheerleader" '
            . 'AS "cheerleader_GridFieldSortableHeaderTest_Cheerleader" '
            . 'ON "cheerleader_GridFieldSortableHeaderTest_Cheerleader"."ID" = '
            . '"GridFieldSortableHeaderTest_Team"."CheerleaderID"',
            $relationListAsql
        );
        $this->assertContains(
            'LEFT JOIN "GridFieldSortableHeaderTest_CheerleaderHat" '
            . 'AS "cheerleader_hat_GridFieldSortableHeaderTest_CheerleaderHat" '
            . 'ON "cheerleader_hat_GridFieldSortableHeaderTest_CheerleaderHat"."ID" = '
            . '"cheerleader_GridFieldSortableHeaderTest_Cheerleader"."HatID"',
            $relationListAsql
        );

        // Test sorting is correct
        $this->assertEquals(
            array('Cologne', 'Auckland', 'Wellington', 'Melbourne'),
            $relationListA->column('City')
        );
        $state->SortDirection = 'desc';
        $relationListAdesc = $component->getManipulatedData($gridField, $list);
        $this->assertEquals(
            array('Melbourne', 'Wellington', 'Auckland', 'Cologne'),
            $relationListAdesc->column('City')
        );

        // Test subclasses of tables
        $state->SortColumn = 'CheerleadersMom.Hat.Colour';
        $state->SortDirection = 'asc';
        $relationListB = $component->getManipulatedData($gridField, $list);
        $relationListBsql = $relationListB->sql();

        // Assert that subclasses are included in the query
        $this->assertContains('FROM "GridFieldSortableHeaderTest_Team"', $relationListBsql);
        $this->assertContains(
            'LEFT JOIN "GridFieldSortableHeaderTest_TeamGroup" '
            . 'ON "GridFieldSortableHeaderTest_TeamGroup"."ID" = "GridFieldSortableHeaderTest_Team"."ID"',
            $relationListBsql
        );
        // Joined tables are joined basetable first
        // Note: CheerLeader is base of Mom table, hence the alias
        $this->assertContains(
            'LEFT JOIN "GridFieldSortableHeaderTest_Cheerleader" '
            . 'AS "cheerleadersmom_GridFieldSortableHeaderTest_Cheerleader" '
            . 'ON "cheerleadersmom_GridFieldSortableHeaderTest_Cheerleader"."ID" = '
            . '"GridFieldSortableHeaderTest_Team"."CheerleadersMomID"',
            $relationListBsql
        );
        // Then the basetable of the joined record is joined to the specific subtable
        $this->assertContains(
            'LEFT JOIN "GridFieldSortableHeaderTest_Mom" '
            . 'AS "cheerleadersmom_GridFieldSortableHeaderTest_Mom" '
            . 'ON "cheerleadersmom_GridFieldSortableHeaderTest_Cheerleader"."ID" = '
            . '"cheerleadersmom_GridFieldSortableHeaderTest_Mom"."ID"',
            $relationListBsql
        );
        $this->assertContains(
            'LEFT JOIN "GridFieldSortableHeaderTest_CheerleaderHat" '
            . 'AS "cheerleadersmom_hat_GridFieldSortableHeaderTest_CheerleaderHat" '
            . 'ON "cheerleadersmom_hat_GridFieldSortableHeaderTest_CheerleaderHat"."ID" = '
            . '"cheerleadersmom_GridFieldSortableHeaderTest_Cheerleader"."HatID"',
            $relationListBsql
        );


        // Test sorting is correct
        $this->assertEquals(
            array('Cologne', 'Auckland', 'Wellington', 'Melbourne'),
            $relationListB->column('City')
        );
        $state->SortDirection = 'desc';
        $relationListBdesc = $component->getManipulatedData($gridField, $list);
        $this->assertEquals(
            array('Melbourne', 'Wellington', 'Auckland', 'Cologne'),
            $relationListBdesc->column('City')
        );
    }
}
