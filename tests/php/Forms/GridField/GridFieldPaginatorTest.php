<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

class GridFieldPaginatorTest extends FunctionalTest
{
    /**
     * @var ArrayList
     */
    protected $list;

    /**
     * @var GridField
     */
    protected $gridField;

    /**
     * @var string
     */
    protected static $fixture_file = 'GridFieldTest.yml';

    /**
     * @var Form
     */
    protected $form;

    /**
     * @var array
     */
    protected static $extra_dataobjects = array(
        Team::class,
        Player::class,
        Cheerleader::class,
    );

    protected function setUp()
    {
        parent::setUp();
        $this->list = new DataList(Team::class);
        $config = GridFieldConfig::create()->addComponents(
            new GridFieldToolbarHeader(), // Required to support pagecount
            new GridFieldPaginator(2),
            new GridFieldPageCount('toolbar-header-right')
        );
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
        $this->form = new Form(null, 'mockform', new FieldList(array($this->gridField)), new FieldList());
    }

    public function testThereIsPaginatorWhenMoreThanOnePage()
    {
        $fieldHolder = $this->gridField->FieldHolder();
        $content = new CSSContentParser($fieldHolder);
        // Check that there is paginator render into the footer
        $this->assertEquals(1, count($content->getBySelector('.datagrid-pagination')));

        // Check that the header and footer both contains a page count
        $this->assertEquals(2, count($content->getBySelector('.pagination-records-number')));
    }

    public function testThereIsNoPaginatorWhenOnlyOnePage()
    {
        // We set the itemsPerPage to an reasonably big number so as to avoid test broke from small changes
        // on the fixture YML file
        $total = $this->list->count();
        $this->gridField->getConfig()->getComponentByType(GridFieldPaginator::class)->setItemsPerPage($total);
        $fieldHolder = $this->gridField->FieldHolder();
        $content = new CSSContentParser($fieldHolder);

        // Check that there is no paginator render into the footer
        $this->assertEquals(0, count($content->getBySelector('.datagrid-pagination')));

        // Check that there is still 'View 1 - 4 of 4' part on the left of the paginator
        $this->assertEquals(2, count($content->getBySelector('.pagination-records-number')));
    }

    public function testUnlimitedRowCount()
    {
        $total = $this->list->count();
        // set up the paginator
        /** @var GridFieldPaginator $paginator */
        $paginator = $this->gridField->getConfig()->getComponentByType(GridFieldPaginator::class);
        $paginator->setThrowExceptionOnBadDataType(true);
        $paginator->setItemsPerPage(1);
        $paginator->getManipulatedData($this->gridField, $this->list);


        $params = $paginator->getTemplateParameters($this->gridField)->toMap();
        $this->assertFalse($params['OnlyOnePage']);
        $this->assertEquals($total, $params['NumRecords']);

        $paginator->setItemsPerPage(0);
        $params = $paginator->getTemplateParameters($this->gridField)->toMap();
        $this->assertTrue($params['OnlyOnePage']);
        $this->assertEquals($total, $params['NumRecords']);
    }

    public function testPaginationAvoidsIllegalOffsets()
    {
        $grid = $this->gridField;
        $total = $this->list->count();
        $perPage = $grid->getConfig()->getComponentByType(GridFieldPaginator::class)->getItemsPerPage();
        // Get the last page that will contain results
        $lastPage = ceil($total / $perPage);
        // Set the paginator state to point to an 'invalid' page
        $grid->State->GridFieldPaginator->currentPage = $lastPage + 1;

        // Get the paginated list
        $list = $grid->getManipulatedList();

        // Assert that the paginator state has been corrected and the list contains items
        $this->assertEquals(1, $grid->State->GridFieldPaginator->currentPage);
        $this->assertEquals($perPage, $list->count());
    }
}
