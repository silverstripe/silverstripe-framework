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
	 * Additional database columns for the new
	 * "_versions" table. Used in {@link augmentDatabase()}
	 * and all Versioned calls decorating or creating
	 * SELECT statements.
	 * 
	 * @var array $db_for_versions_table
	 */
	static $db_for_versions_table = array(
		"RecordID" => "Int",
		"Version" => "Int",
		"WasPublished" => "Boolean",
		"AuthorID" => "Int",
		"PublisherID" => "Int"				
	);
	
	/**
	 * Additional database indexes for the new
	 * "_versions" table. Used in {@link augmentDatabase()}.
	 * 
	 * @var array $indexes_for_versions_table
	 */
	static $indexes_for_versions_table = array(
		'RecordID_Version' => '(RecordID,Version)',
		'RecordID' => true,
		'Version' => true,
		'AuthorID' => true,
		'PublisherID' => true,
	);

	/**
	 * Reset static configuration variables to their default values
	 */
	static function reset() {
		self::$reading_mode = '';

		Session::clear('readingMode');
	}
	
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
			'db' => array(
				'Version' => 'Int',
			),
			'has_many' => array(
				'Versions' => 'SiteTree',
			)
		);
	}
	
	function augmentSQL(SQLQuery &$query) {
		// Get the content at a specific date
		if($date = Versioned::current_archived_date()) {
			foreach($query->from as $table => $dummy) {
				if(!isset($baseTable)) {
					$baseTable = $table;
				}
				$query->renameTable($table, $table . '_versions');
				$query->replaceText("\"$table\".\"ID\"", "\"$table\".\"RecordID\"");
				
				// Add all <basetable>_versions columns
				foreach(self::$db_for_versions_table as $name => $type) {
					$query->select[] = sprintf('"%s_versions"."%s"', $baseTable, $name);
				}
				$query->select[] = sprintf('"%s_versions"."%s" AS "ID"', $baseTable, 'RecordID');

				if($table != $baseTable) {
					$query->from[$table] .= " AND \"{$table}_versions\".\"Version\" = \"{$baseTable}_versions\".\"Version\"";
				}
			}

			// Link to the version archived on that date
			$archiveTable = $this->requireArchiveTempTable($baseTable, $date);
			$query->from[$archiveTable] = "INNER JOIN \"$archiveTable\"
				ON \"$archiveTable\".\"ID\" = \"{$baseTable}_versions\".\"RecordID\" 
				AND \"$archiveTable\".\"Version\" = \"{$baseTable}_versions\".\"Version\"";

		// Get a specific stage
		} else if(Versioned::current_stage() && Versioned::current_stage() != $this->defaultStage 
					&& array_search(Versioned::current_stage(), $this->stages) !== false) {
			foreach($query->from as $table => $dummy) {
				$query->renameTable($table, $table . '_' . Versioned::current_stage());
			}
		}
	}
	
	/**
	 * Keep track of the archive tables that have been created 
	 */
	private static $archive_tables = array();
	
	/**
	 * Called by {@link SapphireTest} when the database is reset.
	 * @todo Reduce the coupling between this and SapphireTest, somehow.
	 */
	public static function on_db_reset() {
		// Drop all temporary tables
		$db = DB::getConn();
		foreach(self::$archive_tables as $tableName) {
			if(method_exists($db, 'dropTable')) $db->dropTable($tableName);
			else $db->query("DROP TABLE \"$tableName\"");
		}

		// Remove references to them
		self::$archive_tables = array();
	}
	
	/**
	 * Create a temporary table mapping each database record to its version on the given date.
	 * This is used by the versioning system to return database content on that date.
	 * @param string $baseTable The base table.
	 * @param string $date The date.  If omitted, then the latest version of each page will be returned.
	 * @todo Ensure that this is DB abstracted
	 */
	protected static function requireArchiveTempTable($baseTable, $date = null) {
		if(!isset(self::$archive_tables[$baseTable])) {
			self::$archive_tables[$baseTable] = DB::createTable("_Archive$baseTable", array(
				"ID" => "INT NOT NULL",
				"Version" => "INT NOT NULL",
			), null, array('temporary' => true));
		}
		
		if(!DB::query("SELECT COUNT(*) FROM \"" . self::$archive_tables[$baseTable] . "\"")->value()) {
			if($date) $dateClause = "WHERE \"LastEdited\" <= '$date'";
			else $dateClause = "";

			DB::query("INSERT INTO \"" . self::$archive_tables[$baseTable] . "\"
				SELECT \"RecordID\", max(\"Version\") FROM \"{$baseTable}_versions\"
				$dateClause
				GROUP BY \"RecordID\"");
		}
		
		return self::$archive_tables[$baseTable];
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
		
		$isRootClass = ($this->owner->class == ClassInfo::baseDataClass($this->owner->class));

		// Build a list of suffixes whose tables need versioning
		$allSuffixes = array();
		foreach (Versioned::$versionableExtensions as $versionableExtension => $suffixes) {
			if ($this->owner->hasExtension($versionableExtension)) {
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

			if($fields = DataObject::database_fields($this->owner->class)) {
				$indexes = $this->owner->databaseIndexes();
				if ($suffix && ($ext = $this->owner->getExtensionInstance($allSuffixes[$suffix]))) {
					if (!$ext->isVersionedTable($table)) continue;
					$ext->setOwner($this->owner);
					$fields = $ext->fieldsInExtraTables($suffix);
					$ext->clearOwner();
					$indexes = $fields['indexes'];
					$fields = $fields['db'];
				}
			
				// Create tables for other stages			
				foreach($this->stages as $stage) {
					// Extra tables for _Live, etc.
					if($stage != $this->defaultStage) {
						DB::requireTable("{$table}_$stage", $fields, $indexes, false);
					}
	
					// Version fields on each root table (including Stage)
					/*
					if($isRootClass) {
						$stageTable = ($stage == $this->defaultStage) ? $table : "{$table}_$stage";
						$parts=Array('datatype'=>'int', 'precision'=>11, 'null'=>'not null', 'default'=>(int)0);
						$values=Array('type'=>'int', 'parts'=>$parts);
						DB::requireField($stageTable, 'Version', $values);
					}
					*/
				}
				
				if($isRootClass) {
					// Create table for all versions
					$versionFields = array_merge(
						self::$db_for_versions_table,
						(array)$fields
					);
				
					$versionIndexes = array_merge(
						self::$indexes_for_versions_table,
						(array)$indexes
					);
				} else {
					// Create fields for any tables of subclasses
					$versionFields = array_merge(
						array(
							"RecordID" => "Int",
							"Version" => "Int",
						),
						(array)$fields
					);
				
					$versionIndexes = array_merge(
						array(
							'RecordID_Version' => array('type' => 'unique', 'value' => 'RecordID,Version'),
							'RecordID' => true,
							'Version' => true,
						),
						(array)$indexes
					);
				}
				
				if(DB::getConn()->hasTable("{$table}_versions")) {
					// Fix data that lacks the uniqueness constraint (since this was added later and
					// bugs meant that the constraint was validated)
					$duplications = DB::query("SELECT MIN(\"ID\") AS \"ID\", \"RecordID\", \"Version\" 
						FROM \"{$table}_versions\" GROUP BY \"RecordID\", \"Version\" 
						HAVING COUNT(*) > 1");
						
					foreach($duplications as $dup) {
						DB::alteration_message("Removing {$table}_versions duplicate data for "
							."{$dup['RecordID']}/{$dup['Version']}" ,"deleted");
						DB::query("DELETE FROM \"{$table}_versions\" WHERE \"RecordID\" = {$dup['RecordID']}
							AND \"Version\" = {$dup['Version']} AND \"ID\" != {$dup['ID']}");
					}
					
					// Remove junk which has no data in parent classes. Only needs to run the following
					// when versioned data is spread over multiple tables					
					if(!$isRootClass && ($versionedTables = ClassInfo::dataClassesFor($table))) {
						
						foreach($versionedTables as $child) {
							if($table == $child) break; // only need subclasses
							
							$count = DB::query("
								SELECT COUNT(*) FROM \"{$table}_versions\"
								LEFT JOIN \"{$child}_versions\" 
									ON \"{$child}_versions\".\"RecordID\" = \"{$table}_versions\".\"RecordID\"
									AND \"{$child}_versions\".\"Version\" = \"{$table}_versions\".\"Version\"
								WHERE \"{$child}_versions\".\"ID\" IS NULL
							")->value();

							if($count > 0) {
								DB::alteration_message("Removing orphaned versioned records", "deleted");
								
								$effectedIDs = DB::query("
									SELECT \"{$table}_versions\".\"ID\" FROM \"{$table}_versions\"
									LEFT JOIN \"{$child}_versions\" 
										ON \"{$child}_versions\".\"RecordID\" = \"{$table}_versions\".\"RecordID\"
										AND \"{$child}_versions\".\"Version\" = \"{$table}_versions\".\"Version\"
									WHERE \"{$child}_versions\".\"ID\" IS NULL
								")->column();

								if(is_array($effectedIDs)) {
									foreach($effectedIDs as $key => $value) {
										DB::query("DELETE FROM \"{$table}_versions\" WHERE \"{$table}_versions\".\"ID\" = '$value'");
									}
								}
							}
						}
					}
				}

				DB::requireTable("{$table}_versions", $versionFields, $versionIndexes);
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
			$baseDataClass = ClassInfo::baseDataClass($table);
			
			$isRootClass = ($table == $baseDataClass);
			
			// Make sure that the augmented write is being applied to a table that can be versioned
			if( !$this->canBeVersioned($table) ) {
				unset($manipulation[$table]);
				continue;
			}
			$id = $manipulation[$table]['id'] ? $manipulation[$table]['id'] : $manipulation[$table]['fields']['ID'];;
			if(!$id) user_error("Couldn't find ID in " . var_export($manipulation[$table], true), E_USER_ERROR);
			
			$rid = isset($manipulation[$table]['RecordID']) ? $manipulation[$table]['RecordID'] : $id;

			$newManipulation = array(
				"command" => "insert",
				"fields" => isset($manipulation[$table]['fields']) ? $manipulation[$table]['fields'] : null
			);
			
			if($this->migratingVersion) {
				$manipulation[$table]['fields']['Version'] = $this->migratingVersion;
			}

			// If we haven't got a version #, then we're creating a new version.
			// Otherwise, we're just copying a version to another table
			if(!isset($manipulation[$table]['fields']['Version'])) {
				// Add any extra, unchanged fields to the version record.
				$data = DB::query("SELECT * FROM \"$table\" WHERE \"ID\" = $id")->record();
				if($data) foreach($data as $k => $v) {
					if (!isset($newManipulation['fields'][$k])) $newManipulation['fields'][$k] = "'" . DB::getConn()->addslashes($v) . "'";
				}

				// Set up a new entry in (table)_versions
				$newManipulation['fields']['RecordID'] = $rid;
				unset($newManipulation['fields']['ID']);

				// Create a new version #
				if (isset($version_table[$table])) $nextVersion = $version_table[$table];
				else unset($nextVersion);

				if($rid && !isset($nextVersion)) $nextVersion = DB::query("SELECT MAX(\"Version\") + 1 FROM \"{$baseDataClass}_versions\" WHERE \"RecordID\" = $rid")->value();
				
				$newManipulation['fields']['Version'] = $nextVersion ? $nextVersion : 1;
				
				if($isRootClass) {
					$userID = (Member::currentUser()) ? Member::currentUser()->ID : 0;
					$newManipulation['fields']['AuthorID'] = $userID;
				}
				


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
			if(Versioned::current_stage() && Versioned::current_stage() != $this->defaultStage) {
				// If the record has already been inserted in the (table), get rid of it. 
				if($manipulation[$table]['command']=='insert') {
					DB::query("DELETE FROM \"{$table}\" WHERE \"ID\"='$id'");
				}
				
				$newTable = $table . '_' . Versioned::current_stage();
				$manipulation[$newTable] = $manipulation[$table];
				unset($manipulation[$table]);
			}
		}
		
		// Clear the migration flag
		if($this->migratingVersion) $this->migrateVersion(null);

		// Add the new version # back into the data object, for accessing after this write
		if(isset($thisVersion)) $this->owner->Version = str_replace("'","",$thisVersion);
	}

	/**
	 * If a write was skipped, then we need to ensure that we don't leave a migrateVersion()
	 * value lying around for the next write.
	 */
	function onAfterSkippedWrite() {
		$this->migrateVersion(null);
	}
	
	/**
	 * Determine if a table is supporting the Versioned extensions (e.g. $table_versions does exists)
	 *
	 * @param string $table Table name
	 * @return boolean
	 */
	function canBeVersioned($table) {
		return ClassInfo::exists($table) 
			&& ClassInfo::is_subclass_of($table, 'DataObject')
			&& DataObject::has_own_table($table);
	}
	
	/**
	 * Check if a certain table has the 'Version' field
	 *
	 * @param string $table Table name
	 * @return boolean Returns false if the field isn't in the table, true otherwise
	 */
	function hasVersionField($table) {
		$rPos = strrpos($table,'_');
		if(($rPos !== false) && in_array(substr($table,$rPos), $this->stages)) {
			$tableWithoutStage = substr($table,0,$rPos);
		} else {
			$tableWithoutStage = $table;
		}
		return ('DataObject' == get_parent_class($tableWithoutStage));
	}
	function extendWithSuffix($table) {
		foreach (Versioned::$versionableExtensions as $versionableExtension => $suffixes) {
			if ($this->owner->hasExtension($versionableExtension)) {
				$ext = $this->owner->getExtensionInstance($versionableExtension);
				$ext->setOwner($this->owner);
				$table = $ext->extendWithSuffix($table);
				$ext->clearOwner();
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

		return DB::query("SELECT \"$table1\".\"Version\" = \"$table2\".\"Version\" FROM \"$table1\" INNER JOIN \"$table2\" ON \"$table1\".\"ID\" = \"$table2\".\"ID\" WHERE \"$table1\".\"ID\" = ".  $this->owner->ID)->value();
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
		$extTable = $this->extendWithSuffix($baseClass);
		
		if(is_numeric($fromStage)) {
			$from = Versioned::get_version($baseClass, $this->owner->ID, $fromStage);
		} else {
			$this->owner->flushCache();
			$from = Versioned::get_one_by_stage($baseClass, $fromStage, "\"{$baseClass}\".\"ID\" = {$this->owner->ID}");
		}
		
		$publisherID = isset(Member::currentUser()->ID) ? Member::currentUser()->ID : 0;
		if($from) {
			$from->forceChange();
			if($createNewVersion) {
				$latest = self::get_latest_version($baseClass, $this->owner->ID);
				$this->owner->Version = $latest->Version + 1;
			} else {
				$from->migrateVersion($from->Version);
			}
			
			// Mark this version as having been published at some stage
			DB::query("UPDATE \"{$extTable}_versions\" SET \"WasPublished\" = '1', \"PublisherID\" = $publisherID WHERE \"RecordID\" = $from->ID AND \"Version\" = $from->Version");

			$oldMode = Versioned::get_reading_mode();
			Versioned::reading_stage($toStage);

			$conn = DB::getConn();
			if(method_exists($conn, 'allowPrimaryKeyEditing')) $conn->allowPrimaryKeyEditing($baseClass, true);
			$from->write();
			if(method_exists($conn, 'allowPrimaryKeyEditing')) $conn->allowPrimaryKeyEditing($baseClass, false);

			$from->destroy();
			
			Versioned::set_reading_mode($oldMode);
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
		//TODO: DB Abstraction: if statement here:
		$stagesAreEqual = DB::query("SELECT CASE WHEN \"$table1\".\"Version\"=\"$table2\".\"Version\" THEN 1 ELSE 0 END FROM \"$table1\" INNER JOIN \"$table2\" ON \"$table1\".\"ID\" = \"$table2\".\"ID\" AND \"$table1\".\"ID\" = {$this->owner->ID}")->value();
		return !$stagesAreEqual;
	}
	
	function Versions($filter = "", $sort = "", $limit = "", $join = "", $having = "") {
		return $this->allVersions($filter, $sort, $limit, $join, $having);
	}
	
	/**
	 * Return a list of all the versions available.
	 * @param string $filter
	 */
	function allVersions($filter = "", $sort = "", $limit = "", $join = "", $having = "") {
		// Make sure the table names are not postfixed (e.g. _Live)
		$oldMode = self::get_reading_mode();
		self::reading_stage('Stage');

		$query = $this->owner->extendedSQL($filter, $sort, $limit, $join, $having);

		foreach($query->from as $table => $tableJoin) {
			if($tableJoin[0] == '"') $baseTable = str_replace('"','',$tableJoin);
			else if (substr($tableJoin,0,5) != 'INNER') $query->from[$table] = "LEFT JOIN \"$table\" ON \"$table\".\"RecordID\" = \"{$baseTable}_versions\".\"RecordID\" AND \"$table\".\"Version\" = \"{$baseTable}_versions\".\"Version\"";
			$query->renameTable($table, $table . '_versions');
		}
		
		// Add all <basetable>_versions columns
		foreach(self::$db_for_versions_table as $name => $type) {
			$query->select[] = sprintf('"%s_versions"."%s"', $baseTable, $name);
		}
		
		$query->where[] = "\"{$baseTable}_versions\".\"RecordID\" = '{$this->owner->ID}'";
		$query->orderby = ($sort) ? $sort : "\"{$baseTable}_versions\".\"LastEdited\" DESC, \"{$baseTable}_versions\".\"Version\" DESC";
		
		$records = $query->execute();
		$versions = new DataObjectSet();
		
		foreach($records as $record) {
			$versions->push(new Versioned_Version($record));
		}
		
		Versioned::set_reading_mode($oldMode);
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
		
		$diff = new DataDifferencer($fromRecord, $toRecord);
		return $diff->diffedData();
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
			$stage = ucfirst(strtolower($_GET['stage']));
			
			if(!in_array($stage, array('Stage', 'Live'))) $stage = 'Live';

			Session::set('readingMode', 'Stage.' . $stage);
		}
		if(isset($_GET['archiveDate'])) {
			Session::set('readingMode', 'Archive.' . $_GET['archiveDate']);
		}
		
		if($mode = Session::get('readingMode')) {
			Versioned::set_reading_mode($mode);
		} else {
			Versioned::reading_stage("Live");
		}

		if(!headers_sent()) {
			if(Versioned::current_stage() == 'Live') {
				Cookie::set('bypassStaticCache', null, 0, null, null, false, true /* httponly */);
			} else {
				Cookie::set('bypassStaticCache', '1', 0, null, null, false, true /* httponly */);
			}
		}
	}
	
	/**
	 * Set the current reading mode.
	 */
	static function set_reading_mode($mode) {
		Versioned::$reading_mode = $mode;
	}
	
	/**
	 * Get the current reading mode.
	 * @return string
	 */
	static function get_reading_mode() {
		return Versioned::$reading_mode;
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
		$parts = explode('.', Versioned::get_reading_mode());
		if($parts[0] == 'Stage') return $parts[1];
	}
	
	/**
	 * Get the current archive date.
	 * @return string
	 */
	static function current_archived_date() {
		$parts = explode('.', Versioned::get_reading_mode());
		if($parts[0] == 'Archive') return $parts[1];
	}
	
	/**
	 * Set the reading stage.
	 * @param string $stage New reading stage.
	 */
	static function reading_stage($stage) {
		Versioned::set_reading_mode('Stage.' . $stage);
	}
	
	/**
	 * Set the reading archive date.
	 * @param string $date New reading archived date.
	 */
	static function reading_archived_date($date) {
		Versioned::set_reading_mode('Archive.' . $date);
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
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($stage);
		
		singleton($class)->flushCache();
		$result = DataObject::get_one($class, $filter, $cache, $orderby);
		singleton($class)->flushCache();

		Versioned::set_reading_mode($oldMode);
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
		$version = DB::query("SELECT \"Version\" FROM \"$stageTable\" WHERE \"ID\" = $id")->value();
		
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
			$filter = "WHERE \"ID\" IN(" .implode(", ", $idList) . ")";
		}
		
		$baseClass = ClassInfo::baseDataClass($class);
		$stageTable = ($stage == 'Stage') ? $baseClass : "{$baseClass}_{$stage}";

		$versions = DB::query("SELECT \"ID\", \"Version\" FROM \"$stageTable\" $filter")->map();
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
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($stage);
		$result = DataObject::get($class, $filter, $sort, $join, $limit, $containerClass);
		Versioned::set_reading_mode($oldMode);
		return $result;
	}
	
	function deleteFromStage($stage) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($stage);
		$clone = clone $this->owner;
		$result = $clone->delete();
		Versioned::set_reading_mode($oldMode);

		// Fix the version number cache (in case you go delete from stage and then check ExistsOnLive)
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		self::$cache_versionnumber[$baseClass][$stage][$this->owner->ID] = null;

		return $result;
	}
	
	function writeToStage($stage, $forceInsert = false) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($stage);
		$result = $this->owner->write(false, $forceInsert);
		Versioned::set_reading_mode($oldMode);
		return $result;
	}
		
	/**
	 * Build a SQL query to get data from the _version table.
	 * This function is similar in style to {@link DataObject::buildSQL}
	 */
	function buildVersionSQL($filter = "", $sort = "") {
		$query = $this->owner->extendedSQL($filter,$sort);
		foreach($query->from as $table => $join) {
			if($join[0] == '"') $baseTable = str_replace('"','',$join);
			else $query->from[$table] = "LEFT JOIN \"$table\" ON \"$table\".\"RecordID\" = \"{$baseTable}_versions\".\"RecordID\" AND \"$table\".\"Version\" = \"{$baseTable}_versions\".\"Version\"";
			$query->renameTable($table, $table . '_versions');
		}
		
		// Add all <basetable>_versions columns
		foreach(self::$db_for_versions_table as $name => $type) {
			$query->select[] = sprintf('"%s_versions"."%s"', $baseTable, $name);
		}
		$query->select[] = sprintf('"%s_versions"."%s" AS "ID"', $baseTable, 'RecordID');
		
		return $query;
	}

	static function build_version_sql($className, $filter = "", $sort = "") {
		$query = singleton($className)->extendedSQL($filter,$sort);
		foreach($query->from as $table => $join) {
			if($join[0] == '"') $baseTable = str_replace('"','',$join);
			else $query->from[$table] = "LEFT JOIN \"$table\" ON \"$table\".\"RecordID\" = \"{$baseTable}_versions\".\"RecordID\" AND \"$table\".\"Version\" = \"{$baseTable}_versions\".\"Version\"";
			$query->renameTable($table, $table . '_versions');
		}
		
		// Add all <basetable>_versions columns
		foreach(self::$db_for_versions_table as $name => $type) {
			$query->select[] = sprintf('"%s_versions"."%s"', $baseTable, $name);
		}
		$query->select[] = sprintf('"%s_versions"."%s" AS "ID"', $baseTable, 'RecordID');
		
		return $query;
	}
	
	/**
	 * Return the latest version of the given page.
	 * 
	 * @return DataObject
	 */
	static function get_latest_version($class, $id) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::set_reading_mode('');

		$baseTable = ClassInfo::baseDataClass($class);
		$query = singleton($class)->buildVersionSQL("\"{$baseTable}\".\"RecordID\" = $id", "\"{$baseTable}\".\"Version\" DESC");
		$query->limit = 1;
		$record = $query->execute()->record();
		if(!$record) return;
		
		$className = $record['ClassName'];
		if(!$className) {
			Debug::show($query->sql());
			Debug::show($record);
			user_error("Versioned::get_version: Couldn't get $class.$id", E_USER_ERROR);
		}

		Versioned::set_reading_mode($oldMode);

		return new $className($record);
	}

	/**
	 * Return the equivalent of a DataObject::get() call, querying the latest
	 * version of each page stored in the (class)_versions tables.
	 *
	 * In particular, this will query deleted records as well as active ones.
	 */
	static function get_including_deleted($class, $filter = "", $sort = "") {
		$query = self::get_including_deleted_query($class, $filter, $sort);
		
		// Process into a DataObjectSet
		$SNG = singleton($class);
		return $SNG->buildDataObjectSet($query->execute(), 'DataObjectSet', null, $class);
	}
	
	/**
	 * Return the query for the equivalent of a DataObject::get() call, querying the latest
	 * version of each page stored in the (class)_versions tables.
	 *
	 * In particular, this will query deleted records as well as active ones.
	 */
	static function get_including_deleted_query($class, $filter = "", $sort = "") {
		$oldMode = Versioned::get_reading_mode();
		Versioned::set_reading_mode('');

		$SNG = singleton($class);
		
		// Build query
		$query = $SNG->buildVersionSQL($filter, $sort);
		$baseTable = ClassInfo::baseDataClass($class);
		$archiveTable = self::requireArchiveTempTable($baseTable);
		$query->from[$archiveTable] = "INNER JOIN \"$archiveTable\"
			ON \"$archiveTable\".\"ID\" = \"{$baseTable}_versions\".\"RecordID\"
			AND \"$archiveTable\".\"Version\" = \"{$baseTable}_versions\".\"Version\"";

		Versioned::set_reading_mode($oldMode);
		return $query;
	}
	
	/**
	 * @return DataObject
	 */
	static function get_version($class, $id, $version) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::set_reading_mode('');

		$baseTable = ClassInfo::baseDataClass($class);
		$query = singleton($class)->buildVersionSQL("\"{$baseTable}\".\"RecordID\" = $id AND \"{$baseTable}\".\"Version\" = $version");
		$record = $query->execute()->record();
		$className = $record['ClassName'];
		if(!$className) {
			Debug::show($query->sql());
			Debug::show($record);
			user_error("Versioned::get_version: Couldn't get $class.$id, version $version", E_USER_ERROR);
		}

		Versioned::set_reading_mode($oldMode);
		return new $className($record);
	}

	/**
	 * @return DataObject
	 */
	static function get_all_versions($class, $id, $version) {
		$baseTable = ClassInfo::baseDataClass($class);
		$query = singleton($class)->buildVersionSQL("\"{$baseTable}\".\"RecordID\" = $id AND \"{$baseTable}\".\"Version\" = $version");
		$record = $query->execute()->record();
		$className = $record['ClassName'];
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
	
	protected static $reading_mode = null;
	
	function updateFieldLabels(&$labels) {
		$labels['Versions'] = _t('Versioned.has_many_Versions', 'Versions', PR_MEDIUM, 'Past Versions of this page');
	}
	
	function flushCache() {
		self::$cache_versionnumber = array();
	}

	/**
	 * Return a piece of text to keep DataObject cache keys appropriately specific
	 */
	function cacheKeyComponent() {
		return 'stage-'.self::current_stage();
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
