<?php
// Include the DB class
require_once("core/model/DB.php");

/**
 * DatabaseAdmin class
 *
 * Utility functions for administrating the database. These can be accessed
 * via URL, e.g. http://www.yourdomain.com/db/build.
 * @package sapphire
 * @subpackage model
 */
class DatabaseAdmin extends Controller {

	/// SECURITY ///
	static $allowed_actions = array(
		'build',
		'cleanup',
		'testinstall',
		'import'
	);
	
	function init() {
		parent::init();
		
		// We allow access to this controller regardless of live-status or ADMIN permission only
		// if on CLI or with the database not ready. The latter makes it less errorprone to do an
		// initial schema build without requiring a default-admin login.
		// Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
		$canAccess = (
			Director::isDev() 
			|| !Security::database_is_ready() 
			|| Director::is_cli() 
			|| Permission::check("ADMIN")
		);
		if(!$canAccess) {
			return Security::permissionFailure($this,
				"This page is secured and you need administrator rights to access it. " .
				"Enter your credentials below and we will send you right along.");
		}
	}

	/**
	 * Get the data classes, grouped by their root class
	 *
	 * @return array Array of data classes, grouped by their root class
	 */
	function groupedDataClasses() {
		// Get all root data objects
		$allClasses = get_declared_classes();
		foreach($allClasses as $class) {
			if(get_parent_class($class) == "DataObject")
				$rootClasses[$class] = array();
		}

		// Assign every other data object one of those
		foreach($allClasses as $class) {
			if(!isset($rootClasses[$class]) && is_subclass_of($class, "DataObject")) {
				foreach($rootClasses as $rootClass => $dummy) {
					if(is_subclass_of($class, $rootClass)) {
						$rootClasses[$rootClass][] = $class;
						break;
					}
				}
			}
		}
		return $rootClasses;
	}


	/**
	 * Display a simple HTML menu of database admin helpers.
	 */
	function index() {
		echo "<h2>Database Administration Helpers</h2>";
		echo "<p><a href=\"build\">Add missing database fields (similar to sanity check).</a></p>";
		echo "<p><a href=\"../images/flush\">Flush <b>all</b> of the generated images.</a></p>";
	}


	/**
	 * Updates the database schema, creating tables & fields as necessary.
	 */
	function build() {
		// The default time limit of 30 seconds is normally not enough
		if(ini_get("safe_mode") != "1") {
			set_time_limit(600);
		}

		// Get all our classes
		ManifestBuilder::create_manifest_file();
		require(MANIFEST_FILE);
		
		if(isset($_GET['returnURL'])) {
			echo "<p>Setting up the database; you will be returned to your site shortly....</p>";
			$this->doBuild(true);
			echo "<p>Done!</p>";
			Director::redirect($_GET['returnURL']);
		} else {
			$this->doBuild(isset($_REQUEST['quiet']) || isset($_REQUEST['from_installer']), !isset($_REQUEST['dont_populate']));
		}
	}

	/**
	 * Check if database needs to be built, and build it if it does.
	 */
	static function autoBuild() {
		$dataClasses = ClassInfo::subclassesFor('DataObject');
		$lastBuilt = self::lastBuilt();
		foreach($dataClasses as $class) {
			if(filemtime(getClassFile($class)) > $lastBuilt) {
				$da = new DatabaseAdmin();
				$da->doBuild(true);
				return;
			}
		}
	}


	/**
	 * Returns the timestamp of the time that the database was last built
	 *
	 * @return string Returns the timestamp of the time that the database was
	 *                last built
	 */
	static function lastBuilt() {
		$file = TEMP_FOLDER . '/database-last-generated-' .
			str_replace(array('\\','/',':'), '.' , Director::baseFolder());

		if(file_exists($file)) {
			return filemtime($file);
		}
	}


	/**
	 * Updates the database schema, creating tables & fields as necessary.
	 *
	 * @param boolean $quiet Don't show messages
	 * @param boolean $populate Populate the database, as well as setting up its schema
	 */
	function doBuild($quiet = false, $populate = true, $testMode = false) {
		if($quiet) {
			DB::quiet();
		} else {
			$conn = DB::getConn();
			$databaseName = ($conn->hasMethod('currentDatabase')) ? $conn->currentDatabase() : "";
			
			if(Director::is_cli()) echo "\n\nBuilding Database $databaseName\n\n";
			else echo "<h2>Building Database $databaseName</h2>";
		}

		// Set up the initial database
		if(!DB::isActive()) {
			if(!$quiet) {
				echo '<p><b>Creating database</b></p>';
			}
			global $databaseConfig;
			$parameters = $databaseConfig ? $databaseConfig : $_REQUEST['db'];
			$connect = DB::getConnect($parameters);
			$username = $parameters['username'];
			$password = $parameters['password'];
			$database = $parameters['database'];

			if(!$database) {
				user_error("No database name given; please give a value for \$databaseConfig['database']", E_USER_ERROR);
			}

			DB::createDatabase($connect, $username, $password, $database);
			// ManifestBuilder::compileManifest();
		}

		// Build the database.  Most of the hard work is handled by DataObject
		$dataClasses = ClassInfo::subclassesFor('DataObject');
		array_shift($dataClasses);

		if(!$quiet) {
			if(Director::is_cli()) echo "\nCREATING DATABASE TABLES\n\n";
			else echo "\n<p><b>Creating database tables</b></p>\n\n";
		}

		$conn = DB::getConn();
		$conn->beginSchemaUpdate();
		foreach($dataClasses as $dataClass) {
			// Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
			if(class_exists($dataClass)) {
				$SNG = singleton($dataClass);
				if($testMode || !($SNG instanceof TestOnly)) {
					if(!$quiet) {
						if(Director::is_cli()) echo " * $dataClass\n";
						else echo "<li>$dataClass</li>\n";
					}
					$SNG->requireTable();
				}
			}
		}
		$conn->endSchemaUpdate();

		if($populate) {
			if(!$quiet) {
				if(Director::is_cli()) echo "\nCREATING DATABASE RECORDS\n\n";
				else echo "\n<p><b>Creating database records</b></p>\n\n";
			}

			foreach($dataClasses as $dataClass) {
				// Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
				// Test_ indicates that it's the data class is part of testing system
				if(strpos($dataClass,'Test_') === false && class_exists($dataClass)) {
					if(!$quiet) {
						if(Director::is_cli()) echo " * $dataClass\n";
						else echo "<li>$dataClass</li>\n";
					}

					singleton($dataClass)->requireDefaultRecords();
				}
			}
		}

		touch(TEMP_FOLDER . '/database-last-generated-' .
					str_replace(array('\\', '/', ':'), '.', Director::baseFolder()));

		if(isset($_REQUEST['from_installer'])) {
			echo "OK";
		}
	}
	
	/**
	 * Clear all data out of the database
	 */
	function clearAllData() {
		$tables = DB::query("SHOW TABLES")->column();
		foreach($tables as $table) {
			DB::query("TRUNCATE `$table`");
		}
	}


	/**
	 * Method used to check mod_rewrite is working correctly in the installer.
	 */
	function testinstall() {
		echo "OK";
	}


	/**
	 * Remove invalid records from tables - that is, records that don't have
	 * corresponding records in their parent class tables.
	 */
	function cleanup() {
		$allClasses = get_declared_classes();
		foreach($allClasses as $class) {
			if(get_parent_class($class) == 'DataObject') {
				$baseClasses[] = $class;
			}
		}

		foreach($baseClasses as $baseClass) {
			// Get data classes
			$subclasses = ClassInfo::subclassesFor($baseClass);
			unset($subclasses[0]);
			foreach($subclasses as $k => $subclass) {
				if(!singleton($subclass)->databaseFields()) {
					unset($subclasses[$k]);
				}
			}

			if($subclasses) {
				$records = DB::query("SELECT * FROM `$baseClass`");


				foreach($subclasses as $subclass) {
					$recordExists[$subclass] =
						DB::query("SELECT ID FROM `$subclass")->keyedColumn();
				}

				foreach($records as $record) {
					foreach($subclasses as $subclass) {
						$id = $record['ID'];
						if(($record['ClassName'] != $subclass) &&
							 (!is_subclass_of($record['ClassName'], $subclass)) &&
							 ($recordExists[$subclass][$id])) {
							$sql = "DELETE FROM `$subclass` WHERE ID = $record[ID]";
							echo "<li>$sql";
							DB::query($sql);
						}
					}
				}
			}
		}
	}

}

?>
