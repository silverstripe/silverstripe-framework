<?php

// namespace SilverStripe\Framework\Model\Versioning

/**
 * The Versioned extension allows your DataObjects to have several versions,
 * allowing you to rollback changes and view history. An example of this is
 * the pages used in the CMS.
 *
 * @property int $Version
 * @property DataObject|Versioned $owner
 *
 * @package framework
 * @subpackage model
 */
class Versioned extends DataExtension implements TemplateGlobalProvider {

	/**
	 * Versioning mode for this object.
	 * Note: Not related to the current versioning mode in the state / session
	 * Will be one of 'StagedVersioned' or 'Versioned';
	 *
	 * @var string
	 */
	protected $mode;

	/**
	 * The default reading mode
	 */
	const DEFAULT_MODE = 'Stage.Live';

	/**
	 * Constructor arg to specify that staging is active on this record.
	 * 'Staging' implies that 'Versioning' is also enabled.
	 */
	const STAGEDVERSIONED = 'StagedVersioned';

	/**
	 * Constructor arg to specify that versioning only is active on this record.
	 */
	const VERSIONED = 'Versioned';

	/**
	 * The Public stage.
	 */
	const LIVE = 'Live';

	/**
	 * The draft (default) stage
	 */
	const DRAFT = 'Stage';

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
	 * Current reading mode
	 *
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
	 * @config
	 * @var boolean
	 */
	private static $prepopulate_versionnumber_cache = true;

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
	 *
	 * Your extension must implement VersionableExtension interface in order to
	 * apply custom tables for versioned.
	 *
	 * @config
	 * @var array
	 */
	private static $versionableExtensions = [];

	/**
	 * Permissions necessary to view records outside of the live stage (e.g. archive / draft stage).
	 *
	 * @config
	 * @var array
	 */
	private static $non_live_permissions = array('CMS_ACCESS_LeftAndMain', 'CMS_ACCESS_CMSMain', 'VIEW_DRAFT_CONTENT');

	/**
	 * List of relationships on this object that are "owned" by this object.
	 * Owership in the context of versioned objects is a relationship where
	 * the publishing of owning objects requires the publishing of owned objects.
	 *
	 * E.g. A page owns a set of banners, as in order for the page to be published, all
	 * banners on this page must also be published for it to be visible.
	 *
	 * Typically any object and its owned objects should be visible in the same edit view.
	 * E.g. a page and {@see GridField} of banners.
	 *
	 * Page hierarchy is typically not considered an ownership relationship.
	 *
	 * Ownership is recursive; If A owns B and B owns C then A owns C.
	 *
	 * @config
	 * @var array List of has_many or many_many relationships owned by this object.
	 */
	private static $owns = array();

	/**
	 * Opposing relationship to owns config; Represents the objects which
	 * own the current object.
	 *
	 * @var array
	 */
	private static $owned_by = array();

	/**
	 * Reset static configuration variables to their default values.
	 */
	public static function reset() {
		self::$reading_mode = '';
		Session::clear('readingMode');
	}

	/**
	 * Amend freshly created DataQuery objects with versioned-specific
	 * information.
	 *
	 * @param SQLSelect
	 * @param DataQuery
	 */
	public function augmentDataQueryCreation(SQLSelect &$query, DataQuery &$dataQuery) {
		$parts = explode('.', Versioned::get_reading_mode());

		if($parts[0] == 'Archive') {
			$dataQuery->setQueryParam('Versioned.mode', 'archive');
			$dataQuery->setQueryParam('Versioned.date', $parts[1]);
		} else if($parts[0] == 'Stage' && $this->hasStages()) {
			$dataQuery->setQueryParam('Versioned.mode', 'stage');
			$dataQuery->setQueryParam('Versioned.stage', $parts[1]);
		}
	}

	/**
	 * Construct a new Versioned object.
	 *
	 * @var string $mode One of "StagedVersioned" or "Versioned".
	 */
	public function __construct($mode = self::STAGEDVERSIONED) {
		parent::__construct();

		// Handle deprecated behaviour
		if($mode === 'Stage' && func_num_args() === 1) {
			Deprecation::notice("5.0", "Versioned now takes a mode as a single parameter");
			$mode = static::VERSIONED;
		} elseif(is_array($mode) || func_num_args() > 1) {
			Deprecation::notice("5.0", "Versioned now takes a mode as a single parameter");
			$mode = func_num_args() > 1 || count($mode) > 1
				? static::STAGEDVERSIONED
				: static::VERSIONED;
		}

		if(!in_array($mode, array(static::STAGEDVERSIONED, static::VERSIONED))) {
			throw new InvalidArgumentException("Invalid mode: {$mode}");
		}

		$this->mode = $mode;
	}

	/**
	 * Cache of version to modified dates for this objects
	 *
	 * @var array
	 */
	protected $versionModifiedCache = array();

	/**
	 * Get modified date for the given version
	 *
	 * @param int $version
	 * @return string
	 */
	protected function getLastEditedForVersion($version) {
		// Cache key
		$baseTable = $this->baseTable();
		$id = $this->owner->ID;
		$key = "{$baseTable}#{$id}/{$version}";

		// Check cache
		if(isset($this->versionModifiedCache[$key])) {
			return $this->versionModifiedCache[$key];
		}

		// Build query
		$table = "\"{$baseTable}_versions\"";
		$query = SQLSelect::create('"LastEdited"', $table)
			->addWhere([
				"{$table}.\"RecordID\"" => $id,
				"{$table}.\"Version\"" => $version
			]);
		$date = $query->execute()->value();
		if($date) {
			$this->versionModifiedCache[$key] = $date;
		}
		return $date;
	}

	/**
	 * Updates query parameters of relations attached to versioned dataobjects
	 *
	 * @param array $params
	 */
	public function updateInheritableQueryParams(&$params) {
		// Skip if versioned isn't set
		if(!isset($params['Versioned.mode'])) {
			return;
		}

		// Adjust query based on original selection criterea
		switch($params['Versioned.mode']) {
			case 'all_versions': {
				// Versioned.mode === all_versions doesn't inherit very well, so default to stage
				$params['Versioned.mode'] = 'stage';
				$params['Versioned.stage'] = static::DRAFT;
				break;
			}
			case 'version': {
				// If we selected this object from a specific version, we need
				// to find the date this version was published, and ensure
				// inherited queries select from that date.
				$version = $params['Versioned.version'];
				$date = $this->getLastEditedForVersion($version);

				// Filter related objects at the same date as this version
				unset($params['Versioned.version']);
				if($date) {
					$params['Versioned.mode'] = 'archive';
					$params['Versioned.date'] = $date;
				} else {
					// Fallback to default
					$params['Versioned.mode'] = 'stage';
					$params['Versioned.stage'] = static::DRAFT;
				}
				break;
			}
		}
	}

	/**
	 * Augment the the SQLSelect that is created by the DataQuery
	 *
	 * See {@see augmentLazyLoadFields} for lazy-loading applied prior to this.
	 *
	 * @param SQLSelect $query
	 * @param DataQuery $dataQuery
	 * @throws InvalidArgumentException
	 */
	public function augmentSQL(SQLSelect $query, DataQuery $dataQuery = null) {
		if(!$dataQuery || !$dataQuery->getQueryParam('Versioned.mode')) {
			return;
		}

		$baseTable = $this->baseTable();
		$versionedMode = $dataQuery->getQueryParam('Versioned.mode');
		switch($versionedMode) {
		// Reading a specific stage (Stage or Live)
		case 'stage':
			// Check if we need to rewrite this table
			$stage = $dataQuery->getQueryParam('Versioned.stage');
			if(!$this->hasStages() || $stage === static::DRAFT) {
				break;
			}
			// Rewrite all tables to select from the live version
			foreach($query->getFrom() as $table => $dummy) {
				if(!$this->isTableVersioned($table)) {
					continue;
				}
				$stageTable = $this->stageTable($table, $stage);
				$query->renameTable($table, $stageTable);
			}
			break;

		// Reading a specific stage, but only return items that aren't in any other stage
		case 'stage_unique':
			if(!$this->hasStages()) {
				break;
			}

			$stage = $dataQuery->getQueryParam('Versioned.stage');
			// Recurse to do the default stage behavior (must be first, we rely on stage renaming happening before
			// below)
			$dataQuery->setQueryParam('Versioned.mode', 'stage');
			$this->augmentSQL($query, $dataQuery);
			$dataQuery->setQueryParam('Versioned.mode', 'stage_unique');

			// Now exclude any ID from any other stage. Note that we double rename to avoid the regular stage rename
			// renaming all subquery references to be Versioned.stage
			foreach([static::DRAFT, static::LIVE] as $excluding) {
				if ($excluding == $stage) {
					continue;
				}

				$tempName = 'ExclusionarySource_'.$excluding;
				$excludingTable = $this->baseTable($excluding);

				$query->addWhere('"'.$baseTable.'"."ID" NOT IN (SELECT "ID" FROM "'.$tempName.'")');
				$query->renameTable($tempName, $excludingTable);
			}
			break;

		// Return all version instances
		case 'archive':
		case 'all_versions':
		case 'latest_versions':
		case 'version':
			foreach($query->getFrom() as $alias => $join) {
				if(!$this->isTableVersioned($alias)) {
					continue;
				}

				if($alias != $baseTable) {
					// Make sure join includes version as well
					$query->setJoinFilter(
						$alias,
						"\"{$alias}_versions\".\"RecordID\" = \"{$baseTable}_versions\".\"RecordID\""
						. " AND \"{$alias}_versions\".\"Version\" = \"{$baseTable}_versions\".\"Version\""
					);
				}
				$query->renameTable($alias, $alias . '_versions');
			}

			// Add all <basetable>_versions columns
			foreach(Config::inst()->get('Versioned', 'db_for_versions_table') as $name => $type) {
				$query->selectField(sprintf('"%s_versions"."%s"', $baseTable, $name), $name);
			}

			// Alias the record ID as the row ID, and ensure ID filters are aliased correctly
			$query->selectField("\"{$baseTable}_versions\".\"RecordID\"", "ID");
			$query->replaceText("\"{$baseTable}_versions\".\"ID\"", "\"{$baseTable}_versions\".\"RecordID\"");

			// However, if doing count, undo rewrite of "ID" column
			$query->replaceText(
				"count(DISTINCT \"{$baseTable}_versions\".\"RecordID\")",
				"count(DISTINCT \"{$baseTable}_versions\".\"ID\")"
			);

			// Add additional versioning filters
			switch($versionedMode) {
				case 'archive': {
					$date = $dataQuery->getQueryParam('Versioned.date');
					if(!$date) {
						throw new InvalidArgumentException("Invalid archive date");
					}
					// Link to the version archived on that date
					$query->addWhere([
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
					]);
					break;
				}
				case 'latest_versions': {
					// Return latest version instances, regardless of whether they are on a particular stage
					// This provides "show all, including deleted" functonality
					$query->addWhere(
						"\"{$baseTable}_versions\".\"Version\" IN
						(SELECT LatestVersion FROM
							(SELECT
								\"{$baseTable}_versions\".\"RecordID\",
								MAX(\"{$baseTable}_versions\".\"Version\") AS LatestVersion
								FROM \"{$baseTable}_versions\"
								GROUP BY \"{$baseTable}_versions\".\"RecordID\"
							) AS \"{$baseTable}_versions_latest\"
							WHERE \"{$baseTable}_versions_latest\".\"RecordID\" = \"{$baseTable}_versions\".\"RecordID\"
						)"
					);
					break;
				}
				case 'version': {
					// If selecting a specific version, filter it here
					$version = $dataQuery->getQueryParam('Versioned.version');
					if(!$version) {
						throw new InvalidArgumentException("Invalid version");
					}
					$query->addWhere([
						"\"{$baseTable}_versions\".\"Version\"" => $version
					]);
					break;
				}
				case 'all_versions':
				default: {
					// If all versions are requested, ensure that records are sorted by this field
					$query->addOrderBy(sprintf('"%s_versions"."%s"', $baseTable, 'Version'));
					break;
				}
			}
			break;
		default:
			throw new InvalidArgumentException("Bad value for query parameter Versioned.mode: "
				. $dataQuery->getQueryParam('Versioned.mode'));
		}
	}

	/**
	 * Determine if the given versioned table is a part of the sub-tree of the current dataobject
	 * This helps prevent rewriting of other tables that get joined in, in particular, many_many tables
	 *
	 * @param string $table
	 * @return bool True if this table should be versioned
	 */
	protected function isTableVersioned($table) {
		$schema = DataObject::getSchema();
		$tableClass = $schema->tableClass($table);
		if(empty($tableClass)) {
			return false;
		}

		// Check that this class belongs to the same tree
		$baseClass = $schema->baseDataClass($this->owner);
		if(!is_a($tableClass, $baseClass, true)) {
			return false;
		}

		// Check that this isn't a derived table
		// (e.g. _Live, or a many_many table)
		$mainTable = $schema->tableName($tableClass);
		if($mainTable !== $table) {
			return false;
		}

		return true;
	}

	/**
	 * For lazy loaded fields requiring extra sql manipulation, ie versioning.
	 *
	 * @param SQLSelect $query
	 * @param DataQuery $dataQuery
	 * @param DataObject $dataObject
	 */
	public function augmentLoadLazyFields(SQLSelect &$query, DataQuery &$dataQuery = null, $dataObject) {
		// The VersionedMode local variable ensures that this decorator only applies to
		// queries that have originated from the Versioned object, and have the Versioned
		// metadata set on the query object. This prevents regular queries from
		// accidentally querying the *_versions tables.
		$versionedMode = $dataObject->getSourceQueryParam('Versioned.mode');
		$modesToAllowVersioning = array('all_versions', 'latest_versions', 'archive', 'version');
		if(
			!empty($dataObject->Version) &&
			(!empty($versionedMode) && in_array($versionedMode,$modesToAllowVersioning))
		) {
			// This will ensure that augmentSQL will select only the same version as the owner,
			// regardless of how this object was initially selected
			$versionColumn = $this->owner->getSchema()->sqlColumnForField($this->owner, 'Version');
			$dataQuery->where([
				$versionColumn => $dataObject->Version
			]);
			$dataQuery->setQueryParam('Versioned.mode', 'all_versions');
		}
	}

	public function augmentDatabase() {
		$owner = $this->owner;
		$class = get_class($owner);
		$baseTable = $this->baseTable();
		$classTable = $owner->getSchema()->tableName($owner);

		$isRootClass = $class === $owner->baseClass();

		// Build a list of suffixes whose tables need versioning
		$allSuffixes = array();
		$versionableExtensions = $owner->config()->versionableExtensions;
		if(count($versionableExtensions)){
			foreach ($versionableExtensions as $versionableExtension => $suffixes) {
				if ($owner->hasExtension($versionableExtension)) {
					foreach ((array)$suffixes as $suffix) {
						$allSuffixes[$suffix] = $versionableExtension;
					}
				}
			}
		}

		// Add the default table with an empty suffix to the list (table name = class name)
		$allSuffixes[''] = null;

		foreach ($allSuffixes as $suffix => $extension) {
			// Check tables for this build
			if ($suffix) {
				$suffixBaseTable = "{$baseTable}_{$suffix}";
				$suffixTable = "{$classTable}_{$suffix}";
			}  else {
				$suffixBaseTable = $baseTable;
				$suffixTable = $classTable;
			}

			$fields = DataObject::database_fields($owner->class);
			unset($fields['ID']);
			if($fields) {
				$options = Config::inst()->get($owner->class, 'create_table_options', Config::FIRST_SET);
				$indexes = $owner->databaseIndexes();
				$extensionClass = $allSuffixes[$suffix];
				if ($suffix && ($extension = $owner->getExtensionInstance($extensionClass))) {
					if (!$extension instanceof VersionableExtension) {
						throw new LogicException(
							"Extension {$extensionClass} must implement VersionableExtension"
						);
					}
					// Allow versionable extension to customise table fields and indexes
					$extension->setOwner($owner);
					if ($extension->isVersionedTable($suffixTable)) {
						$extension->updateVersionableFields($suffix, $fields, $indexes);
					}
					$extension->clearOwner();
				}

				// Build _Live table
				if($this->hasStages()) {
					$liveTable = $this->stageTable($suffixTable, static::LIVE);
					DB::require_table($liveTable, $fields, $indexes, false, $options);
				}

				// Build _versions table
				//Unique indexes will not work on versioned tables, so we'll convert them to standard indexes:
				$nonUniqueIndexes = $this->uniqueToIndex($indexes);
				if($isRootClass) {
					// Create table for all versions
					$versionFields = array_merge(
						Config::inst()->get('Versioned', 'db_for_versions_table'),
						(array)$fields
					);
					$versionIndexes = array_merge(
						Config::inst()->get('Versioned', 'indexes_for_versions_table'),
						(array)$nonUniqueIndexes
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
							'RecordID_Version' => array('type' => 'unique', 'value' => '"RecordID","Version"'),
							'RecordID' => true,
							'Version' => true,
						),
						(array)$nonUniqueIndexes
					);
				}

				// Cleanup any orphans
				$this->cleanupVersionedOrphans("{$suffixBaseTable}_versions", "{$suffixTable}_versions");

				// Build versions table
				DB::require_table("{$suffixTable}_versions", $versionFields, $versionIndexes, true, $options);
			} else {
				DB::dont_require_table("{$suffixTable}_versions");
				if($this->hasStages()) {
					$liveTable = $this->stageTable($suffixTable, static::LIVE);
					DB::dont_require_table($liveTable);
				}
			}
		}
	}

	/**
	 * Cleanup orphaned records in the _versions table
	 *
	 * @param string $baseTable base table to use as authoritative source of records
	 * @param string $childTable Sub-table to clean orphans from
	 */
	protected function cleanupVersionedOrphans($baseTable, $childTable) {
		// Skip if child table doesn't exist
		if(!DB::get_schema()->hasTable($childTable)) {
			return;
		}
		// Skip if tables are the same
		if($childTable === $baseTable) {
			return;
		}

		// Select all orphaned version records
		$orphanedQuery = SQLSelect::create()
			->selectField("\"{$childTable}\".\"ID\"")
			->setFrom("\"{$childTable}\"");

		// If we have a parent table limit orphaned records
		// to only those that exist in this
		if(DB::get_schema()->hasTable($baseTable)) {
			$orphanedQuery
				->addLeftJoin(
					$baseTable,
					"\"{$childTable}\".\"RecordID\" = \"{$baseTable}\".\"RecordID\"
					AND \"{$childTable}\".\"Version\" = \"{$baseTable}\".\"Version\""
				)
				->addWhere("\"{$baseTable}\".\"ID\" IS NULL");
		}

		$count = $orphanedQuery->count();
		if($count > 0) {
			DB::alteration_message("Removing {$count} orphaned versioned records", "deleted");
			$ids = $orphanedQuery->execute()->column();
			foreach($ids as $id) {
				DB::prepared_query("DELETE FROM \"{$childTable}\" WHERE \"ID\" = ?", array($id));
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
	 * @param array $manipulation Source manipulation data
	 * @param string $class Class
	 * @param string $table Table Table for this class
	 * @param int $recordID ID of record to version
	 */
	protected function augmentWriteVersioned(&$manipulation, $class, $table, $recordID) {
		$baseDataClass = DataObject::getSchema()->baseDataClass($class);
		$baseDataTable = DataObject::getSchema()->tableName($baseDataClass);

		// Set up a new entry in (table)_versions
		$newManipulation = array(
			"command" => "insert",
			"fields" => isset($manipulation[$table]['fields']) ? $manipulation[$table]['fields'] : null,
			"class" => $class,
		);

		// Add any extra, unchanged fields to the version record.
		$data = DB::prepared_query("SELECT * FROM \"{$table}\" WHERE \"ID\" = ?", array($recordID))->record();

		if ($data) {
			$fields = DataObject::database_fields($class);

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
				FROM \"{$baseDataTable}_versions\" WHERE \"RecordID\" = ?",
				array($recordID)
			)->value();
		}
		$nextVersion = $nextVersion ?: 1;

		if($class === $baseDataClass) {
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
	 * @param int $recordID ID of record to version
	 */
	protected function augmentWriteStaged(&$manipulation, $table, $recordID) {
		// If the record has already been inserted in the (table), get rid of it.
		if($manipulation[$table]['command'] == 'insert') {
			DB::prepared_query(
				"DELETE FROM \"{$table}\" WHERE \"ID\" = ?",
				array($recordID)
			);
		}

		$newTable = $this->stageTable($table, Versioned::get_stage());
		$manipulation[$newTable] = $manipulation[$table];
		unset($manipulation[$table]);
	}


	public function augmentWrite(&$manipulation) {
		// get Version number from base data table on write
		$version = null;
		$owner = $this->owner;
		$baseDataTable = DataObject::getSchema()->baseDataTable($owner);
		if(isset($manipulation[$baseDataTable]['fields'])) {
			if ($this->migratingVersion) {
				$manipulation[$baseDataTable]['fields']['Version'] = $this->migratingVersion;
			}
			if (isset($manipulation[$baseDataTable]['fields']['Version'])) {
				$version = $manipulation[$baseDataTable]['fields']['Version'];
			}
		}

		// Update all tables
		$tables = array_keys($manipulation);
		foreach($tables as $table) {

			// Make sure that the augmented write is being applied to a table that can be versioned
			$class = isset($manipulation[$table]['class']) ? $manipulation[$table]['class'] : null;
			if(!$class || !$this->canBeVersioned($class) ) {
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
				$this->augmentWriteVersioned($manipulation, $class, $table, $id);
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
			if($this->hasStages() && static::get_stage() === static::LIVE) {
				$this->augmentWriteStaged($manipulation, $class, $id);
			}
		}

		// Clear the migration flag
		if($this->migratingVersion) {
			$this->migrateVersion(null);
		}

		// Add the new version # back into the data object, for accessing
		// after this write
		if(isset($thisVersion)) {
			$owner->Version = str_replace("'","", $thisVersion);
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
	 */
	public function onAfterSkippedWrite() {
		$this->migrateVersion(null);
	}

	/**
	 * Find all objects owned by the current object.
	 * Note that objects will only be searched in the same stage as the given record.
	 *
	 * @param bool $recursive True if recursive
	 * @param ArrayList $list Optional list to add items to
	 * @return ArrayList list of objects
	 */
	public function findOwned($recursive = true, $list = null)
	{
		// Find objects in these relationships
		return $this->findRelatedObjects('owns', $recursive, $list);
	}

	/**
	 * Find objects which own this object.
	 * Note that objects will only be searched in the same stage as the given record.
	 *
	 * @param bool $recursive True if recursive
	 * @param ArrayList $list Optional list to add items to
	 * @return ArrayList list of objects
	 */
	public function findOwners($recursive = true, $list = null) {
		if (!$list) {
			$list = new ArrayList();
		}

		// Build reverse lookup for ownership
		// @todo - Cache this more intelligently
		$rules = $this->lookupReverseOwners();

		// Hand off to recursive method
		return $this->findOwnersRecursive($recursive, $list, $rules);
	}

	/**
	 * Find objects which own this object.
	 * Note that objects will only be searched in the same stage as the given record.
	 *
	 * @param bool $recursive True if recursive
	 * @param ArrayList $list List to add items to
	 * @param array $lookup List of reverse lookup rules for owned objects
	 * @return ArrayList list of objects
	 */
	public function findOwnersRecursive($recursive, $list, $lookup) {
		// First pass: find objects that are explicitly owned_by (e.g. custom relationships)
		$owners = $this->findRelatedObjects('owned_by', false);

		// Second pass: Find owners via reverse lookup list
		foreach($lookup as $ownedClass => $classLookups) {
			// Skip owners of other objects
			if(!is_a($this->owner, $ownedClass)) {
				continue;
			}
			foreach($classLookups as $classLookup) {
				// Merge new owners into this object's owners
				$ownerClass = $classLookup['class'];
				$ownerRelation = $classLookup['relation'];
				$result = $this->owner->inferReciprocalComponent($ownerClass, $ownerRelation);
				$this->mergeRelatedObjects($owners, $result);
			}
		}

		// Merge all objects into the main list
		$newItems = $this->mergeRelatedObjects($list, $owners);

		// If recursing, iterate over all newly added items
		if($recursive) {
			foreach($newItems as $item) {
				/** @var Versioned|DataObject $item */
				$item->findOwnersRecursive(true, $list, $lookup);
			}
		}

		return $list;
	}

	/**
	 * Find a list of classes, each of which with a list of methods to invoke
	 * to lookup owners.
	 *
	 * @return array
	 */
	protected function lookupReverseOwners() {
		// Find all classes with 'owns' config
		$lookup = array();
		foreach(ClassInfo::subclassesFor('DataObject') as $class) {
			// Ensure this class is versioned
			if(!Object::has_extension($class, 'Versioned')) {
				continue;
			}

			// Check owned objects for this class
			$owns = Config::inst()->get($class, 'owns', Config::UNINHERITED);
			if(empty($owns)) {
				continue;
			}

			/** @var DataObject $instance */
			$instance = $class::singleton();
			foreach($owns as $owned) {
				// Find owned class
				$ownedClass = $instance->getRelationClass($owned);
				// Skip custom methods that don't have db relationsm
				if(!$ownedClass) {
					continue;
				}
				if($ownedClass === 'DataObject') {
					throw new LogicException(sprintf(
						"Relation %s on class %s cannot be owned as it is polymorphic",
						$owned, $class
					));
				}

				// Add lookup for owned class
				if(!isset($lookup[$ownedClass])) {
					$lookup[$ownedClass] = array();
				}
				$lookup[$ownedClass][] = [
					'class' => $class,
					'relation' => $owned
				];
			}
		}
		return $lookup;
	}


	/**
	 * Find objects in the given relationships, merging them into the given list
	 *
	 * @param array $source Config property to extract relationships from
	 * @param bool $recursive True if recursive
	 * @param ArrayList $list Optional list to add items to
	 * @return ArrayList The list
	 */
	public function findRelatedObjects($source, $recursive = true, $list = null)
	{
		if (!$list) {
			$list = new ArrayList();
		}

		// Skip search for unsaved records
		$owner = $this->owner;
		if(!$owner->isInDB()) {
			return $list;
		}

		$relationships = $owner->config()->{$source};
		foreach($relationships as $relationship) {
			// Warn if invalid config
			if(!$owner->hasMethod($relationship)) {
				trigger_error(sprintf(
					"Invalid %s config value \"%s\" on object on class \"%s\"",
					$source,
					$relationship,
					$owner->class
				), E_USER_WARNING);
				continue;
			}

			// Inspect value of this relationship
			$items = $owner->{$relationship}();

			// Merge any new item
			$newItems = $this->mergeRelatedObjects($list, $items);

			// Recurse if necessary
			if($recursive) {
				foreach($newItems as $item) {
					/** @var Versioned|DataObject $item */
					$item->findRelatedObjects($source, true, $list);
				}
			}
		}
		return $list;
	}

	/**
	 * Helper method to merge owned/owning items into a list.
	 * Items already present in the list will be skipped.
	 *
	 * @param ArrayList $list Items to merge into
	 * @param mixed $items List of new items to merge
	 * @return ArrayList List of all newly added items that did not already exist in $list
	 */
	protected function mergeRelatedObjects($list, $items) {
		$added = new ArrayList();
		if(!$items) {
			return $added;
		}
		if($items instanceof DataObject) {
			$items = array($items);
		}

		/** @var Versioned|DataObject $item */
		foreach($items as $item) {
			// Identify item
			$itemKey = $item->class . '/' . $item->ID;

			// Skip unsaved, unversioned, or already checked objects
			if(!$item->isInDB() || !$item->has_extension('Versioned') || isset($list[$itemKey])) {
				continue;
			}

			// Save record
			$list[$itemKey] = $item;
			$added[$itemKey] = $item;
		}
		return $added;
	}

	/**
	 * This function should return true if the current user can publish this record.
	 * It can be overloaded to customise the security model for an application.
	 *
	 * Denies permission if any of the following conditions is true:
	 * - canPublish() on any extension returns false
	 * - canEdit() returns false
	 *
	 * @param Member $member
	 * @return bool True if the current user can publish this record.
	 */
	public function canPublish($member = null) {
		// Skip if invoked by extendedCan()
		if(func_num_args() > 4) {
			return null;
		}

		if(!$member) {
			$member = Member::currentUser();
		}

		if(Permission::checkMember($member, "ADMIN")) {
			return true;
		}

		// Standard mechanism for accepting permission changes from extensions
		$owner = $this->owner;
		$extended = $owner->extendedCan('canPublish', $member);
		if($extended !== null) {
			return $extended;
		}

		// Default to relying on edit permission
		return $owner->canEdit($member);
	}

	/**
	 * Check if the current user can delete this record from live
	 *
	 * @param null $member
	 * @return mixed
	 */
	public function canUnpublish($member = null) {
		// Skip if invoked by extendedCan()
		if(func_num_args() > 4) {
			return null;
		}

		if(!$member) {
			$member = Member::currentUser();
		}

		if(Permission::checkMember($member, "ADMIN")) {
			return true;
		}

		// Standard mechanism for accepting permission changes from extensions
		$owner = $this->owner;
		$extended = $owner->extendedCan('canUnpublish', $member);
		if($extended !== null) {
			return $extended;
		}

		// Default to relying on canPublish
		return $owner->canPublish($member);
	}

	/**
	 * Check if the current user is allowed to archive this record.
	 * If extended, ensure that both canDelete and canUnpublish are extended also
	 *
	 * @param Member $member
	 * @return bool
	 */
	public function canArchive($member = null) {
		// Skip if invoked by extendedCan()
		if(func_num_args() > 4) {
			return null;
		}

		if(!$member) {
            $member = Member::currentUser();
        }

		if(Permission::checkMember($member, "ADMIN")) {
			return true;
		}

		// Standard mechanism for accepting permission changes from extensions
		$owner = $this->owner;
		$extended = $owner->extendedCan('canArchive', $member);
		if($extended !== null) {
            return $extended;
        }

		// Check if this record can be deleted from stage
        if(!$owner->canDelete($member)) {
            return false;
        }

        // Check if we can delete from live
        if(!$owner->canUnpublish($member)) {
            return false;
        }

		return true;
	}

	/**
	 * Check if the user can revert this record to live
	 *
	 * @param Member $member
	 * @return bool
	 */
	public function canRevertToLive($member = null) {
		$owner = $this->owner;

		// Skip if invoked by extendedCan()
		if(func_num_args() > 4) {
			return null;
		}

		// Can't revert if not on live
		if(!$owner->isPublished()) {
			return false;
		}

		if(!$member) {
            $member = Member::currentUser();
        }

		if(Permission::checkMember($member, "ADMIN")) {
			return true;
		}

		// Standard mechanism for accepting permission changes from extensions
		$extended = $owner->extendedCan('canRevertToLive', $member);
		if($extended !== null) {
            return $extended;
        }

		// Default to canEdit
		return $owner->canEdit($member);
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
		$owner = $this->owner;
		$mode = $owner->getSourceQueryParam("Versioned.mode");
		$stage = $owner->getSourceQueryParam("Versioned.stage");
		if ($mode === 'stage' && $stage === static::LIVE) {
			return true;
		}

		// Bypass if site is unsecured
		if (Session::get('unsecuredDraftSite')) {
			return true;
		}

		// Bypass if record doesn't have a live stage
		if(!$this->hasStages()) {
			return true;
		}

		// If we weren't definitely loaded from live, and we can't view non-live content, we need to
		// check to make sure this version is the live version and so can be viewed
		$latestVersion = Versioned::get_versionnumber_by_stage($owner->class, static::LIVE, $owner->ID);
		if ($latestVersion == $owner->Version) {
			// Even if this is loaded from a non-live stage, this is the live version
			return true;
		}

		// Extend versioned behaviour
		$extended = $owner->extendedCan('canViewNonLive', $member);
		if($extended !== null) {
			return (bool)$extended;
		}

		// Fall back to default permission check
		$permissions = Config::inst()->get($owner->class, 'non_live_permissions', Config::FIRST_SET);
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
		Versioned::set_stage($stage);

		$owner = $this->owner;
		$versionFromStage = DataObject::get($owner->class)->byID($owner->ID);

		Versioned::set_reading_mode($oldMode);
		return $versionFromStage ? $versionFromStage->canView($member) : false;
	}

	/**
	 * Determine if a class is supporting the Versioned extensions (e.g.
	 * $table_versions does exists).
	 *
	 * @param string $class Class name
	 * @return boolean
	 */
	public function canBeVersioned($class) {
		return ClassInfo::exists($class)
			&& is_subclass_of($class, 'DataObject')
			&& DataObject::has_own_table($class);
	}

	/**
	 * Check if a certain table has the 'Version' field.
	 *
	 * @param string $table Table name
	 *
	 * @return boolean Returns false if the field isn't in the table, true otherwise
	 */
	public function hasVersionField($table) {
		// Base table has version field
		$class = DataObject::getSchema()->tableClass($table);
		return $class === DataObject::getSchema()->baseDataClass($class);
	}

	/**
	 * @param string $table
	 *
	 * @return string
	 */
	public function extendWithSuffix($table) {
		$owner = $this->owner;
		$versionableExtensions = $owner->config()->versionableExtensions;

		if(count($versionableExtensions)){
			foreach ($versionableExtensions as $versionableExtension => $suffixes) {
				if ($owner->hasExtension($versionableExtension)) {
					$ext = $owner->getExtensionInstance($versionableExtension);
					$ext->setOwner($owner);
					$table = $ext->extendWithSuffix($table);
					$ext->clearOwner();
				}
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
		$owner = $this->owner;
		$draftTable = $this->baseTable();
		$liveTable = $this->stageTable($draftTable, static::LIVE);

		return DB::prepared_query("SELECT \"$draftTable\".\"Version\" = \"$liveTable\".\"Version\" FROM \"$draftTable\"
			 INNER JOIN \"$liveTable\" ON \"$draftTable\".\"ID\" = \"$liveTable\".\"ID\"
			 WHERE \"$draftTable\".\"ID\" = ?",
			array($owner->ID)
		)->value();
	}

	/**
	 * @deprecated 4.0..5.0
	 */
	public function doPublish() {
		Deprecation::notice('5.0', 'Use publishRecursive instead');
		return $this->owner->publishRecursive();
	}

	/**
	 * Publish this object and all owned objects to Live
	 *
	 * @return bool
	 */
	public function publishRecursive() {
		$owner = $this->owner;
		if(!$owner->publishSingle()) {
			return false;
		}

		// Publish owned objects
		foreach ($owner->findOwned(false) as $object) {
			/** @var Versioned|DataObject $object */
			$object->publishRecursive();
		}

		// Unlink any objects disowned as a result of this action
		// I.e. objects which aren't owned anymore by this record, but are by the old live record
		$owner->unlinkDisownedObjects(Versioned::DRAFT, Versioned::LIVE);

		return true;
	}

	/**
	 * Publishes this object to Live, but doesn't publish owned objects.
	 *
	 * @return bool True if publish was successful
	 */
	public function publishSingle() {
		$owner = $this->owner;
		if(!$owner->canPublish()) {
			return false;
		}

		$owner->invokeWithExtensions('onBeforePublish');
		$owner->write();
		$owner->copyVersionToStage(static::DRAFT, static::LIVE);
		$owner->invokeWithExtensions('onAfterPublish');
		return true;
	}

	/**
	 * Set foreign keys of has_many objects to 0 where those objects were
	 * disowned as a result of a partial publish / unpublish.
	 * I.e. this object and its owned objects were recently written to $targetStage,
	 * but deleted objects were not.
	 *
	 * Note that this operation does not create any new Versions
	 *
	 * @param string $sourceStage Objects in this stage will not be unlinked.
	 * @param string $targetStage Objects which exist in this stage but not $sourceStage
	 * will be unlinked.
	 */
	public function unlinkDisownedObjects($sourceStage, $targetStage) {
		$owner = $this->owner;

		// after publishing, objects which used to be owned need to be
		// dis-connected from this object (set ForeignKeyID = 0)
		$owns = $owner->config()->owns;
		$hasMany = $owner->config()->has_many;
		if(empty($owns) || empty($hasMany)) {
			return;
		}

		$ownedHasMany = array_intersect($owns, array_keys($hasMany));
		foreach($ownedHasMany as $relationship) {
			// Find metadata on relationship
			$joinClass = $owner->hasManyComponent($relationship);
			$joinField = $owner->getRemoteJoinField($relationship, 'has_many', $polymorphic);
			$idField = $polymorphic ? "{$joinField}ID" : $joinField;
			$joinTable = DataObject::getSchema()->tableForField($joinClass, $idField);

			// Generate update query which will unlink disowned objects
			$targetTable = $this->stageTable($joinTable, $targetStage);
			$disowned = new SQLUpdate("\"{$targetTable}\"");
			$disowned->assign("\"{$idField}\"", 0);
			$disowned->addWhere(array(
				"\"{$targetTable}\".\"{$idField}\"" => $owner->ID
			));

			// Build exclusion list (items to owned objects we need to keep)
			$sourceTable = $this->stageTable($joinTable, $sourceStage);
			$owned = new SQLSelect("\"{$sourceTable}\".\"ID\"", "\"{$sourceTable}\"");
			$owned->addWhere(array(
				"\"{$sourceTable}\".\"{$idField}\"" => $owner->ID
			));

			// Apply class condition if querying on polymorphic has_one
			if($polymorphic) {
				$disowned->assign("\"{$joinField}Class\"", null);
				$disowned->addWhere(array(
					"\"{$targetTable}\".\"{$joinField}Class\"" => get_class($owner)
				));
				$owned->addWhere(array(
					"\"{$sourceTable}\".\"{$joinField}Class\"" => get_class($owner)
				));
			}

			// Merge queries and perform unlink
			$ownedSQL = $owned->sql($ownedParams);
			$disowned->addWhere(array(
				"\"{$targetTable}\".\"ID\" NOT IN ({$ownedSQL})" => $ownedParams
			));

			$owner->extend('updateDisownershipQuery', $disowned, $sourceStage, $targetStage, $relationship);

			$disowned->execute();
		}
	}

	/**
	 * Removes the record from both live and stage
	 *
	 * @return bool Success
	 */
	public function doArchive() {
		$owner = $this->owner;
		if(!$owner->canArchive()) {
			return false;
		}

		$owner->invokeWithExtensions('onBeforeArchive', $this);
		$owner->doUnpublish();
		$owner->delete();
		$owner->invokeWithExtensions('onAfterArchive', $this);

		return true;
	}

	/**
	 * Removes this record from the live site
	 *
	 * @return bool Flag whether the unpublish was successful
	 */
	public function doUnpublish() {
		$owner = $this->owner;
		if(!$owner->canUnpublish()) {
			return false;
		}

		// Skip if this record isn't saved
		if(!$owner->isInDB()) {
			return false;
		}

		// Skip if this record isn't on live
		if(!$owner->isPublished()) {
			return false;
		}

		$owner->invokeWithExtensions('onBeforeUnpublish');

		$origReadingMode = static::get_reading_mode();
		static::set_stage(static::LIVE);

		// This way our ID won't be unset
		$clone = clone $owner;
		$clone->delete();

		static::set_reading_mode($origReadingMode);

		$owner->invokeWithExtensions('onAfterUnpublish');
		return true;
	}

	/**
	 * Trigger unpublish of owning objects
	 */
	public function onAfterUnpublish() {
		$owner = $this->owner;

		// Any objects which owned (and thus relied on the unpublished object) are now unpublished automatically.
		foreach ($owner->findOwners(false) as $object) {
			/** @var Versioned|DataObject $object */
			$object->doUnpublish();
		}
	}


	/**
	 * Revert the draft changes: replace the draft content with the content on live
	 *
	 * @return bool True if the revert was successful
	 */
	public function doRevertToLive() {
		$owner = $this->owner;
		if(!$owner->canRevertToLive()) {
			return false;
		}

		$owner->invokeWithExtensions('onBeforeRevertToLive');
		$owner->copyVersionToStage(static::LIVE, static::DRAFT, false);
		$owner->invokeWithExtensions('onAfterRevertToLive');
		return true;
	}

	/**
	 * Trigger revert of all owned objects to stage
	 */
	public function onAfterRevertToLive() {
		$owner = $this->owner;
		/** @var Versioned|DataObject $liveOwner */
		$liveOwner = static::get_by_stage(get_class($owner), static::LIVE)
			->byID($owner->ID);

		// Revert any owned objects from the live stage only
		foreach ($liveOwner->findOwned(false) as $object) {
			/** @var Versioned|DataObject $object */
			$object->doRevertToLive();
		}

		// Unlink any objects disowned as a result of this action
		// I.e. objects which aren't owned anymore by this record, but are by the old draft record
		$owner->unlinkDisownedObjects(Versioned::LIVE, Versioned::DRAFT);
	}

	/**
	 * @deprecated 4.0..5.0
	 */
	public function publish($fromStage, $toStage, $createNewVersion = false) {
		Deprecation::notice('5.0', 'Use copyVersionToStage instead');
		$this->owner->copyVersionToStage($fromStage, $toStage, $createNewVersion);
	}

	/**
	 * Move a database record from one stage to the other.
	 *
	 * @param int|string $fromStage Place to copy from.  Can be either a stage name or a version number.
	 * @param string $toStage Place to copy to.  Must be a stage name.
	 * @param bool $createNewVersion Set this to true to create a new version number.
	 * By default, the existing version number will be copied over.
	 */
	public function copyVersionToStage($fromStage, $toStage, $createNewVersion = false) {
		$owner = $this->owner;
		$owner->invokeWithExtensions('onBeforeVersionedPublish', $fromStage, $toStage, $createNewVersion);

		$baseClass = $owner->baseClass();

		/** @var Versioned|DataObject $from */
		if(is_numeric($fromStage)) {
			$from = Versioned::get_version($baseClass, $owner->ID, $fromStage);
		} else {
			$owner->flushCache();
			$from = Versioned::get_one_by_stage($baseClass, $fromStage, array(
				"\"{$baseClass}\".\"ID\" = ?" => $owner->ID
			));
		}
		if(!$from) {
			throw new InvalidArgumentException("Can't find {$baseClass}#{$owner->ID} in stage {$fromStage}");
		}

		$from->forceChange();
		if($createNewVersion) {
			// Clear version to be automatically created on write
			$from->Version = null;
		} else {
			$from->migrateVersion($from->Version);

			// Mark this version as having been published at some stage
			$publisherID = isset(Member::currentUser()->ID) ? Member::currentUser()->ID : 0;
			$extTable = $this->extendWithSuffix($baseClass);
			DB::prepared_query("UPDATE \"{$extTable}_versions\"
				SET \"WasPublished\" = ?, \"PublisherID\" = ?
				WHERE \"RecordID\" = ? AND \"Version\" = ?",
				array(1, $publisherID, $from->ID, $from->Version)
			);
		}

		// Change to new stage, write, and revert state
		$oldMode = Versioned::get_reading_mode();
		Versioned::set_stage($toStage);

		// Migrate stage prior to write
		$from->setSourceQueryParam('Versioned.mode', 'stage');
		$from->setSourceQueryParam('Versioned.stage', $toStage);

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

		$owner->invokeWithExtensions('onAfterVersionedPublish', $fromStage, $toStage, $createNewVersion);
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
	 * @return bool
	 */
	public function stagesDiffer($stage1, $stage2) {
		$table1 = $this->baseTable($stage1);
		$table2 = $this->baseTable($stage2);

		$owner = $this->owner;
		if(!is_numeric($owner->ID)) {
			return true;
		}

		// We test for equality - if one of the versions doesn't exist, this
		// will be false.

		// TODO: DB Abstraction: if statement here:
		$stagesAreEqual = DB::prepared_query(
			"SELECT CASE WHEN \"$table1\".\"Version\"=\"$table2\".\"Version\" THEN 1 ELSE 0 END
			 FROM \"$table1\" INNER JOIN \"$table2\" ON \"$table1\".\"ID\" = \"$table2\".\"ID\"
			 AND \"$table1\".\"ID\" = ?",
			array($owner->ID)
		)->value();

		return !$stagesAreEqual;
	}

	/**
	 * @param string $filter
	 * @param string $sort
	 * @param string $limit
	 * @param string $join Deprecated, use leftJoin($table, $joinClause) instead
	 * @param string $having
	 * @return ArrayList
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
	 * @return ArrayList
	 */
	public function allVersions($filter = "", $sort = "", $limit = "", $join = "", $having = "") {
		// Make sure the table names are not postfixed (e.g. _Live)
		$oldMode = static::get_reading_mode();
		static::set_stage(static::DRAFT);

		$owner = $this->owner;
		$list = DataObject::get(get_class($owner), $filter, $sort, $join, $limit);
		if($having) {
			$list->having($having);
		}

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
			"\"{$baseTable}_versions\".\"RecordID\" = ?" => $owner->ID
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
	 * Compare two version, and return the diff between them.
	 *
	 * @param string $from The version to compare from.
	 * @param string $to The version to compare to.
	 *
	 * @return DataObject
	 */
	public function compareVersions($from, $to) {
		$owner = $this->owner;
		$fromRecord = Versioned::get_version($owner->class, $owner->ID, $from);
		$toRecord = Versioned::get_version($owner->class, $owner->ID, $to);

		$diff = new DataDifferencer($fromRecord, $toRecord);

		return $diff->diffedData();
	}

	/**
	 * Return the base table - the class that directly extends DataObject.
	 *
	 * Protected so it doesn't conflict with DataObject::baseTable()
	 *
	 * @param string $stage
	 * @return string
	 */
	protected function baseTable($stage = null) {
		$baseTable = $this->owner->baseTable();
		return $this->stageTable($baseTable, $stage);
	}

	/**
	 * Given a table and stage determine the table name.
	 *
	 * Note: Stages this asset does not exist in will default to the draft table.
	 *
	 * @param string $table Main table
	 * @param string $stage
	 * @return string Staged table name
	 */
	public function stageTable($table, $stage) {
		if($this->hasStages() && $stage === static::LIVE) {
			return "{$table}_{$stage}";
		}
		return $table;
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
		if((!$request->getVar('stage') || $request->getVar('stage') === static::LIVE)
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
		// Check any pre-existing session mode
		$preexistingMode = Session::get('readingMode');

		// Determine the reading mode
		if(isset($_GET['stage'])) {
			$stage = ucfirst(strtolower($_GET['stage']));
			if(!in_array($stage, array(static::DRAFT, static::LIVE))) {
				$stage = static::LIVE;
			}
			$mode = 'Stage.' . $stage;
		} elseif (isset($_GET['archiveDate']) && strtotime($_GET['archiveDate'])) {
			$mode = 'Archive.' . $_GET['archiveDate'];
		} elseif($preexistingMode) {
			$mode = $preexistingMode;
		} else {
			$mode = static::DEFAULT_MODE;
		}

		// Save reading mode
		Versioned::set_reading_mode($mode);

		// Try not to store the mode in the session if not needed
		if(($preexistingMode && $preexistingMode !== $mode)
			|| (!$preexistingMode && $mode !== static::DEFAULT_MODE)
		) {
			Session::set('readingMode', $mode);
		}

		if(!headers_sent() && !Director::is_cli()) {
			if(Versioned::get_stage() == 'Live') {
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
		self::$reading_mode = $mode;
	}

	/**
	 * Get the current reading mode.
	 *
	 * @return string
	 */
	public static function get_reading_mode() {
		return self::$reading_mode;
	}

	/**
	 * Get the current reading stage.
	 *
	 * @return string
	 */
	public static function get_stage() {
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
		if($parts[0] == 'Archive') {
			return $parts[1];
		}
	}

	/**
	 * Set the reading stage.
	 *
	 * @param string $stage New reading stage.
	 * @throws InvalidArgumentException
	 */
	public static function set_stage($stage) {
		if(!in_array($stage, [static::LIVE, static::DRAFT])) {
			throw new \InvalidArgumentException("Invalid stage name \"{$stage}\"");
		}
		static::set_reading_mode('Stage.' . $stage);
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
	 * @param string $sort A sort expression to be inserted into the ORDER BY clause.
	 *
	 * @return DataObject
	 */
	public static function get_one_by_stage($class, $stage, $filter = '', $cache = true, $sort = '') {
		// TODO: No identity cache operating
		$items = static::get_by_stage($class, $stage, $filter, $sort, null, 1);

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
		$baseClass = DataObject::getSchema()->baseDataClass($class);
		$stageTable = DataObject::getSchema()->tableName($baseClass);
		if($stage === static::LIVE) {
			$stageTable .= "_{$stage}";
		}

		// cached call
		if($cache && isset(self::$cache_versionnumber[$baseClass][$stage][$id])) {
			return self::$cache_versionnumber[$baseClass][$stage][$id];
		}

		// get version as performance-optimized SQL query (gets called for each record in the sitetree)
		$version = DB::prepared_query(
			"SELECT \"Version\" FROM \"$stageTable\" WHERE \"ID\" = ?",
			array($id)
		)->value();

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
	 * is null, then every record will be pre-cached.
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

		/** @var Versioned|DataObject $singleton */
		$singleton = DataObject::singleton($class);
		$baseClass = $singleton->baseClass();
		$baseTable = $singleton->baseTable();
		$stageTable = $singleton->stageTable($baseTable, $stage);

		$versions = DB::prepared_query("SELECT \"ID\", \"Version\" FROM \"$stageTable\" $filter", $parameters)->map();

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
	 * @return DataList A modified DataList designated to the specified stage
	 */
	public static function get_by_stage(
		$class, $stage, $filter = '', $sort = '', $join = '', $limit = null, $containerClass = 'DataList'
	) {
		$result = DataObject::get($class, $filter, $sort, $join, $limit, $containerClass);
		return $result->setDataQueryParam(array(
			'Versioned.mode' => 'stage',
			'Versioned.stage' => $stage
		));
	}

	/**
	 * Delete this record from the given stage
	 *
	 * @param string $stage
	 */
	public function deleteFromStage($stage) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::set_stage($stage);
		$owner = $this->owner;
		$clone = clone $owner;
		$clone->delete();
		Versioned::set_reading_mode($oldMode);

		// Fix the version number cache (in case you go delete from stage and then check ExistsOnLive)
		$baseClass = $owner->baseClass();
		self::$cache_versionnumber[$baseClass][$stage][$owner->ID] = null;
	}

	/**
	 * Write the given record to the draft stage
	 *
	 * @param string $stage
	 * @param boolean $forceInsert
	 * @return int The ID of the record
	 */
	public function writeToStage($stage, $forceInsert = false) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::set_stage($stage);

		$owner = $this->owner;
		$owner->forceChange();
		$result = $owner->write(false, $forceInsert);
		Versioned::set_reading_mode($oldMode);

		return $result;
	}

	/**
	 * Roll the draft version of this record to match the published record.
	 * Caution: Doesn't overwrite the object properties with the rolled back version.
	 *
	 * {@see doRevertToLive()} to reollback to live
	 *
	 * @param int $version Version number
	 */
	public function doRollbackTo($version) {
		$owner = $this->owner;
		$owner->extend('onBeforeRollback', $version);
		$owner->copyVersionToStage($version, static::DRAFT, true);
		$owner->writeWithoutVersion();
		$owner->extend('onAfterRollback', $version);
	}

	public function onAfterRollback($version) {
		// Find record at this version
		$baseClass = DataObject::getSchema()->baseDataClass($this->owner);
		/** @var Versioned|DataObject $recordVersion */
		$recordVersion = static::get_version($baseClass, $this->owner->ID, $version);

		// Note that unlike other publishing actions, rollback is NOT recursive;
		// The owner collects all objects and writes them back using writeToStage();
		foreach ($recordVersion->findOwned() as $object) {
			/** @var Versioned|DataObject $object */
			$object->writeToStage(static::DRAFT);
		}
	}

	/**
	 * Return the latest version of the given record.
	 *
	 * @param string $class
	 * @param int $id
	 * @return DataObject
	 */
	public static function get_latest_version($class, $id) {
		$baseClass = DataObject::getSchema()->baseDataClass($class);
		$list = DataList::create($baseClass)
			->setDataQueryParam("Versioned.mode", "latest_versions");

		return $list->byID($id);
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
		$owner = $this->owner;
		if(!$owner->isInDB()) {
			return false;
		}

		$version = static::get_latest_version($owner->class, $owner->ID);
		return ($version->Version == $owner->Version);
	}

	/**
	 * Check if this record exists on live
	 *
	 * @return bool
	 */
	public function isPublished() {
		$owner = $this->owner;
		if(!$owner->isInDB()) {
			return false;
		}

		// Non-staged objects are considered "published" if saved
		if(!$this->hasStages()) {
			return true;
		}

		$table = $this->baseTable(static::LIVE);
		$result = DB::prepared_query(
			"SELECT COUNT(*) FROM \"{$table}\" WHERE \"{$table}\".\"ID\" = ?",
			array($owner->ID)
		);
		return (bool)$result->value();
	}

	/**
	 * Check if this record exists on the draft stage
	 *
	 * @return bool
	 */
	public function isOnDraft() {
		$owner = $this->owner;
		if(!$owner->isInDB()) {
			return false;
		}

		$table = $this->baseTable();
		$result = DB::prepared_query(
			"SELECT COUNT(*) FROM \"{$table}\" WHERE \"{$table}\".\"ID\" = ?",
			array($owner->ID)
		);
		return (bool)$result->value();
	}



	/**
	 * Return the equivalent of a DataList::create() call, querying the latest
	 * version of each record stored in the (class)_versions tables.
	 *
	 * In particular, this will query deleted records as well as active ones.
	 *
	 * @param string $class
	 * @param string $filter
	 * @param string $sort
	 * @return DataList
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
		$baseClass = DataObject::getSchema()->baseDataClass($class);
		$list = DataList::create($baseClass)
			->setDataQueryParam([
				"Versioned.mode" => 'version',
				"Versioned.version" => $version
			]);

		return $list->byID($id);
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
		$list = DataList::create($class)
			->filter('ID', $id)
			->setDataQueryParam('Versioned.mode', 'all_versions');

		return $list;
	}

	/**
	 * @param array $labels
	 */
	public function updateFieldLabels(&$labels) {
		$labels['Versions'] = _t('Versioned.has_many_Versions', 'Versions', 'Past Versions of this record');
	}

	/**
	 * @param FieldList
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

	public function flushCache() {
		self::$cache_versionnumber = array();
	}

	/**
	 * Return a piece of text to keep DataObject cache keys appropriately specific.
	 *
	 * @return string
	 */
	public function cacheKeyComponent() {
		return 'versionedmode-'.static::get_reading_mode();
	}

	/**
	 * Returns an array of possible stages.
	 *
	 * @return array
	 */
	public function getVersionedStages() {
		if($this->hasStages()) {
			return [static::DRAFT, static::LIVE];
		} else {
			return [static::DRAFT];
		}
	}

	public static function get_template_global_variables() {
		return array(
			'CurrentReadingMode' => 'get_reading_mode'
		);
	}

	/**
	 * Check if this object has stages
	 *
	 * @return bool True if this object is staged
	 */
	public function hasStages() {
		return $this->mode === static::STAGEDVERSIONED;
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

	/**
	 * Create a new version from a database row
	 *
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
	 * Either 'published' if published, or 'internal' if not.
	 *
	 * @return string
	 */
	public function PublishedClass() {
		return $this->record['WasPublished'] ? 'published' : 'internal';
	}

	/**
	 * Author of this DataObject
	 *
	 * @return Member
	 */
	public function Author() {
		return Member::get()->byId($this->record['AuthorID']);
	}

	/**
	 * Member object of the person who last published this record
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
	 * True if this record is published via publish() method
	 *
	 * @return boolean
	 */
	public function Published() {
		return !empty($this->record['WasPublished']);
	}

	/**
	 * Traverses to a field referenced by relationships between data objects, returning the value
	 * The path to the related field is specified with dot separated syntax (eg: Parent.Child.Child.FieldName)
	 *
	 * @param $fieldName string
	 * @return string | null - will return null on a missing value
	 */
	public function relField($fieldName) {
		$component = $this;

		// We're dealing with relations here so we traverse the dot syntax
		if(strpos($fieldName, '.') !== false) {
			$relations = explode('.', $fieldName);
			$fieldName = array_pop($relations);
			foreach($relations as $relation) {
				// Inspect $component for element $relation
				if($component->hasMethod($relation)) {
					// Check nested method
						$component = $component->$relation();
				} elseif($component instanceof SS_List) {
					// Select adjacent relation from DataList
						$component = $component->relation($relation);
				} elseif($component instanceof DataObject
					&& ($dbObject = $component->dbObject($relation))
				) {
					// Select db object
					$component = $dbObject;
				} else {
					user_error("$relation is not a relation/field on ".get_class($component), E_USER_ERROR);
				}
			}
		}

		// Bail if the component is null
		if(!$component) {
			return null;
		}
		if ($component->hasMethod($fieldName)) {
			return $component->$fieldName();
		}
		return $component->$fieldName;
	}
}
