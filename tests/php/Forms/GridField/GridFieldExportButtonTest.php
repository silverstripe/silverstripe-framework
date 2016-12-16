<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Forms\Tests\GridField\GridFieldExportButtonTest\NoView;
use SilverStripe\Forms\Tests\GridField\GridFieldExportButtonTest\Team;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldPaginator;

class GridFieldExportButtonTest extends SapphireTest
{

    protected $list;

    protected $gridField;

    protected $form;

    protected static $fixture_file = 'GridFieldExportButtonTest.yml';

    protected $extraDataObjects = array(
        Team::class,
        NoView::class
    );

    public function setUp()
    {
        parent::setUp();

        $this->list = new DataList(Team::class);
        $this->list = $this->list->sort('Name');
        $config = GridFieldConfig::create()->addComponent(new GridFieldExportButton());
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
    }

    public function testCanView()
    {
        $list = new DataList(NoView::class);

        $button = new GridFieldExportButton();
        $button->setExportColumns(array('Name' => 'My Name'));

        $config = GridFieldConfig::create()->addComponent(new GridFieldExportButton());
        $gridField = new GridField('testfield', 'testfield', $list, $config);

        $this->assertEquals(
            "\"My Name\"\n",
            $button->generateExportFileData($gridField)
        );
    }

    public function testGenerateFileDataBasicFields()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns(array('Name' => 'My Name'));

        $this->assertEquals(
            '"My Name"'."\n".
            'Test'."\n".
            'Test2'."\n",
            $button->generateExportFileData($this->gridField)
        );
    }

    public function testGenerateFileDataAnonymousFunctionField()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns(
            array(
            'Name' => 'Name',
            'City' => function ($obj) {
                return $obj->getValue() . ' city';
            }
            )
        );

        $this->assertEquals(
            'Name,City'."\n".
            'Test,"City city"'."\n".
            'Test2,"Quoted ""City"" 2 city"'."\n",
            $button->generateExportFileData($this->gridField)
        );
    }

    public function testBuiltInFunctionNameCanBeUsedAsHeader()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns(
            array(
            'Name' => 'Name',
            'City' => 'strtolower'
            )
        );

        $this->assertEquals(
            'Name,strtolower'."\n".
            'Test,City'."\n".
            'Test2,"Quoted ""City"" 2"'."\n",
            $button->generateExportFileData($this->gridField)
        );
    }

    public function testNoCsvHeaders()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns(
            array(
            'Name' => 'Name',
            'City' => 'City'
            )
        );
        $button->setCsvHasHeader(false);

        $this->assertEquals(
            'Test,City'."\n".
            'Test2,"Quoted ""City"" 2"'."\n",
            $button->generateExportFileData($this->gridField)
        );
    }

    public function testArrayListInput()
    {
        $button = new GridFieldExportButton();
        $this->gridField->getConfig()->addComponent(new GridFieldPaginator());

        //Create an ArrayList 1 greater the Paginator's default 15 rows
        $arrayList = new ArrayList();
        for ($i = 1; $i <= 16; $i++) {
            $dataobject = new DataObject(
                array ( 'ID' => $i )
            );
            $arrayList->add($dataobject);
        }
        $this->gridField->setList($arrayList);

        $this->assertEquals(
            "ID\n".
            "1\n".
            "2\n".
            "3\n".
            "4\n".
            "5\n".
            "6\n".
            "7\n".
            "8\n".
            "9\n".
            "10\n".
            "11\n".
            "12\n".
            "13\n".
            "14\n".
            "15\n".
            "16\n",
            $button->generateExportFileData($this->gridField)
        );
    }

    public function testZeroValue()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns(
            array(
            'RugbyTeamNumber' => 'Rugby Team Number'
            )
        );

        $this->assertEquals(
            "\"Rugby Team Number\"\n2\n0\n",
            $button->generateExportFileData($this->gridField)
        );
    }
}
