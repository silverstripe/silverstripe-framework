<?php
/**
 * @package sapphire
 * @subpackage testing
 */

/*
$dir = dirname(dirname((__FILE__)));
$_SERVER['SCRIPT_FILENAME'] = "$dir/main.php";
chdir($dir);

require_once 'core/Core.php';
require_once("core/ManifestBuilder.php");
require_once("core/ClassInfo.php");
require_once('core/Object.php');
require_once('core/control/Director.php');
require_once('filesystem/Filesystem.php');
require_once("core/Session.php");

$envFiles = array('../_ss_environment.php', '../../_ss_environment.php', '../../../_ss_environment.php');
foreach($envFiles as $envFile) {
        if(@file_exists($envFile)) {
                include($envFile);
                break;
        }
}

require_once(MANIFEST_FILE);
print_r($_CLASS_MANIFEST);

if(ManifestBuilder::staleManifest()) ManifestBuilder::compileManifest();

require_once(MANIFEST_FILE);
*/

/**
 */
if(hasPhpUnit()) {
require_once 'PHPUnit/Framework.php';
}

/**
 * Test case class for the Sapphire framework.
 * Sapphire unit testing is based on PHPUnit, but provides a number of hooks into our data model that make it easier to work with.
 * @package sapphire
 * @subpackage testing
 */
class SapphireTest extends PHPUnit_Framework_TestCase {
	static $fixture_file = null;
	
	function setUp() {
		$className = get_class($this);
		$fixtureFile = eval("return {$className}::\$fixture_file;");

		if($fixtureFile) {
			// Create a temporary database
			$dbConn = DB::getConn();
			$dbname = 'tmpdb' . rand(1000000,9999999);
			while(!$dbname || $dbConn->databaseExists($dbname)) {
				$dbname = 'tmpdb' . rand(1000000,9999999);
			}
			$dbConn->selectDatabase($dbname);
		
			// This code is a bit misplaced; we want some way of the whole session being reinitialised...
			Versioned::reading_stage(null);

			$dbConn->createDatabase();
			singleton('DataObject')->flushCache();

			$dbadmin = new DatabaseAdmin();
			$dbadmin->doBuild(true, false);

			// Load the fixture into the database
			$className = get_class($this);
			$this->loadFixture($fixtureFile);
		}
	}
	
	/**
	 * Array of
	 */
	protected $fixtureDictionary;
	
	
	/**
	 * Get the ID of an object from the fixture.
	 * @param $className The data class, as specified in your fixture file.  Parent classes won't work
	 * @param $identifier The identifier string, as provided in your fixture file
	 */
	protected function idFromFixture($className, $identifier) {
		return $this->fixtureDictionary[$className][$identifier];
	}
	
	/**
	 * Return all of the IDs in the fixture of a particular class name.
	 * @return A map of fixture-identifier => object-id
	 */
	protected function allFixtureIDs($className) {
		return $this->fixtureDictionary[$className];
	}

	/**
	 * Get an object from the fixture.
	 * @param $className The data class, as specified in your fixture file.  Parent classes won't work
	 * @param $identifier The identifier string, as provided in your fixture file
	 */
	protected function objFromFixture($className, $identifier) {
		return DataObject::get_by_id($className, $this->idFromFixture($className, $identifier));
	}
	
	/**
	 * Load a YAML fixture file into the database.
	 * Once loaded, you can use idFromFixture() and objFromFixture() to get items from the fixture
	 * @param $fixtureFile The location of the .yml fixture file, relative to the site base dir
	 */
	function loadFixture($fixtureFile) {
		$parser = new Spyc();
		$fixtureContent = $parser->load(Director::baseFolder().'/'.$fixtureFile);
		
		$this->fixtureDictionary = array();
		
		foreach($fixtureContent as $dataClass => $items) {
			foreach($items as $identifier => $fields) {
				$obj = new $dataClass();
				foreach($fields as $fieldName => $fieldVal) {
					if($obj->many_many($fieldName) || $obj->has_many($fieldName)) {
						$parsedItems = array();
						$items = split(' *, *',trim($fieldVal));
						foreach($items as $item) {
							$parsedItems[] = $this->parseFixtureVal($item);
						}
						$obj->write();
						if($obj->many_many($fieldName)) {
							$obj->getManyManyComponents($fieldName)->setByIDList($parsedItems);
						} else {
							$obj->getComponents($fieldName)->setByIDList($parsedItems);
						}
						
					} else {
						$obj->$fieldName = $this->parseFixtureVal($fieldVal);
					}
				}
				$obj->write();
				
				// Populate the dictionary with the ID
				$this->fixtureDictionary[$dataClass][$identifier] = $obj->ID;
			}
		}
	}
	
	/**
	 * Parse a value from a fixture file.  If it starts with => it will get an ID from the fixture dictionary
	 */
	protected function parseFixtureVal($fieldVal) {
		// Parse a dictionary reference - used to set foreign keys
		if(substr($fieldVal,0,2) == '=>') {
			list($a, $b) = explode('.', substr($fieldVal,2), 2);
			return $this->fixtureDictionary[$a][$b];

		// Regular field value setting
		} else {
			return $fieldVal;
		}
	}
	
	function tearDown() {
		// Delete our temporary database
		$dbConn = DB::getConn();
		if($dbConn && substr($dbConn->currentDatabase(),0,5) == 'tmpdb') {
			$dbName = $dbConn->currentDatabase();
			if($dbName && DB::query("SHOW DATABASES LIKE '$dbName'")->value()) {
				// echo "Deleted temp database " . $dbConn->currentDatabase() . "\n";
				$dbConn->dropDatabase();
			}
		}
	}
}

?>
