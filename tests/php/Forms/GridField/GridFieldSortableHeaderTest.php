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
        $relationListAsql = $relationListA->sql();

        // Assert that all tables are joined properly
        $this->assertSQLContains(sprintf('FROM %s', Convert::symbol2sql('GridFieldSortableHeaderTest_Team')), $relationListAsql);
        $this->assertSQLContains(
            sprintf('LEFT JOIN %s ON %s = %s',
                Convert::symbol2sql('GridFieldSortableHeaderTest_TeamGroup'),
                Convert::symbol2sql('GridFieldSortableHeaderTest_TeamGroup.ID'),
                Convert::symbol2sql('GridFieldSortableHeaderTest_Team.ID')
            ),
            $relationListAsql
        );
        $this->assertSQLContains(
            sprintf('LEFT JOIN %s AS %s ON %s = %s',
                Convert::symbol2sql('GridFieldSortableHeaderTest_Cheerleader'),
                Convert::symbol2sql('cheerleader_GridFieldSortableHeaderTest_Cheerleader'),
                Convert::symbol2sql('cheerleader_GridFieldSortableHeaderTest_Cheerleader.ID'),
                Convert::symbol2sql('GridFieldSortableHeaderTest_Team.CheerleaderID')
            ),
            $relationListAsql
        );
        $this->assertSQLContains(
            sprintf('LEFT JOIN %s AS %s ON %s = %s',
                Convert::symbol2sql('GridFieldSortableHeaderTest_CheerleaderHat'),
                Convert::symbol2sql('cheerleader_hat_GridFieldSortableHeaderTest_CheerleaderHat'),
                Convert::symbol2sql('cheerleader_hat_GridFieldSortableHeaderTest_CheerleaderHat.ID'),
                Convert::symbol2sql('cheerleader_GridFieldSortableHeaderTest_Cheerleader.HatID')
            ),
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
        $this->assertSQLContains(sprintf('FROM %s', Convert::symbol2sql('GridFieldSortableHeaderTest_Team')), $relationListBsql);
        $this->assertSQLContains(
            sprintf('LEFT JOIN %s ON %s = %s',
                Convert::symbol2sql('GridFieldSortableHeaderTest_TeamGroup'),
                Convert::symbol2sql('GridFieldSortableHeaderTest_TeamGroup.ID'),
                Convert::symbol2sql('GridFieldSortableHeaderTest_Team.ID')
            ),
            $relationListBsql
        );
        // Joined tables are joined basetable first
        $this->assertSQLContains(
            sprintf('LEFT JOIN %s AS %s ON %s = %s',
                Convert::symbol2sql('GridFieldSortableHeaderTest_Cheerleader'),
                Convert::symbol2sql('cheerleadersmom_GridFieldSortableHeaderTest_Cheerleader'),
                Convert::symbol2sql('cheerleadersmom_GridFieldSortableHeaderTest_Cheerleader.ID'),
                Convert::symbol2sql('GridFieldSortableHeaderTest_Team.CheerleadersMomID')
            ),
            $relationListBsql
        );
        // Then the basetable of the joined record is joined to the specific subtable
        $this->assertSQLContains(
            sprintf('LEFT JOIN %s AS %s ON %s = %s',
                Convert::symbol2sql('GridFieldSortableHeaderTest_Mom'),
                Convert::symbol2sql('cheerleadersmom_GridFieldSortableHeaderTest_Mom'),
                Convert::symbol2sql('cheerleadersmom_GridFieldSortableHeaderTest_Cheerleader.ID'),
                Convert::symbol2sql('cheerleadersmom_GridFieldSortableHeaderTest_Mom.ID')
            ),
            $relationListBsql
        );
        $this->assertSQLContains(
            sprintf('LEFT JOIN %s AS %s ON %s = %s',
                Convert::symbol2sql('GridFieldSortableHeaderTest_CheerleaderHat'),
                Convert::symbol2sql('cheerleadersmom_hat_GridFieldSortableHeaderTest_CheerleaderHat'),
                Convert::symbol2sql('cheerleadersmom_hat_GridFieldSortableHeaderTest_CheerleaderHat.ID'),
                Convert::symbol2sql('cheerleadersmom_GridFieldSortableHeaderTest_Cheerleader.HatID')
            ),
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
