<?php

/**
 * @package framework
 * @subpackage tests
 */
class CsvBulkLoaderTest extends SapphireTest {

	protected static $fixture_file = 'CsvBulkLoaderTest.yml';

	protected $extraDataObjects = array(
		'CsvBulkLoaderTest_Team',
		'CsvBulkLoaderTest_Player',
		'CsvBulkLoaderTest_PlayerContract',
	);

	/**
	 * Test plain import with column auto-detection
	 */
	public function testLoad() {
		$loader = new CsvBulkLoader('CsvBulkLoaderTest_Player');
		$filepath = $this->getCurrentAbsolutePath() . '/CsvBulkLoaderTest_PlayersWithHeader.csv';
		$file = fopen($filepath, 'r');
		$compareCount = $this->getLineCount($file);
		fgetcsv($file); // pop header row
		$compareRow = fgetcsv($file);
		$results = $loader->load($filepath);

		// Test that right amount of columns was imported
		$this->assertEquals(5, $results->Count(), 'Test correct count of imported data');

		// Test that columns were correctly imported
		$obj = DataObject::get_one("CsvBulkLoaderTest_Player", array(
			'"CsvBulkLoaderTest_Player"."FirstName"' => 'John'
		));
		$this->assertNotNull($obj);
		$this->assertEquals("He's a good guy", $obj->Biography);
		$this->assertEquals("1988-01-31", $obj->Birthday);
		$this->assertEquals("1", $obj->IsRegistered);

		fclose($file);
	}

	/**
	 * Test plain import with clear_table_before_import
	 	 */
	public function testDeleteExistingRecords() {
		$loader = new CsvBulkLoader('CsvBulkLoaderTest_Player');
		$filepath = $this->getCurrentAbsolutePath() . '/CsvBulkLoaderTest_PlayersWithHeader.csv';
		$loader->deleteExistingRecords = true;
		$results1 = $loader->load($filepath);
		$this->assertEquals(5, $results1->Count(), 'Test correct count of imported data on first load');

		//delete existing data before doing second CSV import
		$results2 = $loader->load($filepath, '512MB', true);
		//get all instances of the loaded DataObject from the database and count them
		$resultDataObject = DataObject::get('CsvBulkLoaderTest_Player');

		$this->assertEquals(5, $resultDataObject->Count(),
			'Test if existing data is deleted before new data is added');
		}

	/**
	 * Test import with manual column mapping
	 */
	public function testLoadWithColumnMap() {
		$loader = new CsvBulkLoader('CsvBulkLoaderTest_Player');
		$filepath = $this->getCurrentAbsolutePath() . '/CsvBulkLoaderTest_Players.csv';
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
		$obj = DataObject::get_one("CsvBulkLoaderTest_Player", array(
			'"CsvBulkLoaderTest_Player"."FirstName"' => 'John'
		));
		$this->assertNotNull($obj);
		$this->assertEquals("He's a good guy", $obj->Biography);
		$this->assertEquals("1988-01-31", $obj->Birthday);
		$this->assertEquals("1", $obj->IsRegistered);

		$obj2 = DataObject::get_one("CsvBulkLoaderTest_Player", array(
			'"CsvBulkLoaderTest_Player"."FirstName"' => 'Jane'
		));
		$this->assertNotNull($obj2);
		$this->assertEquals('0', $obj2->IsRegistered);

		fclose($file);
	}

	/**
	 * Test import with manual column mapping and custom column names
	 */
	public function testLoadWithCustomHeaderAndRelation() {
		$loader = new CsvBulkLoader('CsvBulkLoaderTest_Player');
		$filepath = $this->getCurrentAbsolutePath() . '/CsvBulkLoaderTest_PlayersWithCustomHeaderAndRelation.csv';
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
		$testTeam = DataObject::get_one('CsvBulkLoaderTest_Team', null, null, '"Created" DESC');
		$this->assertEquals('20', $testTeam->TeamSize, 'Augumenting existing has_one relation works');

		// Test of creating relation
		$testContract = DataObject::get_one('CsvBulkLoaderTest_PlayerContract');
		$testPlayer = DataObject::get_one("CsvBulkLoaderTest_Player", array(
			'"CsvBulkLoaderTest_Player"."FirstName"' => 'John'
		));
		$this->assertEquals($testPlayer->ContractID, $testContract->ID, 'Creating new has_one relation works');

		// Test nested setting of relation properties
		$contractAmount = DBField::create_field('Currency', $compareRow[5])->RAW();
		$this->assertEquals($testPlayer->Contract()->Amount, $contractAmount,
			'Setting nested values in a relation works');

		fclose($file);
	}

	/**
	 * Test import with custom identifiers by importing the data.
	 *
	 * @todo Test duplicateCheck callbacks
	 */
	public function testLoadWithIdentifiers() {
		// first load
		$loader = new CsvBulkLoader('CsvBulkLoaderTest_Player');
		$filepath = $this->getCurrentAbsolutePath() . '/CsvBulkLoaderTest_PlayersWithId.csv';
		$loader->duplicateChecks = array(
			'ExternalIdentifier' => 'ExternalIdentifier',
			'NonExistantIdentifier' => 'ExternalIdentifier',
			'ExternalIdentifier' => 'ExternalIdentifier',
			'AdditionalIdentifier' => 'ExternalIdentifier'
		);
		$results = $loader->load($filepath);
		$createdPlayers = $results->Created();

		$player = $createdPlayers->First();
		$this->assertEquals($player->FirstName, 'John');
		$this->assertEquals($player->Biography, 'He\'s a good guy',
			'test updating of duplicate imports within the same import works');

		// load with updated data
		$filepath = FRAMEWORK_PATH . '/tests/dev/CsvBulkLoaderTest_PlayersWithIdUpdated.csv';
		$results = $loader->load($filepath);

		// HACK need to update the loaded record from the database
		$player = DataObject::get_by_id('CsvBulkLoaderTest_Player', $player->ID);
		$this->assertEquals($player->FirstName, 'JohnUpdated', 'Test updating of existing records works');

		// null values are valid imported
		// $this->assertEquals($player->Biography, 'He\'s a good guy',
		//	'Test retaining of previous information on duplicate when overwriting with blank field');
	}

	public function testLoadWithCustomImportMethods() {
		$loader = new CsvBulkLoaderTest_CustomLoader('CsvBulkLoaderTest_Player');
		$filepath = $this->getCurrentAbsolutePath() . '/CsvBulkLoaderTest_PlayersWithHeader.csv';
		$loader->columnMap = array(
			'FirstName' => '->importFirstName',
			'Biography' => 'Biography',
			'Birthday' => 'Birthday',
			'IsRegistered' => 'IsRegistered'
		);
		$results = $loader->load($filepath);
		$createdPlayers = $results->Created();
		$player = $createdPlayers->First();
		$this->assertEquals($player->FirstName, 'Customized John');
		$this->assertEquals($player->Biography, "He's a good guy");
		$this->assertEquals($player->IsRegistered, "1");
	}

	public function testLoadWithCustomImportMethodDuplicateMap() {
		$loader = new CsvBulkLoaderTest_CustomLoader('CsvBulkLoaderTest_Player');
		$filepath = $this->getCurrentAbsolutePath() . '/CsvBulkLoaderTest_PlayersWithHeader.csv';
		$loader->columnMap = array(
			'FirstName' => '->updatePlayer',
			'Biography' => '->updatePlayer',
			'Birthday' => 'Birthday',
			'IsRegistered' => 'IsRegistered'
		);

		$results = $loader->load($filepath);

		$createdPlayers = $results->Created();
		$player = $createdPlayers->First();

		$this->assertEquals($player->FirstName, "John. He's a good guy. ");
	}


	protected function getLineCount(&$file) {
		$i = 0;
		while(fgets($file) !== false) $i++;
		rewind($file);
		return $i;
	}

	public function testLargeFileSplitIntoSmallerFiles() {
		Config::inst()->update('CsvBulkLoader', 'lines', 3);

		$loader = new CsvBulkLoader('CsvBulkLoaderTest_Player');
		$path = $this->getCurrentAbsolutePath() . '/CsvBulkLoaderTest_LargeListOfPlayers.csv';

		$results = $loader->load($path);

		$this->assertEquals(10, $results->Count());
	}

}

class CsvBulkLoaderTest_CustomLoader extends CsvBulkLoader implements TestOnly {
	public function importFirstName(&$obj, $val, $record) {
		$obj->FirstName = "Customized {$val}";
	}

	public function updatePlayer(&$obj, $val, $record) {
		$obj->FirstName .= $val . '. ';
	}
}

class CsvBulkLoaderTest_Team extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar(255)',
		'TeamSize' => 'Int',
	);

	private static $has_many = array(
		'Players' => 'CsvBulkLoaderTest_Player',
	);

}

class CsvBulkLoaderTest_Player extends DataObject implements TestOnly {

	private static $db = array(
		'FirstName' => 'Varchar(255)',
		'Biography' => 'HTMLText',
		'Birthday' => 'Date',
		'ExternalIdentifier' => 'Varchar(255)', // used for uniqueness checks on passed property
		'IsRegistered' => 'Boolean'
	);

	private static $has_one = array(
		'Team' => 'CsvBulkLoaderTest_Team',
		'Contract' => 'CsvBulkLoaderTest_PlayerContract'
	);

	public function getTeamByTitle($title) {
		return DataObject::get_one("CsvBulkLoaderTest_Team", array(
			'"CsvBulkLoaderTest_Team"."Title"' => $title
		));
	}

	/**
	 * Custom setter for "Birthday" property when passed/imported
	 * in different format.
	 *
	 * @param string $val
	 * @param array $record
	 */
	public function setUSBirthday($val, $record = null) {
		$this->Birthday = preg_replace('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-90-9]{2,4})/', '\\3-\\1-\\2', $val);
	}
}


class CsvBulkLoaderTest_PlayerContract extends DataObject implements TestOnly {
	private static $db = array(
		'Amount' => 'Currency',
	);
}


