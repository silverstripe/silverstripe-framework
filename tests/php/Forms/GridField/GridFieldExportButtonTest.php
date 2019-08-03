<?php

namespace SilverStripe\Forms\Tests\GridField;

use League\Csv\Reader;
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
use SilverStripe\ORM\FieldType\DBField;

class GridFieldExportButtonTest extends SapphireTest
{

    /**
     * @var DataList
     */
    protected $list;

    /**
     * @var GridField
     */
    protected $gridField;

    protected static $fixture_file = 'GridFieldExportButtonTest.yml';

    protected static $extra_dataobjects = [
        Team::class,
        NoView::class,
    ];

    protected function setUp(): void
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
        $button->setExportColumns(['Name' => 'My Name']);

        $config = GridFieldConfig::create()->addComponent(new GridFieldExportButton());
        $gridField = new GridField('testfield', 'testfield', $list, $config);

        $csvReader = Reader::createFromString($button->generateExportFileData($gridField));

        $this->assertEquals(
            "\"My Name\"\r\n",
            (string) $csvReader
        );
    }

    public function testGenerateFileDataBasicFields()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns(['Name' => 'My Name']);

        $csvReader = Reader::createFromString($button->generateExportFileData($this->gridField));

        $this->assertEquals(
            '"My Name"' . "\r\n" . 'Test' . "\r\n" . 'Test2' . "\r\n",
            (string) $csvReader
        );
    }

    public function testXLSSanitisation()
    {
        // Create risky object
        $object = new Team();
        $object->Name = '=SUM(1, 2)';
        $object->write();

        // Export
        $button = new GridFieldExportButton();
        $button->setExportColumns(['Name' => 'My Name']);

        $csvReader = Reader::createFromString($button->generateExportFileData($this->gridField));

        $this->assertEquals(
            "\"My Name\"\r\n\"\t=SUM(1, 2)\"\r\nTest\r\nTest2\r\n",
            (string) $csvReader
        );
    }

    public function testGenerateFileDataAnonymousFunctionField()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns([
            'Name' => 'Name',
            'City' => function (DBField $obj) {
                return $obj->getValue() . ' city';
            }
        ]);

        $csvReader = Reader::createFromString($button->generateExportFileData($this->gridField));

        $this->assertEquals(
            'Name,City' . "\r\n" . 'Test,"City city"' . "\r\n" . 'Test2,"Quoted ""City"" 2 city"' . "\r\n",
            (string) $csvReader
        );
    }

    public function testBuiltInFunctionNameCanBeUsedAsHeader()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns([
            'Name' => 'Name',
            'City' => 'strtolower',
        ]);

        $csvReader = Reader::createFromString($button->generateExportFileData($this->gridField));

        $this->assertEquals(
            'Name,strtolower' . "\r\n" . 'Test,City' . "\r\n" . 'Test2,"Quoted ""City"" 2"' . "\r\n",
            (string) $csvReader
        );
    }

    public function testNoCsvHeaders()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns([
            'Name' => 'Name',
            'City' => 'City',
        ]);
        $button->setCsvHasHeader(false);

        $csvReader = Reader::createFromString($button->generateExportFileData($this->gridField));

        $this->assertEquals(
            'Test,City' . "\r\n" . 'Test2,"Quoted ""City"" 2"' . "\r\n",
            (string) $csvReader
        );
    }

    public function testArrayListInput()
    {
        $button = new GridFieldExportButton();
        $this->gridField->getConfig()->addComponent(new GridFieldPaginator());

        //Create an ArrayList 1 greater the Paginator's default 15 rows
        $arrayList = new ArrayList();
        for ($i = 1; $i <= 16; $i++) {
            $dataobject = new DataObject(['ID' => $i]);
            $arrayList->add($dataobject);
        }
        $this->gridField->setList($arrayList);

        $exportData = $button->generateExportFileData($this->gridField);

        $csvReader = Reader::createFromString($exportData);

        $this->assertEquals(
            "ID\r\n" . "1\r\n" . "2\r\n" . "3\r\n" . "4\r\n" . "5\r\n" . "6\r\n" . "7\r\n" . "8\r\n"
            . "9\r\n" . "10\r\n" . "11\r\n" . "12\r\n" . "13\r\n" . "14\r\n" . "15\r\n" . "16\r\n",
            (string) $csvReader
        );
    }

    public function testZeroValue()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns([
            'RugbyTeamNumber' => 'Rugby Team Number'
        ]);

        $csvReader = Reader::createFromString($button->generateExportFileData($this->gridField));

        $this->assertEquals(
            "\"Rugby Team Number\"\r\n2\r\n0\r\n",
            (string) $csvReader
        );
    }
}
