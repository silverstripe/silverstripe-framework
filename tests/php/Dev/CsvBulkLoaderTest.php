<?php

namespace SilverStripe\Dev\Tests;

use League\Csv\Writer;
use SilverStripe\Dev\Tests\CsvBulkLoaderTest\CustomLoader;
use SilverStripe\Dev\Tests\CsvBulkLoaderTest\Player;
use SilverStripe\Dev\Tests\CsvBulkLoaderTest\PlayerContract;
use SilverStripe\Dev\Tests\CsvBulkLoaderTest\Team;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\Dev\SapphireTest;

class CsvBulkLoaderTest extends SapphireTest
{

    protected static $fixture_file = 'CsvBulkLoaderTest.yml';

    protected static $extra_dataobjects = array(
        Team::class,
        Player::class,
        PlayerContract::class,
    );

    /**
     * Name of csv test dir
     *
     * @var string
     */
    protected $csvPath = null;

    protected function setUp()
    {
        parent::setUp();
        $this->csvPath = __DIR__ . '/CsvBulkLoaderTest/csv/';
    }

    /**
     * Test plain import with column auto-detection
     */
    public function testLoad()
    {
        $loader = new CsvBulkLoader(Player::class);
        $filepath = $this->csvPath . 'PlayersWithHeader.csv';
        $file = fopen($filepath, 'r');
        $compareCount = $this->getLineCount($file);
        fgetcsv($file); // pop header row
        $compareRow = fgetcsv($file);
        $results = $loader->load($filepath);

        // Test that right amount of columns was imported
        $this->assertCount(5, $results, 'Test correct count of imported data');

        // Test that columns were correctly imported
        $obj = DataObject::get_one(
            Player::class,
            array(
            '"CsvBulkLoaderTest_Player"."FirstName"' => 'John'
            )
        );
        $this->assertNotNull($obj);
        $this->assertEquals("He's a good guy", $obj->Biography);
        $this->assertEquals("1988-01-31", $obj->Birthday);
        $this->assertEquals("1", $obj->IsRegistered);

        fclose($file);
    }

    /**
     * Test plain import with clear_table_before_import
         */
    public function testDeleteExistingRecords()
    {
        $loader = new CsvBulkLoader(Player::class);
        $filepath = $this->csvPath . 'PlayersWithHeader.csv';
        $loader->deleteExistingRecords = true;
        $results1 = $loader->load($filepath);
        $this->assertCount(5, $results1, 'Test correct count of imported data on first load');

        //delete existing data before doing second CSV import
        $results2 = $loader->load($filepath);
        //get all instances of the loaded DataObject from the database and count them
        $resultDataObject = DataObject::get(Player::class);

        $this->assertCount(
            5,
            $resultDataObject,
            'Test if existing data is deleted before new data is added'
        );
    }

    public function testLeadingTabs()
    {
        $loader = new CsvBulkLoader(Player::class);
        $loader->hasHeaderRow = false;
        $loader->columnMap = array(
            'FirstName',
            'Biography',
            null, // ignored column
            'Birthday',
            'IsRegistered'
        );
        $filepath = $this->csvPath . 'PlayersWithTabs.csv';
        $results = $loader->load($filepath);
        $this->assertCount(5, $results);

        $expectedBios = [
            "\tHe's a good guy",
            "=She is awesome.\nSo awesome that she gets multiple rows and \"escaped\" strings in her biography",
            "-Pretty old\, with an escaped comma",
            "@Unicode FTW",
            "+Unicode FTW",
        ];

        foreach (Player::get()->column('Biography') as $bio) {
            $this->assertContains($bio, $expectedBios);
        }

        $this->assertEquals(Player::get()->count(), count($expectedBios));
    }

    /**
     * Test import with manual column mapping
     */
    public function testLoadWithColumnMap()
    {
        $loader = new CsvBulkLoader(Player::class);
        $filepath = $this->csvPath . 'Players.csv';
        $file = fopen($filepath, 'r');
        $compareCount = $this->getLineCount($file);
        $compareRow = fgetcsv($file);
        $loader->columnMap = array(
            'FirstName',
            'Biography',
            null, // ignored column
            'Birthday',
            'IsRegistered'
        );
        $loader->hasHeaderRow = false;
        $results = $loader->load($filepath);

        // Test that right amount of columns was imported
        $this->assertCount(4, $results, 'Test correct count of imported data');

        // Test that columns were correctly imported
        $obj = DataObject::get_one(
            Player::class,
            array(
            '"CsvBulkLoaderTest_Player"."FirstName"' => 'John'
            )
        );
        $this->assertNotNull($obj);
        $this->assertEquals("He's a good guy", $obj->Biography);
        $this->assertEquals("1988-01-31", $obj->Birthday);
        $this->assertEquals("1", $obj->IsRegistered);

        $obj2 = DataObject::get_one(
            Player::class,
            array(
            '"CsvBulkLoaderTest_Player"."FirstName"' => 'Jane'
            )
        );
        $this->assertNotNull($obj2);
        $this->assertEquals('0', $obj2->IsRegistered);

        fclose($file);
    }

    /**
     * Test import with manual column mapping and custom column names
     */
    public function testLoadWithCustomHeaderAndRelation()
    {
        $loader = new CsvBulkLoader(Player::class);
        $filepath = $this->csvPath . 'PlayersWithCustomHeaderAndRelation.csv';
        $file = fopen($filepath, 'r');
        $compareCount = $this->getLineCount($file);
        fgetcsv($file); // pop header row
        $compareRow = fgetcsv($file);
        $loader->columnMap = array(
            'first name' => 'FirstName',
            'bio' => 'Biography',
            'bday' => 'Birthday',
            'teamtitle' => 'Team.Title', // test existing relation
            'teamsize' => 'Team.TeamSize', // test existing relation
            'salary' => 'Contract.Amount' // test relation creation
        );
        $loader->hasHeaderRow = true;
        $loader->relationCallbacks = array(
            'Team.Title' => array(
                'relationname' => 'Team',
                'callback' => 'getTeamByTitle'
            ),
            // contract should be automatically discovered
        );
        $results = $loader->load($filepath);

        // Test that right amount of columns was imported
        $this->assertCount(1, $results, 'Test correct count of imported data');

        // Test of augumenting existing relation (created by fixture)
        $testTeam = DataObject::get_one(Team::class, null, null, '"Created" DESC');
        $this->assertEquals('20', $testTeam->TeamSize, 'Augumenting existing has_one relation works');

        // Test of creating relation
        $testContract = DataObject::get_one(PlayerContract::class);
        $testPlayer = DataObject::get_one(
            Player::class,
            array(
            '"CsvBulkLoaderTest_Player"."FirstName"' => 'John'
            )
        );
        $this->assertEquals($testPlayer->ContractID, $testContract->ID, 'Creating new has_one relation works');

        // Test nested setting of relation properties
        $contractAmount = DBField::create_field('Currency', $compareRow[5])->RAW();
        $this->assertEquals(
            $testPlayer->Contract()->Amount,
            $contractAmount,
            'Setting nested values in a relation works'
        );

        fclose($file);
    }

    /**
     * Test import with custom identifiers by importing the data.
     *
     * @todo Test duplicateCheck callbacks
     */
    public function testLoadWithIdentifiers()
    {
        // first load
        $loader = new CsvBulkLoader(Player::class);
        $filepath = $this->csvPath . 'PlayersWithId.csv';
        $loader->duplicateChecks = array(
            'ExternalIdentifier' => 'ExternalIdentifier',
            'NonExistantIdentifier' => 'ExternalIdentifier',
            'AdditionalIdentifier' => 'ExternalIdentifier'
        );
        $results = $loader->load($filepath);
        $createdPlayers = $results->Created();

        $player = $createdPlayers->first();
        $this->assertEquals($player->FirstName, 'John');
        $this->assertEquals(
            $player->Biography,
            'He\'s a good guy',
            'test updating of duplicate imports within the same import works'
        );

        // load with updated data
        $filepath = $this->csvPath . 'PlayersWithIdUpdated.csv';
        $results = $loader->load($filepath);

        // HACK need to update the loaded record from the database
        $player = DataObject::get_by_id(Player::class, $player->ID);
        $this->assertEquals($player->FirstName, 'JohnUpdated', 'Test updating of existing records works');

        // null values are valid imported
        // $this->assertEquals($player->Biography, 'He\'s a good guy',
        //  'Test retaining of previous information on duplicate when overwriting with blank field');
    }

    public function testLoadWithCustomImportMethods()
    {
        $loader = new CustomLoader(Player::class);
        $filepath = $this->csvPath . 'PlayersWithHeader.csv';
        $loader->columnMap = array(
            'FirstName' => '->importFirstName',
            'Biography' => 'Biography',
            'Birthday' => 'Birthday',
            'IsRegistered' => 'IsRegistered'
        );
        $results = $loader->load($filepath);
        $createdPlayers = $results->Created();
        $player = $createdPlayers->first();
        $this->assertEquals('Customized John', $player->FirstName);
        $this->assertEquals("He's a good guy", $player->Biography);
        $this->assertEquals("1", $player->IsRegistered);
    }

    public function testLoadWithCustomImportMethodDuplicateMap()
    {
        $loader = new CustomLoader(Player::class);
        $filepath = $this->csvPath . 'PlayersWithHeader.csv';
        $loader->columnMap = array(
            'FirstName' => '->updatePlayer',
            'Biography' => '->updatePlayer',
            'Birthday' => 'Birthday',
            'IsRegistered' => 'IsRegistered'
        );

        $results = $loader->load($filepath);

        $createdPlayers = $results->Created();
        $player = $createdPlayers->first();

        $this->assertEquals($player->FirstName, "John. He's a good guy. ");
    }

    public function testLoadWithByteOrderMark()
    {
        $loader = new CsvBulkLoader(Player::class);
        $loader->load($this->csvPath . 'PlayersWithHeaderAndBOM.csv');

        $players = Player::get();

        $this->assertCount(3, $players);
        $this->assertListContains([
            ['FirstName' => 'Jamie', 'Birthday' => '1882-01-31'],
            ['FirstName' => 'Järg', 'Birthday' => '1982-06-30'],
            ['FirstName' => 'Jacob', 'Birthday' => '2000-04-30'],
        ], $players);
    }

    protected function getLineCount(&$file)
    {
        $i = 0;
        while (fgets($file) !== false) {
            $i++;
        }
        rewind($file);
        return $i;
    }

    public function testLargeFileSplitIntoSmallerFiles()
    {
        Config::modify()->set(CsvBulkLoader::class, 'lines', 3);

        $loader = new CsvBulkLoader(Player::class);
        $path = $this->csvPath . 'LargeListOfPlayers.csv';

        $results = $loader->load($path);

        $this->assertCount(10, $results);
    }
}
