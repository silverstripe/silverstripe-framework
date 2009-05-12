<?php
/**
 * The Versioned decorator allows your DataObjects to have several versions, allowing
 * you to rollback changes and view history. An example of this is the pages used in the CMS.
 * @package sapphire
 * @subpackage model
 */
class Versioned extends DataObjectDecorator {
	/**
	 * An array of possible stages.
	 * @var array
	 */
	protected $stages;
	
	/**
	 * The 'default' stage.
	 * @var string
	 */
	protected $defaultStage;
	
	/**
	 * The 'live' stage.
	 * @var string
	 */
	protected $liveStage;
	
	/**
	 * A version that a DataObject should be when it is 'migrating',
	 * that is, when it is in the process of moving from one stage to another.
	 * @var string
	 */
	public $migratingVersion;
	
	/**
	 * A cache used by get_versionnumber_by_stage().
	 * Clear through {@link flushCache()}.
	 * 
	 * @var array
	 */
	protected static $cache_versionnumber;
	
	/**
	 * Construct a new Versioned object.
	 * @var array $stages The different stages the versioned object can be.
	 * The first stage is consiedered the 'default' stage, the last stage is
	 * considered the 'live' stage.
	 */
	function __construct($stages) {
		parent::__construct();

		if(!is_array($stages)) {
			$stages = func_get_args();
		}
		$this->stages = $stages;
		$this->defaultStage = reset($stages);
		$this->liveStage = array_pop($stages);
	}
	
	function extraStatics() {
		return array(
			'has_many' => array(
				'Versions' => 'SiteTree',
			)
		);
	}
	
	function augmentSQL(SQLQuery &$query) {
		// Get the content at a specific date
		if($date = Versioned::$reading_archived_date) {
			foreach($query->from as $table => $dummy) {
				if(!isset($baseTable)) {
					$baseTable = $table;
				}
				$query->renameTable($table, $table . '_versions');
				$query->replaceText(".ID", ".RecordID");
				$query->select[] = "`{$baseTable}_versions`.RecordID AS ID";

				if($table != $baseTable) {
					$query->from[$table] .= " AND `{$table}_versions`.Version = `{$baseTable}_versions`.Version";
				}
			}

			// Link to the version archived on that date
			$this->requireArchiveTempTable($baseTable, $date);
			$query->from["_Archive$baseTable"] = "INNER JOIN `_Archive$baseTable`
				ON `_Archive$baseTable`.RecordID = `{$baseTable}_versions`.RecordID 
				AND `_Archive$baseTable`.Version = `{$baseTable}_versions`.Version";

		// Get a specific stage
		} else if(Versioned::$reading_stage && Versioned::$reading_stage != $this->defaultStage 
					&& array_search(Versioned::$reading_stage,$this->stages) !== false) {
			foreach($query->from as $table => $dummy) {
				$query->renameTable($table, $table . '_' . Versioned::$reading_stage);
			}
		}
	}
	
	/**
	 * Create a temporary table mapping each database record to its version on the given date.
	 * This is used by the versioning system to return database content on that date.
	 * @param string $baseTable The base table.
	 * @param string $date The date.  If omitted, then the latest version of each page will be returned.
	 */
	protected static function requireArchiveTempTable($baseTable, $date = null) {
		DB::query("CREATE TEMPORARY TABLE IF NOT EXISTS _Archive$baseTable (
				RecordID INT NOT NULL PRIMARY KEY,
				Version INT NOT NULL
			)");
			
		if(!DB::query("SELECT COUNT(*) FROM _Archive$baseTable")->value()) {
			if($date) $dateClause = "WHERE LastEdited <= '$date'";
			else $dateClause = "";
		
			DB::query("INSERT INTO _Archive$baseTable
				SELECT RecordID, max(Version) FROM {$baseTable}_versions
				$dateClause
				GROUP BY RecordID");
		}
	}

	/**
	 * An array of DataObject extensions that may require versioning for extra tables
	 * The array value is a set of suffixes to form these table names, assuming a preceding '_'.
	 * E.g. if Extension1 creates a new table 'Class_suffix1' 
	 * and Extension2 the tables 'Class_suffix2' and 'Class_suffix3':
	 *
	 * 	$versionableExtensions = array(
	 * 		'Extension1' => 'suffix1',
	 * 		'Extension2' => array('suffix2', 'suffix3'),
	 * 	);
	 * 
	 * Make sure your extension has a static $enabled-property that determines if it is
	 * processed by Versioned.
	 *
	 * @var array
	 */
	protected static $versionableExtensions = array('Translatable' => 'lang');
	
	function augmentDatabase() {
		$classTable = $this->owner->class;

		// Build a list of suffixes whose tables need versioning
		$allSuffixes = array();
		foreach (Versioned::$versionableExtensions as $versionableExtension => $suffixes) {
			if ($this->owner->hasExtension($versionableExtension) && singleton($versionableExtension)->stat('enabled')) {
				$allSuffixes = array_merge($allSuffixes, (array)$suffixes);
				foreach ((array)$suffixes as $suffix) {
					$allSuffixes[$suffix] = $versionableExtension;
				}
			}
		}

		// Add the default table with an empty suffix to the list (table name = class name)
		array_push($allSuffixes,'');

		foreach ($allSuffixes as $key => $suffix) {
			// check that this is a valid suffix
			if (!is_int($key)) continue;
			
			if ($suffix) $table = "{$classTable}_$suffix";
			else $table = $classTable;

			if(($fields = $this->owner->databaseFields())) {
				$indexes = $this->owner->databaseIndexes();
				if($this->owner->parentClass() == "DataObject") {
					$rootTable = true;
				}
				if ($suffix && ($ext = $this->owner->extInstance($allSuffixes[$suffix]))) {
					if (!$ext->isVersionedTable($table)) continue;
					$fields = $ext->fieldsInExtraTables($suffix);
					$indexes = $fields['indexes'];
					$fields = $fields['db'];
				}
			
				// Create tables for other stages			
				foreach($this->stages as $stage) {
					// Extra tables for _Live, etc.
					if($stage != $this->defaultStage) {
						DB::requireTable("{$table}_$stage", $fields, $indexes);
						/*
						if(!DB::query("SELECT * FROM {$table}_$stage")->value()) {
							$fieldList = implode(", ",array_keys($fields));
							DB::query("INSERT INTO `{$table}_$stage` (ID,$fieldList)
								SELECT ID,$fieldList FROM `$table`");
						}
						*/
					}
	
					// Version fields on each root table (including Stage)
					if(isset($rootTable)) {
						$stageTable = ($stage == $this->defaultStage) ? $table : "{$table}_$stage";
						DB::requireField($stageTable, "Version", "int(11) not null default '0'");
					}
				}
				
				// Create table for all versions
				$versionFields = array_merge(
					array(
						"RecordID" => "Int",
						"Version" => "Int",
						"WasPublished" => "Boolean",
						"AuthorID" => "Int",
						"PublisherID" => "Int"				
					),
					(array)$fields
				);
				
				$versionIndexes = array_merge(
					array(
						'RecordID_Version' => '(RecordID, Version)',
						'RecordID' => true,
						'Version' => true,
						'AuthorID' => true,
						'PublisherID' => true,
					),
					(array)$indexes
				);
				
				DB::requireTable("{$table}_versions", $versionFields, $versionIndexes);
				/*
				if(!DB::query("SELECT * FROM {$table}_versions")->value()) {
					$fieldList = implode(", ",array_keys($fields));
									
					DB::query("INSERT INTO `{$table}_versions` ($fieldList, RecordID, Version) 
						SELECT $fieldList, ID AS RecordID, 1 AS Version FROM `$table`");
				}
				*/
				
			} else {
				DB::dontRequireTable("{$table}_versions");
				foreach($this->stages as $stage) {
					if($stage != $this->defaultStage) DB::dontrequireTable("{$table}_$stage");
				}
			}
		}
	}
	
	/**
	 * Augment a write-record request.
	 * @param SQLQuery $manipulation Query to augment.
	 */
	function augmentWrite(&$manipulation) {
		$tables = array_keys($manipulation);
		$version_table = array();
		foreach($tables as $table) {
			
			// Make sure that the augmented write is being applied to a table that can be versioned
			if( !$this->canBeVersioned($table) ) {
				// Debug::message( "$table doesn't exist or has no database fields" );
				unset($manipulation[$table]);
				continue;
			}
			$id = $manipulation[$table]['id'] ? $manipulation[$table]['id'] : $manipulation[$table]['fields']['ID'];//echo 'id' .$id.' from '.$manipulation[$table]['id'].' and '.$manipulation[$table]['fields']['ID']."\n\n<br><br>";
			if(!$id) user_error("Couldn't find ID in " . var_export($manipulation[$table], true), E_USER_ERROR);
			
			$rid = isset($manipulation[$table]['RecordID']) ? $manipulation[$table]['RecordID'] : $id;

			$newManipulation = array(
				"command" => "insert",
				"fields" => isset($manipulation[$table]['fields']) ? $manipulation[$table]['fields'] : null
			);
			
			if($this->migratingVersion) {
				$manipulation[$table]['fields']['Version'] = $this->migratingVersion;
			}
				
			// If we haven't got a version #, then we're creating a new version.  Otherwise, we're just
			// copying a version to another table
			
			if(!isset($manipulation[$table]['fields']['Version'])) {
				// Add any extra, unchanged fields to the version record.
				$data = DB::query("SELECT * FROM `$table` WHERE ID = $id")->record();
				if($data) foreach($data as $k => $v) {
					if (!isset($newManipulation['fields'][$k])) $newManipulation['fields'][$k] = "'" . addslashes($v) . "'";
				}

				// Set up a new entry in (table)_versions
				$newManipulation['fields']['RecordID'] = $rid;
				unset($newManipulation['fields']['ID']);

				// Create a new version #
				if (isset($version_table[$table])) $nextVersion = $version_table[$table];
				else unset($nextVersion);
				if($rid && !isset($nextVersion)) $nextVersion = DB::query("SELECT MAX(Version) + 1 FROM {$table}_versions WHERE RecordID = $rid")->value();
				
				$newManipulation['fields']['Version'] = $nextVersion ? $nextVersion : 1;
				$newManipulation['fields']['AuthorID'] = Member::currentUserID() ? Member::currentUserID() : 0;


				$manipulation["{$table}_versions"] = $newManipulation;

				// Add the version number to this data
				$manipulation[$table]['fields']['Version'] = $newManipulation['fields']['Version'];
				$version_table[$table] = $nextVersion;
			}
			
			// Putting a Version of -1 is a signal to leave the version table alone, despite their being no version
			if($manipulation[$table]['fields']['Version'] < 0) unset($manipulation[$table]['fields']['Version']);

			if(!$this->hasVersionField($table)) unset($manipulation[$table]['fields']['Version']);
			
			// Grab a version number - it should be the same across all tables.
			if(isset($manipulation[$table]['fields']['Version'])) $thisVersion = $manipulation[$table]['fields']['Version'];
			
			// If we're editing Live, then use (table)_Live instead of (table)
			if(Versioned::$reading_stage && Versioned::$reading_stage != $this->defaultStage) {
				$newTable = $table . '_' . Versioned::$reading_stage;
				$manipulation[$newTable] = $manipulation[$table];
				unset($manipulation[$table]);
			}
		}
		
		// Add the new version # back into the data object, for accessing after this write
		if(isset($thisVersion)) $this->owner->Version = str_replace("'","",$thisVersion);
	}
	
	/**
	 * Determine if a table is supporting the Versioned extensions (e.g. $table_versions does exists)
	 *
	 * @param string $table Table name
	 * @return boolean
	 */
	function canBeVersioned($table) {
		$dbFields = singleton($table)->databaseFields();
		return !(!ClassInfo::exists($table) || !is_subclass_of($table, 'DataObject' ) || empty( $dbFields ));
	}
	
	/**
	 * Check if a certain table has the 'Version' field
	 *
	 * @param string $table Table name
	 * @return boolean Returns false if the field isn't in the table, true otherwise
	 */
	function hasVersionField($table) {
		
		$tableParts = explode('_',$table);
		return ('DataObject' == get_parent_class($tableParts[0]));
	}
	function extendWithSuffix($table) {
		foreach (Versioned::$versionableExtensions as $versionableExtension => $suffixes) {
			if ($this->owner->hasExtension($versionableExtension)) {
				$table = $this->owner->extInstance($versionableExtension)->extendWithSuffix($table);
			}
		}
		return $table;
	}

	//-----------------------------------------------------------------------------------------------//
	
	/**
	 * Get the latest published DataObject.
	 * @return DataObject
	 */
	function latestPublished() {
		// Get the root data object class - this will have the version field
		$table1 = $this->owner->class;
		while( ($p = get_parent_class($table1)) != "DataObject") $table1 = $p;
		
		$table2 = $table1 . "_$this->liveStage";

		return DB::query("SELECT $table1.Version = $table2.Version FROM $table1 INNER JOIN $table2 ON $table1.ID = $table2.ID WHERE $table1.ID = ".  $this->owner->ID)->value();
	}
	
	/**
	 * Move a database record from one stage to the other.
	 * @param fromStage Place to copy from.  Can be either a stage name or a version number.
	 * @param toStage Place to copy to.  Must be a stage name.
	 * @param createNewVersion Set this to true to create a new version number.  By default, the existing version number will be copied over.
	 */
	function publish($fromStage, $toStage, $createNewVersion = false) {
		$baseClass = $this->owner->class;
		while( ($p = get_parent_class($baseClass)) != "DataObject") $baseClass = $p;
		$extTable = $this->extendWithSuffix($baseClass);//die($extTable);
		
		if(is_numeric($fromStage)) {
			$from = Versioned::get_version($this->owner->class, $this->owner->ID, $fromStage);
		} else {
			$this->owner->flushCache();
			$from = Versioned::get_one_by_stage($this->owner->class, $fromStage, "`{$baseClass}`.`ID` = {$this->owner->ID}");
		}
		
		$publisherID = isset(Member::currentUser()->ID) ? Member::currentUser()->ID : 0;
		if($from) {
			$from->forceChange();
			if(!$createNewVersion) $from->migrateVersion($from->Version);
			
			// Mark this version as having been published at some stage
			DB::query("UPDATE `{$extTable}_versions` SET WasPublished = 1, PublisherID = $publisherID WHERE RecordID = $from->ID AND Version = $from->Version");

			$oldStage = Versioned::$reading_stage;
			Versioned::$reading_stage = $toStage;
			$from->write();
			$from->destroy();
			
			Versioned::$reading_stage = $oldStage;
		} else {
			user_error("Can't find {$this->owner->URLSegment}/{$this->owner->ID} in stage $fromStage", E_USER_WARNING);
		}
	}
	
	/**
	 * Set the migrating version.
	 * @param string $version The version.
	 */
	function migrateVersion($version) {
		$this->migratingVersion = $version;
	}
	
	/**
	 * Compare two stages to see if they're different.
	 * Only checks the version numbers, not the actual content.
	 * @param string $stage1 The first stage to check.
	 * @param string $stage2
	 */
	function stagesDiffer($stage1, $stage2) {
		$table1 = $this->baseTable($stage1);
		$table2 = $this->baseTable($stage2);
		
		if(!is_numeric($this->owner->ID)) {
			return true;
		}
            
		// We test for equality - if one of the versions doesn't exist, this will be false
		$stagesAreEqual = DB::query("SELECT if(`$table1`.Version=`$table2`.Version,1,0) FROM `$table1` INNER JOIN `$table2` ON `$table1`.ID = `$table2`.ID AND `$table1`.ID = {$this->owner->ID}")->value();
		return !$stagesAreEqual;
	}
	
	function Versions($filter = "") {
		return $this->allVersions($filter);
	}
	
	/**
	 * Return a list of all the versions available.
	 * @param string $filter
	 */
	function allVersions($filter = "") {
		$query = $this->owner->extendedSQL($filter,"");

		foreach($query->from as $table => $join) {
			if($join[0] == '`') $baseTable = str_replace('`','',$join);
			else if (substr($join,0,5) != 'INNER') $query->from[$table] = "LEFT JOIN `$table` ON `$table`.RecordID = `{$baseTable}_versions`.RecordID AND `$table`.Version = `{$baseTable}_versions`.Version";
			$query->renameTable($table, $table . '_versions');
		}
		$query->select[] = "`{$baseTable}_versions`.AuthorID, `{$baseTable}_versions`.Version, `{$baseTable}_versions`.RecordID";
		
		$query->where[] = "`{$baseTable}_versions`.RecordID = '{$this->owner->ID}'";
		$query->orderby = "`{$baseTable}_versions`.LastEdited DESC, `{$baseTable}_versions`.Version DESC";
		

		$records = $query->execute();
		$versions = new DataObjectSet();
		
		foreach($records as $record) {
			$versions->push(new Versioned_Version($record));
		}
		
		return $versions;
	}
	
	/**
	 * Compare two version, and return the diff between them.
	 * @param string $from The version to compare from.
	 * @param string $to The version to compare to.
	 * @return DataObject
	 */
	function compareVersions($from, $to) {
		$fromRecord = Versioned::get_version($this->owner->class, $this->owner->ID, $from);
		$toRecord = Versioned::get_version($this->owner->class, $this->owner->ID, $to);

		
		$fields = array_keys($fromRecord->getAllFields());
		
		foreach($fields as $field) {
			if(in_array($field, array("ID","Version","RecordID","AuthorID", "ParentID"))) continue;
			
			$fromRecord->$field = Diff::compareHTML($fromRecord->$field, $toRecord->$field);
		}
		
		return $fromRecord;
	}
	
	/**
	 * Return the base table - the class that directly extends DataObject.
	 * @return string
	 */
	function baseTable($stage = null) {
		$tableClasses = ClassInfo::dataClassesFor($this->owner->class);
		$baseClass = array_shift($tableClasses);
		return (!$stage || $stage == $this->defaultStage) ? $baseClass : $baseClass . "_$stage";		
	}
		
	//-----------------------------------------------------------------------------------------------//
	
	/**
	 * Choose the stage the site is currently on.
	 * If $_GET['stage'] is set, then it will use that stage, and store it in the session.
	 * if $_GET['archiveDate'] is set, it will use that date, and store it in the session.
	 * If neither of these are set, it checks the session, otherwise the stage is set to 'Live'.
	 */
	static function choose_site_stage() {
		if(isset($_GET['stage'])) {
			$_GET['stage'] = ucfirst(strtolower($_GET['stage']));
			Session::set('currentStage', $_GET['stage']);
			Session::clear('archiveDate');
		}
		if(isset($_GET['archiveDate'])) {
			Session::set('archiveDate', $_GET['archiveDate']);
		}
		
		if(Session::get('archiveDate')) {
			Versioned::reading_archived_date(Session::get('archiveDate'));
		} else if(Session::get('currentStage')) {
			Versioned::reading_stage(Session::get('currentStage'));
		} else {
			Versioned::reading_stage("Live");
		}
	}
	
	/**
	 * Get the name of the 'live' stage.
	 * @return string
	 */
	static function get_live_stage() {
		return "Live";
	}
	
	/**
	 * Get the current reading stage.
	 * @return string
	 */
	static function current_stage() {
		return Versioned::$reading_stage;
	}
	
	/**
	 * Get the current archive date.
	 * @return string
	 */
	static function current_archived_date() {
		return Versioned::$reading_archived_date;
	}
	
	/**
	 * Set the reading stage.
	 * @param string $stage New reading stage.
	 */
	static function reading_stage($stage) {
		Versioned::$reading_stage = $stage;
	}
	
	/**
	 * Set the reading archive date.
	 * @param string $date New reading archived date.
	 */
	static function reading_archived_date($date) {
		Versioned::$reading_archived_date = $date;
	}
	
	/**
	 * Get a singleton instance of a class in the given stage.
	 * 
	 * @param string $class The name of the class.
	 * @param string $stage The name of the stage.
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param boolean $cache Use caching.
	 * @param string $orderby A sort expression to be inserted into the ORDER BY clause.
	 * @return DataObject
	 */
	static function get_one_by_stage($class, $stage, $filter = '', $cache = true, $orderby = '') {
		$oldStage = Versioned::$reading_stage;
		Versioned::$reading_stage = $stage;
		singleton($class)->flushCache();
		$result = DataObject::get_one($class, $filter, $cache, $orderby);
		singleton($class)->flushCache();

		Versioned::$reading_stage = $oldStage;
		return $result;
	}
	
	/**
	 * Gets the current version number of a specific record.
	 * 
	 * @param string $class
	 * @param string $stage
	 * @param int $id
	 * @param boolean $cache
	 * @return int
	 */
	static function get_versionnumber_by_stage($class, $stage, $id, $cache = true) {
		$baseClass = ClassInfo::baseDataClass($class);
		$stageTable = ($stage == 'Stage') ? $baseClass : "{$baseClass}_{$stage}";

		// cached call
		if($cache && isset(self::$cache_versionnumber[$baseClass][$stage][$id])) {
			return self::$cache_versionnumber[$baseClass][$stage][$id];
		}
		
		// get version as performance-optimized SQL query (gets called for each page in the sitetree)
		$version = DB::query("SELECT Version FROM `$stageTable` WHERE ID = $id")->value();
		
		// cache value (if required)
		if($cache) {
			if(!isset(self::$cache_versionnumber[$baseClass])) self::$cache_versionnumber[$baseClass] = array();
			if(!isset(self::$cache_versionnumber[$baseClass][$stage])) self::$cache_versionnumber[$baseClass][$stage] = array();
			self::$cache_versionnumber[$baseClass][$stage][$id] = $version;
		}
		
		return $version;
	}
	
	/**
	 * Pre-populate the cache for Versioned::get_versionnumber_by_stage() for a list of record IDs,
	 * for more efficient database querying.  If $idList is null, then every page will be pre-cached.
	 */
	static function prepopulate_versionnumber_cache($class, $stage, $idList = null) {
		$filter = "";
		if($idList) {
			// Validate the ID list
			foreach($idList as $id) if(!is_numeric($id)) user_error("Bad ID passed to Versioned::prepopulate_versionnumber_cache() in \$idList: " . $id, E_USER_ERROR);
			$filter = "WHERE ID IN(" .implode(", ", $idList) . ")";
		}
		
		$baseClass = ClassInfo::baseDataClass($class);
		$stageTable = ($stage == 'Stage') ? $baseClass : "{$baseClass}_{$stage}";

		$versions = DB::query("SELECT ID, Version FROM `$stageTable` $filter")->map();
		foreach($versions as $id => $version) {
			self::$cache_versionnumber[$baseClass][$stage][$id] = $version;
		}
	}
	
	/**
	 * Get a set of class instances by the given stage.
	 * 
	 * @param string $class The name of the class.
	 * @param string $stage The name of the stage.
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param string $sort A sort expression to be inserted into the ORDER BY clause.
	 * @param string $join A join expression, such as LEFT JOIN or INNER JOIN
	 * @param int $limit A limit on the number of records returned from the database.
	 * @param string $containerClass The container class for the result set (default is DataObjectSet)
	 * @return DataObjectSet
	 */
	static function get_by_stage($class, $stage, $filter = '', $sort = '', $join = '', $limit = '', $containerClass = 'DataObjectSet') {
		$oldStage = Versioned::$reading_stage;
		Versioned::$reading_stage = $stage;
		$result = DataObject::get($class, $filter, $sort, $join, $limit, $containerClass);
		Versioned::$reading_stage = $oldStage;
		return $result;
	}
	
	function deleteFromStage($stage) {
		$oldStage = Versioned::$reading_stage;
		Versioned::$reading_stage = $stage;
		$result = $this->owner->delete();
		Versioned::$reading_stage = $oldStage;
		return $result;
	}
	
	function writeToStage($stage, $forceInsert = false) {
		$oldStage = Versioned::$reading_stage;
		Versioned::$reading_stage = $stage;
		$result = $this->owner->write(false, $forceInsert);
		Versioned::$reading_stage = $oldStage;
		return $result;
	}
		
	/**
	 * Build a SQL query to get data from the _version table.
	 * This function is similar in style to {@link DataObject::buildSQL}
	 */
	function buildVersionSQL($filter = "", $sort = "") {
		$query = $this->owner->extendedSQL($filter,$sort);
		foreach($query->from as $table => $join) {
			if($join[0] == '`') $baseTable = str_replace('`','',$join);
			else $query->from[$table] = "LEFT JOIN `$table` ON `$table`.RecordID = `{$baseTable}_versions`.RecordID AND `$table`.Version = `{$baseTable}_versions`.Version";
			$query->renameTable($table, $table . '_versions');
		}
		$query->select[] = "`{$baseTable}_versions`.AuthorID, `{$baseTable}_versions`.Version, `{$baseTable}_versions`.RecordID AS ID";
		return $query;
	}

	static function build_version_sql($className, $filter = "", $sort = "") {
		$query = singleton($className)->extendedSQL($filter,$sort);
		foreach($query->from as $table => $join) {
			if($join[0] == '`') $baseTable = str_replace('`','',$join);
			else $query->from[$table] = "LEFT JOIN `$table` ON `$table`.RecordID = `{$baseTable}_versions`.RecordID AND `$table`.Version = `{$baseTable}_versions`.Version";
			$query->renameTable($table, $table . '_versions');
		}
		$query->select[] = "`{$baseTable}_versions`.AuthorID, `{$baseTable}_versions`.Version, `{$baseTable}_versions`.RecordID AS ID";
		return $query;
	}
	
	/**
	 * Return the latest version of the given page.
	 * 
	 * @return DataObject
	 */
	static function get_latest_version($class, $id) {
		$oldStage = Versioned::$reading_stage;
		Versioned::$reading_stage = null;

		$baseTable = ClassInfo::baseDataClass($class);
		$query = singleton($class)->buildVersionSQL("`{$baseTable}`.RecordID = $id", "`{$baseTable}`.Version DESC");
		$query->limit = 1;
		$record = $query->execute()->record();
		if(!$record) return;
		
		$className = $record['ClassName'];
		if(!$className) {
			Debug::show($query->sql());
			Debug::show($record);
			user_error("Versioned::get_version: Couldn't get $class.$id, version $version", E_USER_ERROR);
		}

		Versioned::$reading_stage = $oldStage;

		return new $className($record);
	}

	/**
	 * Return the equivalent of a DataObject::get() call, querying the latest
	 * version of each page stored in the (class)_versions tables.
	 *
	 * In particular, this will query deleted records as well as active ones.
	 */
	static function get_including_deleted($class, $filter = "", $sort = "") {
		$oldStage = Versioned::$reading_stage;
		Versioned::$reading_stage = null;

		$SNG = singleton($class);
		
		// Build query
		$query = $SNG->buildVersionSQL($filter, $sort);
		$baseTable = ClassInfo::baseDataClass($class);
		self::requireArchiveTempTable($baseTable);
		$query->from["_Archive$baseTable"] = "INNER JOIN `_Archive$baseTable`
			ON `_Archive$baseTable`.RecordID = `{$baseTable}_versions`.RecordID 
			AND `_Archive$baseTable`.Version = `{$baseTable}_versions`.Version";
		
		// Process into a DataObjectSet
		$result = $SNG->buildDataObjectSet($query->execute());

		Versioned::$reading_stage = $oldStage;
		return $result;
	}
	
	/**
	 * @return DataObject
	 */
	static function get_version($class, $id, $version) {
		$oldStage = Versioned::$reading_stage;
		Versioned::$reading_stage = null;

		$baseTable = ClassInfo::baseDataClass($class);
		$query = singleton($class)->buildVersionSQL("`{$baseTable}`.RecordID = $id AND `{$baseTable}`.Version = $version");
		$record = $query->execute()->record();
		$className = $record['ClassName'];
		if(!$className) {
			Debug::show($query->sql());
			Debug::show($record);
			user_error("Versioned::get_version: Couldn't get $class.$id, version $version", E_USER_ERROR);
		}

		Versioned::$reading_stage = $oldStage;

		return new $className($record);
	}

	/**
	 * @return DataObject
	 */
	static function get_all_versions($class, $id, $version) {
		$baseTable = ClassInfo::baseDataClass($class);
		$query = singleton($class)->buildVersionSQL("`{$baseTable}`.RecordID = $id AND `{$baseTable}`.Version = $version");
		$record = $query->execute()->record();
		$className = $record[ClassName];
		if(!$className) {
			Debug::show($query->sql());
			Debug::show($record);
			user_error("Versioned::get_version: Couldn't get $class.$id, version $version", E_USER_ERROR);
		}
		return new $className($record);
	}
	
	function contentcontrollerInit($controller) {
		self::choose_site_stage();
	}
	function modelascontrollerInit($controller) {
		self::choose_site_stage();
	}
	
	protected static $reading_stage = null;
	protected static $reading_archived_date = null;
	
	function updateFieldLabels(&$labels) {
		$labels['Versions'] = _t('Versioned.has_many_Versions', 'Versions', PR_MEDIUM, 'Past Versions of this page');
	}
	
	function flushCache() {
		self::$cache_versionnumber = array();
	}
}

/**
 * Represents a single version of a record.
 * @package sapphire
 * @subpackage model
 * @see Versioned
 */
class Versioned_Version extends ViewableData {
	protected $record;
	protected $object;
	
	function __construct($record) {
		$this->record = $record;
		$record['ID'] = $record['RecordID'];
		$className = $record['ClassName'];
		
		$this->object = new $className($record);
		$this->failover = $this->object;
		
		parent::__construct();
	}
	
	function PublishedClass() {
		return $this->record['WasPublished'] ? 'published' : 'internal';
	}
	
	function Author() {
		return DataObject::get_by_id("Member", $this->record['AuthorID']);
	}
	
	function Publisher() {
		if( !$this->record['WasPublished'] )
			return null;
			
		return DataObject::get_by_id("Member", $this->record['PublisherID']);
	}
	
	function Published() {
		return !empty( $this->record['WasPublished'] );
	}
}

?>