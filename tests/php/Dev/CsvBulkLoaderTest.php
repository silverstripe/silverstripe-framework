<?php

namespace SilverStripe\Dev\Tests;

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
        $this->assertEquals(5, $results->Count(), 'Test correct count of imported data');

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
        $this->assertEquals(5, $results1->Count(), 'Test correct count of imported data on first load');

        //delete existing data before doing second CSV import
        $results2 = $loader->load($filepath);
        //get all instances of the loaded DataObject from the database and count them
        $resultDataObject = DataObject::get(Player::class);

        $this->assertEquals(
            5,
            $resultDataObject->count(),
            'Test if existing data is deleted before new data is added'
        );
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
        $this->assertEquals(4, $results->Count(), 'Test correct count of imported data');

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
        $this->assertEquals(1, $results->Count(), 'Test correct count of imported data');

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
        $this->assertEquals($player->FirstName, 'Customized John');
        $this->assertEquals($player->Biography, "He's a good guy");
        $this->assertEquals($player->IsRegistered, "1");
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

        $this->assertEquals(10, $results->Count());
    }
}
