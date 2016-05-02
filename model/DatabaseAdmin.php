<?php
// Include the DB class
require_once("model/DB.php");

/**
 * DatabaseAdmin class
 *
 * Utility functions for administrating the database. These can be accessed
 * via URL, e.g. http://www.yourdomain.com/db/build.
 *
 * @package framework
 * @subpackage model
 */
class DatabaseAdmin extends Controller {

	/// SECURITY ///
	private static $allowed_actions = array(
		'index',
		'build',
		'cleanup',
		'import'
	);

	public function init() {
		parent::init();

		// We allow access to this controller regardless of live-status or ADMIN permission only
		// if on CLI or with the database not ready. The latter makes it less errorprone to do an
		// initial schema build without requiring a default-admin login.
		// Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
		$isRunningTests = (class_exists('SapphireTest', false) && SapphireTest::is_running_test());
		$canAccess = (
			Director::isDev()
			|| !Security::database_is_ready()
			// We need to ensure that DevelopmentAdminTest can simulate permission failures when running
			// "dev/tests" from CLI.
			|| (Director::is_cli() && !$isRunningTests)
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
	public function groupedDataClasses() {
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
	 * When we're called as /dev/build, that's actually the index. Do the same
	 * as /dev/build/build.
	 */
	public function index() {
		return $this->build();
	}

	/**
	 * Updates the database schema, creating tables & fields as necessary.
	 */
	public function build() {
		// The default time limit of 30 seconds is normally not enough
		increase_time_limit_to(600);

		// Get all our classes
		SS_ClassLoader::instance()->getManifest()->regenerate();

		$url = $this->getReturnURL();
		if($url) {
			echo "<p>Setting up the database; you will be returned to your site shortly....</p>";
			$this->doBuild(true);
			echo "<p>Done!</p>";
			$this->redirect($url);
		} else {
			$quiet = $this->request->requestVar('quiet') !== null;
			$fromInstaller = $this->request->requestVar('from_installer') !== null;
			$populate = $this->request->requestVar('dont_populate') === null;
			$this->doBuild($quiet || $fromInstaller, $populate);
		}
	}

	/**
	 * Gets the url to return to after build
	 *
	 * @return string|null
	 */
	protected function getReturnURL() {
		$url = $this->request->getVar('returnURL');

		// Check that this url is a site url
		if(empty($url) || !Director::is_site_url($url)) {
			return null;
		}

		// Convert to absolute URL
		return Director::absoluteURL($url, true);
	}

	/**
	 * Check if database needs to be built, and build it if it does.
	 */
	public static function autoBuild() {
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
	 * Build the default data, calling requireDefaultRecords on all
	 * DataObject classes
	 */
	public function buildDefaults() {
		$dataClasses = ClassInfo::subclassesFor('DataObject');
		array_shift($dataClasses);
		foreach($dataClasses as $dataClass){
			singleton($dataClass)->requireDefaultRecords();
			print "Defaults loaded for $dataClass<br/>";
		}
	}

	/**
	 * Returns the timestamp of the time that the database was last built
	 *
	 * @return string Returns the timestamp of the time that the database was
	 *                last built
	 */
	public static function lastBuilt() {
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
	public function doBuild($quiet = false, $populate = true, $testMode = false) {
		if($quiet) {
			DB::quiet();
		} else {
			$conn = DB::get_conn();
			// Assumes database class is like "MySQLDatabase" or "MSSQLDatabase" (suffixed with "Database")
			$dbType = substr(get_class($conn), 0, -8);
			$dbVersion = $conn->getVersion();
			$databaseName = (method_exists($conn, 'currentDatabase')) ? $conn->getSelectedDatabase() : "";

			if(Director::is_cli()) {
				echo sprintf("\n\nBuilding database %s using %s %s\n\n", $databaseName, $dbType, $dbVersion);
			} else {
				echo sprintf("<h2>Building database %s using %s %s</h2>", $databaseName, $dbType, $dbVersion);
			}
		}

		// Set up the initial database
		if(!DB::is_active()) {
			if(!$quiet) {
				echo '<p><b>Creating database</b></p>';
			}

			// Load parameters from existing configuration
			global $databaseConfig;
			if(empty($databaseConfig) && empty($_REQUEST['db'])) {
				user_error("No database configuration available", E_USER_ERROR);
			}
			$parameters = (!empty($databaseConfig)) ? $databaseConfig : $_REQUEST['db'];

			// Check database name is given
			if(empty($parameters['database'])) {
				user_error("No database name given; please give a value for \$databaseConfig['database']",
							E_USER_ERROR);
			}
			$database = $parameters['database'];

			// Establish connection and create database in two steps
			unset($parameters['database']);
			DB::connect($parameters);
			DB::create_database($database);
		}

		// Build the database.  Most of the hard work is handled by DataObject
		$dataClasses = ClassInfo::subclassesFor('DataObject');
		array_shift($dataClasses);

		if(!$quiet) {
			if(Director::is_cli()) echo "\nCREATING DATABASE TABLES\n\n";
			else echo "\n<p><b>Creating database tables</b></p>\n\n";
		}

		// Initiate schema update
		$dbSchema = DB::get_schema();
		$dbSchema->schemaUpdate(function() use($dataClasses, $testMode, $quiet){
			foreach($dataClasses as $dataClass) {
				// Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
				if(!class_exists($dataClass)) continue;

				// Check if this class should be excluded as per testing conventions
				$SNG = singleton($dataClass);
				if(!$testMode && $SNG instanceof TestOnly) continue;

				// Log data
				if(!$quiet) {
					if(Director::is_cli()) echo " * $dataClass\n";
					else echo "<li>$dataClass</li>\n";
				}

				// Instruct the class to apply its schema to the database
				$SNG->requireTable();
			}
		});
		ClassInfo::reset_db_cache();

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

		touch(TEMP_FOLDER
			. '/database-last-generated-'
			. str_replace(array('\\', '/', ':'), '.', Director::baseFolder())
		);

		if(isset($_REQUEST['from_installer'])) {
			echo "OK";
		}

		if(!$quiet) {
			echo (Director::is_cli()) ? "\n Database build completed!\n\n" :"<p>Database build completed!</p>";
		}

		ClassInfo::reset_db_cache();
	}

	/**
	 * Clear all data out of the database
	 *
	 * @deprecated since version 4.0
	 */
	public function clearAllData() {
		Deprecation::notice('4.0', 'Use DB::get_conn()->clearAllData() instead');
		DB::get_conn()->clearAllData();
	}

	/**
	 * Remove invalid records from tables - that is, records that don't have
	 * corresponding records in their parent class tables.
	 */
	public function cleanup() {
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
				if(DataObject::has_own_table($subclass)) {
					unset($subclasses[$k]);
				}
			}

			if($subclasses) {
				$records = DB::query("SELECT * FROM \"$baseClass\"");


				foreach($subclasses as $subclass) {
					$recordExists[$subclass] =
						DB::query("SELECT \"ID\" FROM \"$subclass\"")->keyedColumn();
				}

				foreach($records as $record) {
					foreach($subclasses as $subclass) {
						$id = $record['ID'];
						if(($record['ClassName'] != $subclass) &&
							(!is_subclass_of($record['ClassName'], $subclass)) &&
								(isset($recordExists[$subclass][$id]))) {
							$sql = "DELETE FROM \"$subclass\" WHERE \"ID\" = $record[ID]";
							echo "<li>$sql";
							DB::query($sql);
						}
					}
				}
			}
		}
	}

}
