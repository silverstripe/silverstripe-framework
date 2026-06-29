<?php

namespace SilverStripe\Forms\Tests\GridField;

use League\Csv\Reader;
use LogicException;
use ReflectionMethod;
use SilverStripe\Forms\Tests\GridField\GridFieldExportButtonTest\NoView;
use SilverStripe\Forms\Tests\GridField\GridFieldExportButtonTest\Team;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ArrayData;

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

    /**
     * @var GridFieldConfig
     */
    protected $gridFieldConfig;

    /**
     * @var GridFieldExportButton
     */
    protected $exportButton;

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
        $this->gridFieldConfig = GridFieldConfig::create()->addComponent(
            $this->exportButton = new GridFieldExportButton()
        );
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $this->gridFieldConfig);
    }

    public function testCanView()
    {
        $list = new DataList(NoView::class);

        $button = new GridFieldExportButton();
        $button->setExportColumns(['Name' => 'My Name']);

        $config = GridFieldConfig::create()->addComponent(new GridFieldExportButton());
        $gridField = new GridField('testfield', 'testfield', $list, $config);

        $csvReader = $this->createReader($button->generateExportFileData($gridField));
        $bom = $csvReader->getInputBOM();

        $this->assertEquals(
            "$bom\"My Name\"\r\n",
            (string) $csvReader
        );
    }

    public function testGenerateFileDataBasicFields()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns(['Name' => 'My Name']);

        $csvReader = $this->createReader($button->generateExportFileData($this->gridField));
        $bom = $csvReader->getInputBOM();

        $this->assertEquals(
            $bom . '"My Name"' . "\r\n" . 'Test' . "\r\n" . 'Test2' . "\r\n",
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

        $csvReader = $this->createReader($button->generateExportFileData($this->gridField));
        $bom = $csvReader->getInputBOM();

        $this->assertEquals(
            "$bom\"My Name\"\r\n\"\t=SUM(1, 2)\"\r\nTest\r\nTest2\r\n",
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

        $csvReader = $this->createReader($button->generateExportFileData($this->gridField));
        $bom = $csvReader->getInputBOM();

        $this->assertEquals(
            $bom . 'Name,City' . "\r\n" . 'Test,"City city"' . "\r\n" . 'Test2,"Quoted ""City"" 2 city"' . "\r\n",
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

        $csvReader = $this->createReader($button->generateExportFileData($this->gridField));
        $bom = $csvReader->getInputBOM();

        $this->assertEquals(
            $bom . 'Name,strtolower' . "\r\n" . 'Test,City' . "\r\n" . 'Test2,"Quoted ""City"" 2"' . "\r\n",
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

        $csvReader = $this->createReader($button->generateExportFileData($this->gridField));
        $bom = $csvReader->getInputBOM();

        $this->assertEquals(
            $bom . 'Test,City' . "\r\n" . 'Test2,"Quoted ""City"" 2"' . "\r\n",
            (string) $csvReader
        );
    }

    public function testArrayListInput()
    {
        $button = new GridFieldExportButton();
        $columns = new GridFieldDataColumns();
        $columns->setDisplayFields(['ID' => 'ID']);
        $this->gridFieldConfig->addComponent($columns);
        $this->gridFieldConfig->addComponent(new GridFieldPaginator());

        //Create an ArrayList 1 greater the Paginator's default 15 rows
        $arrayList = new ArrayList();
        for ($i = 1; $i <= 16; $i++) {
            $datum = new ArrayData(['ID' => $i]);
            $arrayList->add($datum);
        }
        $this->gridField->setList($arrayList);

        $exportData = $button->generateExportFileData($this->gridField);

        $csvReader = $this->createReader($exportData);
        $bom = $csvReader->getInputBOM();

        $this->assertEquals(
            $bom . "ID\r\n" . "1\r\n" . "2\r\n" . "3\r\n" . "4\r\n" . "5\r\n" . "6\r\n" . "7\r\n" . "8\r\n" . "9\r\n" . "10\r\n" . "11\r\n" . "12\r\n" . "13\r\n" . "14\r\n" . "15\r\n" . "16\r\n",
            (string) $csvReader
        );
    }

    public function testZeroValue()
    {
        $button = new GridFieldExportButton();
        $button->setExportColumns([
            'RugbyTeamNumber' => 'Rugby Team Number'
        ]);

        $csvReader = $this->createReader($button->generateExportFileData($this->gridField));
        $bom = $csvReader->getInputBOM();

        $this->assertEquals(
            "$bom\"Rugby Team Number\"\r\n2\r\n0\r\n",
            (string) $csvReader
        );
    }

    public function testGetExportColumnsForGridFieldThrowsException()
    {
        $component = new GridFieldExportButton();
        $gridField = new GridField('dummy', 'dummy', new ArrayList());
        $gridField->getConfig()->removeComponentsByType(GridFieldDataColumns::class);
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot dynamically determine columns. Add a GridFieldDataColumns component to your GridField'
            . " or implement a summaryFields() method on $modelClass"
        );

        $reflectionMethod = new ReflectionMethod($component, 'getExportColumnsForGridField');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($component, $gridField);
    }

    public function testSetExportFileName()
    {
        $this->exportButton->setExportFileName('export.csv');

        $this->assertEquals(
            'export.csv',
            $this->exportButton->getExportFileName($this->gridField)
        );

        $this->exportButton->setExportFileName('[classname]-export.csv');

        $this->assertEquals(
            'team-export.csv',
            $this->exportButton->getExportFileName($this->gridField)
        );

        $mockDate = '2024-12-31 22:10:59';
        DBDatetime::set_mock_now($mockDate);
        
        $this->exportButton->setExportFileName('export-[timestamp].csv');
        
        $this->assertEquals(
            'export-2024-12-31-22-10-59.csv',
            $this->exportButton->getExportFileName($this->gridField)
        );
        
        $this->exportButton->setExportFileName('[classname]-export-[timestamp].csv');
        
        $this->assertEquals(
            'team-export-2024-12-31-22-10-59.csv',
            $this->exportButton->getExportFileName($this->gridField)
        );

        DBDatetime::clear_mock_now();
    }

    public function testSetTimeStampFormat()
    {
        $mockDate = '2024-12-31 22:10:59';
        DBDatetime::set_mock_now($mockDate);
        
        $this->exportButton->setTimeStampFormat('yyyyMMdd-HHmmss');
        
        $this->assertEquals(
            'team-export-20241231-221059.csv',
            $this->exportButton->getExportFileName($this->gridField)
        );
        
        $this->exportButton->setTimeStampFormat('dd-MM-yyyy');
        
        $this->assertEquals(
            'team-export-31-12-2024.csv',
            $this->exportButton->getExportFileName($this->gridField)
        );

        DBDatetime::clear_mock_now();
    }

    protected function createReader($string)
    {
        $reader = Reader::createFromString($string);

        // Explicitly set the output BOM in league/csv 9
        if (method_exists($reader, 'getContent')) {
            $reader->setOutputBOM(Reader::BOM_UTF8);
        }

        return $reader;
    }
}
