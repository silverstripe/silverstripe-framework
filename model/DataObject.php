<?php
/**
 * A single database record & abstract class for the data-access-model.
 *
 * <h2>Extensions</h2>
 *
 * See {@link Extension} and {@link DataExtension}.
 *
 * <h2>Permission Control</h2>
 *
 * Object-level access control by {@link Permission}. Permission codes are arbitrary
 * strings which can be selected on a group-by-group basis.
 *
 * <code>
 * class Article extends DataObject implements PermissionProvider {
 *  static $api_access = true;
 *
 *  function canView($member = false) {
 *    return Permission::check('ARTICLE_VIEW');
 *  }
 *  function canEdit($member = false) {
 *    return Permission::check('ARTICLE_EDIT');
 *  }
 *  function canDelete() {
 *    return Permission::check('ARTICLE_DELETE');
 *  }
 *  function canCreate() {
 *    return Permission::check('ARTICLE_CREATE');
 *  }
 *  function providePermissions() {
 *    return array(
 *      'ARTICLE_VIEW' => 'Read an article object',
 *      'ARTICLE_EDIT' => 'Edit an article object',
 *      'ARTICLE_DELETE' => 'Delete an article object',
 *      'ARTICLE_CREATE' => 'Create an article object',
 *    );
 *  }
 * }
 * </code>
 *
 * Object-level access control by {@link Group} membership:
 * <code>
 * class Article extends DataObject {
 *   static $api_access = true;
 *
 *   function canView($member = false) {
 *     if(!$member) $member = Member::currentUser();
 *     return $member->inGroup('Subscribers');
 *   }
 *   function canEdit($member = false) {
 *     if(!$member) $member = Member::currentUser();
 *     return $member->inGroup('Editors');
 *   }
 *
 *   // ...
 * }
 * </code>
 *
 * If any public method on this class is prefixed with an underscore,
 * the results are cached in memory through {@link cachedCall()}.
 *
 *
 * @todo Add instance specific removeExtension() which undos loadExtraStatics()
 *  and defineMethods()
 *
 * @package framework
 * @subpackage model
 *
 * @property integer ID ID of the DataObject, 0 if the DataObject doesn't exist in database.
 * @property string ClassName Class name of the DataObject
 * @property string LastEdited Date and time of DataObject's last modification.
 * @property string Created Date and time of DataObject creation.
 */
class DataObject extends ViewableData implements DataObjectInterface, i18nEntityProvider {

	/**
	 * Human-readable singular name.
	 * @var string
	 * @config
	 */
	private static $singular_name = null;

	/**
	 * Human-readable plural name
	 * @var string
	 * @config
	 */
	private static $plural_name = null;

	/**
	 * Allow API access to this object?
	 * @todo Define the options that can be set here
	 * @config
	 */
	private static $api_access = false;

	/**
	 * True if this DataObject has been destroyed.
	 * @var boolean
	 */
	public $destroyed = false;

	/**
	 * The DataModel from this this object comes
	 */
	protected $model;

	/**
	 * Data stored in this objects database record. An array indexed by fieldname.
	 *
	 * Use {@link toMap()} if you want an array representation
	 * of this object, as the $record array might contain lazy loaded field aliases.
	 *
	 * @var array
	 */
	protected $record;

	/**
	 * Represents a field that hasn't changed (before === after, thus before == after)
	 */
	const CHANGE_NONE = 0;

	/**
	 * Represents a field that has changed type, although not the loosely defined value.
	 * (before !== after && before == after)
	 * E.g. change 1 to true or "true" to true, but not true to 0.
	 * Value changes are by nature also considered strict changes.
	 */
	const CHANGE_STRICT = 1;

	/**
	 * Represents a field that has changed the loosely defined value
	 * (before != after, thus, before !== after))
	 * E.g. change false to true, but not false to 0
	 */
	const CHANGE_VALUE = 2;

	/**
	 * An array indexed by fieldname, true if the field has been changed.
	 * Use {@link getChangedFields()} and {@link isChanged()} to inspect
	 * the changed state.
	 *
	 * @var array
	 */
	private $changed;

	/**
	 * The database record (in the same format as $record), before
	 * any changes.
	 * @var array
	 */
	protected $original;

	/**
	 * Used by onBeforeDelete() to ensure child classes call parent::onBeforeDelete()
	 * @var boolean
	 */
	protected $brokenOnDelete = false;

	/**
	 * Used by onBeforeWrite() to ensure child classes call parent::onBeforeWrite()
	 * @var boolean
	 */
	protected $brokenOnWrite = false;

	/**
	 * @config
	 * @var boolean Should dataobjects be validated before they are written?
	 * Caution: Validation can contain safeguards against invalid/malicious data,
	 * and check permission levels (e.g. on {@link Group}). Therefore it is recommended
	 * to only disable validation for very specific use cases.
	 */
	private static $validation_enabled = true;

	/**
	 * Static caches used by relevant functions.
	 */
	public static $cache_has_own_table = array();
	protected static $_cache_db = array();
	protected static $_cache_get_one;
	protected static $_cache_get_class_ancestry;
	protected static $_cache_composite_fields = array();
	protected static $_cache_is_composite_field = array();
	protected static $_cache_custom_database_fields = array();
	protected static $_cache_field_labels = array();

	// base fields which are not defined in static $db
	private static $fixed_fields = array(
		'ID' => 'Int',
		'ClassName' => 'Enum',
		'LastEdited' => 'SS_Datetime',
		'Created' => 'SS_Datetime',
	);

	/**
	 * Non-static relationship cache, indexed by component name.
	 */
	protected $components;

	/**
	 * Non-static cache of has_many and many_many relations that can't be written until this object is saved.
	 */
	protected $unsavedRelations;

	/**
	 * Returns when validation on DataObjects is enabled.
	 *
	 * @deprecated 3.2 Use the "DataObject.validation_enabled" config setting instead
	 * @return bool
	 */
	public static function get_validation_enabled() {
		Deprecation::notice('3.2', 'Use the "DataObject.validation_enabled" config setting instead');
		return Config::inst()->get('DataObject', 'validation_enabled');
	}

	/**
	 * Set whether DataObjects should be validated before they are written.
	 *
	 * Caution: Validation can contain safeguards against invalid/malicious data,
	 * and check permission levels (e.g. on {@link Group}). Therefore it is recommended
	 * to only disable validation for very specific use cases.
	 *
	 * @param $enable bool
	 * @see DataObject::validate()
	 * @deprecated 3.2 Use the "DataObject.validation_enabled" config setting instead
	 */
	public static function set_validation_enabled($enable) {
		Deprecation::notice('3.2', 'Use the "DataObject.validation_enabled" config setting instead');
		Config::inst()->update('DataObject', 'validation_enabled', (bool)$enable);
	}

	/**
	 * @var [string] - class => ClassName field definition cache for self::database_fields
	 */
	private static $classname_spec_cache = array();

	/**
	 * Clear all cached classname specs. It's necessary to clear all cached subclassed names
	 * for any classes if a new class manifest is generated.
	 */
	public static function clear_classname_spec_cache() {
		self::$classname_spec_cache = array();
		PolymorphicForeignKey::clear_classname_spec_cache();
	}

	/**
	 * Determines the specification for the ClassName field for the given class
	 *
	 * @param string $class
	 * @param boolean $queryDB Determine if the DB may be queried for additional information
	 * @return string Resulting ClassName spec. If $queryDB is true this will include all
	 * legacy types that no longer have concrete classes in PHP
	 */
	public static function get_classname_spec($class, $queryDB = true) {
		// Check cache
		if(!empty(self::$classname_spec_cache[$class])) return self::$classname_spec_cache[$class];

		// Build known class names
		$classNames = ClassInfo::subclassesFor($class);

		// Enhance with existing classes in order to prevent legacy details being lost
		if($queryDB && DB::get_schema()->hasField($class, 'ClassName')) {
			$existing = DB::query("SELECT DISTINCT \"ClassName\" FROM \"{$class}\"")->column();
			$classNames = array_unique(array_merge($classNames, $existing));
		}
		$spec = "Enum('" . implode(', ', $classNames) . "')";

		// Only cache full information if queried
		if($queryDB) self::$classname_spec_cache[$class] = $spec;
		return $spec;
	}

	/**
	 * Return the complete map of fields on this object, including "Created", "LastEdited" and "ClassName".
	 * See {@link custom_database_fields()} for a getter that excludes these "base fields".
	 *
	 * @param string $class
	 * @param boolean $queryDB Determine if the DB may be queried for additional information
	 * @return array
	 */
	public static function database_fields($class, $queryDB = true) {
		if(get_parent_class($class) == 'DataObject') {
			// Merge fixed with ClassName spec and custom db fields
			$fixed = self::$fixed_fields;
			unset($fixed['ID']);
			return array_merge(
				$fixed,
				array('ClassName' => self::get_classname_spec($class, $queryDB)),
				self::custom_database_fields($class)
			);
		}

		return self::custom_database_fields($class);
	}

	/**
	 * Get all database columns explicitly defined on a class in {@link DataObject::$db}
	 * and {@link DataObject::$has_one}. Resolves instances of {@link CompositeDBField}
	 * into the actual database fields, rather than the name of the field which
	 * might not equate a database column.
	 *
	 * Does not include "base fields" like "ID", "ClassName", "Created", "LastEdited",
	 * see {@link database_fields()}.
	 *
	 * @uses CompositeDBField->compositeDatabaseFields()
	 *
	 * @param string $class
	 * @return array Map of fieldname to specification, similiar to {@link DataObject::$db}.
	 */
	public static function custom_database_fields($class) {
		if(isset(self::$_cache_custom_database_fields[$class])) {
			return self::$_cache_custom_database_fields[$class];
		}

		$fields = Config::inst()->get($class, 'db', Config::UNINHERITED);

		foreach(self::composite_fields($class, false) as $fieldName => $fieldClass) {
			// Remove the original fieldname, it's not an actual database column
			unset($fields[$fieldName]);

			// Add all composite columns
			$compositeFields = singleton($fieldClass)->compositeDatabaseFields();
			if($compositeFields) foreach($compositeFields as $compositeName => $spec) {
				$fields["{$fieldName}{$compositeName}"] = $spec;
			}
		}

		// Add has_one relationships
		$hasOne = Config::inst()->get($class, 'has_one', Config::UNINHERITED);
		if($hasOne) foreach(array_keys($hasOne) as $field) {

			// Check if this is a polymorphic relation, in which case the relation
			// is a composite field
			if($hasOne[$field] === 'DataObject') {
				$relationField = DBField::create_field('PolymorphicForeignKey', null, $field);
				$relationField->setTable($class);
				if($compositeFields = $relationField->compositeDatabaseFields()) {
					foreach($compositeFields as $compositeName => $spec) {
						$fields["{$field}{$compositeName}"] = $spec;
					}
				}
			} else {
				$fields[$field . 'ID'] = 'ForeignKey';
			}
		}

		$output = (array) $fields;

		self::$_cache_custom_database_fields[$class] = $output;

		return $output;
	}

	/**
	 * Returns the field class if the given db field on the class is a composite field.
	 * Will check all applicable ancestor classes and aggregate results.
	 *
	 * @param string $class Class to check
	 * @param string $name Field to check
	 * @param boolean $aggregated True if parent classes should be checked, or false to limit to this class
	 * @return string Class name of composite field if it exists
	 */
	public static function is_composite_field($class, $name, $aggregated = true) {
		$key = $class . '_' . $name . '_' . (string)$aggregated;

		if(!isset(DataObject::$_cache_is_composite_field[$key])) {
			$isComposite = null;

			if(!isset(DataObject::$_cache_composite_fields[$class])) {
				self::cache_composite_fields($class);
			}

			if(isset(DataObject::$_cache_composite_fields[$class][$name])) {
				$isComposite = DataObject::$_cache_composite_fields[$class][$name];
			} elseif($aggregated && $class != 'DataObject' && ($parentClass=get_parent_class($class)) != 'DataObject') {
				$isComposite = self::is_composite_field($parentClass, $name);
			}

			DataObject::$_cache_is_composite_field[$key] = ($isComposite) ? $isComposite : false;
		}

		return DataObject::$_cache_is_composite_field[$key] ?: null;
	}

	/**
	 * Returns a list of all the composite if the given db field on the class is a composite field.
	 * Will check all applicable ancestor classes and aggregate results.
	 */
	public static function composite_fields($class, $aggregated = true) {
		if(!isset(DataObject::$_cache_composite_fields[$class])) self::cache_composite_fields($class);

		$compositeFields = DataObject::$_cache_composite_fields[$class];

		if($aggregated && $class != 'DataObject' && ($parentClass=get_parent_class($class)) != 'DataObject') {
			$compositeFields = array_merge($compositeFields,
				self::composite_fields($parentClass));
		}

		return $compositeFields;
	}

	/**
	 * Internal cacher for the composite field information
	 */
	private static function cache_composite_fields($class) {
		$compositeFields = array();

		$fields = Config::inst()->get($class, 'db', Config::UNINHERITED);
		if($fields) foreach($fields as $fieldName => $fieldClass) {
			if(!is_string($fieldClass)) continue;

			// Strip off any parameters
			$bPos = strpos($fieldClass, '(');
			if($bPos !== FALSE) $fieldClass = substr($fieldClass, 0, $bPos);

			// Test to see if it implements CompositeDBField
			if(ClassInfo::classImplements($fieldClass, 'CompositeDBField')) {
				$compositeFields[$fieldName] = $fieldClass;
			}
		}

		DataObject::$_cache_composite_fields[$class] = $compositeFields;
	}

	/**
	 * Construct a new DataObject.
	 *
	 * @param array|null $record This will be null for a new database record.  Alternatively, you can pass an array of
	 * field values.  Normally this contructor is only used by the internal systems that get objects from the database.
	 * @param boolean $isSingleton This this to true if this is a singleton() object, a stub for calling methods.
	 *                             Singletons don't have their defaults set.
	 */
	public function __construct($record = null, $isSingleton = false, $model = null) {
		parent::__construct();

		// Set the fields data.
		if(!$record) {
			$record = array(
				'ID' => 0,
				'ClassName' => get_class($this),
				'RecordClassName' => get_class($this)
			);
		}

		if(!is_array($record) && !is_a($record, "stdClass")) {
			if(is_object($record)) $passed = "an object of type '$record->class'";
			else $passed = "The value '$record'";

			user_error("DataObject::__construct passed $passed.  It's supposed to be passed an array,"
				. " taken straight from the database.  Perhaps you should use DataList::create()->First(); instead?",
				E_USER_WARNING);
			$record = null;
		}

		if(is_a($record, "stdClass")) {
			$record = (array)$record;
		}

		// Set $this->record to $record, but ignore NULLs
		$this->record = array();
		foreach($record as $k => $v) {
			// Ensure that ID is stored as a number and not a string
			// To do: this kind of clean-up should be done on all numeric fields, in some relatively
			// performant manner
			if($v !== null) {
				if($k == 'ID' && is_numeric($v)) $this->record[$k] = (int)$v;
				else $this->record[$k] = $v;
			}
		}

		// Identify fields that should be lazy loaded, but only on existing records
		if(!empty($record['ID'])) {
			$currentObj = get_class($this);
			while($currentObj != 'DataObject') {
				$fields = self::custom_database_fields($currentObj);
				foreach($fields as $field => $type) {
					if(!array_key_exists($field, $record)) $this->record[$field.'_Lazy'] = $currentObj;
				}
				$currentObj = get_parent_class($currentObj);
			}
		}

		$this->original = $this->record;

		// Keep track of the modification date of all the data sourced to make this page
		// From this we create a Last-Modified HTTP header
		if(isset($record['LastEdited'])) {
			HTTP::register_modification_date($record['LastEdited']);
		}

		// this must be called before populateDefaults(), as field getters on a DataObject
		// may call getComponent() and others, which rely on $this->model being set.
		$this->model = $model ? $model : DataModel::inst();

		// Must be called after parent constructor
		if(!$isSingleton && (!isset($this->record['ID']) || !$this->record['ID'])) {
			$this->populateDefaults();
		}

		// prevent populateDefaults() and setField() from marking overwritten defaults as changed
		$this->changed = array();
	}

	/**
	 * Set the DataModel
	 * @param DataModel $model
	 * @return DataObject $this
	 */
	public function setDataModel(DataModel $model) {
		$this->model = $model;
		return $this;
	}

	/**
	 * Destroy all of this objects dependant objects and local caches.
	 * You'll need to call this to get the memory of an object that has components or extensions freed.
	 */
	public function destroy() {
		//$this->destroyed = true;
		gc_collect_cycles();
		$this->flushCache(false);
	}

	/**
	 * Create a duplicate of this node.
	 * Note: now also duplicates relations.
	 *
	 * @param $doWrite Perform a write() operation before returning the object.  If this is true, it will create the
	 *                 duplicate in the database.
	 * @return DataObject A duplicate of this node. The exact type will be the type of this node.
	 */
	public function duplicate($doWrite = true) {
		$className = $this->class;
		$map = $this->toMap();
		unset($map['Created']);
		$clone = new $className( $map, false, $this->model );
		$clone->ID = 0;

		$clone->invokeWithExtensions('onBeforeDuplicate', $this, $doWrite);
		if($doWrite) {
			$clone->write();
			$this->duplicateManyManyRelations($this, $clone);
		}
		$clone->invokeWithExtensions('onAfterDuplicate', $this, $doWrite);

		return $clone;
	}

	/**
	 * Copies the many_many and belongs_many_many relations from one object to another instance of the name of object
	 * The destinationObject must be written to the database already and have an ID. Writing is performed
	 * automatically when adding the new relations.
	 *
	 * @param $sourceObject the source object to duplicate from
	 * @param $destinationObject the destination object to populate with the duplicated relations
	 * @return DataObject with the new many_many relations copied in
	 */
	protected function duplicateManyManyRelations($sourceObject, $destinationObject) {
		if (!$destinationObject || $destinationObject->ID < 1) {
			user_error("Can't duplicate relations for an object that has not been written to the database",
				E_USER_ERROR);
		}

		//duplicate complex relations
		// DO NOT copy has_many relations, because copying the relation would result in us changing the has_one
		// relation on the other side of this relation to point at the copy and no longer the original (being a
		// has_one, it can only point at one thing at a time). So, all relations except has_many can and are copied
		if ($sourceObject->hasOne()) foreach($sourceObject->hasOne() as $name => $type) {
			$this->duplicateRelations($sourceObject, $destinationObject, $name);
		}
		if ($sourceObject->manyMany()) foreach($sourceObject->manyMany() as $name => $type) {
			//many_many include belongs_many_many
			$this->duplicateRelations($sourceObject, $destinationObject, $name);
		}

		return $destinationObject;
	}

	/**
	 * Helper function to duplicate relations from one object to another
	 * @param $sourceObject the source object to duplicate from
	 * @param $destinationObject the destination object to populate with the duplicated relations
	 * @param $name the name of the relation to duplicate (e.g. members)
	 */
	private function duplicateRelations($sourceObject, $destinationObject, $name) {
		$relations = $sourceObject->$name();
		if ($relations) {
			if ($relations instanceOf RelationList) {   //many-to-something relation
				if ($relations->Count() > 0) {  //with more than one thing it is related to
					foreach($relations as $relation) {
						$destinationObject->$name()->add($relation);
					}
				}
			} else {    //one-to-one relation
				$destinationObject->{"{$name}ID"} = $relations->ID;
			}
		}
	}

	public function getObsoleteClassName() {
		$className = $this->getField("ClassName");
		if (!ClassInfo::exists($className)) return $className;
	}

	public function getClassName() {
		$className = $this->getField("ClassName");
		if (!ClassInfo::exists($className)) return get_class($this);
		return $className;
	}

	/**
	 * Set the ClassName attribute. {@link $class} is also updated.
	 * Warning: This will produce an inconsistent record, as the object
	 * instance will not automatically switch to the new subclass.
	 * Please use {@link newClassInstance()} for this purpose,
	 * or destroy and reinstanciate the record.
	 *
	 * @param string $className The new ClassName attribute (a subclass of {@link DataObject})
	 * @return DataObject $this
	 */
	public function setClassName($className) {
		$className = trim($className);
		if(!$className || !is_subclass_of($className, 'DataObject')) return;

		$this->class = $className;
		$this->setField("ClassName", $className);
		return $this;
	}

	/**
	 * Create a new instance of a different class from this object's record.
	 * This is useful when dynamically changing the type of an instance. Specifically,
	 * it ensures that the instance of the class is a match for the className of the
	 * record. Don't set the {@link DataObject->class} or {@link DataObject->ClassName}
	 * property manually before calling this method, as it will confuse change detection.
	 *
	 * If the new class is different to the original class, defaults are populated again
	 * because this will only occur automatically on instantiation of a DataObject if
	 * there is no record, or the record has no ID. In this case, we do have an ID but
	 * we still need to repopulate the defaults.
	 *
	 * @param string $newClassName The name of the new class
	 *
	 * @return DataObject The new instance of the new class, The exact type will be of the class name provided.
	 */
	public function newClassInstance($newClassName) {
		$originalClass = $this->ClassName;
		$newInstance = new $newClassName(array_merge(
			$this->record,
			array(
				'ClassName' => $originalClass,
				'RecordClassName' => $originalClass,
			)
		), false, $this->model);

		if($newClassName != $originalClass) {
			$newInstance->setClassName($newClassName);
			$newInstance->populateDefaults();
			$newInstance->forceChange();
		}

		return $newInstance;
	}

	/**
	 * Adds methods from the extensions.
	 * Called by Object::__construct() once per class.
	 */
	public function defineMethods() {
		parent::defineMethods();

		// Define the extra db fields - this is only necessary for extensions added in the
		// class definition.  Object::add_extension() will call this at definition time for
		// those objects, which is a better mechanism.  Perhaps extensions defined inside the
		// class def can somehow be applied at definiton time also?
		if($this->extension_instances) foreach($this->extension_instances as $i => $instance) {
			if(!$instance->class) {
				$class = get_class($instance);
				user_error("DataObject::defineMethods(): Please ensure {$class}::__construct() calls"
					. " parent::__construct()", E_USER_ERROR);
			}
		}

		if($this->class == 'DataObject') return;

		// Set up accessors for joined items
		if($manyMany = $this->manyMany()) {
			foreach($manyMany as $relationship => $class) {
				$this->addWrapperMethod($relationship, 'getManyManyComponents');
			}
		}
		if($hasMany = $this->hasMany()) {

			foreach($hasMany as $relationship => $class) {
				$this->addWrapperMethod($relationship, 'getComponents');
			}

		}
		if($hasOne = $this->hasOne()) {
			foreach($hasOne as $relationship => $class) {
				$this->addWrapperMethod($relationship, 'getComponent');
			}
		}
		if($belongsTo = $this->belongsTo()) foreach(array_keys($belongsTo) as $relationship) {
			$this->addWrapperMethod($relationship, 'getComponent');
		}
	}

	/**
	 * Returns true if this object "exists", i.e., has a sensible value.
	 * The default behaviour for a DataObject is to return true if
	 * the object exists in the database, you can override this in subclasses.
	 *
	 * @return boolean true if this object exists
	 */
	public function exists() {
		return (isset($this->record['ID']) && $this->record['ID'] > 0);
	}

	/**
	 * Returns TRUE if all values (other than "ID") are
	 * considered empty (by weak boolean comparison).
	 * Only checks for fields listed in {@link custom_database_fields()}
	 *
	 * @todo Use DBField->hasValue()
	 *
	 * @return boolean
	 */
	public function isEmpty(){
		$isEmpty = true;
		$customFields = self::custom_database_fields(get_class($this));
		if($map = $this->toMap()){
			foreach($map as $k=>$v){
				// only look at custom fields
				if(!array_key_exists($k, $customFields)) continue;

				$dbObj = ($v instanceof DBField) ? $v : $this->dbObject($k);
				$isEmpty = ($isEmpty && !$dbObj->exists());
			}
		}
		return $isEmpty;
	}

	/**
	 * Get the user friendly singular name of this DataObject.
	 * If the name is not defined (by redefining $singular_name in the subclass),
	 * this returns the class name.
	 *
	 * @return string User friendly singular name of this DataObject
	 */
	public function singular_name() {
		if(!$name = $this->stat('singular_name')) {
			$name = ucwords(trim(strtolower(preg_replace('/_?([A-Z])/', ' $1', $this->class))));
		}

		return $name;
	}

	/**
	 * Get the translated user friendly singular name of this DataObject
	 * same as singular_name() but runs it through the translating function
	 *
	 * Translating string is in the form:
	 *     $this->class.SINGULARNAME
	 * Example:
	 *     Page.SINGULARNAME
	 *
	 * @return string User friendly translated singular name of this DataObject
	 */
	public function i18n_singular_name() {
		return _t($this->class.'.SINGULARNAME', $this->singular_name());
	}

	/**
	 * Get the user friendly plural name of this DataObject
	 * If the name is not defined (by renaming $plural_name in the subclass),
	 * this returns a pluralised version of the class name.
	 *
	 * @return string User friendly plural name of this DataObject
	 */
	public function plural_name() {
		if($name = $this->stat('plural_name')) {
			return $name;
		} else {
			$name = $this->singular_name();
			//if the penultimate character is not a vowel, replace "y" with "ies"
			if (preg_match('/[^aeiou]y$/i', $name)) {
				$name = substr($name,0,-1) . 'ie';
			}
			return ucfirst($name . 's');
		}
	}

	/**
	 * Get the translated user friendly plural name of this DataObject
	 * Same as plural_name but runs it through the translation function
	 * Translation string is in the form:
	 *      $this->class.PLURALNAME
	 * Example:
	 *      Page.PLURALNAME
	 *
	 * @return string User friendly translated plural name of this DataObject
	 */
	public function i18n_plural_name()
	{
		$name = $this->plural_name();
		return _t($this->class.'.PLURALNAME', $name);
	}

	/**
	 * Standard implementation of a title/label for a specific
	 * record. Tries to find properties 'Title' or 'Name',
	 * and falls back to the 'ID'. Useful to provide
	 * user-friendly identification of a record, e.g. in errormessages
	 * or UI-selections.
	 *
	 * Overload this method to have a more specialized implementation,
	 * e.g. for an Address record this could be:
	 * <code>
	 * function getTitle() {
	 *   return "{$this->StreetNumber} {$this->StreetName} {$this->City}";
	 * }
	 * </code>
	 *
	 * @return string
	 */
	public function getTitle() {
		if($this->hasDatabaseField('Title')) return $this->getField('Title');
		if($this->hasDatabaseField('Name')) return $this->getField('Name');

		return "#{$this->ID}";
	}

	/**
	 * Returns the associated database record - in this case, the object itself.
	 * This is included so that you can call $dataOrController->data() and get a DataObject all the time.
	 *
	 * @return DataObject Associated database record
	 */
	public function data() {
		return $this;
	}

	/**
	 * Convert this object to a map.
	 *
	 * @return array The data as a map.
	 */
	public function toMap() {
		$this->loadLazyFields();
		return $this->record;
	}

	/**
	 * Return all currently fetched database fields.
	 *
	 * This function is similar to toMap() but doesn't trigger the lazy-loading of all unfetched fields.
	 * Obviously, this makes it a lot faster.
	 *
	 * @return array The data as a map.
	 */
	public function getQueriedDatabaseFields() {
		return $this->record;
	}

	/**
	 * Update a number of fields on this object, given a map of the desired changes.
	 *
	 * The field names can be simple names, or you can use a dot syntax to access $has_one relations.
	 * For example, array("Author.FirstName" => "Jim") will set $this->Author()->FirstName to "Jim".
	 *
	 * update() doesn't write the main object, but if you use the dot syntax, it will write()
	 * the related objects that it alters.
	 *
	 * @param array $data A map of field name to data values to update.
	 * @return DataObject $this
	 */
	public function update($data) {
		foreach($data as $k => $v) {
			// Implement dot syntax for updates
			if(strpos($k,'.') !== false) {
				$relations = explode('.', $k);
				$fieldName = array_pop($relations);
				$relObj = $this;
				foreach($relations as $i=>$relation) {
					// no support for has_many or many_many relationships,
					// as the updater wouldn't know which object to write to (or create)
					if($relObj->$relation() instanceof DataObject) {
						$parentObj = $relObj;
						$relObj = $relObj->$relation();
						// If the intermediate relationship objects have been created, then write them
						if($i<sizeof($relation)-1 && !$relObj->ID || (!$relObj->ID && $parentObj != $this)) {
							$relObj->write();
							$relatedFieldName = $relation."ID";
							$parentObj->$relatedFieldName = $relObj->ID;
							$parentObj->write();
						}
					} else {
						user_error(
							"DataObject::update(): Can't traverse relationship '$relation'," .
							"it has to be a has_one relationship or return a single DataObject",
							E_USER_NOTICE
						);
						// unset relation object so we don't write properties to the wrong object
						unset($relObj);
						break;
					}
				}

				if($relObj) {
					$relObj->$fieldName = $v;
					$relObj->write();
					$relatedFieldName = $relation."ID";
					$this->$relatedFieldName = $relObj->ID;
					$relObj->flushCache();
				} else {
					user_error("Couldn't follow dot syntax '$k' on '$this->class' object", E_USER_WARNING);
				}
			} else {
				$this->$k = $v;
			}
		}
		return $this;
	}

	/**
	 * Pass changes as a map, and try to
	 * get automatic casting for these fields.
	 * Doesn't write to the database. To write the data,
	 * use the write() method.
	 *
	 * @param array $data A map of field name to data values to update.
	 * @return DataObject $this
	 */
	public function castedUpdate($data) {
		foreach($data as $k => $v) {
			$this->setCastedField($k,$v);
		}
		return $this;
	}

	/**
	 * Merges data and relations from another object of same class,
	 * without conflict resolution. Allows to specify which
	 * dataset takes priority in case its not empty.
	 * has_one-relations are just transferred with priority 'right'.
	 * has_many and many_many-relations are added regardless of priority.
	 *
	 * Caution: has_many/many_many relations are moved rather than duplicated,
	 * meaning they are not connected to the merged object any longer.
	 * Caution: Just saves updated has_many/many_many relations to the database,
	 * doesn't write the updated object itself (just writes the object-properties).
	 * Caution: Does not delete the merged object.
	 * Caution: Does now overwrite Created date on the original object.
	 *
	 * @param $obj DataObject
	 * @param $priority String left|right Determines who wins in case of a conflict (optional)
	 * @param $includeRelations Boolean Merge any existing relations (optional)
	 * @param $overwriteWithEmpty Boolean Overwrite existing left values with empty right values.
	 *                            Only applicable with $priority='right'. (optional)
	 * @return Boolean
	 */
	public function merge($rightObj, $priority = 'right', $includeRelations = true, $overwriteWithEmpty = false) {
		$leftObj = $this;

		if($leftObj->ClassName != $rightObj->ClassName) {
			// we can't merge similiar subclasses because they might have additional relations
			user_error("DataObject->merge(): Invalid object class '{$rightObj->ClassName}'
			(expected '{$leftObj->ClassName}').", E_USER_WARNING);
			return false;
		}

		if(!$rightObj->ID) {
			user_error("DataObject->merge(): Please write your merged-in object to the database before merging,
				to make sure all relations are transferred properly.').", E_USER_WARNING);
			return false;
		}

		// makes sure we don't merge data like ID or ClassName
		$leftData = $leftObj->inheritedDatabaseFields();
		$rightData = $rightObj->inheritedDatabaseFields();

		foreach($rightData as $key=>$rightVal) {
			// don't merge conflicting values if priority is 'left'
			if($priority == 'left' && $leftObj->{$key} !== $rightObj->{$key}) continue;

			// don't overwrite existing left values with empty right values (if $overwriteWithEmpty is set)
			if($priority == 'right' && !$overwriteWithEmpty && empty($rightObj->{$key})) continue;

			// TODO remove redundant merge of has_one fields
			$leftObj->{$key} = $rightObj->{$key};
		}

		// merge relations
		if($includeRelations) {
			if($manyMany = $this->manyMany()) {
				foreach($manyMany as $relationship => $class) {
					$leftComponents = $leftObj->getManyManyComponents($relationship);
					$rightComponents = $rightObj->getManyManyComponents($relationship);
					if($rightComponents && $rightComponents->exists()) {
						$leftComponents->addMany($rightComponents->column('ID'));
					}
					$leftComponents->write();
				}
			}

			if($hasMany = $this->hasMany()) {
				foreach($hasMany as $relationship => $class) {
					$leftComponents = $leftObj->getComponents($relationship);
					$rightComponents = $rightObj->getComponents($relationship);
					if($rightComponents && $rightComponents->exists()) {
						$leftComponents->addMany($rightComponents->column('ID'));
					}
					$leftComponents->write();
				}

			}

			if($hasOne = $this->hasOne()) {
				foreach($hasOne as $relationship => $class) {
					$leftComponent = $leftObj->getComponent($relationship);
					$rightComponent = $rightObj->getComponent($relationship);
					if($leftComponent->exists() && $rightComponent->exists() && $priority == 'right') {
						$leftObj->{$relationship . 'ID'} = $rightObj->{$relationship . 'ID'};
					}
				}
			}
		}

		return true;
	}

	/**
	 * Forces the record to think that all its data has changed.
	 * Doesn't write to the database. Only sets fields as changed
	 * if they are not already marked as changed.
	 *
	 * @return $this
	 */
	public function forceChange() {
		// Ensure lazy fields loaded
		$this->loadLazyFields();

		// $this->record might not contain the blank values so we loop on $this->inheritedDatabaseFields() as well
		$fieldNames = array_unique(array_merge(
			array_keys($this->record),
			array_keys($this->inheritedDatabaseFields())
		));

		foreach($fieldNames as $fieldName) {
			if(!isset($this->changed[$fieldName])) $this->changed[$fieldName] = self::CHANGE_STRICT;
			// Populate the null values in record so that they actually get written
			if(!isset($this->record[$fieldName])) $this->record[$fieldName] = null;
		}

		// @todo Find better way to allow versioned to write a new version after forceChange
		if($this->isChanged('Version')) unset($this->changed['Version']);
		return $this;
	}

	/**
	 * Validate the current object.
	 *
	 * By default, there is no validation - objects are always valid!  However, you can overload this method in your
	 * DataObject sub-classes to specify custom validation, or use the hook through DataExtension.
	 *
	 * Invalid objects won't be able to be written - a warning will be thrown and no write will occur.  onBeforeWrite()
	 * and onAfterWrite() won't get called either.
	 *
	 * It is expected that you call validate() in your own application to test that an object is valid before
	 * attempting a write, and respond appropriately if it isn't.
	 *
	 * @see {@link ValidationResult}
	 * @return ValidationResult
	 */
	protected function validate() {
		$result = ValidationResult::create();
		$this->extend('validate', $result);
		return $result;
	}

	/**
	 * Public accessor for {@see DataObject::validate()}
	 *
	 * @return ValidationResult
	 */
	public function doValidate() {
		// validate will be public in 4.0
		return $this->validate();
	}

	/**
	 * Event handler called before writing to the database.
	 * You can overload this to clean up or otherwise process data before writing it to the
	 * database.  Don't forget to call parent::onBeforeWrite(), though!
	 *
	 * This called after {@link $this->validate()}, so you can be sure that your data is valid.
	 *
	 * @uses DataExtension->onBeforeWrite()
	 */
	protected function onBeforeWrite() {
		$this->brokenOnWrite = false;

		$dummy = null;
		$this->extend('onBeforeWrite', $dummy);
	}

	/**
	 * Event handler called after writing to the database.
	 * You can overload this to act upon changes made to the data after it is written.
	 * $this->changed will have a record
	 * database.  Don't forget to call parent::onAfterWrite(), though!
	 *
	 * @uses DataExtension->onAfterWrite()
	 */
	protected function onAfterWrite() {
		$dummy = null;
		$this->extend('onAfterWrite', $dummy);
	}

	/**
	 * Event handler called before deleting from the database.
	 * You can overload this to clean up or otherwise process data before delete this
	 * record.  Don't forget to call parent::onBeforeDelete(), though!
	 *
	 * @uses DataExtension->onBeforeDelete()
	 */
	protected function onBeforeDelete() {
		$this->brokenOnDelete = false;

		$dummy = null;
		$this->extend('onBeforeDelete', $dummy);
	}

	protected function onAfterDelete() {
		$this->extend('onAfterDelete');
	}

	/**
	 * Load the default values in from the self::$defaults array.
	 * Will traverse the defaults of the current class and all its parent classes.
	 * Called by the constructor when creating new records.
	 *
	 * @uses DataExtension->populateDefaults()
	 * @return DataObject $this
	 */
	public function populateDefaults() {
		$classes = array_reverse(ClassInfo::ancestry($this));

		foreach($classes as $class) {
			$defaults = Config::inst()->get($class, 'defaults', Config::UNINHERITED);

			if($defaults && !is_array($defaults)) {
				user_error("Bad '$this->class' defaults given: " . var_export($defaults, true),
					E_USER_WARNING);
				$defaults = null;
			}

			if($defaults) foreach($defaults as $fieldName => $fieldValue) {
				// SRM 2007-03-06: Stricter check
				if(!isset($this->$fieldName) || $this->$fieldName === null) {
					$this->$fieldName = $fieldValue;
				}
				// Set many-many defaults with an array of ids
				if(is_array($fieldValue) && $this->manyManyComponent($fieldName)) {
					$manyManyJoin = $this->$fieldName();
					$manyManyJoin->setByIdList($fieldValue);
				}
			}
			if($class == 'DataObject') {
				break;
			}
		}

		$this->extend('populateDefaults');
		return $this;
	}

	/**
	 * Determine validation of this object prior to write
	 *
	 * @return ValidationException Exception generated by this write, or null if valid
	 */
	protected function validateWrite() {
		if ($this->ObsoleteClassName) {
			return new ValidationException(
				"Object is of class '{$this->ObsoleteClassName}' which doesn't exist - ".
				"you need to change the ClassName before you can write it",
				E_USER_WARNING
			);
		}

		if(Config::inst()->get('DataObject', 'validation_enabled')) {
			$result = $this->validate();
			if (!$result->valid()) {
				return new ValidationException(
					$result,
					$result->message(),
					E_USER_WARNING
				);
			}
		}
	}

	/**
	 * Prepare an object prior to write
	 *
	 * @throws ValidationException
	 */
	protected function preWrite() {
		// Validate this object
		if($writeException = $this->validateWrite()) {
			// Used by DODs to clean up after themselves, eg, Versioned
			$this->invokeWithExtensions('onAfterSkippedWrite');
			throw $writeException;
		}

		// Check onBeforeWrite
		$this->brokenOnWrite = true;
		$this->onBeforeWrite();
		if($this->brokenOnWrite) {
			user_error("$this->class has a broken onBeforeWrite() function."
				. " Make sure that you call parent::onBeforeWrite().", E_USER_ERROR);
		}
	}

	/**
	 * Detects and updates all changes made to this object
	 *
	 * @param bool $forceChanges If set to true, force all fields to be treated as changed
	 * @return bool True if any changes are detected
	 */
	protected function updateChanges($forceChanges = false)
	{
		if($forceChanges) {
			// Force changes, but only for loaded fields
			foreach($this->record as $field => $value) {
				$this->changed[$field] = static::CHANGE_VALUE;
			}
			return true;
		}
		return $this->isChanged();
	}

	/**
	 * Writes a subset of changes for a specific table to the given manipulation
	 *
	 * @param string $baseTable Base table
	 * @param string $now Timestamp to use for the current time
	 * @param bool $isNewRecord Whether this should be treated as a new record write
	 * @param array $manipulation Manipulation to write to
	 * @param string $class Table and Class to select and write to
	 */
	protected function prepareManipulationTable($baseTable, $now, $isNewRecord, &$manipulation, $class) {
		$manipulation[$class] = array();

		// Extract records for this table
		foreach($this->record as $fieldName => $fieldValue) {

			// Check if this record pertains to this table, and
			// we're not attempting to reset the BaseTable->ID
			if(	empty($this->changed[$fieldName])
				|| ($class === $baseTable && $fieldName === 'ID')
				|| (!self::has_own_table_database_field($class, $fieldName)
					&& !self::is_composite_field($class, $fieldName, false))
			) {
				continue;
			}


			// if database column doesn't correlate to a DBField instance...
			$fieldObj = $this->dbObject($fieldName);
			if(!$fieldObj) {
				$fieldObj = DBField::create_field('Varchar', $fieldValue, $fieldName);
			}

			// Ensure DBField is repopulated and written to the manipulation
			$fieldObj->setValue($fieldValue, $this->record);
			$fieldObj->writeToManipulation($manipulation[$class]);
		}

		// Ensure update of Created and LastEdited columns
		if($baseTable === $class) {
			$manipulation[$class]['fields']['LastEdited'] = $now;
			if($isNewRecord) {
				$manipulation[$class]['fields']['Created']
					= empty($this->record['Created'])
						? $now
						: $this->record['Created'];
				$manipulation[$class]['fields']['ClassName'] = $this->class;
			}
		}

		// Inserts done one the base table are performed in another step, so the manipulation should instead
		// attempt an update, as though it were a normal update.
		$manipulation[$class]['command'] = $isNewRecord ? 'insert' : 'update';
		$manipulation[$class]['id'] = $this->record['ID'];
	}

	/**
	 * Ensures that a blank base record exists with the basic fixed fields for this dataobject
	 *
	 * Does nothing if an ID is already assigned for this record
	 *
	 * @param string $baseTable Base table
	 * @param string $now Timestamp to use for the current time
	 */
	protected function writeBaseRecord($baseTable, $now) {
		// Generate new ID if not specified
		if($this->isInDB()) return;

		// Perform an insert on the base table
		$insert = new SQLInsert('"'.$baseTable.'"');
		$insert
			->assign('"Created"', $now)
			->execute();
		$this->changed['ID'] = self::CHANGE_VALUE;
		$this->record['ID'] = DB::get_generated_id($baseTable);
	}

	/**
	 * Generate and write the database manipulation for all changed fields
	 *
	 * @param string $baseTable Base table
	 * @param string $now Timestamp to use for the current time
	 * @param bool $isNewRecord If this is a new record
	 */
	protected function writeManipulation($baseTable, $now, $isNewRecord) {
		// Generate database manipulations for each class
		$manipulation = array();
		foreach($this->getClassAncestry() as $class) {
			if(self::has_own_table($class)) {
				$this->prepareManipulationTable($baseTable, $now, $isNewRecord, $manipulation, $class);
			}
		}

		// Allow extensions to extend this manipulation
		$this->extend('augmentWrite', $manipulation);

		// New records have their insert into the base data table done first, so that they can pass the
		// generated ID on to the rest of the manipulation
		if($isNewRecord) {
			$manipulation[$baseTable]['command'] = 'update';
		}

		// Perform the manipulation
		DB::manipulate($manipulation);
	}

	/**
	 * Writes all changes to this object to the database.
	 *  - It will insert a record whenever ID isn't set, otherwise update.
	 *  - All relevant tables will be updated.
	 *  - $this->onBeforeWrite() gets called beforehand.
	 *  - Extensions such as Versioned will ammend the database-write to ensure that a version is saved.
	 *
	 *  @uses DataExtension->augmentWrite()
	 *
	 * @param boolean $showDebug Show debugging information
	 * @param boolean $forceInsert Run INSERT command rather than UPDATE, even if record already exists
	 * @param boolean $forceWrite Write to database even if there are no changes
	 * @param boolean $writeComponents Call write() on all associated component instances which were previously
	 *                                 retrieved through {@link getComponent()}, {@link getComponents()} or
	 *                                 {@link getManyManyComponents()} (Default: false)
	 * @return int The ID of the record
	 * @throws ValidationException Exception that can be caught and handled by the calling function
	 */
	public function write($showDebug = false, $forceInsert = false, $forceWrite = false, $writeComponents = false) {
		$now = SS_Datetime::now()->Rfc2822();

		// Execute pre-write tasks
		$this->preWrite();

		// Check if we are doing an update or an insert
		$isNewRecord = !$this->isInDB() || $forceInsert;

		// Check changes exist, abort if there are none
		$hasChanges = $this->updateChanges($isNewRecord);
		if($hasChanges || $forceWrite || $isNewRecord) {
			// New records have their insert into the base data table done first, so that they can pass the
			// generated primary key on to the rest of the manipulation
			$baseTable = ClassInfo::baseDataClass($this->class);
			$this->writeBaseRecord($baseTable, $now);

			// Write the DB manipulation for all changed fields
			$this->writeManipulation($baseTable, $now, $isNewRecord);

			// If there's any relations that couldn't be saved before, save them now (we have an ID here)
			$this->writeRelations();
			$this->onAfterWrite();
			$this->changed = array();
		} else {
			if($showDebug) Debug::message("no changes for DataObject");

			// Used by DODs to clean up after themselves, eg, Versioned
			$this->invokeWithExtensions('onAfterSkippedWrite');
		}

		// Ensure Created and LastEdited are populated
		if(!isset($this->record['Created'])) {
			$this->record['Created'] = $now;
		}
		$this->record['LastEdited'] = $now;

		// Write relations as necessary
		if($writeComponents) $this->writeComponents(true);

		// Clears the cache for this object so get_one returns the correct object.
		$this->flushCache();

		return $this->record['ID'];
	}

	/**
	 * Writes cached relation lists to the database, if possible
	 */
	public function writeRelations() {
		if(!$this->isInDB()) return;

		// If there's any relations that couldn't be saved before, save them now (we have an ID here)
		if($this->unsavedRelations) {
			foreach($this->unsavedRelations as $name => $list) {
				$list->changeToList($this->$name());
			}
			$this->unsavedRelations = array();
		}
	}

	/**
	 * Write the cached components to the database. Cached components could refer to two different instances of the
	 * same record.
	 *
	 * @param $recursive Recursively write components
	 * @return DataObject $this
	 */
	public function writeComponents($recursive = false) {
		if(!$this->components) return $this;

		foreach($this->components as $component) {
			$component->write(false, false, false, $recursive);
		}
		return $this;
	}

	/**
	 * Delete this data object.
	 * $this->onBeforeDelete() gets called.
	 * Note that in Versioned objects, both Stage and Live will be deleted.
	 *  @uses DataExtension->augmentSQL()
	 */
	public function delete() {
		$this->brokenOnDelete = true;
		$this->onBeforeDelete();
		if($this->brokenOnDelete) {
			user_error("$this->class has a broken onBeforeDelete() function."
				. " Make sure that you call parent::onBeforeDelete().", E_USER_ERROR);
		}

		// Deleting a record without an ID shouldn't do anything
		if(!$this->ID) throw new LogicException("DataObject::delete() called on a DataObject without an ID");

		// TODO: This is quite ugly.  To improve:
		//  - move the details of the delete code in the DataQuery system
		//  - update the code to just delete the base table, and rely on cascading deletes in the DB to do the rest
		//    obviously, that means getting requireTable() to configure cascading deletes ;-)
		$srcQuery = DataList::create($this->class, $this->model)->where("ID = $this->ID")->dataQuery()->query();
		foreach($srcQuery->queriedTables() as $table) {
			$delete = new SQLDelete("\"$table\"", array('"ID"' => $this->ID));
			$delete->execute();
		}
		// Remove this item out of any caches
		$this->flushCache();

		$this->onAfterDelete();

		$this->OldID = $this->ID;
		$this->ID = 0;
	}

	/**
	 * Delete the record with the given ID.
	 *
	 * @param string $className The class name of the record to be deleted
	 * @param int $id ID of record to be deleted
	 */
	public static function delete_by_id($className, $id) {
		$obj = DataObject::get_by_id($className, $id);
		if($obj) {
			$obj->delete();
		} else {
			user_error("$className object #$id wasn't found when calling DataObject::delete_by_id", E_USER_WARNING);
		}
	}

	/**
	 * Get the class ancestry, including the current class name.
	 * The ancestry will be returned as an array of class names, where the 0th element
	 * will be the class that inherits directly from DataObject, and the last element
	 * will be the current class.
	 *
	 * @return array Class ancestry
	 */
	public function getClassAncestry() {
		if(!isset(DataObject::$_cache_get_class_ancestry[$this->class])) {
			DataObject::$_cache_get_class_ancestry[$this->class] = array($this->class);
			while(($class=get_parent_class(DataObject::$_cache_get_class_ancestry[$this->class][0])) != "DataObject") {
				array_unshift(DataObject::$_cache_get_class_ancestry[$this->class], $class);
			}
		}
		return DataObject::$_cache_get_class_ancestry[$this->class];
	}

	/**
	 * Return a component object from a one to one relationship, as a DataObject.
	 * If no component is available, an 'empty component' will be returned for
	 * non-polymorphic relations, or for polymorphic relations with a class set.
	 *
	 * @param string $componentName Name of the component
	 *
	 * @return DataObject The component object. It's exact type will be that of the component.
	 */
	public function getComponent($componentName) {
		if(isset($this->components[$componentName])) {
			return $this->components[$componentName];
		}

		if($class = $this->hasOneComponent($componentName)) {
			$joinField = $componentName . 'ID';
			$joinID    = $this->getField($joinField);

			// Extract class name for polymorphic relations
			if($class === 'DataObject') {
				$class = $this->getField($componentName . 'Class');
				if(empty($class)) return null;
			}

			if($joinID) {
				$component = DataObject::get_by_id($class, $joinID);
			}

			if(empty($component)) {
				$component = $this->model->$class->newObject();
			}
		} elseif($class = $this->belongsToComponent($componentName)) {

			$joinField = $this->getRemoteJoinField($componentName, 'belongs_to', $polymorphic);
			$joinID    = $this->ID;

			if($joinID) {

				$filter = $polymorphic
					? array(
						"{$joinField}ID" => $joinID,
						"{$joinField}Class" => $this->class
					)
					: array(
						$joinField => $joinID
					);
				$component = DataObject::get($class)->filter($filter)->first();
			}

			if(empty($component)) {
				$component = $this->model->$class->newObject();
				if($polymorphic) {
					$component->{$joinField.'ID'} = $this->ID;
					$component->{$joinField.'Class'} = $this->class;
				} else {
					$component->$joinField = $this->ID;
				}
			}
		} else {
			throw new Exception("DataObject->getComponent(): Could not find component '$componentName'.");
		}

		$this->components[$componentName] = $component;
		return $component;
	}

	/**
	 * Returns a one-to-many relation as a HasManyList
	 *
	 * @param string $componentName Name of the component
	 * @param string|null $filter Deprecated. A filter to be inserted into the WHERE clause
	 * @param string|null|array $sort Deprecated. A sort expression to be inserted into the ORDER BY clause. If omitted,
	 *                                the static field $default_sort on the component class will be used.
	 * @param string $join Deprecated, use leftJoin($table, $joinClause) instead
	 * @param string|null|array $limit Deprecated. A limit expression to be inserted into the LIMIT clause
	 *
	 * @return HasManyList The components of the one-to-many relationship.
	 */
	public function getComponents($componentName, $filter = null, $sort = null, $join = null, $limit = null) {
		$result = null;

		if(!$componentClass = $this->hasManyComponent($componentName)) {
			user_error("DataObject::getComponents(): Unknown 1-to-many component '$componentName'"
				. " on class '$this->class'", E_USER_ERROR);
		}

		if($join) {
			throw new \InvalidArgumentException(
				'The $join argument has been removed. Use leftJoin($table, $joinClause) instead.'
			);
		}

		if($filter !== null || $sort !== null || $limit !== null) {
			Deprecation::notice('4.0', 'The $filter, $sort and $limit parameters for DataObject::getComponents()
				have been deprecated. Please manipluate the returned list directly.', Deprecation::SCOPE_GLOBAL);
		}

		// If we haven't been written yet, we can't save these relations, so use a list that handles this case
		if(!$this->ID) {
			if(!isset($this->unsavedRelations[$componentName])) {
				$this->unsavedRelations[$componentName] =
					new UnsavedRelationList($this->class, $componentName, $componentClass);
			}
			return $this->unsavedRelations[$componentName];
		}

		// Determine type and nature of foreign relation
		$joinField = $this->getRemoteJoinField($componentName, 'has_many', $polymorphic);
		if($polymorphic) {
			$result = PolymorphicHasManyList::create($componentClass, $joinField, $this->class);
		} else {
			$result = HasManyList::create($componentClass, $joinField);
		}

		if($this->model) $result->setDataModel($this->model);

		return $result
			->forForeignID($this->ID)
			->where($filter)
			->limit($limit)
			->sort($sort);
	}

	/**
	 * @deprecated
	 */
	public function getComponentsQuery($componentName, $filter = "", $sort = "", $join = "", $limit = "") {
		Deprecation::notice('4.0', "Use getComponents to get a filtered DataList for an object's relation");
		return $this->getComponents($componentName, $filter, $sort, $join, $limit);
	}

	/**
	 * Find the foreign class of a relation on this DataObject, regardless of the relation type.
	 *
	 * @param $relationName Relation name.
	 * @return string Class name, or null if not found.
	 */
	public function getRelationClass($relationName) {
		// Go through all relationship configuration fields.
		$candidates = array_merge(
			($relations = Config::inst()->get($this->class, 'has_one')) ? $relations : array(),
			($relations = Config::inst()->get($this->class, 'has_many')) ? $relations : array(),
			($relations = Config::inst()->get($this->class, 'many_many')) ? $relations : array(),
			($relations = Config::inst()->get($this->class, 'belongs_many_many')) ? $relations : array(),
			($relations = Config::inst()->get($this->class, 'belongs_to')) ? $relations : array()
		);

		if (isset($candidates[$relationName])) {
			$remoteClass = $candidates[$relationName];

			// If dot notation is present, extract just the first part that contains the class.
			if(($fieldPos = strpos($remoteClass, '.'))!==false) {
				return substr($remoteClass, 0, $fieldPos);
			}

			// Otherwise just return the class
			return $remoteClass;
		}

		return null;
	}

	/**
	 * Tries to find the database key on another object that is used to store a
	 * relationship to this class. If no join field can be found it defaults to 'ParentID'.
	 *
	 * If the remote field is polymorphic then $polymorphic is set to true, and the return value
	 * is in the form 'Relation' instead of 'RelationID', referencing the composite DBField.
	 *
	 * @param string $component Name of the relation on the current object pointing to the
	 * remote object.
	 * @param string $type the join type - either 'has_many' or 'belongs_to'
	 * @param boolean $polymorphic Flag set to true if the remote join field is polymorphic.
	 * @return string
	 */
	public function getRemoteJoinField($component, $type = 'has_many', &$polymorphic = false) {
		// Extract relation from current object
		if($type === 'has_many') {
			$remoteClass = $this->hasManyComponent($component, false);
		} else {
			$remoteClass = $this->belongsToComponent($component, false);
		}

		if(empty($remoteClass)) {
			throw new Exception("Unknown $type component '$component' on class '$this->class'");
		}
		if(!ClassInfo::exists(strtok($remoteClass, '.'))) {
			throw new Exception(
				"Class '$remoteClass' not found, but used in $type component '$component' on class '$this->class'"
			);
		}

		// If presented with an explicit field name (using dot notation) then extract field name
		$remoteField = null;
		if(strpos($remoteClass, '.') !== false) {
			list($remoteClass, $remoteField) = explode('.', $remoteClass);
		}

		// Reference remote has_one to check against
		$remoteRelations = Config::inst()->get($remoteClass, 'has_one');

		// Without an explicit field name, attempt to match the first remote field
		// with the same type as the current class
		if(empty($remoteField)) {
			// look for remote has_one joins on this class or any parent classes
			$remoteRelationsMap = array_flip($remoteRelations);
			foreach(array_reverse(ClassInfo::ancestry($this)) as $class) {
				if(array_key_exists($class, $remoteRelationsMap)) {
					$remoteField = $remoteRelationsMap[$class];
					break;
				}
			}
		}

		// In case of an indeterminate remote field show an error
		if(empty($remoteField)) {
			$polymorphic = false;
			$message = "No has_one found on class '$remoteClass'";
			if($type == 'has_many') {
				// include a hint for has_many that is missing a has_one
				$message .= ", the has_many relation from '$this->class' to '$remoteClass'";
				$message .= " requires a has_one on '$remoteClass'";
			}
			throw new Exception($message);
		}

		// If given an explicit field name ensure the related class specifies this
		if(empty($remoteRelations[$remoteField])) {
			throw new Exception("Missing expected has_one named '$remoteField'
				on class '$remoteClass' referenced by $type named '$component'
				on class {$this->class}"
			);
		}

		// Inspect resulting found relation
		if($remoteRelations[$remoteField] === 'DataObject') {
			$polymorphic = true;
			return $remoteField; // Composite polymorphic field does not include 'ID' suffix
		} else {
			$polymorphic = false;
			return $remoteField . 'ID';
		}
	}

	/**
	 * Returns a many-to-many component, as a ManyManyList.
	 * @param string $componentName Name of the many-many component
	 * @return ManyManyList The set of components
	 *
	 * @todo Implement query-params
	 */
	public function getManyManyComponents($componentName, $filter = null, $sort = null, $join = null, $limit = null) {
		list($parentClass, $componentClass, $parentField, $componentField, $table)
			= $this->manyManyComponent($componentName);

		if($filter !== null || $sort !== null || $join !== null || $limit !== null) {
			Deprecation::notice('4.0', 'The $filter, $sort, $join and $limit parameters for
				DataObject::getManyManyComponents() have been deprecated.
				Please manipluate the returned list directly.', Deprecation::SCOPE_GLOBAL);
		}

		// If we haven't been written yet, we can't save these relations, so use a list that handles this case
		if(!$this->ID) {
			if(!isset($this->unsavedRelations[$componentName])) {
				$this->unsavedRelations[$componentName] =
					new UnsavedRelationList($parentClass, $componentName, $componentClass);
			}
			return $this->unsavedRelations[$componentName];
		}

		$extraFields = $this->manyManyExtraFieldsForComponent($componentName) ?: array();
		$result = ManyManyList::create($componentClass, $table, $componentField, $parentField, $extraFields);


		// Store component data in query meta-data
		$result = $result->alterDataQuery(function($query) use ($extraFields) {
			$query->setQueryParam('Component.ExtraFields', $extraFields);
		});
		
		if($this->model) $result->setDataModel($this->model);

		$this->extend('updateManyManyComponents', $result);

		// If this is called on a singleton, then we return an 'orphaned relation' that can have the
		// foreignID set elsewhere.
		return $result
			->forForeignID($this->ID)
			->where($filter)
			->sort($sort)
			->limit($limit);
	}

	/**
	 * @deprecated 4.0 Method has been replaced by hasOne() and hasOneComponent()
	 * @param string $component
	 * @return array|null
	 */
	public function has_one($component = null) {
		if($component) {
			Deprecation::notice('4.0', 'Please use hasOneComponent() instead');
			return $this->hasOneComponent($component);
		}

		Deprecation::notice('4.0', 'Please use hasOne() instead');
		return $this->hasOne();
	}

	/**
	 * Return the class of a one-to-one component.  If $component is null, return all of the one-to-one components and
	 * their classes. If the selected has_one is a polymorphic field then 'DataObject' will be returned for the type.
	 *
	 * @param string $component Deprecated - Name of component
	 * @return string|array The class of the one-to-one component, or an array of all one-to-one components and
	 * 							their classes.
	 */
	public function hasOne($component = null) {
		if($component) {
			Deprecation::notice(
				'4.0',
				'Please use DataObject::hasOneComponent() instead of passing a component name to hasOne()',
				Deprecation::SCOPE_GLOBAL
			);
			return $this->hasOneComponent($component);
		}

		return (array)Config::inst()->get($this->class, 'has_one', Config::INHERITED);
	}

	/**
	 * Return data for a specific has_one component.
	 * @param string $component
	 * @return string|null
	 */
	public function hasOneComponent($component) {
		$hasOnes = (array)Config::inst()->get($this->class, 'has_one', Config::INHERITED);

		if(isset($hasOnes[$component])) {
			return $hasOnes[$component];
		}
	}

	/**
	 * @deprecated 4.0 Method has been replaced by belongsTo() and belongsToComponent()
	 * @param string $component
	 * @param bool $classOnly
	 * @return array|null
	 */
	public function belongs_to($component = null, $classOnly = true) {
		if($component) {
			Deprecation::notice('4.0', 'Please use belongsToComponent() instead');
			return $this->belongsToComponent($component, $classOnly);
		}

		Deprecation::notice('4.0', 'Please use belongsTo() instead');
		return $this->belongsTo(null, $classOnly);
	}

	/**
	 * Returns the class of a remote belongs_to relationship. If no component is specified a map of all components and
	 * their class name will be returned.
	 *
	 * @param string $component - Name of component
	 * @param bool $classOnly If this is TRUE, than any has_many relationships in the form "ClassName.Field" will have
	 *        the field data stripped off. It defaults to TRUE.
	 * @return string|array
	 */
	public function belongsTo($component = null, $classOnly = true) {
		if($component) {
			Deprecation::notice(
				'4.0',
				'Please use DataObject::belongsToComponent() instead of passing a component name to belongsTo()',
				Deprecation::SCOPE_GLOBAL
			);
			return $this->belongsToComponent($component, $classOnly);
		}

		$belongsTo = (array)Config::inst()->get($this->class, 'belongs_to', Config::INHERITED);
		if($belongsTo && $classOnly) {
			return preg_replace('/(.+)?\..+/', '$1', $belongsTo);
		} else {
			return $belongsTo ? $belongsTo : array();
		}
	}

	/**
	 * Return data for a specific belongs_to component.
	 * @param string $component
	 * @param bool $classOnly If this is TRUE, than any has_many relationships in the form "ClassName.Field" will have
	 *        the field data stripped off. It defaults to TRUE.
	 * @return string|false
	 */
	public function belongsToComponent($component, $classOnly = true) {
		$belongsTo = (array)Config::inst()->get($this->class, 'belongs_to', Config::INHERITED);

		if($belongsTo && array_key_exists($component, $belongsTo)) {
			$belongsTo = $belongsTo[$component];
		} else {
			return false;
		}

		return ($classOnly) ? preg_replace('/(.+)?\..+/', '$1', $belongsTo) : $belongsTo;
	}

	/**
	 * Return all of the database fields defined in self::$db and all the parent classes.
	 * Doesn't include any fields specified by self::$has_one.  Use $this->hasOne() to get these fields
	 *
	 * @param string $fieldName Limit the output to a specific field name
	 * @return array The database fields
	 */
	public function db($fieldName = null) {
		$classes = ClassInfo::ancestry($this, true);

		// If we're looking for a specific field, we want to hit subclasses first as they may override field types
		if($fieldName) {
			$classes = array_reverse($classes);
		}

		$items = array();
		foreach($classes as $class) {
			if(isset(self::$_cache_db[$class])) {
				$dbItems = self::$_cache_db[$class];
			} else {
				$dbItems = (array) Config::inst()->get($class, 'db', Config::UNINHERITED);
				self::$_cache_db[$class] = $dbItems;
			}

			if($fieldName) {
				if(isset($dbItems[$fieldName])) {
					return $dbItems[$fieldName];
				}
			} else {
				$items = isset($items) ? array_merge((array) $items, $dbItems) : $dbItems;
			}
		}

		return $items;
	}

	/**
	 * @deprecated 4.0 Method has been replaced by hasMany() and hasManyComponent()
	 * @param string $component
	 * @param bool $classOnly
	 * @return array|null
	 */
	public function has_many($component = null, $classOnly = true) {
		if($component) {
			Deprecation::notice('4.0', 'Please use hasManyComponent() instead');
			return $this->hasManyComponent($component, $classOnly);
		}

		Deprecation::notice('4.0', 'Please use hasMany() instead');
		return $this->hasMany(null, $classOnly);
	}

	/**
	 * Gets the class of a one-to-many relationship. If no $component is specified then an array of all the one-to-many
	 * relationships and their classes will be returned.
	 *
	 * @param string $component Deprecated - Name of component
	 * @param bool $classOnly If this is TRUE, than any has_many relationships in the form "ClassName.Field" will have
	 *        the field data stripped off. It defaults to TRUE.
	 * @return string|array|false
	 */
	public function hasMany($component = null, $classOnly = true) {
		if($component) {
			Deprecation::notice(
				'4.0',
				'Please use DataObject::hasManyComponent() instead of passing a component name to hasMany()',
				Deprecation::SCOPE_GLOBAL
			);
			return $this->hasManyComponent($component, $classOnly);
		}

		$hasMany = (array)Config::inst()->get($this->class, 'has_many', Config::INHERITED);
		if($hasMany && $classOnly) {
			return preg_replace('/(.+)?\..+/', '$1', $hasMany);
		} else {
			return $hasMany ? $hasMany : array();
		}
	}

	/**
	 * Return data for a specific has_many component.
	 * @param string $component
	 * @param bool $classOnly If this is TRUE, than any has_many relationships in the form "ClassName.Field" will have
	 *        the field data stripped off. It defaults to TRUE.
	 * @return string|false
	 */
	public function hasManyComponent($component, $classOnly = true) {
		$hasMany = (array)Config::inst()->get($this->class, 'has_many', Config::INHERITED);

		if($hasMany && array_key_exists($component, $hasMany)) {
			$hasMany = $hasMany[$component];
		} else {
			return false;
		}

		return ($classOnly) ? preg_replace('/(.+)?\..+/', '$1', $hasMany) : $hasMany;
	}

	/**
	 * @deprecated 4.0 Method has been replaced by manyManyExtraFields() and
	 *                 manyManyExtraFieldsForComponent()
	 * @param string $component
	 * @return array
	 */
	public function many_many_extraFields($component = null) {
		if($component) {
			Deprecation::notice('4.0', 'Please use manyManyExtraFieldsForComponent() instead');
			return $this->manyManyExtraFieldsForComponent($component);
		}

		Deprecation::notice('4.0', 'Please use manyManyExtraFields() instead');
		return $this->manyManyExtraFields();
	}

	/**
	 * Return the many-to-many extra fields specification.
	 *
	 * If you don't specify a component name, it returns all
	 * extra fields for all components available.
	 *
	 * @param string $component Deprecated - Name of component
	 * @return array|null
	 */
	public function manyManyExtraFields($component = null) {
		if($component) {
			Deprecation::notice(
				'4.0',
				'Please use DataObject::manyManyExtraFieldsForComponent() instead of passing a component name
					to manyManyExtraFields()',
				Deprecation::SCOPE_GLOBAL
			);
			return $this->manyManyExtraFieldsForComponent($component);
		}

		return Config::inst()->get($this->class, 'many_many_extraFields', Config::INHERITED);
	}

	/**
	 * Return the many-to-many extra fields specification for a specific component.
	 * @param string $component
	 * @return array|null
	 */
	public function manyManyExtraFieldsForComponent($component) {
		// Get all many_many_extraFields defined in this class or parent classes
		$extraFields = (array)Config::inst()->get($this->class, 'many_many_extraFields', Config::INHERITED);
		// Extra fields are immediately available
		if(isset($extraFields[$component])) {
			return $extraFields[$component];
		}

		// Check this class' belongs_many_manys to see if any of their reverse associations contain extra fields
		$manyMany = (array)Config::inst()->get($this->class, 'belongs_many_many', Config::INHERITED);
		$candidate = (isset($manyMany[$component])) ? $manyMany[$component] : null;
		if($candidate) {
			$relationName = null;
			// Extract class and relation name from dot-notation
			if(strpos($candidate, '.') !== false) {
				list($candidate, $relationName) = explode('.', $candidate, 2);
			}

			// If we've not already found the relation name from dot notation, we need to find a relation that points
			// back to this class. As there's no dot-notation, there can only be one relation pointing to this class,
			// so it's safe to assume that it's the correct one
			if(!$relationName) {
				$candidateManyManys = (array)Config::inst()->get($candidate, 'many_many', Config::UNINHERITED);

				foreach($candidateManyManys as $relation => $relatedClass) {
					if (is_a($this, $relatedClass)) {
						$relationName = $relation;
					}
				}
			}

			// If we've found a matching relation on the target class, see if we can find extra fields for it
			$extraFields = (array)Config::inst()->get($candidate, 'many_many_extraFields', Config::UNINHERITED);
			if(isset($extraFields[$relationName])) {
				return $extraFields[$relationName];
			}
		}

		return isset($items) ? $items : null;
	}

	/**
	 * @deprecated 4.0 Method has been renamed to manyMany()
	 * @param string $component
	 * @return array|null
	 */
	public function many_many($component = null) {
		if($component) {
			Deprecation::notice('4.0', 'Please use manyManyComponent() instead');
			return $this->manyManyComponent($component);
		}

		Deprecation::notice('4.0', 'Please use manyMany() instead');
		return $this->manyMany();
	}

	/**
	 * Return information about a many-to-many component.
	 * The return value is an array of (parentclass, childclass).  If $component is null, then all many-many
	 * components are returned.
	 *
	 * @see DataObject::manyManyComponent()
	 * @param string $component Deprecated - Name of component
	 * @return array|null An array of (parentclass, childclass), or an array of all many-many components
	 */
	public function manyMany($component = null) {
		if($component) {
			Deprecation::notice(
				'4.0',
				'Please use DataObject::manyManyComponent() instead of passing a component name to manyMany()',
				Deprecation::SCOPE_GLOBAL
			);
			return $this->manyManyComponent($component);
		}

		$manyManys = (array)Config::inst()->get($this->class, 'many_many', Config::INHERITED);
		$belongsManyManys = (array)Config::inst()->get($this->class, 'belongs_many_many', Config::INHERITED);

		$items = array_merge($manyManys, $belongsManyManys);
		return $items;
	}

	/**
	 * Return information about a specific many_many component. Returns a numeric array of:
	 * array(
	 * 	<classname>,		The class that relation is defined in e.g. "Product"
	 * 	<candidateName>,	The target class of the relation e.g. "Category"
	 * 	<parentField>,		The field name pointing to <classname>'s table e.g. "ProductID"
	 * 	<childField>,		The field name pointing to <candidatename>'s table e.g. "CategoryID"
	 * 	<joinTable>			The join table between the two classes e.g. "Product_Categories"
	 * )
	 * @param string $component The component name
	 * @return array|null
	 */
	public function manyManyComponent($component) {
		$classes = $this->getClassAncestry();
		foreach($classes as $class) {
			$manyMany = Config::inst()->get($class, 'many_many', Config::UNINHERITED);
			// Check if the component is defined in many_many on this class
			$candidate = (isset($manyMany[$component])) ? $manyMany[$component] : null;
			if($candidate) {
				$parentField = $class . "ID";
				$childField = ($class == $candidate) ? "ChildID" : $candidate . "ID";
				return array($class, $candidate, $parentField, $childField, "{$class}_$component");
			}

			// Check if the component is defined in belongs_many_many on this class
			$belongsManyMany = Config::inst()->get($class, 'belongs_many_many', Config::UNINHERITED);
			$candidate = (isset($belongsManyMany[$component])) ? $belongsManyMany[$component] : null;
			if($candidate) {
				// Extract class and relation name from dot-notation
				if(strpos($candidate, '.') !== false) {
					list($candidate, $relationName) = explode('.', $candidate, 2);
				}

				$childField = $candidate . "ID";

				// We need to find the inverse component name
				$otherManyMany = Config::inst()->get($candidate, 'many_many', Config::UNINHERITED);
				if(!$otherManyMany) {
					throw new LogicException("Inverse component of $candidate not found ({$this->class})");
				}

				// If we've got a relation name (extracted from dot-notation), we can already work out
				// the join table and candidate class name...
				if(isset($relationName) && isset($otherManyMany[$relationName])) {
					$candidateClass = $otherManyMany[$relationName];
					$joinTable = "{$candidate}_{$relationName}";
				} else {
					// ... otherwise, we need to loop over the many_manys and find a relation that
					// matches up to this class
					foreach($otherManyMany as $inverseComponentName => $candidateClass) {
						if($candidateClass == $class || is_subclass_of($class, $candidateClass)) {
							$joinTable = "{$candidate}_{$inverseComponentName}";
							break;
						}
					}
				}

				// If we could work out the join table, we've got all the info we need
				if(isset($joinTable)) {
					$parentField = ($class == $candidate) ? "ChildID" : $candidateClass . "ID";
					return array($class, $candidate, $parentField, $childField, $joinTable);
				}

				throw new LogicException("Orphaned \$belongs_many_many value for $this->class.$component");
			}
		}
	}

	/**
	 * This returns an array (if it exists) describing the database extensions that are required, or false if none
	 *
	 * This is experimental, and is currently only a Postgres-specific enhancement.
	 *
	 * @return array or false
	 */
	public function database_extensions($class){
		$extensions = Config::inst()->get($class, 'database_extensions', Config::UNINHERITED);

		if($extensions)
			return $extensions;
		else
			return false;
	}

	/**
	 * Generates a SearchContext to be used for building and processing
	 * a generic search form for properties on this object.
	 *
	 * @return SearchContext
	 */
	public function getDefaultSearchContext() {
		return new SearchContext(
			$this->class,
			$this->scaffoldSearchFields(),
			$this->defaultSearchFilters()
		);
	}

	/**
	 * Determine which properties on the DataObject are
	 * searchable, and map them to their default {@link FormField}
	 * representations. Used for scaffolding a searchform for {@link ModelAdmin}.
	 *
	 * Some additional logic is included for switching field labels, based on
	 * how generic or specific the field type is.
	 *
	 * Used by {@link SearchContext}.
	 *
	 * @param array $_params
	 *   'fieldClasses': Associative array of field names as keys and FormField classes as values
	 *   'restrictFields': Numeric array of a field name whitelist
	 * @return FieldList
	 */
	public function scaffoldSearchFields($_params = null) {
		$params = array_merge(
			array(
				'fieldClasses' => false,
				'restrictFields' => false
			),
			(array)$_params
		);
		$fields = new FieldList();
		foreach($this->searchableFields() as $fieldName => $spec) {
			if($params['restrictFields'] && !in_array($fieldName, $params['restrictFields'])) continue;

			// If a custom fieldclass is provided as a string, use it
			if($params['fieldClasses'] && isset($params['fieldClasses'][$fieldName])) {
				$fieldClass = $params['fieldClasses'][$fieldName];
				$field = new $fieldClass($fieldName);
			// If we explicitly set a field, then construct that
			} else if(isset($spec['field'])) {
				// If it's a string, use it as a class name and construct
				if(is_string($spec['field'])) {
					$fieldClass = $spec['field'];
					$field = new $fieldClass($fieldName);

				// If it's a FormField object, then just use that object directly.
				} else if($spec['field'] instanceof FormField) {
					$field = $spec['field'];

				// Otherwise we have a bug
				} else {
					user_error("Bad value for searchable_fields, 'field' value: "
						. var_export($spec['field'], true), E_USER_WARNING);
				}

			// Otherwise, use the database field's scaffolder
			} else {
				$field = $this->relObject($fieldName)->scaffoldSearchField();
			}

			if (strstr($fieldName, '.')) {
				$field->setName(str_replace('.', '__', $fieldName));
			}
			$field->setTitle($spec['title']);

			$fields->push($field);
		}
		return $fields;
	}

	/**
	 * Scaffold a simple edit form for all properties on this dataobject,
	 * based on default {@link FormField} mapping in {@link DBField::scaffoldFormField()}.
	 * Field labels/titles will be auto generated from {@link DataObject::fieldLabels()}.
	 *
	 * @uses FormScaffolder
	 *
	 * @param array $_params Associative array passing through properties to {@link FormScaffolder}.
	 * @return FieldList
	 */
	public function scaffoldFormFields($_params = null) {
		$params = array_merge(
			array(
				'tabbed' => false,
				'includeRelations' => false,
				'restrictFields' => false,
				'fieldClasses' => false,
				'ajaxSafe' => false
			),
			(array)$_params
		);

		$fs = new FormScaffolder($this);
		$fs->tabbed = $params['tabbed'];
		$fs->includeRelations = $params['includeRelations'];
		$fs->restrictFields = $params['restrictFields'];
		$fs->fieldClasses = $params['fieldClasses'];
		$fs->ajaxSafe = $params['ajaxSafe'];

		return $fs->getFieldList();
	}

	/**
	 * Allows user code to hook into DataObject::getCMSFields prior to updateCMSFields
	 * being called on extensions
	 *
	 * @param callable $callback The callback to execute
	 */
	protected function beforeUpdateCMSFields($callback) {
		$this->beforeExtending('updateCMSFields', $callback);
	}

	/**
	 * Centerpiece of every data administration interface in Silverstripe,
	 * which returns a {@link FieldList} suitable for a {@link Form} object.
	 * If not overloaded, we're using {@link scaffoldFormFields()} to automatically
	 * generate this set. To customize, overload this method in a subclass
	 * or extended onto it by using {@link DataExtension->updateCMSFields()}.
	 *
	 * <code>
	 * class MyCustomClass extends DataObject {
	 *  static $db = array('CustomProperty'=>'Boolean');
	 *
	 *  function getCMSFields() {
	 *    $fields = parent::getCMSFields();
	 *    $fields->addFieldToTab('Root.Content',new CheckboxField('CustomProperty'));
	 *    return $fields;
	 *  }
	 * }
	 * </code>
	 *
	 * @see Good example of complex FormField building: SiteTree::getCMSFields()
	 *
	 * @return FieldList Returns a TabSet for usage within the CMS - don't use for frontend forms.
	 */
	public function getCMSFields() {
		$tabbedFields = $this->scaffoldFormFields(array(
			// Don't allow has_many/many_many relationship editing before the record is first saved
			'includeRelations' => ($this->ID > 0),
			'tabbed' => true,
			'ajaxSafe' => true
		));

		$this->extend('updateCMSFields', $tabbedFields);

		return $tabbedFields;
	}

	/**
	 * need to be overload by solid dataobject, so that the customised actions of that dataobject,
	 * including that dataobject's extensions customised actions could be added to the EditForm.
	 *
	 * @return an Empty FieldList(); need to be overload by solid subclass
	 */
	public function getCMSActions() {
		$actions = new FieldList();
		$this->extend('updateCMSActions', $actions);
		return $actions;
	}


	/**
	 * Used for simple frontend forms without relation editing
	 * or {@link TabSet} behaviour. Uses {@link scaffoldFormFields()}
	 * by default. To customize, either overload this method in your
	 * subclass, or extend it by {@link DataExtension->updateFrontEndFields()}.
	 *
	 * @todo Decide on naming for "website|frontend|site|page" and stick with it in the API
	 *
	 * @param array $params See {@link scaffoldFormFields()}
	 * @return FieldList Always returns a simple field collection without TabSet.
	 */
	public function getFrontEndFields($params = null) {
		$untabbedFields = $this->scaffoldFormFields($params);
		$this->extend('updateFrontEndFields', $untabbedFields);

		return $untabbedFields;
	}

	/**
	 * Gets the value of a field.
	 * Called by {@link __get()} and any getFieldName() methods you might create.
	 *
	 * @param string $field The name of the field
	 *
	 * @return mixed The field value
	 */
	public function getField($field) {
		// If we already have an object in $this->record, then we should just return that
		if(isset($this->record[$field]) && is_object($this->record[$field]))  return $this->record[$field];

		// Do we have a field that needs to be lazy loaded?
		if(isset($this->record[$field.'_Lazy'])) {
			$tableClass = $this->record[$field.'_Lazy'];
			$this->loadLazyFields($tableClass);
		}

		// Otherwise, we need to determine if this is a complex field
		if(self::is_composite_field($this->class, $field)) {
			$helper = $this->castingHelper($field);
			$fieldObj = Object::create_from_string($helper, $field);

			$compositeFields = $fieldObj->compositeDatabaseFields();
			foreach ($compositeFields as $compositeName => $compositeType) {
				if(isset($this->record[$field.$compositeName.'_Lazy'])) {
					$tableClass = $this->record[$field.$compositeName.'_Lazy'];
					$this->loadLazyFields($tableClass);
				}
			}

			// write value only if either the field value exists,
			// or a valid record has been loaded from the database
			$value = (isset($this->record[$field])) ? $this->record[$field] : null;
			if($value || $this->exists()) $fieldObj->setValue($value, $this->record, false);

			$this->record[$field] = $fieldObj;

			return $this->record[$field];
		}

		return isset($this->record[$field]) ? $this->record[$field] : null;
	}

	/**
	 * Loads all the stub fields that an initial lazy load didn't load fully.
	 *
	 * @param string $tableClass Base table to load the values from. Others are joined as required.
	 * Not specifying a tableClass will load all lazy fields from all tables.
	 * @return bool Flag if lazy loading succeeded
	 */
	protected function loadLazyFields($tableClass = null) {
		if(!$this->isInDB() || !is_numeric($this->ID)) {
			return false;
		}

		if (!$tableClass) {
			$loaded = array();

			foreach ($this->record as $key => $value) {
				if (strlen($key) > 5 && substr($key, -5) == '_Lazy' && !array_key_exists($value, $loaded)) {
					$this->loadLazyFields($value);
					$loaded[$value] = $value;
				}
			}

			return false;
		}

		$dataQuery = new DataQuery($tableClass);

		// Reset query parameter context to that of this DataObject
		if($params = $this->getSourceQueryParams()) {
			foreach($params as $key => $value) $dataQuery->setQueryParam($key, $value);
		}

		// Limit query to the current record, unless it has the Versioned extension,
		// in which case it requires special handling through augmentLoadLazyFields()
		if(!$this->hasExtension('Versioned')) {
			$dataQuery->where("\"$tableClass\".\"ID\" = {$this->record['ID']}")->limit(1);
		}

		$columns = array();

		// Add SQL for fields, both simple & multi-value
		// TODO: This is copy & pasted from buildSQL(), it could be moved into a method
		$databaseFields = self::database_fields($tableClass, false);
		if($databaseFields) foreach($databaseFields as $k => $v) {
			if(!isset($this->record[$k]) || $this->record[$k] === null) {
				$columns[] = $k;
			}
		}

		if ($columns) {
			$query = $dataQuery->query();
			$this->extend('augmentLoadLazyFields', $query, $dataQuery, $this);
			$this->extend('augmentSQL', $query, $dataQuery);

			$dataQuery->setQueriedColumns($columns);
			$newData = $dataQuery->execute()->record();

			// Load the data into record
			if($newData) {
				foreach($newData as $k => $v) {
					if (in_array($k, $columns)) {
						$this->record[$k] = $v;
						$this->original[$k] = $v;
						unset($this->record[$k . '_Lazy']);
					}
				}

			// No data means that the query returned nothing; assign 'null' to all the requested fields
			} else {
				foreach($columns as $k) {
					$this->record[$k] = null;
					$this->original[$k] = null;
					unset($this->record[$k . '_Lazy']);
				}
			}
		}
		return true;
	}

	/**
	 * Return the fields that have changed.
	 *
	 * The change level affects what the functions defines as "changed":
	 * - Level CHANGE_STRICT (integer 1) will return strict changes, even !== ones.
	 * - Level CHANGE_VALUE (integer 2) is more lenient, it will only return real data changes,
	 *   for example a change from 0 to null would not be included.
	 *
	 * Example return:
	 * <code>
	 * array(
	 *   'Title' = array('before' => 'Home', 'after' => 'Home-Changed', 'level' => DataObject::CHANGE_VALUE)
	 * )
	 * </code>
	 *
	 * @param boolean $databaseFieldsOnly Get only database fields that have changed
	 * @param int $changeLevel The strictness of what is defined as change. Defaults to strict
	 * @return array
	 */
	public function getChangedFields($databaseFieldsOnly = false, $changeLevel = self::CHANGE_STRICT) {
		$changedFields = array();

		// Update the changed array with references to changed obj-fields
		foreach($this->record as $k => $v) {
			if(is_object($v) && method_exists($v, 'isChanged') && $v->isChanged()) {
				$this->changed[$k] = self::CHANGE_VALUE;
			}
		}

		if($databaseFieldsOnly) {
			// Merge all DB fields together
			$inheritedFields = $this->inheritedDatabaseFields();
			$compositeFields = static::composite_fields(get_class($this));
			$fixedFields = $this->config()->fixed_fields;
			$databaseFields = array_merge(
				$inheritedFields,
				$fixedFields,
				$compositeFields
			);
			$fields = array_intersect_key((array)$this->changed, $databaseFields);
		} else {
			$fields = $this->changed;
		}

		// Filter the list to those of a certain change level
		if($changeLevel > self::CHANGE_STRICT) {
			if($fields) foreach($fields as $name => $level) {
				if($level < $changeLevel) {
					unset($fields[$name]);
				}
			}
		}

		if($fields) foreach($fields as $name => $level) {
			$changedFields[$name] = array(
				'before' => array_key_exists($name, $this->original) ? $this->original[$name] : null,
				'after' => array_key_exists($name, $this->record) ? $this->record[$name] : null,
				'level' => $level
			);
		}

		return $changedFields;
	}

	/**
	 * Uses {@link getChangedFields()} to determine if fields have been changed
	 * since loading them from the database.
	 *
	 * @param string $fieldName Name of the database field to check, will check for any if not given
	 * @param int $changeLevel See {@link getChangedFields()}
	 * @return boolean
	 */
	public function isChanged($fieldName = null, $changeLevel = self::CHANGE_STRICT) {
		if (!$fieldName) {
			// Limit "any changes" to db fields only
			$changed = $this->getChangedFields(true, $changeLevel);
			return !empty($changed);
		} else {
			// Given a field name, check all fields
			$changed = $this->getChangedFields(false, $changeLevel);
			return array_key_exists($fieldName, $changed);
		}
	}

	/**
	 * Set the value of the field
	 * Called by {@link __set()} and any setFieldName() methods you might create.
	 *
	 * @param string $fieldName Name of the field
	 * @param mixed $val New field value
	 * @return DataObject $this
	 */
	public function setField($fieldName, $val) {
		//if it's a has_one component, destroy the cache
		if (substr($fieldName, -2) == 'ID') {
			unset($this->components[substr($fieldName, 0, -2)]);
		}
		// Situation 1: Passing an DBField
		if($val instanceof DBField) {
			$val->Name = $fieldName;

			// If we've just lazy-loaded the column, then we need to populate the $original array by
			// called getField(). Too much overhead? Could this be done by a quicker method? Maybe only
			// on a call to getChanged()?
			$this->getField($fieldName);

			$this->record[$fieldName] = $val;
		// Situation 2: Passing a literal or non-DBField object
		} else {
			// If this is a proper database field, we shouldn't be getting non-DBField objects
			if(is_object($val) && $this->db($fieldName)) {
				user_error('DataObject::setField: passed an object that is not a DBField', E_USER_WARNING);
			}

			// if a field is not existing or has strictly changed
			if(!isset($this->record[$fieldName]) || $this->record[$fieldName] !== $val) {
				// TODO Add check for php-level defaults which are not set in the db
				// TODO Add check for hidden input-fields (readonly) which are not set in the db
				// At the very least, the type has changed
				$this->changed[$fieldName] = self::CHANGE_STRICT;

				if((!isset($this->record[$fieldName]) && $val) || (isset($this->record[$fieldName])
						&& $this->record[$fieldName] != $val)) {

					// Value has changed as well, not just the type
					$this->changed[$fieldName] = self::CHANGE_VALUE;
				}

				// If we've just lazy-loaded the column, then we need to populate the $original array by
				// called getField(). Too much overhead? Could this be done by a quicker method? Maybe only
				// on a call to getChanged()?
				$this->getField($fieldName);

				// Value is always saved back when strict check succeeds.
				$this->record[$fieldName] = $val;
			}
		}
		return $this;
	}

	/**
	 * Set the value of the field, using a casting object.
	 * This is useful when you aren't sure that a date is in SQL format, for example.
	 * setCastedField() can also be used, by forms, to set related data.  For example, uploaded images
	 * can be saved into the Image table.
	 *
	 * @param string $fieldName Name of the field
	 * @param mixed $value New field value
	 * @return DataObject $this
	 */
	public function setCastedField($fieldName, $val) {
		if(!$fieldName) {
			user_error("DataObject::setCastedField: Called without a fieldName", E_USER_ERROR);
		}
		$castingHelper = $this->castingHelper($fieldName);
		if($castingHelper) {
			$fieldObj = Object::create_from_string($castingHelper, $fieldName);
			$fieldObj->setValue($val);
			$fieldObj->saveInto($this);
		} else {
			$this->$fieldName = $val;
		}
		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function castingHelper($field) {
		if ($fieldSpec = $this->db($field)) {
			return $fieldSpec;
		}

		// many_many_extraFields aren't presented by db(), so we check if the source query params
		// provide us with meta-data for a many_many relation we can inspect for extra fields.
		$queryParams = $this->getSourceQueryParams();
		if (!empty($queryParams['Component.ExtraFields'])) {
			$extraFields = $queryParams['Component.ExtraFields'];

			if (isset($extraFields[$field])) {
				return $extraFields[$field];
			}
		}

		return parent::castingHelper($field);
	}

	/**
	 * Returns true if the given field exists in a database column on any of
	 * the objects tables and optionally look up a dynamic getter with
	 * get<fieldName>().
	 *
	 * @param string $field Name of the field
	 * @return boolean True if the given field exists
	 */
	public function hasField($field) {
		return (
			array_key_exists($field, $this->record)
			|| $this->db($field)
			|| (substr($field,-2) == 'ID') && $this->hasOneComponent(substr($field,0, -2))
			|| $this->hasMethod("get{$field}")
		);
	}

	/**
	 * Returns true if the given field exists as a database column
	 *
	 * @param string $field Name of the field
	 *
	 * @return boolean
	 */
	public function hasDatabaseField($field) {
		if(isset(self::$fixed_fields[$field])) return true;

		return array_key_exists($field, $this->inheritedDatabaseFields());
	}

	/**
	 * Returns the field type of the given field, if it belongs to this class, and not a parent.
	 * Note that the field type will not include constructor arguments in round brackets, only the classname.
	 *
	 * @param string $field Name of the field
	 * @return string The field type of the given field
	 */
	public function hasOwnTableDatabaseField($field) {
		return self::has_own_table_database_field($this->class, $field);
	}

	/**
	 * Returns the field type of the given field, if it belongs to this class, and not a parent.
	 * Note that the field type will not include constructor arguments in round brackets, only the classname.
	 *
	 * @param string $class Class name to check
	 * @param string $field Name of the field
	 * @return string The field type of the given field
	 */
	public static function has_own_table_database_field($class, $field) {
		// Since database_fields omits 'ID'
		if($field == "ID") return "Int";

		$fieldMap = self::database_fields($class, false);

		// Remove string-based "constructor-arguments" from the DBField definition
		if(isset($fieldMap[$field])) {
			$spec = $fieldMap[$field];
			if(is_string($spec)) return strtok($spec,'(');
			else return $spec['type'];
		}
	}

	/**
	 * Returns true if given class has its own table. Uses the rules for whether the table should exist rather than
	 * actually looking in the database.
	 *
	 * @param string $dataClass
	 * @return bool
	 */
	public static function has_own_table($dataClass) {
		if(!is_subclass_of($dataClass,'DataObject')) return false;

		$dataClass = ClassInfo::class_name($dataClass);
		if(!isset(DataObject::$cache_has_own_table[$dataClass])) {
			if(get_parent_class($dataClass) == 'DataObject') {
				DataObject::$cache_has_own_table[$dataClass] = true;
			} else {
				DataObject::$cache_has_own_table[$dataClass]
					= Config::inst()->get($dataClass, 'db', Config::UNINHERITED)
					|| Config::inst()->get($dataClass, 'has_one', Config::UNINHERITED);
			}
		}
		return DataObject::$cache_has_own_table[$dataClass];
	}

	/**
	 * Returns true if the member is allowed to do the given action.
	 * See {@link extendedCan()} for a more versatile tri-state permission control.
	 *
	 * @param string $perm The permission to be checked, such as 'View'.
	 * @param Member $member The member whose permissions need checking.  Defaults to the currently logged
	 * in user.
	 *
	 * @return boolean True if the the member is allowed to do the given action
	 */
	public function can($perm, $member = null) {
		if(!isset($member)) {
			$member = Member::currentUser();
		}
		if(Permission::checkMember($member, "ADMIN")) return true;

		if($this->manyManyComponent('Can' . $perm)) {
			if($this->ParentID && $this->SecurityType == 'Inherit') {
				if(!($p = $this->Parent)) {
					return false;
				}
				return $this->Parent->can($perm, $member);

			} else {
				$permissionCache = $this->uninherited('permissionCache');
				$memberID = $member ? $member->ID : 'none';

				if(!isset($permissionCache[$memberID][$perm])) {
					if($member->ID) {
						$groups = $member->Groups();
					}

					$groupList = implode(', ', $groups->column("ID"));

					// TODO Fix relation table hardcoding
					$query = new SQLQuery(
						"\"Page_Can$perm\".PageID",
					array("\"Page_Can$perm\""),
						"GroupID IN ($groupList)");

					$permissionCache[$memberID][$perm] = $query->execute()->column();

					if($perm == "View") {
						// TODO Fix relation table hardcoding
						$query = new SQLQuery("\"SiteTree\".\"ID\"", array(
							"\"SiteTree\"",
							"LEFT JOIN \"Page_CanView\" ON \"Page_CanView\".\"PageID\" = \"SiteTree\".\"ID\""
							), "\"Page_CanView\".\"PageID\" IS NULL");

							$unsecuredPages = $query->execute()->column();
							if($permissionCache[$memberID][$perm]) {
								$permissionCache[$memberID][$perm]
									= array_merge($permissionCache[$memberID][$perm], $unsecuredPages);
							} else {
								$permissionCache[$memberID][$perm] = $unsecuredPages;
							}
					}

					Config::inst()->update($this->class, 'permissionCache', $permissionCache);
				}

				if($permissionCache[$memberID][$perm]) {
					return in_array($this->ID, $permissionCache[$memberID][$perm]);
				}
			}
		} else {
			return parent::can($perm, $member);
		}
	}

	/**
	 * Process tri-state responses from permission-alterting extensions.  The extensions are
	 * expected to return one of three values:
	 *
	 *  - false: Disallow this permission, regardless of what other extensions say
	 *  - true: Allow this permission, as long as no other extensions return false
	 *  - NULL: Don't affect the outcome
	 *
	 * This method itself returns a tri-state value, and is designed to be used like this:
	 *
	 * <code>
	 * $extended = $this->extendedCan('canDoSomething', $member);
	 * if($extended !== null) return $extended;
	 * else return $normalValue;
	 * </code>
	 *
	 * @param String $methodName Method on the same object, e.g. {@link canEdit()}
	 * @param Member|int $member
	 * @return boolean|null
	 */
	public function extendedCan($methodName, $member) {
		$results = $this->extend($methodName, $member);
		if($results && is_array($results)) {
			// Remove NULLs
			$results = array_filter($results, function($v) {return !is_null($v);});
			// If there are any non-NULL responses, then return the lowest one of them.
			// If any explicitly deny the permission, then we don't get access
			if($results) return min($results);
		}
		return null;
	}

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canView($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		return Permission::check('ADMIN', 'any', $member);
	}

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canEdit($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		return Permission::check('ADMIN', 'any', $member);
	}

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canDelete($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		return Permission::check('ADMIN', 'any', $member);
	}

	/**
	 * @todo Should canCreate be a static method?
	 *
	 * @param Member $member
	 * @return boolean
	 */
	public function canCreate($member = null) {
		$extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
		return Permission::check('ADMIN', 'any', $member);
	}

	/**
	 * Debugging used by Debug::show()
	 *
	 * @return string HTML data representing this object
	 */
	public function debug() {
		$val = "<h3>Database record: $this->class</h3>\n<ul>\n";
		if($this->record) foreach($this->record as $fieldName => $fieldVal) {
			$val .= "\t<li>$fieldName: " . Debug::text($fieldVal) . "</li>\n";
		}
		$val .= "</ul>\n";
		return $val;
	}

	/**
	 * Return the DBField object that represents the given field.
	 * This works similarly to obj() with 2 key differences:
	 *   - it still returns an object even when the field has no value.
	 *   - it only matches fields and not methods
	 *   - it matches foreign keys generated by has_one relationships, eg, "ParentID"
	 *
	 * @param string $fieldName Name of the field
	 * @return DBField The field as a DBField object
	 */
	public function dbObject($fieldName) {
		// If we have a CompositeDBField object in $this->record, then return that
		if(isset($this->record[$fieldName]) && is_object($this->record[$fieldName])) {
			return $this->record[$fieldName];

		// Special case for ID field
		} else if($fieldName == 'ID') {
			return new PrimaryKey($fieldName, $this);

		// Special case for ClassName
		} else if($fieldName == 'ClassName') {
			$val = get_class($this);
			return DBField::create_field('Varchar', $val, $fieldName);

		} else if(array_key_exists($fieldName, self::$fixed_fields)) {
			return DBField::create_field(self::$fixed_fields[$fieldName], $this->$fieldName, $fieldName);

		// General casting information for items in $db
		} else if($helper = $this->db($fieldName)) {
			$obj = Object::create_from_string($helper, $fieldName);
			$obj->setValue($this->$fieldName, $this->record, false);
			return $obj;

		// Special case for has_one relationships
		} else if(preg_match('/ID$/', $fieldName) && $this->hasOneComponent(substr($fieldName,0,-2))) {
			$val = $this->$fieldName;
			return DBField::create_field('ForeignKey', $val, $fieldName, $this);

		// has_one for polymorphic relations do not end in ID
		} else if(($type = $this->hasOneComponent($fieldName)) && ($type === 'DataObject')) {
			$val = $this->$fieldName();
			return DBField::create_field('PolymorphicForeignKey', $val, $fieldName, $this);

		}
	}

	/**
	 * Traverses to a DBField referenced by relationships between data objects.
	 *
	 * The path to the related field is specified with dot separated syntax
	 * (eg: Parent.Child.Child.FieldName).
	 *
	 * @param string $fieldPath
	 *
	 * @return mixed DBField of the field on the object or a DataList instance.
	 */
	public function relObject($fieldPath) {
		$object = null;

		if(strpos($fieldPath, '.') !== false) {
			$parts = explode('.', $fieldPath);
			$fieldName = array_pop($parts);

			// Traverse dot syntax
			$component = $this;

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

			$object = $component->dbObject($fieldName);

		} else {
			$object = $this->dbObject($fieldPath);
		}

		return $object;
	}

	/**
	 * Traverses to a field referenced by relationships between data objects, returning the value
	 * The path to the related field is specified with dot separated syntax (eg: Parent.Child.Child.FieldName)
	 *
	 * @param $fieldPath string
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
		if($component->hasMethod($fieldName)) {
			return $component->$fieldName();
		}
		return $component->$fieldName;
	}

	/**
	 * Temporary hack to return an association name, based on class, to get around the mangle
	 * of having to deal with reverse lookup of relationships to determine autogenerated foreign keys.
	 *
	 * @return String
	 */
	public function getReverseAssociation($className) {
		if (is_array($this->manyMany())) {
			$many_many = array_flip($this->manyMany());
			if (array_key_exists($className, $many_many)) return $many_many[$className];
		}
		if (is_array($this->hasMany())) {
			$has_many = array_flip($this->hasMany());
			if (array_key_exists($className, $has_many)) return $has_many[$className];
		}
		if (is_array($this->hasOne())) {
			$has_one = array_flip($this->hasOne());
			if (array_key_exists($className, $has_one)) return $has_one[$className];
		}

		return false;
	}

	/**
	 * Return all objects matching the filter
	 * sub-classes are automatically selected and included
	 *
	 * @param string $callerClass The class of objects to be returned
	 * @param string|array $filter A filter to be inserted into the WHERE clause.
	 * Supports parameterised queries. See SQLQuery::addWhere() for syntax examples.
	 * @param string|array $sort A sort expression to be inserted into the ORDER
	 * BY clause.  If omitted, self::$default_sort will be used.
	 * @param string $join Deprecated 3.0 Join clause. Use leftJoin($table, $joinClause) instead.
	 * @param string|array $limit A limit expression to be inserted into the LIMIT clause.
	 * @param string $containerClass The container class to return the results in.
	 *
	 * @todo $containerClass is Ignored, why?
	 *
	 * @return DataList The objects matching the filter, in the class specified by $containerClass
	 */
	public static function get($callerClass = null, $filter = "", $sort = "", $join = "", $limit = null,
			$containerClass = 'DataList') {

		if($callerClass == null) {
			$callerClass = get_called_class();
			if($callerClass == 'DataObject') {
				throw new \InvalidArgumentException('Call <classname>::get() instead of DataObject::get()');
			}

			if($filter || $sort || $join || $limit || ($containerClass != 'DataList')) {
				throw new \InvalidArgumentException('If calling <classname>::get() then you shouldn\'t pass any other'
					. ' arguments');
			}

			$result = DataList::create(get_called_class());
			$result->setDataModel(DataModel::inst());
			return $result;
		}

		if($join) {
			throw new \InvalidArgumentException(
				'The $join argument has been removed. Use leftJoin($table, $joinClause) instead.'
			);
		}

		$result = DataList::create($callerClass)->where($filter)->sort($sort);

		if($limit && strpos($limit, ',') !== false) {
			$limitArguments = explode(',', $limit);
			$result = $result->limit($limitArguments[1],$limitArguments[0]);
		} elseif($limit) {
			$result = $result->limit($limit);
		}

		$result->setDataModel(DataModel::inst());
		return $result;
	}


	/**
	 * @deprecated
	 */
	public function Aggregate($class = null) {
		Deprecation::notice('4.0', 'Call aggregate methods on a DataList directly instead. In templates'
			. ' an example of the new syntax is &lt% cached List(Member).max(LastEdited) %&gt instead'
			. ' (check partial-caching.md documentation for more details.)');

		if($class) {
			$list = new DataList($class);
			$list->setDataModel(DataModel::inst());
		} else if(isset($this)) {
			$list = new DataList(get_class($this));
			$list->setDataModel($this->model);
		} else {
			throw new \InvalidArgumentException("DataObject::aggregate() must be called as an instance method or passed"
				. " a classname");
		}
		return $list;
	}

	/**
	 * @deprecated
	 */
	public function RelationshipAggregate($relationship) {
		Deprecation::notice('4.0', 'Call aggregate methods on a relationship directly instead.');

		return $this->$relationship();
	}

	/**
	 * Return the first item matching the given query.
	 * All calls to get_one() are cached.
	 *
	 * @param string $callerClass The class of objects to be returned
	 * @param string|array $filter A filter to be inserted into the WHERE clause.
	 * Supports parameterised queries. See SQLQuery::addWhere() for syntax examples.
	 * @param boolean $cache Use caching
	 * @param string $orderby A sort expression to be inserted into the ORDER BY clause.
	 *
	 * @return DataObject The first item matching the query
	 */
	public static function get_one($callerClass, $filter = "", $cache = true, $orderby = "") {
		$SNG = singleton($callerClass);

		$cacheComponents = array($filter, $orderby, $SNG->extend('cacheKeyComponent'));
		$cacheKey = md5(var_export($cacheComponents, true));

		// Flush destroyed items out of the cache
		if($cache && isset(DataObject::$_cache_get_one[$callerClass][$cacheKey])
				&& DataObject::$_cache_get_one[$callerClass][$cacheKey] instanceof DataObject
				&& DataObject::$_cache_get_one[$callerClass][$cacheKey]->destroyed) {

			DataObject::$_cache_get_one[$callerClass][$cacheKey] = false;
		}
		if(!$cache || !isset(DataObject::$_cache_get_one[$callerClass][$cacheKey])) {
			$dl = DataObject::get($callerClass)->where($filter)->sort($orderby);
			$item = $dl->First();

			if($cache) {
				DataObject::$_cache_get_one[$callerClass][$cacheKey] = $item;
				if(!DataObject::$_cache_get_one[$callerClass][$cacheKey]) {
					DataObject::$_cache_get_one[$callerClass][$cacheKey] = false;
				}
			}
		}
		return $cache ? DataObject::$_cache_get_one[$callerClass][$cacheKey] : $item;
	}

	/**
	 * Flush the cached results for all relations (has_one, has_many, many_many)
	 * Also clears any cached aggregate data.
	 *
	 * @param boolean $persistent When true will also clear persistent data stored in the Cache system.
	 *                            When false will just clear session-local cached data
	 * @return DataObject $this
	 */
	public function flushCache($persistent = true) {
		if($persistent) Aggregate::flushCache($this->class);

		if($this->class == 'DataObject') {
			DataObject::$_cache_get_one = array();
			return $this;
		}

		$classes = ClassInfo::ancestry($this->class);
		foreach($classes as $class) {
			if(isset(DataObject::$_cache_get_one[$class])) unset(DataObject::$_cache_get_one[$class]);
		}

		$this->extend('flushCache');

		$this->components = array();
		return $this;
	}

	/**
	 * Flush the get_one global cache and destroy associated objects.
	 */
	public static function flush_and_destroy_cache() {
		if(DataObject::$_cache_get_one) foreach(DataObject::$_cache_get_one as $class => $items) {
			if(is_array($items)) foreach($items as $item) {
				if($item) $item->destroy();
			}
		}
		DataObject::$_cache_get_one = array();
	}

	/**
	 * Reset all global caches associated with DataObject.
	 */
	public static function reset() {
		self::clear_classname_spec_cache();
		DataObject::$cache_has_own_table = array();
		DataObject::$_cache_db = array();
		DataObject::$_cache_get_one = array();
		DataObject::$_cache_composite_fields = array();
		DataObject::$_cache_is_composite_field = array();
		DataObject::$_cache_custom_database_fields = array();
		DataObject::$_cache_get_class_ancestry = array();
		DataObject::$_cache_field_labels = array();
	}

	/**
	 * Return the given element, searching by ID
	 *
	 * @param string $callerClass The class of the object to be returned
	 * @param int $id The id of the element
	 * @param boolean $cache See {@link get_one()}
	 *
	 * @return DataObject The element
	 */
	public static function get_by_id($callerClass, $id, $cache = true) {
		if(!is_numeric($id)) {
			user_error("DataObject::get_by_id passed a non-numeric ID #$id", E_USER_WARNING);
		}

		// Check filter column
		if(is_subclass_of($callerClass, 'DataObject')) {
			$baseClass = ClassInfo::baseDataClass($callerClass);
			$column = "\"$baseClass\".\"ID\"";
		} else{
			// This simpler code will be used by non-DataObject classes that implement DataObjectInterface
			$column = '"ID"';
		}

		// Relegate to get_one
		return DataObject::get_one($callerClass, array($column => $id), $cache);
	}

	/**
	 * Get the name of the base table for this object
	 */
	public function baseTable() {
		$tableClasses = ClassInfo::dataClassesFor($this->class);
		return array_shift($tableClasses);
	}

	/**
	 * @var Array Parameters used in the query that built this object.
	 * This can be used by decorators (e.g. lazy loading) to
	 * run additional queries using the same context.
	 */
	protected $sourceQueryParams;

	/**
	 * @see $sourceQueryParams
	 * @return array
	 */
	public function getSourceQueryParams() {
		return $this->sourceQueryParams;
	}

	/**
	 * @see $sourceQueryParams
	 * @param array
	 */
	public function setSourceQueryParams($array) {
		$this->sourceQueryParams = $array;
	}

	/**
	 * @see $sourceQueryParams
	 * @param array
	 */
	public function setSourceQueryParam($key, $value) {
		$this->sourceQueryParams[$key] = $value;
	}

	/**
	 * @see $sourceQueryParams
	 * @return Mixed
	 */
	public function getSourceQueryParam($key) {
		if(isset($this->sourceQueryParams[$key])) return $this->sourceQueryParams[$key];
		else return null;
	}

	//-------------------------------------------------------------------------------------------//

	/**
	 * Return the database indexes on this table.
	 * This array is indexed by the name of the field with the index, and
	 * the value is the type of index.
	 */
	public function databaseIndexes() {
		$has_one = $this->uninherited('has_one',true);
		$classIndexes = $this->uninherited('indexes',true);
		//$fileIndexes = $this->uninherited('fileIndexes', true);

		$indexes = array();

		if($has_one) {
			foreach($has_one as $relationshipName => $fieldType) {
				$indexes[$relationshipName . 'ID'] = true;
			}
		}

		if($classIndexes) {
			foreach($classIndexes as $indexName => $indexType) {
				$indexes[$indexName] = $indexType;
			}
		}

		if(get_parent_class($this) == "DataObject") {
			$indexes['ClassName'] = true;
		}

		return $indexes;
	}

	/**
	 * Check the database schema and update it as necessary.
	 *
	 * @uses DataExtension->augmentDatabase()
	 */
	public function requireTable() {
		// Only build the table if we've actually got fields
		$fields = self::database_fields($this->class);
		$extensions = self::database_extensions($this->class);

		$indexes = $this->databaseIndexes();

		// Validate relationship configuration
		$this->validateModelDefinitions();

		if($fields) {
			$hasAutoIncPK = ($this->class == ClassInfo::baseDataClass($this->class));
			DB::require_table($this->class, $fields, $indexes, $hasAutoIncPK, $this->stat('create_table_options'),
				$extensions);
		} else {
			DB::dont_require_table($this->class);
		}

		// Build any child tables for many_many items
		if($manyMany = $this->uninherited('many_many', true)) {
			$extras = $this->uninherited('many_many_extraFields', true);
			foreach($manyMany as $relationship => $childClass) {
				// Build field list
				$manymanyFields = array(
					"{$this->class}ID" => "Int",
				(($this->class == $childClass) ? "ChildID" : "{$childClass}ID") => "Int",
				);
				if(isset($extras[$relationship])) {
					$manymanyFields = array_merge($manymanyFields, $extras[$relationship]);
				}

				// Build index list
				$manymanyIndexes = array(
					"{$this->class}ID" => true,
				(($this->class == $childClass) ? "ChildID" : "{$childClass}ID") => true,
				);

				DB::require_table("{$this->class}_$relationship", $manymanyFields, $manymanyIndexes, true, null,
					$extensions);
			}
		}

		// Let any extentions make their own database fields
		$this->extend('augmentDatabase', $dummy);
	}

	/**
	 * Validate that the configured relations for this class use the correct syntaxes
	 * @throws LogicException
	 */
	protected function validateModelDefinitions() {
		$modelDefinitions = array(
			'db' => Config::inst()->get($this->class, 'db', Config::UNINHERITED),
			'has_one' => Config::inst()->get($this->class, 'has_one', Config::UNINHERITED),
			'has_many' => Config::inst()->get($this->class, 'has_many', Config::UNINHERITED),
			'belongs_to' => Config::inst()->get($this->class, 'belongs_to', Config::UNINHERITED),
			'many_many' => Config::inst()->get($this->class, 'many_many', Config::UNINHERITED),
			'belongs_many_many' => Config::inst()->get($this->class, 'belongs_many_many', Config::UNINHERITED),
			'many_many_extraFields' => Config::inst()->get($this->class, 'many_many_extraFields', Config::UNINHERITED)
		);

		foreach($modelDefinitions as $defType => $relations) {
			if( ! $relations) continue;

			foreach($relations as $k => $v) {
				if($defType === 'many_many_extraFields') {
					if(!is_array($v)) {
						throw new LogicException("$this->class::\$many_many_extraFields has a bad entry: "
							. var_export($k, true) . " => " . var_export($v, true)
							. ". Each many_many_extraFields entry should map to a field specification array.");
					}
				} else {
					if(!is_string($k) || is_numeric($k) || !is_string($v)) {
						throw new LogicException("$this->class::$defType has a bad entry: "
							. var_export($k, true). " => " . var_export($v, true) . ".  Each map key should be a
							 relationship name, and the map value should be the data class to join to.");
					}
				}
			}
		}
	}

	/**
	 * Add default records to database. This function is called whenever the
	 * database is built, after the database tables have all been created. Overload
	 * this to add default records when the database is built, but make sure you
	 * call parent::requireDefaultRecords().
	 *
	 * @uses DataExtension->requireDefaultRecords()
	 */
	public function requireDefaultRecords() {
		$defaultRecords = $this->config()->get('default_records', Config::UNINHERITED);

		if(!empty($defaultRecords)) {
			$hasData = DataObject::get_one($this->class);
			if(!$hasData) {
				$className = $this->class;
				foreach($defaultRecords as $record) {
					$obj = $this->model->$className->newObject($record);
					$obj->write();
				}
				DB::alteration_message("Added default records to $className table","created");
			}
		}

		// Let any extentions make their own database default data
		$this->extend('requireDefaultRecords', $dummy);
	}

	/**
	 * Returns fields bu traversing the class heirachy in a bottom-up direction.
	 *
	 * Needed to avoid getCMSFields being empty when customDatabaseFields overlooks
	 * the inheritance chain of the $db array, where a child data object has no $db array,
	 * but still needs to know the properties of its parent. This should be merged into databaseFields or
	 * customDatabaseFields.
	 *
	 * @todo review whether this is still needed after recent API changes
	 */
	public function inheritedDatabaseFields() {
		$fields     = array();
		$currentObj = $this->class;

		while($currentObj != 'DataObject') {
			$fields     = array_merge($fields, self::custom_database_fields($currentObj));
			$currentObj = get_parent_class($currentObj);
		}

		return (array) $fields;
	}

	/**
	 * Get the default searchable fields for this object, as defined in the
	 * $searchable_fields list. If searchable fields are not defined on the
	 * data object, uses a default selection of summary fields.
	 *
	 * @return array
	 */
	public function searchableFields() {
		// can have mixed format, need to make consistent in most verbose form
		$fields = $this->stat('searchable_fields');
		$labels = $this->fieldLabels();

		// fallback to summary fields (unless empty array is explicitly specified)
		if( ! $fields && ! is_array($fields)) {
			$summaryFields = array_keys($this->summaryFields());
			$fields = array();

			// remove the custom getters as the search should not include them
			if($summaryFields) {
				foreach($summaryFields as $key => $name) {
					$spec = $name;

					// Extract field name in case this is a method called on a field (e.g. "Date.Nice")
					if(($fieldPos = strpos($name, '.')) !== false) {
						$name = substr($name, 0, $fieldPos);
					}

					if($this->hasDatabaseField($name)) {
						$fields[] = $name;
					} elseif($this->relObject($spec)) {
						$fields[] = $spec;
					}
				}
			}
		}

		// we need to make sure the format is unified before
		// augmenting fields, so extensions can apply consistent checks
		// but also after augmenting fields, because the extension
		// might use the shorthand notation as well

		// rewrite array, if it is using shorthand syntax
		$rewrite = array();
		foreach($fields as $name => $specOrName) {
			$identifer = (is_int($name)) ? $specOrName : $name;

			if(is_int($name)) {
				// Format: array('MyFieldName')
				$rewrite[$identifer] = array();
			} elseif(is_array($specOrName)) {
				// Format: array('MyFieldName' => array(
				//   'filter => 'ExactMatchFilter',
				//   'field' => 'NumericField', // optional
				//   'title' => 'My Title', // optional
				// ))
				$rewrite[$identifer] = array_merge(
					array('filter' => $this->relObject($identifer)->stat('default_search_filter_class')),
					(array)$specOrName
				);
			} else {
				// Format: array('MyFieldName' => 'ExactMatchFilter')
				$rewrite[$identifer] = array(
					'filter' => $specOrName,
				);
			}
			if(!isset($rewrite[$identifer]['title'])) {
				$rewrite[$identifer]['title'] = (isset($labels[$identifer]))
					? $labels[$identifer] : FormField::name_to_label($identifer);
			}
			if(!isset($rewrite[$identifer]['filter'])) {
				$rewrite[$identifer]['filter'] = 'PartialMatchFilter';
			}
		}

		$fields = $rewrite;

		// apply DataExtensions if present
		$this->extend('updateSearchableFields', $fields);

		return $fields;
	}

	/**
	 * Get any user defined searchable fields labels that
	 * exist. Allows overriding of default field names in the form
	 * interface actually presented to the user.
	 *
	 * The reason for keeping this separate from searchable_fields,
	 * which would be a logical place for this functionality, is to
	 * avoid bloating and complicating the configuration array. Currently
	 * much of this system is based on sensible defaults, and this property
	 * would generally only be set in the case of more complex relationships
	 * between data object being required in the search interface.
	 *
	 * Generates labels based on name of the field itself, if no static property
	 * {@link self::field_labels} exists.
	 *
	 * @uses $field_labels
	 * @uses FormField::name_to_label()
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 *
	 * @return array|string Array of all element labels if no argument given, otherwise the label of the field
	 */
	public function fieldLabels($includerelations = true) {
		$cacheKey = $this->class . '_' . $includerelations;

		if(!isset(self::$_cache_field_labels[$cacheKey])) {
			$customLabels = $this->stat('field_labels');
			$autoLabels = array();

			// get all translated static properties as defined in i18nCollectStatics()
			$ancestry = ClassInfo::ancestry($this->class);
			$ancestry = array_reverse($ancestry);
			if($ancestry) foreach($ancestry as $ancestorClass) {
				if($ancestorClass == 'ViewableData') break;
				$types = array(
					'db'        => (array)Config::inst()->get($ancestorClass, 'db', Config::UNINHERITED)
				);
				if($includerelations){
					$types['has_one'] = (array)Config::inst()->get($ancestorClass, 'has_one', Config::UNINHERITED);
					$types['has_many'] = (array)Config::inst()->get($ancestorClass, 'has_many', Config::UNINHERITED);
					$types['many_many'] = (array)Config::inst()->get($ancestorClass, 'many_many', Config::UNINHERITED);
					$types['belongs_many_many'] = (array)Config::inst()->get($ancestorClass, 'belongs_many_many', Config::UNINHERITED);
				}
				foreach($types as $type => $attrs) {
					foreach($attrs as $name => $spec) {
						$autoLabels[$name] = _t("{$ancestorClass}.{$type}_{$name}",FormField::name_to_label($name));
					}
				}
			}

			$labels = array_merge((array)$autoLabels, (array)$customLabels);
			$this->extend('updateFieldLabels', $labels);
			self::$_cache_field_labels[$cacheKey] = $labels;
		}

		return self::$_cache_field_labels[$cacheKey];
	}

	/**
	 * Get a human-readable label for a single field,
	 * see {@link fieldLabels()} for more details.
	 *
	 * @uses fieldLabels()
	 * @uses FormField::name_to_label()
	 *
	 * @param string $name Name of the field
	 * @return string Label of the field
	 */
	public function fieldLabel($name) {
		$labels = $this->fieldLabels();
		return (isset($labels[$name])) ? $labels[$name] : FormField::name_to_label($name);
	}

	/**
	 * Get the default summary fields for this object.
	 *
	 * @todo use the translation apparatus to return a default field selection for the language
	 *
	 * @return array
	 */
	public function summaryFields() {
		$fields = $this->stat('summary_fields');

		// if fields were passed in numeric array,
		// convert to an associative array
		if($fields && array_key_exists(0, $fields)) {
			$fields = array_combine(array_values($fields), array_values($fields));
		}

		if (!$fields) {
			$fields = array();
			// try to scaffold a couple of usual suspects
			if ($this->hasField('Name')) $fields['Name'] = 'Name';
			if ($this->hasDataBaseField('Title')) $fields['Title'] = 'Title';
			if ($this->hasField('Description')) $fields['Description'] = 'Description';
			if ($this->hasField('FirstName')) $fields['FirstName'] = 'First Name';
		}
		$this->extend("updateSummaryFields", $fields);

		// Final fail-over, just list ID field
		if(!$fields) $fields['ID'] = 'ID';

		// Localize fields (if possible)
		foreach($this->fieldLabels(false) as $name => $label) {
			// only attempt to localize if the label definition is the same as the field name.
			// this will preserve any custom labels set in the summary_fields configuration
			if(isset($fields[$name]) && $name === $fields[$name]) {
				$fields[$name] = $label;
			}
		}

		return $fields;
	}

	/**
	 * Defines a default list of filters for the search context.
	 *
	 * If a filter class mapping is defined on the data object,
	 * it is constructed here. Otherwise, the default filter specified in
	 * {@link DBField} is used.
	 *
	 * @todo error handling/type checking for valid FormField and SearchFilter subclasses?
	 *
	 * @return array
	 */
	public function defaultSearchFilters() {
		$filters = array();

		foreach($this->searchableFields() as $name => $spec) {
			$filterClass = $spec['filter'];

			if($spec['filter'] instanceof SearchFilter) {
				$filters[$name] = $spec['filter'];
			} else {
				$class = $spec['filter'];

				if(!is_subclass_of($spec['filter'], 'SearchFilter')) {
					$class = 'PartialMatchFilter';
				}

				$filters[$name] = new $class($name);
			}
		}

		return $filters;
	}

	/**
	 * @return boolean True if the object is in the database
	 */
	public function isInDB() {
		return is_numeric( $this->ID ) && $this->ID > 0;
	}

	/*
	 * @ignore
	 */
	private static $subclass_access = true;

	/**
	 * Temporarily disable subclass access in data object qeur
	 */
	public static function disable_subclass_access() {
		self::$subclass_access = false;
	}
	public static function enable_subclass_access() {
		self::$subclass_access = true;
	}

	//-------------------------------------------------------------------------------------------//

	/**
	 * Database field definitions.
	 * This is a map from field names to field type. The field
	 * type should be a class that extends .
	 * @var array
	 * @config
	 */
	private static $db = null;

	/**
	 * Use a casting object for a field. This is a map from
	 * field name to class name of the casting object.
	 * @var array
	 */
	private static $casting = array(
		"ID" => 'Int',
		"ClassName" => 'Varchar',
		"LastEdited" => "SS_Datetime",
		"Created" => "SS_Datetime",
		"Title" => 'Text',
	);

	/**
	 * Specify custom options for a CREATE TABLE call.
	 * Can be used to specify a custom storage engine for specific database table.
	 * All options have to be keyed for a specific database implementation,
	 * identified by their class name (extending from {@link SS_Database}).
	 *
	 * <code>
	 * array(
	 *  'MySQLDatabase' => 'ENGINE=MyISAM'
	 * )
	 * </code>
	 *
	 * Caution: This API is experimental, and might not be
	 * included in the next major release. Please use with care.
	 *
	 * @var array
	 * @config
	 */
	private static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);

	/**
	 * If a field is in this array, then create a database index
	 * on that field. This is a map from fieldname to index type.
	 * See {@link SS_Database->requireIndex()} and custom subclasses for details on the array notation.
	 *
	 * @var array
	 * @config
	 */
	private static $indexes = null;

	/**
	 * Inserts standard column-values when a DataObject
	 * is instanciated. Does not insert default records {@see $default_records}.
	 * This is a map from fieldname to default value.
	 *
	 *  - If you would like to change a default value in a sub-class, just specify it.
	 *  - If you would like to disable the default value given by a parent class, set the default value to 0,'',
	 *    or false in your subclass.  Setting it to null won't work.
	 *
	 * @var array
	 * @config
	 */
	private static $defaults = null;

	/**
	 * Multidimensional array which inserts default data into the database
	 * on a db/build-call as long as the database-table is empty. Please use this only
	 * for simple constructs, not for SiteTree-Objects etc. which need special
	 * behaviour such as publishing and ParentNodes.
	 *
	 * Example:
	 * array(
	 *  array('Title' => "DefaultPage1", 'PageTitle' => 'page1'),
	 *  array('Title' => "DefaultPage2")
	 * ).
	 *
	 * @var array
	 * @config
	 */
	private static $default_records = null;

	/**
	 * One-to-zero relationship defintion. This is a map of component name to data type. In order to turn this into a
	 * true one-to-one relationship you can add a {@link DataObject::$belongs_to} relationship on the child class.
	 *
	 * Note that you cannot have a has_one and belongs_to relationship with the same name.
	 *
	 *	@var array
	 * @config
	 */
	private static $has_one = null;

	/**
	 * A meta-relationship that allows you to define the reverse side of a {@link DataObject::$has_one}.
	 *
	 * This does not actually create any data structures, but allows you to query the other object in a one-to-one
	 * relationship from the child object. If you have multiple belongs_to links to another object you can use the
	 * syntax "ClassName.HasOneName" to specify which foreign has_one key on the other object to use.
	 *
	 * Note that you cannot have a has_one and belongs_to relationship with the same name.
	 *
	 * @var array
	 * @config
	 */
	private static $belongs_to;

	/**
	 * This defines a one-to-many relationship. It is a map of component name to the remote data class.
	 *
	 * This relationship type does not actually create a data structure itself - you need to define a matching $has_one
	 * relationship on the child class. Also, if the $has_one relationship on the child class has multiple links to this
	 * class you can use the syntax "ClassName.HasOneRelationshipName" in the remote data class definition to show
	 * which foreign key to use.
	 *
	 * @var array
	 * @config
	 */
	private static $has_many = null;

	/**
	 * many-many relationship definitions.
	 * This is a map from component name to data type.
	 * @var array
	 * @config
	 */
	private static $many_many = null;

	/**
	 * Extra fields to include on the connecting many-many table.
	 * This is a map from field name to field type.
	 *
	 * Example code:
	 * <code>
	 * public static $many_many_extraFields = array(
	 *  'Members' => array(
	 *			'Role' => 'Varchar(100)'
	 *		)
	 * );
	 * </code>
	 *
	 * @var array
	 * @config
	 */
	private static $many_many_extraFields = null;

	/**
	 * The inverse side of a many-many relationship.
	 * This is a map from component name to data type.
	 * @var array
	 * @config
	 */
	private static $belongs_many_many = null;

	/**
	 * The default sort expression. This will be inserted in the ORDER BY
	 * clause of a SQL query if no other sort expression is provided.
	 * @var string
	 * @config
	 */
	private static $default_sort = null;

	/**
	 * Default list of fields that can be scaffolded by the ModelAdmin
	 * search interface.
	 *
	 * Overriding the default filter, with a custom defined filter:
	 * <code>
	 *  static $searchable_fields = array(
	 *     "Name" => "PartialMatchFilter"
	 *  );
	 * </code>
	 *
	 * Overriding the default form fields, with a custom defined field.
	 * The 'filter' parameter will be generated from {@link DBField::$default_search_filter_class}.
	 * The 'title' parameter will be generated from {@link DataObject->fieldLabels()}.
	 * <code>
	 *  static $searchable_fields = array(
	 *    "Name" => array(
	 *      "field" => "TextField"
	 *    )
	 *  );
	 * </code>
	 *
	 * Overriding the default form field, filter and title:
	 * <code>
	 *  static $searchable_fields = array(
	 *    "Organisation.ZipCode" => array(
	 *      "field" => "TextField",
	 *      "filter" => "PartialMatchFilter",
	 *      "title" => 'Organisation ZIP'
	 *    )
	 *  );
	 * </code>
	 * @config
	 */
	private static $searchable_fields = null;

	/**
	 * User defined labels for searchable_fields, used to override
	 * default display in the search form.
	 * @config
	 */
	private static $field_labels = null;

	/**
	 * Provides a default list of fields to be used by a 'summary'
	 * view of this object.
	 * @config
	 */
	private static $summary_fields = null;

	/**
	 * Provides a list of allowed methods that can be called via RESTful api.
	 */
	public static $allowed_actions = null;

	/**
	 * Collect all static properties on the object
	 * which contain natural language, and need to be translated.
	 * The full entity name is composed from the class name and a custom identifier.
	 *
	 * @return array A numerical array which contains one or more entities in array-form.
	 * Each numeric entity array contains the "arguments" for a _t() call as array values:
	 * $entity, $string, $priority, $context.
	 */
	public function provideI18nEntities() {
		$entities = array();

		$entities["{$this->class}.SINGULARNAME"] = array(
			$this->singular_name(),

			'Singular name of the object, used in dropdowns and to generally identify a single object in the interface'
		);

		$entities["{$this->class}.PLURALNAME"] = array(
			$this->plural_name(),

			'Pural name of the object, used in dropdowns and to generally identify a collection of this object in the'
			. ' interface'
		);

		return $entities;
	}

	/**
	 * Returns true if the given method/parameter has a value
	 * (Uses the DBField::hasValue if the parameter is a database field)
	 *
	 * @param string $field The field name
	 * @param array $arguments
	 * @param bool $cache
	 * @return boolean
	 */
	public function hasValue($field, $arguments = null, $cache = true) {
		// has_one fields should not use dbObject to check if a value is given
		if(!$this->hasOneComponent($field) && ($obj = $this->dbObject($field))) {
			return $obj->exists();
		} else {
			return parent::hasValue($field, $arguments, $cache);
		}
	}

}
