<?php

/**
 * The Versioned extension allows your DataObjects to have several versions, allowing you to rollback changes and view
 * history. An example of this is the pages used in the CMS.
 *
 * @property int $Version
 *
 * @package framework
 * @subpackage model
 *
 * @property DataObject owner
 * @property int  RecordID
 * @property int  Version
 * @property bool WasPublished
 * @property int  AuthorID
 * @property int  PublisherID
 */
class Versioned extends DataExtension implements TemplateGlobalProvider {

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
	 * The default reading mode
	 */
	const DEFAULT_MODE = 'Stage.Live';

	/**
	 * A version that a DataObject should be when it is 'migrating', that is, when it is in the process of moving from
	 * one stage to another.
	 * @var string
	 */
	public $migratingVersion;

	/**
	 * A cache used by get_versionnumber_by_stage(). Clear through {@link flushCache()}.
	 * @var array
	 */
	protected static $cache_versionnumber;

	/** @var string */
	protected static $reading_mode = null;

	/**
	 * Flag which is temporarily changed during the write() process to influence augmentWrite() behaviour. If set to
	 * true, no new version will be created for the following write. Needs to be public as other classes introspect this
	 * state during the write process in order to adapt to this versioning behaviour.
	 * @var bool
	 */
	public $_nextWriteWithoutVersion = false;

	/**
	 * Additional database columns for the new "_versions" table. Used in {@link augmentDatabase()} and all Versioned
	 * calls extending or creating SELECT statements.
	 * @var array
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
	 * @config
	 */
	private static $db = array(
		'Version' => 'Int'
	);

	/**
	 * Used to enable or disable the prepopulation of the version number cache. Defaults to true.
	 * @var bool
   	 */
	private static $prepopulate_versionnumber_cache = true;

	/**
	 * Keep track of the archive tables that have been created.
	 * @var array
	 */
	private static $archive_tables = array();

	/**
	 * Additional database indexes for the new "_versions" table. Used in {@link augmentDatabase()}.
	 * @var array
	 */
	private static $indexes_for_versions_table = array(
		'RecordID_Version' => '("RecordID","Version")',
		'RecordID' => true,
		'Version' => true,
		'AuthorID' => true,
		'PublisherID' => true,
	);


	/**
	 * An array of DataObject extensions that may require versioning for extra tables. The array value is a set of
	 * suffixes to form these table names, assuming a preceding '_'. E.g. if Extension1 creates a new table
	 * 'Class_suffix1' and Extension2 the tables 'Class_suffix2' and 'Class_suffix3':
	 *
	 * 	$versionableExtensions = array(
	 * 		'Extension1' => 'suffix1',
	 * 		'Extension2' => array('suffix2', 'suffix3'),
	 * 	);
	 *
	 * This can also be manipulated by updating the current loaded config
	 *
	 * SiteTree:
	 *   versionableExtensions:
	 *     - Extension1:
	 *       - suffix1
	 *       - suffix2
	 *     - Extension2:
	 *       - suffix1
	 *       - suffix2
	 *
	 * or programatically:
	 *
	 *  Config::inst()->update($this->owner->class, 'versionableExtensions',
	 *  array('Extension1' => 'suffix1', 'Extension2' => array('suffix2', 'suffix3')));
	 *
	 * Make sure your extension has a static $enabled-property that determines if it is processed by Versioned.
	 *
	 * @var array
	 */
	protected static $versionableExtensions = array('Translatable' => 'lang');

	/**
	 * Permissions necessary to view records outside of the live stage (e.g. archive / draft stage).
	 *
	 * @config
	 * @var array
	 */
	private static $non_live_permissions = array('CMS_ACCESS_LeftAndMain', 'CMS_ACCESS_CMSMain', 'VIEW_DRAFT_CONTENT');

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
	 * @param array $stages The different stages the versioned object can be. The first stage is considered the
	 *                      'default' stage, the last stage is considered the 'live' stage.
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
	 * Amend freshly created DataQuery objects with versioned-specific information.
	 *
	 * @param SQLQuery  $query
	 * @param DataQuery $dataQuery
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
	 * Augment the the SQLQuery that is created by the DataQuery.
	 * @todo Should this all go into VersionedDataQuery?
	 *
	 * @param SQLQuery  $query
	 * @param DataQuery $dataQuery
	 */
	public function augmentSQL(SQLQuery &$query, DataQuery &$dataQuery = null) {
		if(!$dataQuery || !$dataQuery->getQueryParam('Versioned.mode')) {
			return;
		}

		$baseTable = ClassInfo::baseDataClass($dataQuery->dataClass());

		switch($dataQuery->getQueryParam('Versioned.mode')) {
		// Reading a specific data from the archive
		case 'archive':
			$date = $dataQuery->getQueryParam('Versioned.date');
			foreach($query->getFrom() as $table => $dummy) {
				if(!DB::get_schema()->hasTable($table . '_versions')) {
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
			$query->addWhere(array(
				"\"{$baseTable}_versions\".\"Version\" IN
				(SELECT LatestVersion FROM
					(SELECT
						\"{$baseTable}_versions\".\"RecordID\",
						MAX(\"{$baseTable}_versions\".\"Version\") AS LatestVersion
						FROM \"{$baseTable}_versions\"
						WHERE \"{$baseTable}_versions\".\"LastEdited\" <= ?
						GROUP BY \"{$baseTable}_versions\".\"RecordID\"
					) AS \"{$baseTable}_versions_latest\"
					WHERE \"{$baseTable}_versions_latest\".\"RecordID\" = \"{$baseTable}_versions\".\"RecordID\"
				)" => $date
			));
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
				if($order === "\"$baseTable\".\"ID\"" || trim($order, "'\"") === "ID") {
					unset($orders[$order]);
					$orders["\"{$baseTable}_versions\".\"RecordID\""] = $dir;
				}
			}
			$query->setOrderBy($orders);

			// latest_version has one more step. Return latest version instances, regardless of whether they are on a
			// particular stage. This provides "show all, including deleted" functonality
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
	 * For lazy loaded fields requiring extra SQL manipulation, ie versioning.
	 *
	 * @param SQLQuery   $query
	 * @param DataQuery  $dataQuery
	 * @param DataObject $dataObject
	 */
	public function augmentLoadLazyFields(SQLQuery &$query, DataQuery &$dataQuery = null, $dataObject) {
		// The VersionedMode local variable ensures that this decorator only applies to queries that have originated
		// from the Versioned object, and have the Versioned metadata set on the query object. This prevents regular
		// queries from accidentally querying the *_versions tables.
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
		$db = DB::get_conn();
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
		$versionableExtensions = $this->owner->config()->versionableExtensions;
		if(count($versionableExtensions)){

			foreach ($versionableExtensions as $versionableExtension => $suffixes) {
				if ($this->owner->hasExtension($versionableExtension)) {
					$allSuffixes = array_merge($allSuffixes, (array)$suffixes);
					foreach ((array)$suffixes as $suffix) {
						$allSuffixes[$suffix] = $versionableExtension;
					}
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
					$indexes = $this->uniqueToIndex($indexes);
					if($stage != $this->defaultStage) {
						DB::require_table("{$table}_$stage", $fields, $indexes, false, $options);
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
					$indexes = $this->uniqueToIndex($indexes);
					$versionIndexes = array_merge(
						array(
							'RecordID_Version' => array('type' => 'unique', 'value' => '"RecordID","Version"'),
							'RecordID' => true,
							'Version' => true,
						),
						(array)$indexes
					);
				}

				if(DB::get_schema()->hasTable("{$table}_versions")) {
					// Fix data that lacks the uniqueness constraint (since this was added later and bugs meant that
					// the constraint was validated)
					$duplications = DB::query("SELECT MIN(\"ID\") AS \"ID\", \"RecordID\", \"Version\"
						FROM \"{$table}_versions\" GROUP BY \"RecordID\", \"Version\"
						HAVING COUNT(*) > 1");

					foreach($duplications as $dup) {
						DB::alteration_message("Removing {$table}_versions duplicate data for "
							."{$dup['RecordID']}/{$dup['Version']}" ,"deleted");
						DB::prepared_query(
							"DELETE FROM \"{$table}_versions\" WHERE \"RecordID\" = ?
							AND \"Version\" = ? AND \"ID\" != ?",
							array($dup['RecordID'], $dup['Version'], $dup['ID'])
						);
					}

					// Remove junk which has no data in parent classes. Only needs to run the following when versioned
					// data is spread over multiple tables
					if(!$isRootClass && ($versionedTables = ClassInfo::dataClassesFor($table))) {

						foreach($versionedTables as $child) {
							if($table === $child) break; // only need subclasses

							// Select all orphaned version records
							$orphanedQuery = SQLSelect::create()
								->selectField("\"{$table}_versions\".\"ID\"")
								->setFrom("\"{$table}_versions\"");

							// If we have a parent table limit orphaned records
							// to only those that exist in this
							if(DB::get_schema()->hasTable("{$child}_versions")) {
								$orphanedQuery
									->addLeftJoin(
										"{$child}_versions",
										"\"{$child}_versions\".\"RecordID\" = \"{$table}_versions\".\"RecordID\"
										AND \"{$child}_versions\".\"Version\" = \"{$table}_versions\".\"Version\""
									)
									->addWhere("\"{$child}_versions\".\"ID\" IS NULL");
							}

							$count = $orphanedQuery->count();
							if($count > 0) {
								DB::alteration_message("Removing {$count} orphaned versioned records", "deleted");
								$ids = $orphanedQuery->execute()->column();
								foreach($ids as $id) {
									DB::prepared_query(
										"DELETE FROM \"{$table}_versions\" WHERE \"ID\" = ?",
										array($id)
									);
								}
							}
						}
					}
				}

				DB::require_table("{$table}_versions", $versionFields, $versionIndexes, true, $options);
			} else {
				DB::dont_require_table("{$table}_versions");
				foreach($this->stages as $stage) {
					if($stage != $this->defaultStage) DB::dont_require_table("{$table}_$stage");
				}
			}
		}
	}

	/**
	 * Helper for augmentDatabase() to find unique indexes and convert them to non-unique
	 *
	 * @param array $indexes The indexes to convert
	 * @return array $indexes
	 */
	private function uniqueToIndex($indexes) {
		$unique_regex = '/unique/i';
		$results = array();
		foreach ($indexes as $key => $index) {
			$results[$key] = $index;

			// support string descriptors
			if (is_string($index)) {
				if (preg_match($unique_regex, $index)) {
					$results[$key] = preg_replace($unique_regex, 'index', $index);
				}
			}

			// canonical, array-based descriptors
			elseif (is_array($index)) {
				if (strtolower($index['type']) == 'unique') {
					$results[$key]['type'] = 'index';
				}
			}
		}
		return $results;
	}

	/**
	 * Generates a ($table)_version DB manipulation and injects it into the current $manipulation
	 *
	 * @param SQLQuery $manipulation The query to augment
	 * @param string $table
	 * @param string|int $recordID
	 */
	protected function augmentWriteVersioned(&$manipulation, $table, $recordID) {
		$baseDataClass = ClassInfo::baseDataClass($table);

		// Set up a new entry in (table)_versions
		$newManipulation = array(
			"command" => "insert",
			"fields" => isset($manipulation[$table]['fields']) ? $manipulation[$table]['fields'] : null
		);

		// Add any extra, unchanged fields to the version record.
		$data = DB::prepared_query("SELECT * FROM \"$table\" WHERE \"ID\" = ?", array($recordID))->record();

		if ($data) {
			$fields = DataObject::database_fields($table);

			if (is_array($fields)) {
				$data = array_intersect_key($data, $fields);

				foreach ($data as $k => $v) {
					if (!isset($newManipulation['fields'][$k])) {
						$newManipulation['fields'][$k] = $v;
					}
				}
			}
		}

		// Ensure that the ID is instead written to the RecordID field
		$newManipulation['fields']['RecordID'] = $recordID;
		unset($newManipulation['fields']['ID']);

		// Generate next version ID to use
		$nextVersion = 0;
		if($recordID) {
			$nextVersion = DB::prepared_query("SELECT MAX(\"Version\") + 1
				FROM \"{$baseDataClass}_versions\" WHERE \"RecordID\" = ?",
				array($recordID)
			)->value();
		}
		$nextVersion = $nextVersion ?: 1;

		if($table === $baseDataClass) {
			// Write AuthorID for baseclass
			$userID = (Member::currentUser()) ? Member::currentUser()->ID : 0;
			$newManipulation['fields']['AuthorID'] = $userID;

			// Update main table version if not previously known
			$manipulation[$table]['fields']['Version'] = $nextVersion;
		}

		// Update _versions table manipulation
		$newManipulation['fields']['Version'] = $nextVersion;
		$manipulation["{$table}_versions"] = $newManipulation;
	}

	/**
	 * Rewrite the given manipulation to update the selected (non-default) stage
	 *
	 * @param array $manipulation Source manipulation data
	 * @param string $table Name of table
	 * @param string|int $recordID ID of record to version
	 */
	protected function augmentWriteStaged(&$manipulation, $table, $recordID) {
		// If the record has already been inserted in the (table), get rid of it.
		if($manipulation[$table]['command'] == 'insert') {
			DB::prepared_query(
				"DELETE FROM \"{$table}\" WHERE \"ID\" = ?",
				array($recordID)
			);
		}

		$newTable = $table . '_' . Versioned::current_stage();
		$manipulation[$newTable] = $manipulation[$table];
		unset($manipulation[$table]);
	}

	/**
	 * @param array $manipulation
	 */
	public function augmentWrite(&$manipulation) {
		// get Version number from base data table on write
		$version = null;
		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		if(isset($manipulation[$baseDataClass]['fields'])) {
			if ($this->migratingVersion) {
				$manipulation[$baseDataClass]['fields']['Version'] = $this->migratingVersion;
			}
			if (isset($manipulation[$baseDataClass]['fields']['Version'])) {
				$version = $manipulation[$baseDataClass]['fields']['Version'];
			}
		}

		// Update all tables
		$tables = array_keys($manipulation);
		foreach($tables as $table) {

			// Make sure that the augmented write is being applied to a table that can be versioned
			if( !$this->canBeVersioned($table) ) {
				unset($manipulation[$table]);
				continue;
			}

			// Get ID field
			$id = $manipulation[$table]['id']
				? $manipulation[$table]['id']
				: $manipulation[$table]['fields']['ID'];
			if(!$id) {
				user_error("Couldn't find ID in " . var_export($manipulation[$table], true), E_USER_ERROR);
			}

			if($version < 0 || $this->_nextWriteWithoutVersion) {
				// Putting a Version of -1 is a signal to leave the version table alone, despite their being no version
				unset($manipulation[$table]['fields']['Version']);
			} elseif(empty($version)) {
				// If we haven't got a version #, then we're creating a new version.
				// Otherwise, we're just copying a version to another table
				$this->augmentWriteVersioned($manipulation, $table, $id);
			}

			// Remove "Version" column from subclasses of baseDataClass
			if(!$this->hasVersionField($table)) {
				unset($manipulation[$table]['fields']['Version']);
			}

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
				$this->augmentWriteStaged($manipulation, $table, $id);
			}
		}

		// Clear the migration flag
		if($this->migratingVersion) {
			$this->migrateVersion(null);
		}

		// Add the new version # back into the data object, for accessing after this write
		if(isset($thisVersion)) {
			$this->owner->Version = str_replace("'","", $thisVersion);
		}
	}

	/**
	 * Perform a write without affecting the version table.
	 *
	 * @return int The ID of the written record
	 */
	public function writeWithoutVersion() {
		$this->_nextWriteWithoutVersion = true;

		return $this->owner->write();
	}

	public function onAfterWrite() {
		$this->_nextWriteWithoutVersion = false;
	}

	/**
	 * If a write was skipped, then we need to ensure that we don't leave a migrateVersion() value lying around for the
	 * next write.
	 */
	public function onAfterSkippedWrite() {
		$this->migrateVersion(null);
	}

	/**
	 * Extend permissions to include additional security for objects that are not published to live.
	 *
	 * @param Member $member
	 * @return bool|null
	 */
	public function canView($member = null) {
		// Invoke default version-gnostic canView
		if ($this->owner->canViewVersioned($member) === false) {
			return false;
		}
	}

	/**
	 * Determine if there are any additional restrictions on this object for the given reading version.
	 *
	 * Override this in a subclass to customise any additional effect that Versioned applies to canView.
	 *
	 * This is expected to be called by canView, and thus is only responsible for denying access if
	 * the default canView would otherwise ALLOW access. Thus it should not be called in isolation
	 * as an authoritative permission check.
	 *
	 * This has the following extension points:
	 *  - canViewDraft is invoked if Mode = stage and Stage = stage
	 *  - canViewArchived is invoked if Mode = archive
	 *
	 * @param Member $member
	 * @return bool False is returned if the current viewing mode denies visibility
	 */
	public function canViewVersioned($member = null) {
		// Bypass when live stage
		$mode = $this->owner->getSourceQueryParam("Versioned.mode");
		$stage = $this->owner->getSourceQueryParam("Versioned.stage");
		if ($mode === 'stage' && $stage === static::get_live_stage()) {
			return true;
		}

		// Bypass if site is unsecured
		if (Session::get('unsecuredDraftSite')) {
			return true;
		}

		// If there are less than 2 stages, we can exit early since comparing stages is not needed
		if(count($this->stages) < 2){
			return true;
		}

		// If we weren't definitely loaded from live, and we can't view non-live content, we need to
		// check to make sure this version is the live version and so can be viewed.
		$latestVersion = Versioned::get_versionnumber_by_stage($this->owner->class, $this->liveStage, $this->owner->ID);
		if ($latestVersion == $this->owner->Version) {
			// Even if this is loaded from a non-live stage, this is the live version
			return true;
		}

		// Extend versioned behaviour
		$extended = $this->owner->extendedCan('canViewNonLive', $member);
		if($extended !== null) {
			return (bool)$extended;
		}

		// Fall back to default permission check
		$permissions = Config::inst()->get($this->owner->class, 'non_live_permissions', Config::FIRST_SET);
		$check = Permission::checkMember($member, $permissions);
		return (bool)$check;
	}

	/**
	 * Determines canView permissions for the latest version of this object on a specific stage.
	 * Usually the stage is read from {@link Versioned::current_stage()}.
	 *
	 * This method should be invoked by user code to check if a record is visible in the given stage.
	 *
	 * This method should not be called via ->extend('canViewStage'), but rather should be
	 * overridden in the extended class.
	 *
	 * @param string $stage
	 * @param Member $member
	 * @return bool
	 */
	public function canViewStage($stage = 'Live', $member = null) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($stage);

		$versionFromStage = DataObject::get($this->owner->class)->byID($this->owner->ID);

		Versioned::set_reading_mode($oldMode);
		return $versionFromStage ? $versionFromStage->canView($member) : false;
	}

	/**
	 * Determine if a table supports the Versioned extensions (e.g. $table_versions does exists).
	 *
	 * @param string $table Table name
	 * @return bool
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
	 * @return bool
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
	 * @return string
	 */
	public function extendWithSuffix($table) {
		$versionableExtensions = $this->owner->config()->versionableExtensions;

		if(count($versionableExtensions)){
			foreach ($versionableExtensions as $versionableExtension => $suffixes) {
				if ($this->owner->hasExtension($versionableExtension)) {
					$ext = $this->owner->getExtensionInstance($versionableExtension);
					$ext->setOwner($this->owner);
					$table = $ext->extendWithSuffix($table);
					$ext->clearOwner();
				}
			}
		}

		return $table;
	}

	/**
	 * Is the latest version of the object published?
	 *
	 * @return bool
	 */
	public function latestPublished() {
		// Get the root data object class - this will have the version field
		$table1 = $this->owner->class;
		while( ($p = get_parent_class($table1)) != "DataObject") $table1 = $p;

		$table2 = $table1 . "_$this->liveStage";

		return DB::prepared_query("SELECT \"$table1\".\"Version\" = \"$table2\".\"Version\" FROM \"$table1\"
			 INNER JOIN \"$table2\" ON \"$table1\".\"ID\" = \"$table2\".\"ID\"
			 WHERE \"$table1\".\"ID\" = ?",
			array($this->owner->ID)
		)->value();
	}

	/**
	 * Move a database record from one stage to the other.
	 *
	 * @param string $fromStage        Place to copy from.  Can be either a stage name or a version number.
	 * @param string $toStage          Place to copy to.  Must be a stage name.
	 * @param bool   $createNewVersion Set this to true to create a new version number.  By default, the existing
	 *                                 version number will be copied over.
	 */
	public function publish($fromStage, $toStage, $createNewVersion = false) {
		$this->owner->extend('onBeforeVersionedPublish', $fromStage, $toStage, $createNewVersion);

		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		$extTable = $this->extendWithSuffix($baseClass);

		/** @var Versioned|DataObject $from */
		if(is_numeric($fromStage)) {
			$from = Versioned::get_version($baseClass, $this->owner->ID, $fromStage);
		} else {
			$this->owner->flushCache();
			$from = Versioned::get_one_by_stage($baseClass, $fromStage, array(
				"\"{$baseClass}\".\"ID\" = ?" => $this->owner->ID
			));
		}
		if(!$from) {
			user_error("Can't find {$this->owner->class}/{$this->owner->ID} in stage {$fromStage}", E_USER_WARNING);
			return;
		}

		// Set version of new record
		$from->forceChange();
		if($createNewVersion) {
			// Clear version to be automatically created on write
			$from->Version = null;
		} else {
			$from->migrateVersion($from->Version);

			// Mark this version as having been published at some stage
			$publisherID = isset(Member::currentUser()->ID) ? Member::currentUser()->ID : 0;
			DB::prepared_query("UPDATE \"{$extTable}_versions\"
				SET \"WasPublished\" = ?, \"PublisherID\" = ?
				WHERE \"RecordID\" = ? AND \"Version\" = ?",
				array(1, $publisherID, $from->ID, $from->Version)
			);
		}

		// Change to new stage, write, and revert state
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($toStage);

		$conn = DB::get_conn();
		if(method_exists($conn, 'allowPrimaryKeyEditing')) {
			$conn->allowPrimaryKeyEditing($baseClass, true);
			$from->write();
			$conn->allowPrimaryKeyEditing($baseClass, false);
		} else {
			$from->write();
		}

		$from->destroy();

		Versioned::set_reading_mode($oldMode);
	}

	/**
	 * Set the migrating version.
	 *
	 * @param string $version
	 */
	public function migrateVersion($version) {
		$this->migratingVersion = $version;
	}

	/**
	 * Compare two stages to see if they're different. Only checks the version numbers, not the actual content.
	 *
	 * @param string $stage1 The first stage to check
	 * @param string $stage2 The second stage to check
	 * @return bool
	 */
	public function stagesDiffer($stage1, $stage2) {
		$table1 = $this->baseTable($stage1);
		$table2 = $this->baseTable($stage2);

		if(!is_numeric($this->owner->ID)) {
			return true;
		}

		// We test for equality - if one of the versions doesn't exist, this will be false.

		// TODO: DB Abstraction: if statement here:
		$stagesAreEqual = DB::prepared_query(
			"SELECT CASE WHEN \"$table1\".\"Version\"=\"$table2\".\"Version\" THEN 1 ELSE 0 END
			 FROM \"$table1\" INNER JOIN \"$table2\" ON \"$table1\".\"ID\" = \"$table2\".\"ID\"
			 AND \"$table1\".\"ID\" = ?",
			array($this->owner->ID)
		)->value();

		return !$stagesAreEqual;
	}

	/**
	 * Get a list of versions for this object, optionally with additional SQL parameters
	 *
	 * @param string $filter
	 * @param string $sort
	 * @param string $limit
	 * @param string $join Deprecated, use leftJoin($table, $joinClause) instead
	 * @param string $having
	 * @return DataList
	 */
	public function Versions($filter = "", $sort = "", $limit = "", $join = "", $having = "") {
		return $this->allVersions($filter, $sort, $limit, $join, $having);
	}

	/**
	 * Get a list of versions for this object, optionally with additional SQL parameters
	 *
	 * @param  string $filter
	 * @param  string $sort
	 * @param  string $limit
	 * @param  string $join Deprecated, use leftJoin($table, $joinClause) instead
	 * @param  string $having
	 * @return DataList
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

		$query->addWhere(array(
			"\"{$baseTable}_versions\".\"RecordID\" = ?" => $this->owner->ID
		));
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
	 * Compare two version, and return the differences between them.
	 *
	 * @param string $from The version to compare from
	 * @param string $to   The version to compare to
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
	 * @param string $stage Override the stage used
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
	 * Determine if the current user is able to set the given site stage / archive
	 *
	 * @param SS_HTTPRequest $request
	 * @return bool
	 */
	public static function can_choose_site_stage($request) {
		// Request is allowed if stage isn't being modified
		if((!$request->getVar('stage') || $request->getVar('stage') === static::get_live_stage())
			&& !$request->getVar('archiveDate')
		) {
			return true;
		}

		// Check permissions with member ID in session.
		$member = Member::currentUser();
		$permissions = Config::inst()->get(get_called_class(), 'non_live_permissions');
		return $member && Permission::checkMember($member, $permissions);
	}

	/**
	 * Choose the stage the site is currently on:
	 * - If $_GET['stage'] is set, then it will use that stage, and store it in the session.
	 * - If $_GET['archiveDate'] is set, it will use that date, and store it in the session.
	 * - If neither of these are set, it checks the session, otherwise the stage is set to 'Live'.
	 */
	public static function choose_site_stage() {
		// Check any pre-existing session mode
		$preexistingMode = Session::get('readingMode');

		// Determine the reading mode
		if(isset($_GET['stage'])) {
			$stage = ucfirst(strtolower($_GET['stage']));
			if(!in_array($stage, array('Stage', 'Live'))) $stage = 'Live';
			$mode = 'Stage.' . $stage;
		} elseif (isset($_GET['archiveDate']) && strtotime($_GET['archiveDate'])) {
			$mode = 'Archive.' . $_GET['archiveDate'];
		} elseif($preexistingMode) {
			$mode = $preexistingMode;
		} else {
			$mode = self::DEFAULT_MODE;
		}

		// Save reading mode
		Versioned::set_reading_mode($mode);

		// Try not to store the mode in the session if not needed
		if(($preexistingMode && $preexistingMode !== $mode)
			|| (!$preexistingMode && $mode !== self::DEFAULT_MODE)
		) {
			Session::set('readingMode', $mode);
		}

		if(!headers_sent() && !Director::is_cli()) {
			if(Versioned::current_stage() == 'Live') {
				// clear the cookie if it's set
				if(Cookie::get('bypassStaticCache')) {
					Cookie::force_expiry('bypassStaticCache', null, null, false, true /* httponly */);
				}
			} else {
				// set the cookie if it's cleared
				if(!Cookie::get('bypassStaticCache')) {
					Cookie::set('bypassStaticCache', '1', 0, null, null, false, true /* httponly */);
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
	 * @param string $stage
	 */
	public static function reading_stage($stage) {
		Versioned::set_reading_mode('Stage.' . $stage);
	}

	/**
	 * Set the reading archive date.
	 *
	 * @param string $date
	 */
	public static function reading_archived_date($date) {
		Versioned::set_reading_mode('Archive.' . $date);
	}

	/**
	 * Get a singleton instance of a class in the given stage.
	 *
	 * @param string $class  The name of the class
	 * @param string $stage  The name of the stage
	 * @param string $filter A filter to be inserted into the WHERE clause
	 * @param bool   $cache  Whether to load from the cache instead of fresh from the database
	 * @param string $sort   A sort expression to be inserted into the ORDER BY clause.
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
	 * @param string  $class The classname of the desired object
	 * @param string  $stage The name of the stage to load from
	 * @param int     $id    The object's ID
	 * @param bool    $cache Whether to load from the cache instead of fresh from the database
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

		// get version as performance-optimized SQL query (gets called for each object of this class in the database)
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
	 * Prepopulate the cache for Versioned::get_versionnumber_by_stage() for a list of record IDs, for more efficient
	 * database querying. If $idList is null, then every object will be pre-cached.
	 *
	 * @param string $class  The object class to prepopulate version numbers for
	 * @param string $stage  The stage to prepopulate version numbers from
	 * @param array  $idList A whitelist of IDs to use when prepopulating
	 */
	public static function prepopulate_versionnumber_cache($class, $stage, $idList = null) {
		if (!Config::inst()->get('Versioned', 'prepopulate_versionnumber_cache')) {
			return;
		}
		$filter = "";
		$parameters = array();
		if($idList) {
			// Validate the ID list
			foreach($idList as $id) {
				if(!is_numeric($id)) {
					user_error("Bad ID passed to Versioned::prepopulate_versionnumber_cache() in \$idList: " . $id,
					E_USER_ERROR);
				}
			}
			$filter = 'WHERE "ID" IN ('.DB::placeholders($idList).')';
			$parameters = $idList;
		}

		$baseClass = ClassInfo::baseDataClass($class);
		$stageTable = ($stage == 'Stage') ? $baseClass : "{$baseClass}_{$stage}";

		$versions = DB::prepared_query("SELECT \"ID\", \"Version\" FROM \"$stageTable\" $filter", $parameters)->map();

		foreach($versions as $id => $version) {
			self::$cache_versionnumber[$baseClass][$stage][$id] = $version;
		}
	}

	/**
	 * Get a set of class instances by the given stage.
	 *
	 * @param string     $class          The name of the class.
	 * @param string     $stage          The name of the stage.
	 * @param string     $filter         A filter to be inserted into the WHERE clause.
	 * @param string     $sort           A sort expression to be inserted into the ORDER BY clause.
	 * @param string     $join           Deprecated, use leftJoin($table, $joinClause) instead
	 * @param string|int $limit          A limit on the number of records returned from the database.
	 * @param string     $containerClass The container class for the result set (default is DataList)
	 *
	 * @return DataList A modified DataList designated to the specified stage
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
	 * Delete this item from the specified stage.
	 *
	 * @param string $stage
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
	 * Write this item to the specified stage.
	 *
	 * @param string $stage       The stage to write this item to
	 * @param bool   $forceInsert Whether to force an INSERT query over an UPDATE query
	 * @return int The ID of the item being written
	 */
	public function writeToStage($stage, $forceInsert = false) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::reading_stage($stage);

		$this->owner->forceChange();
		$result = $this->owner->write(false, $forceInsert);
		Versioned::set_reading_mode($oldMode);

		return $result;
	}

	/**
	 * Roll the draft version of this object to match the published one.
	 *
	 * Caution: Doesn't overwrite the object properties with the rolled back version.
	 *
	 * @param string|int $version Either the string 'Live' or a version number
	 */
	public function doRollbackTo($version) {
		$this->owner->extend('onBeforeRollback', $version);
		$this->publish($version, "Stage", true);

		$this->owner->writeWithoutVersion();

		$this->owner->extend('onAfterRollback', $version);
	}

	/**
	 * Return the latest version of the given object.
	 *
	 * @param string $class The classname of the object to lookup
	 * @param string $id    The object of the ID to retrieve
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
	 * @return bool
	 */
	public function isLatestVersion() {
		$version = self::get_latest_version($this->owner->class, $this->owner->ID);
		if($version) {
		return ($version->Version == $this->owner->Version);
	}

		return false;
	}

	/**
	 * Return the equivalent of a DataList::create() call, querying the latest version of each object stored in the
	 * (class)_versions tables. In particular, this will query deleted records as well as active ones.
	 *
	 * @param string $class The type of object to lookup
	 * @param string $filter An optional SQL comparison to add to the WHERE clause
	 * @param string $sort An optional SQL statement to add to the SORT clause
	 */
	public static function get_including_deleted($class, $filter = "", $sort = "") {
		$list = DataList::create($class)
			->where($filter)
			->sort($sort)
			->setDataQueryParam("Versioned.mode", "latest_versions");

		return $list;
	}

	/**
	 * Return the specific version of the given ID.
	 *
	 * Caution: The record is retrieved as a DataObject, but saving back modifications via write() will create a new
	 * version, rather than modifying the existing one.
	 *
	 * @param string $class   The type of object to lookup
	 * @param int    $id      The ID of the object to retrieve
	 * @param int    $version The desired version of the object
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
	 * @param string $class The type of object to lookup
	 * @param int    $id    The ID of the object to retrieve
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
	 * @param FieldList $fields
	 */
	public function updateCMSFields(FieldList $fields) {
		// remove the version field from the CMS as this should be left
		// entirely up to the extension (not the cms user).
		$fields->removeByName('Version');
	}

	/**
	 * Ensure version ID is reset to 0 on duplicate
	 *
	 * @param DataObject $source Record this was duplicated from
	 * @param bool $doWrite
	 */
	public function onBeforeDuplicate($source, $doWrite) {
		$this->owner->Version = 0;
	}

	/**
	 * Clear the cached version numbers from previous queries.
	 */
	public function flushCache() {
		self::$cache_versionnumber = array();
	}

	/**
	 * Returns a piece of text to keep DataObject cache keys appropriately specific.
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

	/**
	 * @return array
	 */
	public static function get_template_global_variables() {
		return array(
			'CurrentReadingMode' => 'get_reading_mode'
		);
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

	/** @var array */
	protected $record;

	/** @var DataObject */
	protected $object;

	/**
	 * @param array $record
	 */
	public function __construct($record) {
		$this->record = $record;
		$record['ID'] = $record['RecordID'];
		$className = $record['ClassName'];

		$this->object = ClassInfo::exists($className) ? new $className($record) : new DataObject($record);
		$this->failover = $this->object;

		parent::__construct();
	}

	/**
	 * Get a CSS classname to use representing whether this version was published or not.
	 *
	 * @return string
	 */
	public function PublishedClass() {
		return $this->record['WasPublished'] ? 'published' : 'internal';
	}

	/**
	 * Gets this version's author (the person who saved to Stage).
	 *
	 * @return Member
	 */
	public function Author() {
		return Member::get()->byId($this->record['AuthorID']);
	}

	/**
	 * Get this version's publisher.
	 *
	 * @return Member
	 */
	public function Publisher() {
		if (!$this->record['WasPublished']) {
			return null;
		}

		return Member::get()->byId($this->record['PublisherID']);
	}

	/**
	 * Determines if this version was ever published.
	 *
	 * @return bool
	 */
	public function Published() {
		return !empty($this->record['WasPublished']);
	}

	/**
	 * Copied from DataObject to allow access via dot notation.
	 *
	 * @param string $fieldName
	 * @return mixed
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
