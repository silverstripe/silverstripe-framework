<?php

/**
 * Database Administration
 *
 * @package sapphire
 * @subpackage model
 */


/**
 * Include the DB class
 */
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
		if(Director::isLive() && Security::database_is_ready() && (!Member::currentUser() || !Member::currentUser()->isAdmin())) {
			Security::permissionFailure($this,
				"This page is secured and you need administrator rights to access it. " .
				"Enter your credentials below and we will send you right along.");
			return;
		}

		// The default time limit of 30 seconds is normally not enough
		if(ini_get("safe_mode") != "1") {
			set_time_limit(600);
		}

		$this->doBuild(isset($_REQUEST['quiet']) || isset($_REQUEST['from_installer']));
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
	function doBuild($quiet = false, $populate = true) {
		$conn = DB::getConn();

		if($quiet) {
			DB::quiet();
		} else {
			echo "<h2>Building Database</h2>";
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
			DB::createDatabase($connect, $username, $password, $database);
			// ManifestBuilder::compileManifest();
		}

		// Get all our classes
		// ManifestBuilder::compileManifest();
		// ManifestBuilder::includeEverything();

		// Build the database.  Most of the hard work is handled by DataObject
		$dataClasses = ClassInfo::subclassesFor('DataObject');
		array_shift($dataClasses);

		if(!$quiet) {
			echo '<p><b>Creating database tables</b></p>';
		}

		$conn->beginSchemaUpdate();
		foreach($dataClasses as $dataClass) {
			// Test_ indicates that it's the data class is part of testing system

			if(strpos($dataClass,'Test_') === false) {
				if(!$quiet) {
					echo "<li>$dataClass</li>";
				}
				singleton($dataClass)->requireTable();
			}
		}
		$conn->endSchemaUpdate();

		ManifestBuilder::update_db_tables();

		if($populate) {
			if(!$quiet) {
				echo '<p><b>Creating database records</b></p>';
			}

			foreach($dataClasses as $dataClass) {
				// Test_ indicates that it's the data class is part of testing system

				if(strpos($dataClass,'Test_') === false) {
					if(!$quiet) {
						echo "<li>$dataClass</li>";
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


	/**
	 * Imports objects based on a specified CSV file in $_GET['FileName']
	 */
	function import(){
		$FileName = $_GET['FileName'];
		$FileName = $_SERVER['DOCUMENT_ROOT'] .
			substr($_SERVER['PHP_SELF'], 0, strlen($_SERVER['PHP_SELF'])-18) .
			"/assets/" . $FileName;

		if(file_exists($FileName)) {
			$handle = fopen($FileName,'r');

			if($handle){
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					$num = count($data);
					$row++;

					if($row == 1){
						for ($c=0; $c < $num; $c++) {
							$ColumnHeaders[] = str_replace(' ','',$data[$c]);
							// Have to add code here to remove unsafe chars..
						}

					} else {
						$Product = new Product();

						for ($c=0; $c < $num; $c++) {
								$Product->$ColumnHeaders[$c] = trim($data[$c]);
						}

						$MainCategory = DataObject::get("ProductGroup",
							"URLSegment LIKE '" . $Product->generateURLSegment(
								$Product->Category) ."'");

						if(!$MainCategory) {
							// if we can't find a main category, create all three sub
							// categories, as they must be unique.

							$ProductGroup = new ProductGroup();
							$ProductGroup->Title = $Product->Category;
							print_r("<ul><li>Created : $ProductGroup->Title</li>");
							$ProductGroup->ParentID = 1;
							$index = $ProductGroup->write();
							$ProductGroup->flushCache();

							if($Product->SubCategory) {
								$ChildProductGroup = new ProductGroup();
								$ChildProductGroup->Title = $Product->SubCategory;
								print_r("<ul><li>Created : $ChildProductGroup->Title</li>");
								$ChildProductGroup->ClassName = "ProductGroup";
								$ChildProductGroup->ParentID = $index;
								$index = $ChildProductGroup->write();
								$ChildProductGroup->flushCache();
							}

							if($Product->SubCategory2) {
								$NestedProductGroup = new ProductGroup();
								$NestedProductGroup->Title = $Product->SubCategory2;
								print_r("<ul><li>Created : $NestedProductGroup->Title</li>");
								$NestedProductGroup->ClassName = "ProductGroup";
								$NestedProductGroup->ParentID = $index;
								$index = $NestedProductGroup->write();
								$NestedProductGroup->flushCache();
							}
						} else {
							// We've  found a main category. check if theres a second...
							print_r("<ul><li>USING : $MainCategory->Title</li>");
							$index = $MainCategory->ID;

							$SubCategory =  DataObject::get_one("ProductGroup",
								"URLSegment LIKE '" . $Product->generateURLSegment(
									$Product->SubCategory) ."'");

							if(!$SubCategory && $Product->SubCategory) {
								$ChildProductGroup = new ProductGroup();
								$ChildProductGroup->Title = $Product->SubCategory;
								print_r("<ul><li>Created : $ChildProductGroup->Title</li>");
								$ChildProductGroup->ClassName = "ProductGroup";
								$ChildProductGroup->ParentID = $index;
								$index = $ChildProductGroup->write();
								$ChildProductGroup->flushCache();

								if($Product->SubCategory2) {
									$NestedProductGroup = new ProductGroup();
									$NestedProductGroup->Title = $Product->SubCategory2;
									print_r("<ul><li>$NestedProductGroup->Title</li>");
									$NestedProductGroup->ClassName = "ProductGroup";
									$NestedProductGroup->ParentID = $index;
									$index = $NestedProductGroup->write();
									$NestedProductGroup->flushCache();
									$index = $SubCategory2->ID;
								}
							} else if($Product->SubCategory){
								print_r("<ul><li>USING : $SubCategory->Title</li>");
								$index = $SubCategory->ID;

								$SubCategory2 = DataObject::get_one("ProductGroup",
									"URLSegment LIKE '" . $Product->generateURLSegment(
										$Product->SubCategory2) ."'");

								if($Product->SubCategory2) {
									$NestedProductGroup = new ProductGroup();
									$NestedProductGroup->Title = $Product->SubCategory2;
									print_r("<ul><li>$NestedProductGroup->Title</li>");
									$NestedProductGroup->ClassName = "ProductGroup";
									$NestedProductGroup->ParentID = $index;
									$index = $NestedProductGroup->write();
									$NestedProductGroup->flushCache();
									$index = $SubCategory2->ID;
							 }
							}
						}

						$MatchedProduct = DataObject::get_one("Product",
							"URLSegment LIKE '" . $Product->generateURLSegment(
								$Product->Title) . "'");

						if($MatchedProduct) {
							// create the new parents / assign many many
							$MatchedProduct->ParentID = $index;
							// create the new product
							$MatchedProduct->write();
							$MatchedProduct->flushCache();
							print_r(" <h4>UPDATED</h4></ul></ul></ul><br/><br/>");
						} else {
						   // save the new product
						   $Product->ParentID = $index;

						   $Product->write();
						   $Product->flushCache();
						   print_r(" <h4>New Product $product->Title</h4></ul></ul></ul><br/><br/>");
						}
					}
				}

				fclose($handle);
			} else {
				print_r("<h1>Error: Could not open file.</h1>");
			}
		} else {
			print_r("<h1>Error: Could not open file.</h1>");
		}

	}

	/**
	 * Imports objects based on a specified CSV file in $_GET['FileName']
	 */
	function generateProductGroups() {
		$FileName = $_GET['FileName'];
		$FileName = $_SERVER['DOCUMENT_ROOT'] .
			substr($_SERVER['PHP_SELF'], 0, strlen($_SERVER['PHP_SELF']) - 18) .
			"/assets/" . $FileName;

		if(file_exists($FileName)) {
			$handle = fopen($FileName,'r');

			if($handle) {
				$i = 0;
				while(($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					$ProductGroup[$i] = $data[0];
					if($data[1]) {
						$ProductGroup[$i][] = $data[1];
					}
					if($data[2]) {
						$ProductGroup[$i][][] = $data[2];
					}
				}
			} else {
				print_r("<h1>Error: Could not open file.</h1>");
			}
		} else {
			print_r("<h1>Error: Could not open file.</h1>");
		}
	}


	/**
	 * This method does nothing at the moment...
	 */
	function makeURL() {}
}


?>
