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
use SilverStripe\Forms\Tests\GridField\GridFieldPaginatorTest\CanViewCheckObject;
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
    protected static $extra_dataobjects = [
        Team::class,
        Player::class,
        Cheerleader::class,
        CanViewCheckObject::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->list = new DataList(Team::class);
        $config = GridFieldConfig::create()->addComponents(
            new GridFieldToolbarHeader(), // Required to support pagecount
            new GridFieldPaginator(2),
            new GridFieldPageCount('toolbar-header-right')
        );
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
        $this->form = new Form(null, 'mockform', new FieldList([$this->gridField]), new FieldList());
    }

    public function testThereIsPaginatorWhenMoreThanOnePage()
    {
        $fieldHolder = $this->gridField->FieldHolder();
        $content = new CSSContentParser($fieldHolder);
        // Check that there is paginator render into the footer
        $this->assertEquals(1, count($content->getBySelector('.datagrid-pagination') ?? []));

        // Check that the header and footer both contains a page count
        $this->assertEquals(2, count($content->getBySelector('.pagination-records-number') ?? []));
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
        $this->assertEquals(0, count($content->getBySelector('.datagrid-pagination') ?? []));

        // Check that there is still 'View 1 - 4 of 4' part on the left of the paginator
        $this->assertEquals(2, count($content->getBySelector('.pagination-records-number') ?? []));
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

    public function providePaginationInteractionWithCanview()
    {
        return [
            'defaults to paginate before canView' => [
                'defaultCanview' => null,
                'instanceCanview' => null,
                // 30 items total because the canview check hadn't been run when the pagination was generated
                'recordsNumber' => 'View 1–5 of 30',
                // The manipulated list has been limited to the first page aka 5 items
                'manipulatedListCount' => 5,
                // Canview is run after pagination, so the list is limited to one page of 5 items and then 2 are
                // removed for failing the canView check.
                'listForDisplayCount' => 3,
            ],
            'property overrides config' => [
                'defaultCanview' => true,
                'instanceCanview' => false,
                'recordsNumber' => 'View 1–5 of 30',
                'manipulatedListCount' => 5,
                'listForDisplayCount' => 3,
            ],
            'paginate after canView via config' => [
                'defaultCanview' => true,
                'instanceCanview' => null,
                // 15 items total because the canview fails for every second item
                'recordsNumber' => 'View 1–5 of 15',
                // The manipulated list has not yet been limited to the first page, so still shows the total
                'manipulatedListCount' => 30,
                // The display list shows the first page, which is 5 items that can be viewed
                'listForDisplayCount' => 5,
            ],
            'paginate after canView via property' => [
                'defaultCanview' => false,
                'instanceCanview' => true,
                'recordsNumber' => 'View 1–5 of 15',
                'manipulatedListCount' => 30,
                'listForDisplayCount' => 5,
            ],
        ];
    }

    /**
     * @dataProvider providePaginationInteractionWithCanview
     */
    public function testPaginationInteractionWithCanview(
        ?bool $defaultCanview,
        ?bool $instanceCanview,
        string $recordsNumber,
        int $manipulatedListCount,
        int $listForDisplayCount
    ) {
        if ($defaultCanview !== null) {
            GridFieldPaginator::config()->set('default_paginate_after_canview', $defaultCanview);
        }
        $gridField = $this->getCanViewTestGridField();
        if ($instanceCanview !== null) {
            $gridField->getConfig()->getComponentByType(GridFieldPaginator::class)->setFilterAfterCanview($instanceCanview);
        }
        $fieldHolder = $gridField->FieldHolder();
        $content = new CSSContentParser($fieldHolder);

        $this->assertSame($recordsNumber, $content->getBySelector('.pagination-records-number')[0]->__toString());
        $this->assertCount($manipulatedListCount, $gridField->getManipulatedList());
        $this->assertCount($listForDisplayCount, $gridField->getListForDisplay());
    }

    private function getCanViewTestGridField(): GridField
    {
        // 30 items
        for ($i = 1; $i <= 30; $i++) {
            $obj = new CanViewCheckObject();
            $obj->Name = "Object {$i}";
            $obj->write();
        }

        $list = CanViewCheckObject::get();
        $config = GridFieldConfig::create()->addComponents(
            new GridFieldToolbarHeader(), // Required to support pagecount
            new GridFieldPaginator(5),
            new GridFieldPageCount('toolbar-header-right')
        );
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        new Form(null, 'mockform', new FieldList([$gridField]), new FieldList());

        return $gridField;
    }
}
