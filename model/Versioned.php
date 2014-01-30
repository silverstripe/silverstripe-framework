<?php

/**
 * The Versioned extension allows your DataObjects to have several versions, 
 * allowing you to rollback changes and view history. An example of this is 
 * the pages used in the CMS.
 *
 * @package framework
 * @subpackage model
 */
class Versioned extends DataExtension {

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
	 * @var string
	 */
	protected static $reading_mode = null;

	/**
	 * @var Boolean Flag which is temporarily changed during the write() process
	 * to influence augmentWrite() behaviour. If set to TRUE, no new version will be created
	 * for the following write. Needs to be public as other classes introspect this state
	 * during the write process in order to adapt to this versioning behaviour.
	 */
	public $_nextWriteWithoutVersion = false;

	/**
	 * Additional database columns for the new
	 * "_versions" table. Used in {@link augmentDatabase()}
	 * and all Versioned calls extending or creating
	 * SELECT statements.
	 * 
	 * @var array $db_for_versions_table
	 */
	private static $db_for_versions_table = array(
		"RecordID" => "Int",
		"Version" => "Int",
		"WasPublished" => "Boolean",
		"AuthorID" => "Int",
		"PublisherID" => "Int"
	);
	
	/**
	 * @var array
	 */
	private static $db = array(
		'Version' => 'Int'
	);

	/**
	 * Used to enable or disable the prepopulation of the version number cache.
	 * Defaults to true.
	 *
	 * @var boolean
	 */
	private static $prepopulate_versionnumber_cache = true;

	/**
	 * Keep track of the archive tables that have been created.
	 *
	 * @var array
	 */
	private static $archive_tables = array();

	/**
	 * Additional database indexes for the new
	 * "_versions" table. Used in {@link augmentDatabase()}.
	 * 
	 * @var array $indexes_for_versions_table
	 */
	private static $indexes_for_versions_table = array(
		'RecordID_Version' => '("RecordID","Version")',
		'RecordID' => true,
		'Version' => true,
		'AuthorID' => true,
		'PublisherID' => true,
	);

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

	/**
	 * Reset static configuration variables to their default values.
	 */
	public static function reset() {
		self::$reading_mode = '';

		Session::clear('readingMode');
	}
	
	/**
	 * Construct a new Versioned object.
	 *
	 * @var array $stages The different stages the versioned object can be.
	 * The first stage is considered the 'default' stage, the last stage is
	 * considered the 'live' stage.
	 */
	public function __construct($stages = array('Stage','Live')) {
		parent::__construct();

		if(!is_array($stages)) {
			$stages = func_get_args();
		}

		$this->stages = $stages;
		$this->defaultStage = reset($stages);
		$this->liveStage = array_pop($stages);
	}
	
	/**
	 * Amend freshly created DataQuery objects with versioned-specific 
	 * information.
	 *
	 * @param SQLQuery
	 * @param DataQuery
	 */
	public function augmentDataQueryCreation(SQLQuery &$query, DataQuery &$dataQuery) {
		$parts = explode('.', Versioned::get_reading_mode());

		if($parts[0] == 'Archive') {
			$dataQuery->setQueryParam('Versioned.mode', 'archive');
			$dataQuery->setQueryParam('Versioned.date', $parts[1]);

		} else if($parts[0] == 'Stage' && $parts[1] != $this->defaultStage 
				&& array_search($parts[1],$this->stages) !== false) {

			$dataQuery->setQueryParam('Versioned.mode', 'stage');
			$dataQuery->setQueryParam('Versioned.stage', $parts[1]);
		}
		
	}

	/**
	 * Augment the the SQLQuery that is created by the DataQuery
	 * @todo Should this all go into VersionedDataQuery?
	 */
	public function augmentSQL(SQLQuery &$query, DataQuery &$dataQuery = null) {
		$baseTable = ClassInfo::baseDataClass($dataQuery->dataClass());
		
		switch($dataQuery->getQueryParam('Versioned.mode')) {
		// Noop
		case '':
			break;

		// Reading a specific data from the archive
		case 'archive':
			$date = $dataQuery->getQueryParam('Versioned.date');
			foreach($query->getFrom() as $table => $dummy) {
				if(!DB::getConn()->hasTable($table . '_versions')) {
					continue;
				}

				$query->renameTable($table, $table . '_versions');
				$query->replaceText("\"{$table}_versions\".\"ID\"", "\"{$table}_versions\".\"RecordID\"");
				$query->replaceText("`{$table}_versions`.`ID`", "`{$table}_versions`.`RecordID`");
				
				// Add all <basetable>_versions columns
				foreach(Config::inst()->get('Versioned', 'db_for_versions_table') as $name => $type) {
					$query->selectField(sprintf('"%s_versions"."%s"', $baseTable, $name), $name);
				}
				$query->selectField(sprintf('"%s_versions"."%s"', $baseTable, 'RecordID'), "ID");

				if($table != $baseTable) {
					$query->addWhere("\"{$table}_versions\".\"Version\" = \"{$baseTable}_versions\".\"Version\"");
				}
			}
			// Link to the version archived on that date
			$safeDate = Convert::raw2sql($date);
			$query->addWhere(
					"\"{$baseTable}_versions\".\"Version\" IN 
					(SELECT LatestVersion FROM 
						(SELECT 
							\"{$baseTable}_versions\".\"RecordID\", 
							MAX(\"{$baseTable}_versions\".\"Version\") AS LatestVersion
							FROM \"{$baseTable}_versions\"
							WHERE \"{$baseTable}_versions\".\"LastEdited\" <= '$safeDate'
							GROUP BY \"{$baseTable}_versions\".\"RecordID\"
						) AS \"{$baseTable}_versions_latest\"
						WHERE \"{$baseTable}_versions_latest\".\"RecordID\" = \"{$baseTable}_versions\".\"RecordID\"
					)");
			break;
		
		// Reading a specific stage (Stage or Live)
		case 'stage':
			$stage = $dataQuery->getQueryParam('Versioned.stage');
			if($stage && ($stage != $this->defaultStage)) {
				foreach($query->getFrom() as $table => $dummy) {
					// Only rewrite table names that are actually part of the subclass tree
					// This helps prevent rewriting of other tables that get joined in, in
					// particular, many_many tables
					if(class_exists($table) && ($table == $this->owner->class 
							|| is_subclass_of($table, $this->owner->class) 
							|| is_subclass_of($this->owner->class, $table))) {
						$query->renameTable($table, $table . '_' . $stage);
					}
				}
			}
			break;

		// Reading a specific stage, but only return items that aren't in any other stage
		case 'stage_unique':
			$stage = $dataQuery->getQueryParam('Versioned.stage');

			// Recurse to do the default stage behavior (must be first, we rely on stage renaming happening before
			// below)
			$dataQuery->setQueryParam('Versioned.mode', 'stage');
			$this->augmentSQL($query, $dataQuery);
			$dataQuery->setQueryParam('Versioned.mode', 'stage_unique');

			// Now exclude any ID from any other stage. Note that we double rename to avoid the regular stage rename
			// renaming all subquery references to be Versioned.stage
			foreach($this->stages as $excluding) {
				if ($excluding == $stage) continue;

				$tempName = 'ExclusionarySource_'.$excluding;
				$excludingTable = $baseTable . ($excluding && $excluding != $this->defaultStage ? "_$excluding" : '');

				$query->addWhere('"'.$baseTable.'"."ID" NOT IN (SELECT "ID" FROM "'.$tempName.'")');
				$query->renameTable($tempName, $excludingTable);
			}
			break;

		// Return all version instances	
		case 'all_versions':
		case 'latest_versions':
			foreach($query->getFrom() as $alias => $join) {
				if($alias != $baseTable) {
					$query->setJoinFilter($alias, "\"$alias\".\"RecordID\" = \"{$baseTable}_versions\".\"RecordID\""
						. " AND \"$alias\".\"Version\" = \"{$baseTable}_versions\".\"Version\"");
				}
				$query->renameTable($alias, $alias . '_versions');
			}
		
			// Add all <basetable>_versions columns
			foreach(Config::inst()->get('Versioned', 'db_for_versions_table') as $name => $type) {
				$query->selectField(sprintf('"%s_versions"."%s"', $baseTable, $name), $name);
			}
			
			// Alias the record ID as the row ID
			$query->selectField(sprintf('"%s_versions"."%s"', $baseTable, 'RecordID'), "ID");
			
			// Ensure that any sort order referring to this ID is correctly aliased
			$orders = $query->getOrderBy();
			foreach($orders as $order => $dir) {
				if($order === "\"$baseTable\".\"ID\"") {
					unset($orders[$order]);
					$orders["\"{$baseTable}_versions\".\"RecordID\""] = $dir;
				}
			}
			$query->setOrderBy($orders);
			
			// latest_version has one more step
			// Return latest version instances, regardless of whether they are on a particular stage
			// This provides "show all, including deleted" functonality
			if($dataQuery->getQueryParam('Versioned.mode') == 'latest_versions') {
				$query->addWhere(
					"\"{$alias}_versions\".\"Version\" IN 
					(SELECT LatestVersion FROM 
						(SELECT 
							\"{$alias}_versions\".\"RecordID\", 
							MAX(\"{$alias}_versions\".\"Version\") AS LatestVersion
							FROM \"{$alias}_versions\"
							GROUP BY \"{$alias}_versions\".\"RecordID\"
						) AS \"{$alias}_versions_latest\"
						WHERE \"{$alias}_versions_latest\".\"RecordID\" = \"{$alias}_versions\".\"RecordID\"
					)");
			} else {
				// If all versions are requested, ensure that records are sorted by this field
				$query->addOrderBy(sprintf('"%s_versions"."%s"', $baseTable, 'Version'));
			}
			break;
		default:
			throw new InvalidArgumentException("Bad value for query parameter Versioned.mode: "
				. $dataQuery->getQueryParam('Versioned.mode'));
		}
	}

	/**
	 * For lazy loaded fields requiring extra sql manipulation, ie versioning.
	 *
	 * @param SQLQuery $query
	 * @param DataQuery $dataQuery
	 * @param DataObject $dataObject
	 */
	public function augmentLoadLazyFields(SQLQuery &$query, DataQuery &$dataQuery = null, $dataObject) {
		// The VersionedMode local variable ensures that this decorator only applies to 
		// queries that have originated from the Versioned object, and have the Versioned 
		// metadata set on the query object. This prevents regular queries from 
		// accidentally querying the *_versions tables.
		$versionedMode = $dataObject->getSourceQueryParam('Versioned.mode');
		$dataClass = $dataQuery->dataClass();
		$modesToAllowVersioning = array('all_versions', 'latest_versions', 'archive');
		if(
			!empty($dataObject->Version) &&
			(!empty($versionedMode) && in_array($versionedMode,$modesToAllowVersioning))
		) {
			$dataQuery->where("\"$dataClass\".\"RecordID\" = " . $dataObject->ID);
			$dataQuery->where("\"$dataClass\".\"Version\" = " . $dataObject->Version);
			$dataQuery->setQueryParam('Versioned.mode', 'all_versions');
		} else {
			// Same behaviour as in DataObject->loadLazyFields
			$dataQuery->where("\"$dataClass\".\"ID\" = {$dataObject->ID}")->limit(1);
		}
	}

	
	/**
	 * Called by {@link SapphireTest} when the database is reset.
	 *
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
	
	public function augmentDatabase() {
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
				$options = Config::inst()->get($this->owner->class, 'create_table_options', Config::FIRST_SET);
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
					// Change unique indexes to 'index'.  Versioned tables may run into unique indexing difficulties
					// otherwise.
					foreach($indexes as $key=>$index){
						if(is_array($index) && $index['type']=='unique'){
							$indexes[$key]['type']='index';
						}
					}
					
					if($stage != $this->defaultStage) {
						DB::requireTable("{$table}_$stage", $fields, $indexes, false, $options);
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
						Config::inst()->get('Versioned', 'db_for_versions_table'),
						(array)$fields
					);
				
					$versionIndexes = array_merge(
						Config::inst()->get('Versioned', 'indexes_for_versions_table'),
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
				
					//Unique indexes will not work on versioned tables, so we'll convert them to standard indexes:
					foreach($indexes as $key=>$index){
						if(is_array($index) && strtolower($index['type'])=='unique'){
							$indexes[$key]['type']='index';
						}
					}
					
					$versionIndexes = array_merge(
						array(
							'RecordID_Version' => array('type' => 'unique', 'value' => '"RecordID","Version"'),
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
										DB::query("DELETE FROM \"{$table}_versions\""
											. " WHERE \"{$table}_versions\".\"ID\" = '$value'");
									}
								}
							}
						}
					}
				}

				DB::requireTable("{$table}_versions", $versionFields, $versionIndexes, true, $options);
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
	 *
	 * @param SQLQuery $manipulation Query to augment.
	 */
	public function augmentWrite(&$manipulation) {
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
					if (!isset($newManipulation['fields'][$k])) {
						$newManipulation['fields'][$k] = "'" . Convert::raw2sql($v) . "'";
					}
				}

				// Set up a new entry in (table)_versions
				$newManipulation['fields']['RecordID'] = $rid;
				unset($newManipulation['fields']['ID']);

				// Create a new version #
				if (isset($version_table[$table])) $nextVersion = $version_table[$table];
				else unset($nextVersion);

				if($rid && !isset($nextVersion)) {
					$nextVersion = DB::query("SELECT MAX(\"Version\") + 1 FROM \"{$baseDataClass}_versions\""
						. " WHERE \"RecordID\" = $rid")->value();
				}
				
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
			if($manipulation[$table]['fields']['Version'] < 0 || $this->_nextWriteWithoutVersion) {
				unset($manipulation[$table]['fields']['Version']);
			}

			if(!$this->hasVersionField($table)) unset($manipulation[$table]['fields']['Version']);
			
			// Grab a version number - it should be the same across all tables.
			if(isset($manipulation[$table]['fields']['Version'])) {
				$thisVersion = $manipulation[$table]['fields']['Version'];
			}
			
			// If we're editing Live, then use (table)_Live instead of (table)
			if(
				Versioned::current_stage() 
				&& Versioned::current_stage() != $this->defaultStage
				&& in_array(Versioned::current_stage(), $this->stages)
			) {
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
		if($this->migratingVersion) {
			$this->migrateVersion(null);
		}

		// Add the new version # back into the data object, for accessing 
		// after this write
		if(isset($thisVersion)) {
			$this->owner->Version = str_replace("'","", $thisVersion);
		}
	}

	/**
	 * Perform a write without affecting the version table.
	 * On objects without versioning.
	 *
	 * @return int The ID of the record
	 */
	public function writeWithoutVersion() {
		$this->_nextWriteWithoutVersion = true;

		return $this->owner->write();
	}

	/**
	 *
	 */
	public function onAfterWrite() {
		$this->_nextWriteWithoutVersion = false;
	}

	/**
	 * If a write was skipped, then we need to ensure that we don't leave a 
	 * migrateVersion() value lying around for the next write.
	 *
	 *
	 */
	public function onAfterSkippedWrite() {
		$this->migrateVersion(null);
	}
	
	/**
	 * Determine if a table is supporting the Versioned extensions (e.g. 
	 * $table_versions does exists).
	 *
	 * @param string $table Table name
	 * @return boolean
	 */
	public function canBeVersioned($table) {
		return ClassInfo::exists($table) 
			&& is_subclass_of($table, 'DataObject')
			&& DataObject::has_own_table($table);
	}
	
	/**
	 * Check if a certain table has the 'Version' field.
	 *
	 * @param string $table Table name
	 *
	 * @return boolean Returns false if the field isn't in the table, true otherwise
	 */
	public function hasVersionField($table) {
		$rPos = strrpos($table,'_');

		if(($rPos !== false) && in_array(substr($table,$rPos), $this->stages)) {
			$tableWithoutStage = substr($table,0,$rPos);
		} else {
			$tableWithoutStage = $table;
		}

		return ('DataObject' == get_parent_class($tableWithoutStage));
	}

	/**
	 * @param string $table
	 *
	 * @return string
	 */
	public function extendWithSuffix($table) {
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

	/**
	 * Get the latest published DataObject.
	 *
	 * @return DataObject
	 */
	public function latestPublished() {
		// Get the root data object class - this will have the version field
		$table1 = $this->owner->class;
		while( ($p = get_parent_class($table1)) != "DataObject") $table1 = $p;
		
		$table2 = $table1 . "_$this->liveStage";

		return DB::query("SELECT \"$table1\".\"Version\" = \"$table2\".\"Version\" FROM \"$table1\""
			. " INNER JOIN \"$table2\" ON \"$table1\".\"ID\" = \"$table2\".\"ID\""
			. " WHERE \"$table1\".\"ID\" = ".  $this->owner->ID)->value();
	}
	
	/**
	 * Move a database record from one stage to the other.
	 *
	 * @param fromStage Place to copy from.  Can be either a stage name or a version number.
	 * @param toStage Place to copy to.  Must be a stage name.
	 * @param createNewVersion Set this to true to create a new version number.  By default, the existing version
	 *                         number will be copied over.
	 */
	public function publish($fromStage, $toStage, $createNewVersion = false) {
		$this->owner->extend('onBeforeVersionedPublish', $fromStage, $toStage, $createNewVersion);
		
		$baseClass = $this->owner->class;
		while( ($p = get_parent_class($baseClass)) != "DataObject") $baseClass = $p;
		$extTable = $this->extendWithSuffix($baseClass);
		
		if(is_numeric($fromStage)) {
			$from = Versioned::get_version($baseClass, $this->owner->ID, $fromStage);
		} else {
			$this->owner->flushCache();
			$from = Versioned::get_one_by_stage($baseClass, $fromStage, "\"{$baseClass}\".\"ID\"={$this->owner->ID}");
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
			DB::query("UPDATE \"{$extTable}_versions\" SET \"WasPublished\" = '1', \"PublisherID\" = $publisherID"
				. " WHERE \"RecordID\" = $from->ID AND \"Version\" = $from->Version");

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
	 *
	 * @param string $version The version.
	 */
	public function migrateVersion($version) {
		$this->migratingVersion = $version;
	}
	
	/**
	 * Compare two stages to see if they're different.
	 *
	 * Only checks the version numbers, not the actual content.
	 *
	 * @param string $stage1 The first stage to check.
	 * @param string $stage2
	 */
	public function stagesDiffer($stage1, $stage2) {
		$table1 = $this->baseTable($stage1);
		$table2 = $this->baseTable($stage2);
		
		if(!is_numeric($this->owner->ID)) {
			return true;
		}

		// We test for equality - if one of the versions doesn't exist, this 
		// will be false.

		// TODO: DB Abstraction: if statement here:
		$stagesAreEqual = DB::query("SELECT CASE WHEN \"$table1\".\"Version\"=\"$table2\".\"Version\""
			. " THEN 1 ELSE 0 END FROM \"$table1\" INNER JOIN \"$table2\" ON \"$table1\".\"ID\" = \"$table2\".\"ID\""
			. " AND \"$table1\".\"ID\" = {$this->owner->ID}")->value();

		return !$stagesAreEqual;
	}
	
	/**
	 * @param string $filter 
	 * @param string $sort   
	 * @param string $limit  
	 * @param string $join Deprecated, use leftJoin($table, $joinClause) instead
	 * @param string $having 
	 */
	public function Versions($filter = "", $sort = "", $limit = "", $join = "", $having = "") {
		return $this->allVersions($filter, $sort, $limit, $join, $having);
	}
	
	/**
	 * Return a list of all the versions available.
	 * 
	 * @param  string $filter 
	 * @param  string $sort   
	 * @param  string $limit  
	 * @param  string $join   Deprecated, use leftJoin($table, $joinClause) instead
	 * @param  string $having 
	 */
	public function allVersions($filter = "", $sort = "", $limit = "", $join = "", $having = "") {
		// Make sure the table names are not postfixed (e.g. _Live)
		$oldMode = self::get_reading_mode();
		self::reading_stage('Stage');
		
		$list = DataObject::get(get_class($this->owner), $filter, $sort, $join, $limit);
		if($having) $having = $list->having($having);
		
		$query = $list->dataQuery()->query();

		foreach($query->getFrom() as $table => $tableJoin) {
			if(is_string($tableJoin) && $tableJoin[0] == '"') {
				$baseTable = str_replace('"','',$tableJoin);
			} elseif(is_string($tableJoin) && substr($tableJoin,0,5) != 'INNER') {
				$query->setFrom(array(
					$table => "LEFT JOIN \"$table\" ON \"$table\".\"RecordID\"=\"{$baseTable}_versions\".\"RecordID\""
						. " AND \"$table\".\"Version\" = \"{$baseTable}_versions\".\"Version\""
				));
			}
			$query->renameTable($table, $table . '_versions');
		}
		
		// Add all <basetable>_versions columns
		foreach(Config::inst()->get('Versioned', 'db_for_versions_table') as $name => $type) {
			$query->selectField(sprintf('"%s_versions"."%s"', $baseTable, $name), $name);
		}
		
		$query->addWhere("\"{$baseTable}_versions\".\"RecordID\" = '{$this->owner->ID}'");
		$query->setOrderBy(($sort) ? $sort 
			: "\"{$baseTable}_versions\".\"LastEdited\" DESC, \"{$baseTable}_versions\".\"Version\" DESC");

		$records = $query->execute();
		$versions = new ArrayList();

		foreach($records as $record) {
			$versions->push(new Versioned_Version($record));
		}
		
		Versioned::set_reading_mode($oldMode);
		return $versions;
	}
	
	/**
	 * Compare two version, and return the diff between them.
	 *
	 * @param string $from The version to compare from.
	 * @param string $to The version to compare to.
	 *
	 * @return DataObject
	 */
	public function compareVersions($from, $to) {
		$fromRecord = Versioned::get_version($this->owner->class, $this->owner->ID, $from);
		$toRecord = Versioned::get_version($this->owner->class, $this->owner->ID, $to);
		
		$diff = new DataDifferencer($fromRecord, $toRecord);

		return $diff->diffedData();
	}
	
	/**
	 * Return the base table - the class that directly extends DataObject.
	 *
	 * @return string
	 */
	public function baseTable($stage = null) {
		$tableClasses = ClassInfo::dataClassesFor($this->owner->class);
		$baseClass = array_shift($tableClasses);

		if(!$stage || $stage == $this->defaultStage) {
			return $baseClass;
		}

		return $baseClass . "_$stage";		
	}
		
	//-----------------------------------------------------------------------------------------------//
	
	/**
	 * Choose the stage the site is currently on.
	 *
	 * If $_GET['stage'] is set, then it will use that stage, and store it in 
	 * the session.
	 *
	 * if $_GET['archiveDate'] is set, it will use that date, and store it in 
	 * the session.
	 *
	 * If neither of these are set, it checks the session, otherwise the stage 
	 * is set to 'Live'.
	 */
	public static function choose_site_stage() {
		if(isset($_GET['stage'])) {
			$stage = ucfirst(strtolower($_GET['stage']));
			
			if(!in_array($stage, array('Stage', 'Live'))) $stage = 'Live';

			Session::set('readingMode', 'Stage.' . $stage);
		}
		if(isset($_GET['archiveDate']) && strtotime($_GET['archiveDate'])) {
			Session::set('readingMode', 'Archive.' . $_GET['archiveDate']);
		}
		
		if($mode = Session::get('readingMode')) {
			Versioned::set_reading_mode($mode);
		} else {
			Versioned::reading_stage("Live");
		}

		if(!headers_sent() && !Director::is_cli()) {
			if(Versioned::current_stage() == 'Live') {
				// clear the cookie if it's set
				if(!empty($_COOKIE['bypassStaticCache'])) {
					Cookie::set('bypassStaticCache', null, 0, null, null, false, true /* httponly */);
					unset($_COOKIE['bypassStaticCache']);
				}
			} else {
				// set the cookie if it's cleared
				if(empty($_COOKIE['bypassStaticCache'])) {
					Cookie::set('bypassStaticCache', '1', 0, null, null, false, true /* httponly */);
					$_COOKIE['bypassStaticCache'] = 1;
				}
			}
		}
	}
	
	/**
	 * Set the current reading mode.
	 *
	 * @param string $mode
	 */
	public static function set_reading_mode($mode) {
		Versioned::$reading_mode = $mode;
	}
	
	/**
	 * Get the current reading mode.
	 *
	 * @return string
	 */
	public static function get_reading_mode() {
		return Versioned::$reading_mode;
	}
	
	/**
	 * Get the name of the 'live' stage.
	 *
	 * @return string
	 */
	public static function get_live_stage() {
		return "Live";
	}
	
	/**
	 * Get the current reading stage.
	 *
	 * @return string
	 */
	public static function current_stage() {
		$parts = explode('.', Versioned::get_reading_mode());

		if($parts[0] == 'Stage') {
			return $parts[1];
		}
	}
	
	/**
	 * Get the current archive date.
	 *
	 * @return string
	 */
	public static function current_archived_date() {
		$parts = explode('.', Versioned::get_reading_mode());
		if($parts[0] == 'Archive') return $parts[1];
	}
	
	/**
	 * Set the reading stage.
	 *
	 * @param string $stage New reading stage.
	 */
	public static function reading_stage($stage) {
		Versioned::set_reading_mode('Stage.' . $stage);
	}
	
	/**
	 * Set the reading archive date.
	 *
	 * @param string $date New reading archived date.
	 */
	public static function reading_archived_date($date) {
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
	 *
	 * @return DataObject
	 */
	public static function get_one_by_stage($class, $stage, $filter = '', $cache = true, $sort = '') {
		// TODO: No identity cache operating
		$items = self::get_by_stage($class, $stage, $filter, $sort, null, 1);

		return $items->First();
	}
	
	/**
	 * Gets the current version number of a specific record.
	 * 
	 * @param string $class
	 * @param string $stage
	 * @param int $id
	 * @param boolean $cache
	 *
	 * @return int
	 */
	public static function get_versionnumber_by_stage($class, $stage, $id, $cache = true) {
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
			if(!isset(self::$cache_versionnumber[$baseClass])) {
				self::$cache_versionnumber[$baseClass] = array();
			}

			if(!isset(self::$cache_versionnumber[$baseClass][$stage])) {
				self::$cache_versionnumber[$baseClass][$stage] = array();
			}

			self::$cache_versionnumber[$baseClass][$stage][$id] = $version;
		}
		
		return $version;
	}
	
	/**
	 * Pre-populate the cache for Versioned::get_versionnumber_by_stage() for 
	 * a list of record IDs, for more efficient database querying.  If $idList 
	 * is null, then every page will be pre-cached.
	 *
	 * @param string $class
	 * @param string $stage
	 * @param array $idList
	 */
	public static function prepopulate_versionnumber_cache($class, $stage, $idList = null) {
		if (!Config::inst()->get('Versioned', 'prepopulate_versionnumber_cache')) {
			return;
		}
		$filter = "";

		if($idList) {
			// Validate the ID list
			foreach($idList as $id) {
				if(!is_numeric($id)) {
					user_error("Bad ID passed to Versioned::prepopulate_versionnumber_cache() in \$idList: " . $id,
					E_USER_ERROR);
				}
			}

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
	 * @param string $join Deprecated, use leftJoin($table, $joinClause) instead
	 * @param int $limit A limit on the number of records returned from the database.
	 * @param string $containerClass The container class for the result set (default is DataList)
	 *
	 * @return SS_List
	 */
	public static function get_by_stage($class, $stage, $filter = '', $sort = '', $join = '', $limit = '',
			$containerClass = 'DataList') {

		$result = DataObject::get($class, $filter, $sort, $join, $limit, $containerClass);
		return $result->setDataQueryParam(array(
			'Versioned.mode' => 'stage',
			'Versioned.stage' => $stage
		));
	}
	
	/**
	 * @param string $stage
	 *
	 * @return int
	 */
	public function deleteFromStage($stage) {
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
	
	/**
	 * @param string $stage
	 * @param boolean $forceInsert
	 */
	public function writeToStage($stage, $forceInsert = false) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($stage);

		$result = $this->owner->write(false, $forceInsert);
		Versioned::set_reading_mode($oldMode);

		return $result;
	}

	/**
	 * Roll the draft version of this page to match the published page.
	 * Caution: Doesn't overwrite the object properties with the rolled back version.
	 * 
	 * @param int $version Either the string 'Live' or a version number
	 */
	public function doRollbackTo($version) {
		$this->owner->extend('onBeforeRollback', $version);
		$this->publish($version, "Stage", true);

		$this->owner->writeWithoutVersion();

		$this->owner->extend('onAfterRollback', $version);
	}
	
	/**
	 * Return the latest version of the given page.
	 * 
	 * @return DataObject
	 */
	public static function get_latest_version($class, $id) {
		$baseClass = ClassInfo::baseDataClass($class);
		$list = DataList::create($baseClass)
			->where("\"$baseClass\".\"RecordID\" = $id")
			->setDataQueryParam("Versioned.mode", "latest_versions");

		return $list->First();
	}
	
	/**
	 * Returns whether the current record is the latest one.
	 *
	 * @todo Performance - could do this directly via SQL.
	 *
	 * @see get_latest_version()
	 * @see latestPublished
	 *
	 * @return boolean
	 */
	public function isLatestVersion() {
		$version = self::get_latest_version($this->owner->class, $this->owner->ID);
		
		return ($version->Version == $this->owner->Version);
	}

	/**
	 * Return the equivalent of a DataList::create() call, querying the latest
	 * version of each page stored in the (class)_versions tables.
	 *
	 * In particular, this will query deleted records as well as active ones.
	 *
	 * @param string $class
	 * @param string $filter
	 * @param string $sort
	 */
	public static function get_including_deleted($class, $filter = "", $sort = "") {
		$list = DataList::create($class)
			->where($filter)
			->sort($sort)
			->setDataQueryParam("Versioned.mode", "latest_versions");

		return $list;
	}
	
	/**
	 * Return the specific version of the given id.
	 *
	 * Caution: The record is retrieved as a DataObject, but saving back 
	 * modifications via write() will create a new version, rather than 
	 * modifying the existing one.
	 * 
	 * @param string $class
	 * @param int $id
	 * @param int $version
	 *
	 * @return DataObject
	 */
	public static function get_version($class, $id, $version) {
		$baseClass = ClassInfo::baseDataClass($class);
		$list = DataList::create($baseClass)
			->where("\"$baseClass\".\"RecordID\" = $id")
			->where("\"$baseClass\".\"Version\" = " . (int)$version)
			->setDataQueryParam("Versioned.mode", 'all_versions');

		return $list->First();
	}

	/**
	 * Return a list of all versions for a given id.
	 *
	 * @param string $class
	 * @param int $id
	 *
	 * @return DataList
	 */
	public static function get_all_versions($class, $id) {
		$baseClass = ClassInfo::baseDataClass($class);
		$list = DataList::create($class)
			->where("\"$baseClass\".\"RecordID\" = $id")
			->setDataQueryParam('Versioned.mode', 'all_versions');

		return $list;
	}

	/**
	 * @param array $labels
	 */
	public function updateFieldLabels(&$labels) {
		$labels['Versions'] = _t('Versioned.has_many_Versions', 'Versions', 'Past Versions of this page');
	}
	
	/**
	 * @param FieldList
	 */
	public function updateCMSFields(FieldList $fields) {
		// remove the version field from the CMS as this should be left 
		// entirely up to the extension (not the cms user). 
		$fields->removeByName('Version');
	}

	public function flushCache() {
		self::$cache_versionnumber = array();
	}

	/**
	 * Return a piece of text to keep DataObject cache keys appropriately specific.
	 *
	 * @return string
	 */
	public function cacheKeyComponent() {
		return 'versionedmode-'.self::get_reading_mode();
	}

	/**
	 * Returns an array of possible stages.
	 *
	 * @return array
	 */
	public function getVersionedStages() {
		return $this->stages;
	}

	/**
	 * @return string
	 */
	public function getDefaultStage() {
		return $this->defaultStage;
	}
}

/**
 * Represents a single version of a record.
 *
 * @package framework
 * @subpackage model
 *
 * @see Versioned
 */
class Versioned_Version extends ViewableData {
	/**
	 * @var array
	 */
	protected $record;

	/**
	 * @var DataObject
	 */
	protected $object;
	
	public function __construct($record) {
		$this->record = $record;
		$record['ID'] = $record['RecordID'];
		$className = $record['ClassName'];
		
		$this->object = ClassInfo::exists($className) ? new $className($record) : new DataObject($record);
		$this->failover = $this->object;
		
		parent::__construct();
	}
	
	/**
	 * @return string
	 */
	public function PublishedClass() {
		return $this->record['WasPublished'] ? 'published' : 'internal';
	}
	
	/**
	 * @return Member
	 */
	public function Author() {
		return Member::get()->byId($this->record['AuthorID']);
	}
	
	/**
	 * @return Member
	 */
	public function Publisher() {
		if (!$this->record['WasPublished']) {
			return null;
		}
			
		return Member::get()->byId($this->record['PublisherID']);
	}
	
	/**
	 * @return boolean
	 */
	public function Published() {
		return !empty($this->record['WasPublished']);
	}

	/**
	 * Copied from DataObject to allow access via dot notation.
	 */
	public function relField($fieldName) {
		$component = $this;

		if(strpos($fieldName, '.') !== false) {
			$parts = explode('.', $fieldName);
			$fieldName = array_pop($parts);

			// Traverse dot syntax
			foreach($parts as $relation) {
				if($component instanceof SS_List) {
					if(method_exists($component,$relation)) {
						$component = $component->$relation();
					} else {
						$component = $component->relation($relation);
					}
				} else {
					$component = $component->$relation();
				}
			}
		}

		// Unlike has-one's, these "relations" can return false
		if($component) {
			if ($component->hasMethod($fieldName)) {
				return $component->$fieldName();
			}

			return $component->$fieldName;
		}
	}
}
