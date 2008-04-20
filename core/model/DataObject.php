<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * A single database record & abstract class for the data-access-model.
 * @package sapphire
 * @subpackage model
 */
class DataObject extends ViewableData implements DataObjectInterface {
	/**
	 * Data stored in this objects database record. An array indexed
	 * by fieldname.
	 * @var array
	 */
	protected $record;

	/**
	 * An array indexed by fieldname, true if the field has been changed.
	 * @var array
	 */
	protected $changed;

	/**
	 * The database record (in the same format as $record), before
	 * any changes.
	 * @var array
	 */
	protected $original;

	protected $defs;
	protected $fieldObjects;

	/**
	 * The one-to-one, one-to-many and many-to-one components
	 * indexed by component name.
	 * @var array
	 */
	protected $components;
	
	
	/**
	 * True if this DataObject has been destroyed.
	 * @var boolean
	 */
	public $destroyed = false;

	/**
	 * Human-readable singular name.
	 * @var string
	 */
	static $singular_name = null;

	/**
	 * Human-readable pluaral name
	 * @var string
	 */
	static $plural_name = null;


	/**
	 * Construct a new DataObject.
	 *
	 * @param array|null $record This will be null for a new database record.  Alternatively, you can pass an array of
	 * field values.  Normally this contructor is only used by the internal systems that get objects from the database.
	 * @param boolean $isSingleton This this to true if this is a singleton() object, a stub for calling methods.  Singletons
	 * don't have their defaults set.
	 */
	function __construct($record = null, $isSingleton = false) {
		// Set the fields data.
		if(!$record) {
			$record = array("ID" => 0);
		}

		if(!is_array($record)) {
			if(is_object($record)) $passed = "an object of type '$record->class'";
			else $passed = "The value '$record'";

			user_error("DataObject::__construct passed $passed.  It's supposed to be passed an array,
				taken straight from the database.  Perhaps you should use DataObject::get_one instead?", E_USER_WARNING);
			$record = null;
		}

		$this->record = $this->original = $record;

		// Keep track of the modification date of all the data sourced to make this page
		// From this we create a Last-Modified HTTP header
		if(isset($record['LastEdited'])) {
			HTTP::register_modification_date($record['LastEdited']);
		}

		parent::__construct();

		// Must be called after parent constructor
		if(!$isSingleton && (!isset($this->record['ID']) || !$this->record['ID'])) {
			$this->populateDefaults();
		}

		// prevent populateDefaults() and setField() from marking overwritten defaults as changed
		$this->changed = array();
	}

	/**
	 * Destroy all of this objects dependant objects.
	 * You'll need to call this to get the memory of an object that has components or extensions freed.
	 */
	function destroy() {
		$this->extension_instances = null;
		$this->components = null;
		$this->destroyed = true;
	}

	/**
	 * Create a duplicate of this node.
	 * Caution: Doesn't duplicate relations.
	 * 
	 * @param $doWrite Perform a write() operation before returning the object.  If this is true, it will create the duplicate in the database.
	 * @return DataObject A duplicate of this node. The exact type will be the type of this node.
	 */
	function duplicate($doWrite = true) {
		$className = $this->class;
		$clone = new $className( $this->record );
		$clone->ID = 0;
		if($doWrite) $clone->write();
		return $clone;
	}

	/**
	 * Set the ClassName attribute; $this->class is also updated.
	 *
	 * @param string $className The new ClassName attribute
	 */
	function setClassName($className) {
		$this->class = trim($className);
		$this->setField("ClassName", $className);
	}

	/**
	 * Create a new instance of a different class from this object's record
	 * This is useful when dynamically changing the type of an instance. Specifically,
	 * it ensures that the instance of the class is a match for the className of the
	 * record.
	 *
	 * @param string $newClassName The name of the new class
	 *
	 * @return DataObject The new instance of the new class, The exact type will be of the class name provided.
	 */
	function newClassInstance($newClassName) {
		$newRecord = $this->record;
		//$newRecord['RecordClassName'] = $newRecord['ClassName'] = $newClassName;

		$newInstance = new $newClassName($newRecord);
		$newInstance->setClassName($newClassName);
		$newInstance->forceChange();

		return $newInstance;
	}

	/**
	 * Adds methods from the extensions.
	 * Called by Object::__construct() once per class.
	 */
	function defineMethods() {
		if($this->class == 'DataObject') return;

		parent::defineMethods();

		// Define the extra db fields
		if($this->extension_instances) foreach($this->extension_instances as $i => $instance) {
			$instance->loadExtraDBFields();
		}
		
		// Set up accessors for joined items
		if($manyMany = $this->many_many()) {
			foreach($manyMany as $relationship => $class) {
				$this->addWrapperMethod($relationship, 'getManyManyComponents');
			}
		}
		if($hasMany = $this->has_many()) {

			foreach($hasMany as $relationship => $class) {
				$this->addWrapperMethod($relationship, 'getComponents');
			}

		}
		if($hasOne = $this->has_one()) {
			foreach($hasOne as $relationship => $class) {
				$this->addWrapperMethod($relationship, 'getComponent');
			}
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
		return ($this->record && $this->record['ID'] > 0);		
	}
	
	public function isEmpty(){
		$isEmpty = true;
		if($this->record){
			foreach($this->record as $k=>$v){
				if($k != "ID"){
					$isEmpty = $isEmpty && !$v;
				}
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
	function singular_name() {
		$name = $this->stat('singular_name');
		if(!$name) {
			$name = ucwords(trim(strtolower(ereg_replace('([A-Z])',' \\1',$this->class))));
		}
		return $name;
	}

	/**
	 * Get the translated user friendly singular name of this DataObject
	 * same as singular_name() but runs it through the translating function
	 *
	 * NOTE:
	 * It uses as default text if no translation found the $add_action when
	 * defined or else the default text is singular_name()
	 *
	 * Translating string is in the form:
	 *     $this->class.SINGULARNAME
	 * Example:
	 *     Page.SINGULARNAME
	 *
	 * @return string User friendly translated singular name of this DataObject
	 */
	function i18n_singular_name()
	{
	        $name = (!empty($this->add_action)) ? $this->add_action : $this->singular_name();
		return _t($this->class.'.SINGULARNAME', $name);
	}

	/**
	 * Get the user friendly plural name of this DataObject
	 * If the name is not defined (by renaming $plural_name in the subclass),
	 * this returns a pluralised version of the class name.
	 *
	 * @return string User friendly plural name of this DataObject
	 */
	function plural_name() {
		if($name = $this->stat('plural_name')) {
			return $name;
		} else {
			$name = $this->singular_name();
			if(substr($name,-1) == 'e') $name = substr($name,0,-1);
			else if(substr($name,-1) == 'y') $name = substr($name,0,-1) . 'ie';

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
	function i18n_plural_name()
	{
	        $name = $this->plural_name();
		return _t($this->class.'.PLURALNAME', $name);
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
		return $this->record;
	}

	/**
	 * Pass a number of field changes in a map.
	 * Doesn't write to the database. To write the data,
	 * use the write() method.
	 *
	 * @param array $data A map of field name to data values to update.
	 */
	public function update($data) {
		foreach($data as $k => $v) {
			$this->$k = $v;
		}
	}

	/**
	 * Pass changes as a map, and try to
	 * get automatic casting for these fields.
	 * Doesn't write to the database. To write the data,
	 * use the write() method.
	 *
	 * @param array $data A map of field name to data values to update.
	 */
	public function castedUpdate($data) {
		foreach($data as $k => $v) {
			$this->setCastedField($k,$v);
		}
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
	 * 	Only applicable with $priority='right'. (optional)
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
		$leftData = $leftObj->customDatabaseFields();
		$rightData = $rightObj->customDatabaseFields();
		
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
			if($manyMany = $this->many_many()) {
				foreach($manyMany as $relationship => $class) {
					$leftComponents = $leftObj->getManyManyComponents($relationship);
					$rightComponents = $rightObj->getManyManyComponents($relationship);
					if($rightComponents && $rightComponents->exists()) $leftComponents->addMany($rightComponents->column('ID'));
					$leftComponents->write();
				}
			}

			if($hasMany = $this->has_many()) {
				foreach($hasMany as $relationship => $class) {
					$leftComponents = $leftObj->getComponents($relationship);
					$rightComponents = $rightObj->getComponents($relationship);
					if($rightComponents && $rightComponents->exists()) $leftComponents->addMany($rightComponents->column('ID'));
					$leftComponents->write();
				}
	
			}

			if($hasOne = $this->has_one()) {
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
	 * Doesn't write to the database.
	 */
	public function forceChange() {
		foreach($this->record as $fieldName => $fieldVal)
			$this->changed[$fieldName] = 1;
	}

	/**
	 * Event handler called before writing to the database.
	 * You can overload this to clean up or otherwise process data before writing it to the
	 * database.  Don't forget to call parent::onBeforeWrite(), though!
	 */
	protected function onBeforeWrite() {
		$this->brokenOnWrite = false;

		$dummy = null;
		$this->extend('augmentBeforeWrite', $dummy);
	}

	/**
	 * Used by onBeforeWrite() to ensure child classes call parent::onBeforeWrite()
	 * @var boolean
	 */
	protected $brokenOnWrite = false;

	/**
	 * Event handler called before deleting from the database.
	 * You can overload this to clean up or otherwise process data before delete this
	 * record.  Don't forget to call parent::onBeforeDelete(), though!
	 */
	protected function onBeforeDelete() {
		$this->brokenOnDelete = false;
	}

	/**
	 * Used by onBeforeDelete() to ensure child classes call parent::onBeforeDelete()
	 * @var boolean
	 */
	protected $brokenOnDelete = false;

	/**
	 * Load the default values in from the self::$defaults array.
	 * Will traverse the defaults of the current class and all its parent classes.
	 * Called by the constructor when creating new records.
	 */
	public function populateDefaults() {
		$classes = array_reverse(ClassInfo::ancestry($this));
		foreach($classes as $class) {
			$singleton = ($class == $this->class) ? $this : singleton($class);

			$defaults = $singleton->stat('defaults');

			if($defaults) foreach($defaults as $fieldName => $fieldValue) {
				// SRM 2007-03-06: Stricter check
				if(!isset($this->$fieldName)) {
					$this->$fieldName = $fieldValue;
				}
				// Set many-many defaults with an array of ids
				if(is_array($fieldValue) && $this->many_many($fieldName)) {
					$manyManyJoin = $this->$fieldName();
					$manyManyJoin->setByIdList($fieldValue);
				}
			}
			if($class == 'DataObject') {
				break;
			}
		}
	}

	/**
	 * Writes all changes to this object to the database.
	 *  - It will insert a record whenever ID isn't set, otherwise update.
	 *  - All relevant tables will be updated.
	 *  - $this->onBeforeWrite() gets called beforehand.
	 *  - Extensions such as Versioned will ammend the database-write to ensure that a version is saved.
	 *  - Calls to {@link DataObjectLog} can be used to see everything that's been changed.
	 *
	 * @param boolean $showDebug Show debugging information
	 * @param boolean $forceInsert Run INSERT command rather than UPDATE, even if record already exists
	 * @param boolean $forceWrite Write to database even if there are no changes
	 *
	 * @return int The ID of the record
	 */
	public function write($showDebug = false, $forceInsert = false, $forceWrite = false) {
		$firstWrite = false;
		$this->brokenOnWrite = true;
		$isNewRecord = false;
		$this->onBeforeWrite();
		if($this->brokenOnWrite) {
			user_error("$this->class has a broken onBeforeWrite() function.  Make sure that you call parent::onBeforeWrite().", E_USER_ERROR);
		}

		// New record = everything has changed

		if(($this->ID && is_numeric($this->ID)) && !$forceInsert) {
			$dbCommand = 'update';
		} else{
			$dbCommand = 'insert';

			$this->changed = array();
			foreach($this->record as $k => $v) {
				$this->changed[$k] = 2;
			}

			$firstWrite = true;
		}

		// No changes made
		if($this->changed) {
			foreach($this->getClassAncestry() as $ancestor) {
				if(ClassInfo::hasTable($ancestor))
					$ancestry[] = $ancestor;
			}

			// Look for some changes to make
			unset($this->changed['ID']);

			$hasChanges = false;
			foreach($this->changed as $fieldName => $changed) {
				if($changed) {
					$hasChanges = true;
					break;
				}
			}

			if($hasChanges || $forceWrite || !$this->record['ID']) {
			
				// New records have their insert into the base data table done first, so that they can pass the 
				// generated primary key on to the rest of the manipulation
				if(!$this->record['ID'] && isset($ancestry[0])) {
					$baseTable = $ancestry[0];

					DB::query("INSERT INTO `{$baseTable}` SET Created = NOW()");
					$this->record['ID'] = DB::getGeneratedID($baseTable);
					$this->changed['ID'] = 2;

					$isNewRecord = true;
				}

				// Divvy up field saving into a number of database manipulations
				if(isset($ancestry) && is_array($ancestry)) {
					foreach($ancestry as $idx => $class) {
						$classSingleton = singleton($class);
						foreach($this->record as $fieldName => $value) {
							if(isset($this->changed[$fieldName]) && $this->changed[$fieldName] && $fieldType = $classSingleton->fieldExists($fieldName)) {
								$manipulation[$class]['fields'][$fieldName] = $value ? ("'" . addslashes($value) . "'") : singleton($fieldType)->nullValue();
							}
						}

						// Add the class name to the base object
						if($idx == 0) {
							$manipulation[$class]['fields']["LastEdited"] = "now()";
							if($dbCommand == 'insert') {
								$manipulation[$class]['fields']["Created"] = "now()";
								//echo "<li>$this->class - " .get_class($this);
								$manipulation[$class]['fields']["ClassName"] = "'$this->class'";
							}
						}

						// In cases where there are no fields, this 'stub' will get picked up on
						if(ClassInfo::hasTable($class)) {
							$manipulation[$class]['command'] = $dbCommand;
							$manipulation[$class]['id'] = $this->record['ID'];
						} else {
							unset($manipulation[$class]);
						}
					}
				}


				$this->extend('augmentWrite', $manipulation);
				// New records have their insert into the base data table done first, so that they can pass the
				// generated ID on to the rest of the manipulation
				if(isset($isNewRecord) && $isNewRecord && isset($manipulation[$baseTable])) {
					$manipulation[$baseTable]['command'] = 'update';
				}
				DB::manipulate($manipulation);

				if(isset($isNewRecord) && $isNewRecord) {
					DataObjectLog::addedObject($this);
				} else {
					DataObjectLog::changedObject($this);
				}

				$this->changed = null;
			} elseif ( $showDebug ) {
				echo "<b>Debug:</b> no changes for DataObject<br />";
			}

			// Clears the cache for this object so get_one returns the correct object.
			$this->flushCache();

			if(!isset($this->record['Created'])) {
				$this->record['Created'] = date('Y-m-d H:i:s');
			}
			$this->record['LastEdited'] = date('Y-m-d H:i:s');
		}

		// Write ComponentSets as necessary
		if($this->components) {
			foreach($this->components as $component) {
				$component->write($firstWrite);
			}
		}

		return $this->record['ID'];
	}


	/**
	 * Perform a write without affecting the version table.
	 * On objects without versioning.
	 *
	 * @return int The ID of the record
	 */
	public function writeWithoutVersion() {
		$this->changed['Version'] = 1;
		if(!isset($this->record['Version'])) {
			$this->record['Version'] = -1;
		}
		return $this->write();
	}

	/**
	 * Delete this data object.
	 * $this->onBeforeDelete() gets called.
	 * Note that in Versioned objects, both Stage and Live will be deleted.
	 */
	public function delete() {
		$this->brokenOnDelete = true;
		$this->onBeforeDelete();
		if($this->brokenOnDelete) {
			user_error("$this->class has a broken onBeforeDelete() function.  Make sure that you call parent::onBeforeDelete().", E_USER_ERROR);
		}
		foreach($this->getClassAncestry() as $ancestor) {
			if(ClassInfo::hastable($ancestor)) {
				$sql = new SQLQuery();
				$sql->delete = true;
				$sql->from[$ancestor] = "`$ancestor`";
				$sql->where[] = "ID = $this->ID";
				$this->extend('augmentSQL', $sql);
				$sql->execute();
			}
		}

		$this->OldID = $this->ID;
		$this->ID = 0;

		DataObjectLog::deletedObject($this);
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
	 * A cache used by getClassAncestry()
	 * @var array
	 */
	protected static $ancestry;

	/**
	 * Get the class ancestry, including the current class name.
	 * The ancestry will be returned as an array of class names, where the 0th element
	 * will be the class that inherits directly from DataObject, and the last element
	 * will be the current class.
	 *
	 * @return array Class ancestry
	 */
	public function getClassAncestry() {
		if(!isset(DataObject::$ancestry[$this->class])) {
			DataObject::$ancestry[$this->class] = array($this->class);
			while(($class = get_parent_class(DataObject::$ancestry[$this->class][0])) != "DataObject") {
				array_unshift(DataObject::$ancestry[$this->class], $class);
			}
		}
		return DataObject::$ancestry[$this->class];
	}

	/**
	 * Return a component object from a one to one relationship, as a DataObject.
	 * If no component is available, an 'empty component' will be returned.
	 *
	 * @param string $componentName Name of the component
	 *
	 * @return DataObject The component object. It's exact type will be that of the component.
	 */
	public function getComponent($componentName) {
		if(isset($this->components[$componentName])) {
			return $this->components[$componentName];
		}

		if($componentClass = $this->has_one($componentName)) {
			$childID = $this->getField($componentName . 'ID');

			if($childID && is_numeric($childID)) {
				$component = DataObject::get_by_id($componentClass,$childID);
			}

			// If no component exists, create placeholder object
			if(!isset($component)) {
				$component = $this->createComponent($componentName);
				// We may have had an orphaned ID that needs to be cleaned up
				$this->setField($componentName . 'ID', 0);
			}

			// If no component exists, create placeholder object
			if(!$component) {
				$component = $this->createComponent($componentName);
			}

			$this->components[$componentName] = $component;
			return $component;
		} else {
			user_error("DataObject::getComponent(): Unknown 1-to-1 component '$componentName' on class '$this->class'", E_USER_ERROR);
		}
	}

	/**
	 * A cache used by component getting classes
	 * @var array
	 */
    protected $componentCache;

	/**
	 * Returns a one-to-many component, as a ComponentSet.
	 *
	 * @param string $componentName Name of the component
	 * @param string $filter A filter to be inserted into the WHERE clause
	 * @param string $sort A sort expression to be inserted into the ORDER BY clause. If omitted, the static field $default_sort on the component class will be used.
	 * @param string $join A single join clause. This can be used for filtering, only 1 instance of each DataObject will be returned.
	 * @param string $limit A limit expression to be inserted into the LIMIT clause
	 *
	 * @return ComponentSet The components of the one-to-many relationship.
	 */
	public function getComponents($componentName, $filter = "", $sort = "", $join = "", $limit = "") {
		$result = null;
		
		$sum = md5("{$filter}_{$sort}_{$join}_{$limit}");
		if(isset($this->componentCache[$componentName . '_' . $sum]) && false != $this->componentCache[$componentName . '_' . $sum]) {
			return $this->componentCache[$componentName . '_' . $sum];
		}

		if(!$componentClass = $this->has_many($componentName)) {
			user_error("DataObject::getComponents(): Unknown 1-to-many component '$componentName' on class '$this->class'", E_USER_ERROR);
		}
		
		$joinField = $this->getComponentJoinField($componentName);
		
		if($this->isInDB()) { //Check to see whether we should query the db
			$componentObj = singleton($componentClass);
			$id = $this->getField("ID");
			
			// get filter
			$combinedFilter = "$joinField = '$id'";
			if($filter) $combinedFilter .= " AND {$filter}";
			
			$result = $componentObj->instance_get($combinedFilter, $sort, $join, $limit, "ComponentSet");
		}
		
		if(!$result) {
			// If this record isn't in the database, then we want to hold onto this specific ComponentSet,
			// because it's the only copy of the data that we have.
			$result = new ComponentSet();
			$this->setComponent($componentName . '_' . $sum, $result);
		}
		
		$result->setComponentInfo("1-to-many", $this, null, null, $componentClass, $joinField);

		return $result;
	}

	/**
	 * Tries to find the db-key for storing a relation (defaults to "ParentID" if no relation is found).
	 * The iteration is necessary because the most specific class does not always have a database-table.
	 *
	 * @param string $componentName Name of one to many component
	 *
	 * @return string Fieldname for the parent-relation
	 */
	public function getComponentJoinField($componentName) {
		if(!$componentClass = $this->has_many($componentName)) {
			user_error("DataObject::getComsponents(): Unknown 1-to-many component '$componentName' on class '$this->class'", E_USER_ERROR);
		}
		$componentObj = singleton($componentClass);

		// get has-one relations
		$reversedComponentRelations = array_flip($componentObj->has_one());

		// get all parentclasses for the current class which have tables
		$allClasses = ClassInfo::ancestry($this->class);

		// use most specific relation by default (turn around order)
		$allClasses = array_reverse($allClasses);

		// traverse up through all classes with database-tables, starting with the most specific
		// (mostly the classname of the calling DataObject)
		foreach($allClasses as $class) {
			// if this class does a "has-one"-representation, use it
			if(isset($reversedComponentRelations[$class]) && false != $reversedComponentRelations[$class]) {
				$joinField = $reversedComponentRelations[$class] . 'ID';
				break;
			}
		}
		if(!isset($joinField)) {
			$joinField = 'ParentID';
		}

		return $joinField;
	}

    /**
     * Sets the component of a relationship.
     *
     * @param string $componentName Name of the component
     * @param DataObject|ComponentSet $componentValue Value of the component
     */
    public function setComponent($componentName, $componentValue) {
    	$this->componentCache[$componentName] = $componentValue;
    }

	/**
	 * Returns a many-to-many component, as a ComponentSet.
	 * @param string $componentName Name of the many-many component
	 * @return ComponentSet The set of components
	 *
	 * @todo Implement query-params
	 */
	public function getManyManyComponents($componentName, $filter = "", $sort = "", $join = "", $limit = "") {
		$sum = md5("{$filter}_{$sort}_{$join}_{$limit}");
    	if(isset($this->componentCache[$componentName . '_' . $sum]) && false != $this->componentCache[$componentName . '_' . $sum]) {
	    	return $this->componentCache[$componentName . '_' . $sum];
    	}

		list($parentClass, $componentClass, $parentField, $componentField, $table) = $this->many_many($componentName);

		if($this->ID && is_numeric($this->ID)) {
			if($componentClass) {
				$componentObj = singleton($componentClass);

				// Join expression is done on SiteTree.ID even if we link to Page; it helps work around
				// database inconsistencies
				$componentBaseClass = ClassInfo::baseDataClass($componentClass);

				$query = $componentObj->extendedSQL(
					"`$table`.$parentField = $this->ID", // filter 
					$sort,
					$limit,
					"INNER JOIN `$table` ON `$table`.$componentField = `$componentBaseClass`.ID" // join
				);
				array_unshift($query->select, "`$table`.*");
				
				if($filter) $query->where[] = $filter;
				if($join) $query->from[] = $join;
				
				$records = $query->execute();
				$result = $this->buildDataObjectSet($records, "ComponentSet", $query, $componentBaseClass);

				if(!$result) {
					$result = new ComponentSet();
				}
			}
		} else {
			$result = new ComponentSet();
		}
		$result->setComponentInfo("many-to-many", $this, $parentClass, $table, $componentClass);

		// If this record isn't in the database, then we want to hold onto this specific ComponentSet,
		// because it's the only copy of the data that we have.
		if(!$this->isInDB()) {
			$this->setComponent($componentName . '_' . $sum, $result);
		}

		return $result;
	}

	/**
	 * Creates an empty component for the given one-one or one-many relationship
	 *
	 * @param string $componentName
	 *
	 * @return DataObject The empty component. The exact class will be that of the components class.
	 */
	protected function createComponent($componentName) {
		if(($componentClass = $this->has_one($componentName)) || ($componentClass = $this->has_many($componentName))) {
			$component = new $componentClass(null);
			return $component;

		} else {
			user_error("DataObject::createComponent(): Unknown 1-to-1 or 1-to-many component '$componentName' on class '$this->class'", E_USER_ERROR);
		}
	}

	/**
	 * Return the class of a one-to-one component.  If $component is null, return all of the one-to-one components and their classes.
	 *
	 * @param string $component Name of component
	 *
	 * @return string|array The class of the one-to-one component, or an array of all one-to-one components and their classes.
	 */
	public function has_one($component = null) {
		$classes = ClassInfo::ancestry($this);

		foreach($classes as $class) {
			// Wait until after we reach DataObject
			if(in_array($class, array('Object', 'ViewableData', 'DataObject'))) continue;

			if($component) {
				$candidate = eval("return isset({$class}::\$has_one[\$component]) ? {$class}::\$has_one[\$component] : null;");
				if($candidate) {
					return $candidate;
				}
			} else {
				eval("\$items = isset(\$items) ? array_merge((array){$class}::\$has_one, (array)\$items) : (array){$class}::\$has_one;");
			}
		}
		return isset($items) ? $items : null;
	}

	/**
	 * Return all of the database fields defined in self::$db and all the parent classes.
	 * Doesn't include any fields specified by self::$has_one.  Use $this->has_one() to get these fields
	 *
	 * @return array The database fields
	 */
	public function db() {
		$classes = ClassInfo::ancestry($this);
		$good = false;
		$items = array();

		foreach($classes as $class) {
			// Wait until after we reach DataObject
			if(!$good) {
				if($class == 'DataObject') {
					$good = true;
				}
				continue;
			}
			eval("\$items = array_merge((array){$class}::\$db, (array)\$items);");
		}

		return $items;
	}

	/**
	 * Return the class of a one-to-many component.  If $component is null, return all of the one-to-many components
	 * and their classes.
	 *
	 * @param string $component Name of component
	 *
	 * @return string|array The class of the one-to-many component, or an array of all one-to-many components and their classes.
	 */
	public function has_many($component = null) {
		$classes = ClassInfo::ancestry($this);

		foreach($classes as $class) {
			if(in_array($class, array('ViewableData', 'Object', 'DataObject'))) continue;

			if($component) {
				$candidate = eval("return isset({$class}::\$has_many[\$component]) ? {$class}::\$has_many[\$component] : null;");
				$candidate = eval("if ( isset({$class}::\$has_many[\$component]) ) { return {$class}::\$has_many[\$component]; } else { return false; }");
				if($candidate) {
					return $candidate;
				}
			} else {
				eval("\$items = isset(\$items) ? array_merge((array){$class}::\$has_many, (array)\$items) : (array){$class}::\$has_many;");
			}
		}

		return isset($items) ? $items : null;
	}

	/**
	 * Return information about a many-to-many component.
	 * The return value is an array of (parentclass, childclass).  If $component is null, then all many-many
	 * components are returned.
	 *
	 * @param string $component Name of component
	 *
	 * @return array  An array of (parentclass, childclass), or an array of all many-many components
	 */
	public function many_many($component = null) {
		$classes = ClassInfo::ancestry($this);

		foreach($classes as $class) {
			// Wait until after we reach DataObject
			if(in_array($class, array('ViewableData', 'Object', 'DataObject'))) continue;

			if($component) {
				// Try many_many
				$candidate = eval("return isset({$class}::\$many_many[\$component]) ? {$class}::\$many_many[\$component] : null;");
				if($candidate) {
					$parentField = $class . "ID";
					$childField = ($class == $candidate) ? "ChildID" : $candidate . "ID";
					return array($class, $candidate, $parentField, $childField, "{$class}_$component");
				}

				// Try belongs_many_many
				$candidate = eval("return isset({$class}::\$belongs_many_many[\$component]) ? {$class}::\$belongs_many_many[\$component] : null;");
				if($candidate) {
					$childField = $candidate . "ID";

					// We need to find the inverse component name
					$otherManyMany = eval("return {$candidate}::\$many_many;");
					if(!$otherManyMany) {
						Debug::message("Inverse component of $candidate not found");
					}

					foreach($otherManyMany as $inverseComponentName => $candidateClass) {
						if($candidateClass == $class || is_subclass_of($class, $candidateClass)) {
							$parentField = ($class == $candidate) ? "ChildID" : $candidateClass . "ID";
							// HACK HACK HACK!
							if($component == 'NestedProducts') {
								$parentField = $candidateClass . "ID";
							}

							return array($class, $candidate, $parentField, $childField, "{$candidate}_$inverseComponentName");
						}
					}
					user_error("Orphaned \$belongs_many_many value for $this->class.$component", E_USER_ERROR);
				}
			} else {
				eval("\$items = isset(\$items) ? array_merge((array){$class}::\$many_many, (array)\$items) : (array){$class}::\$many_many;");
				eval("\$items = array_merge((array){$class}::\$belongs_many_many, (array)\$items);");
			}
		}
		return isset($items) ? $items : null;
	}

	/**
	 * Checks if the given fields have been filled out.
	 * Pass this method a number of field names, it will return true if they all have values.
	 *
	 * @param array|string $args,... The field names may be passed either as an array, or as multiple parameters.
	 *
	 * @return boolean True if all fields have values, otherwise false
	 */
	public function filledOut($args) {
		// Field names can be passed as arguments or an array
		if(!is_array($args)) $args = func_get_args();
		foreach($args as $arg) {
			if(!$this->$arg) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Gets the value of a field.
	 * Called by {@link __get()} and any getFieldName() methods you might create.
	 *
	 * @param string $field The name of the field
	 *
	 * @return mixed The field value
	 */
	protected function getField($field) {
		return isset($this->record[$field]) ? $this->record[$field] : null;
	}

	/**
	 * Return a map of all the fields for this record.
	 *
	 * @return array A map of field names to field values.
	 */
	public function getAllFields() {
		return $this->record;
	}

	/**
	 * Return the fields that have changed.
	 * The change level affects what the functions defines as "changed":
	 * Level 1 will return strict changes, even !== ones.
	 * Level 2 is more lenient, it will onlr return real data changes, for example a change from 0 to null
	 * would not be included.
	 *
	 * @param boolean $databaseFieldsOnly Get only database fields that have changed
	 * @param int $changeLevel The strictness of what is defined as change
	 */
	public function getChangedFields($databaseFieldsOnly = false, $changeLevel = 1) {
		if($databaseFieldsOnly) {
			$customDatabaseFields = $this->customDatabaseFields();
			$fields = array_intersect_key($this->changed, $customDatabaseFields);
		} else {
			$fields = $this->changed;
		}

		// Filter the list to those of a certain change level
		if($changeLevel > 1) {
			foreach($fields as $name => $level) {
				if($level < $changeLevel) {
					unset($fields[$name]);
				}
			}
		}

		return $fields;
	}

	/**
	 * Set the value of the field
	 * Called by {@link __set()} and any setFieldName() methods you might create.
	 *
	 * @param string $fieldName Name of the field
	 * @param mixed $val New field value
	 */
	function setField($fieldName, $val) {
		$defaults = $this->stat('defaults');
		// if a field is not existing or has strictly changed
		if(!isset($this->record[$fieldName]) || $this->record[$fieldName] !== $val) {
			// TODO Add check for php-level defaults which are not set in the db
			// TODO Add check for hidden input-fields (readonly) which are not set in the db
			if(
				// Only existing fields
				$this->fieldExists($fieldName)
				// Catches "0"==NULL
				&& (isset($this->record[$fieldName]) && (intval($val) != intval($this->record[$fieldName])))
				// Main non type-based check
				&& (isset($this->record[$fieldName]) && $this->record[$fieldName] != $val)
			) {
				// Non-strict check fails, so value really changed, e.g. "abc" != "cde"
				$this->changed[$fieldName] = 2;
			} else {
				// Record change-level 1 if only the type changed, e.g. 0 !== NULL
				$this->changed[$fieldName] = 1;
			}

			// value is always saved back when strict check succeeds
			$this->record[$fieldName] = $val;
		}
	}

	/**
	 * Set the value of the field, using a casting object.
	 * This is useful when you aren't sure that a date is in SQL format, for example.
	 * setCastedField() can also be used, by forms, to set related data.  For example, uploaded images
	 * can be saved into the Image table.
	 *
	 * @param string $fieldName Name of the field
	 * @param mixed $value New field value
	 */
	public function setCastedField($fieldName, $val) {
		if(!$fieldName) {
			user_error("DataObject::setCastedField: Called without a fieldName", E_USER_ERROR);
		}
		$castingHelper = $this->castingHelper($fieldName);
		if($castingHelper) {
			$fieldObj = eval($castingHelper);
			$fieldObj->setVal($val);
			$fieldObj->saveInto($this);
		} else {
			$this->$fieldName = $val;
		}
	}

	/**
	 * Returns true if the given field exists
	 *
	 * @param string $field Name of the field
	 *
	 * @return boolean True if the given field exists
	 */
	public function hasField($field) {
		return array_key_exists($field, $this->record);
	}

	/**
	 * Returns true if the given field exists as a database column
	 *
	 * @param string $field Name of the field
	 *
	 * @return boolean
	 */
	public function hasDatabaseField($field) {
		return array_key_exists($field, $this->databaseFields());
	}

	/**
	 * Returns true if the member is allowed to do the given action.
	 *
	 * @param string $perm The permission to be checked, such as 'View'.
	 * @param Member $member The member whose permissions need checking.  Defaults to the currently logged
	 * in user.
	 *
	 * @return boolean True if the the member is allowed to do the given action
	 */
	function can($perm, $member = null) {
		if(!isset($member)) {
			$member = Member::currentUser();
		}
		if($member && $member->isAdmin()) {
			return true;
		}

		if($this->many_many('Can' . $perm)) {
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
					} else {
						$groups = DataObject::get("Group_Unsecure", "");
					}

					$groupList = implode(', ', $groups->column("ID"));

					$query = new SQLQuery(
						"`Page_Can$perm`.PageID",
						array("`Page_Can$perm`"),
						"GroupID IN ($groupList)");

					$permissionCache[$memberID][$perm] = $query->execute()->column();

					if($perm == "View") {
						$query = new SQLQuery("`SiteTree`.ID", array(
							"`SiteTree`",
							"LEFT JOIN `Page_CanView` ON `Page_CanView`.PageID = `SiteTree`.ID"
							), "`Page_CanView`.PageID IS NULL");

						$unsecuredPages = $query->execute()->column();
						if($permissionCache[$memberID][$perm]) {
							$permissionCache[$memberID][$perm] = array_merge($permissionCache[$memberID][$perm], $unsecuredPages);
						} else {
							$permissionCache[$memberID][$perm] = $unsecuredPages;
						}
					}

					$this->set_uninherited('permissionCache', $permissionCache);
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
	 * Debugging used by Debug::show()
	 *
	 * @return string HTML data representing this object
	 */
	public function debug() {
		$val = "<h3>Database record: $this->class</h3><ul>";
		if($this->record) foreach($this->record as $fieldName => $fieldVal) {
			$val .= "<li style=\"list-style-type: disc; margin-left: 20px\">$fieldName : " . Debug::text($fieldVal) . "</li>";
		}
		$val .= "</ul>";
		return $val;
	}

	/**
	 * Returns the field type of the given field, if it belongs to this class, and not a parent.
	 * Can be used to detect whether the given field exists.
	 * Note that the field type will not include constructor arguments; only the classname.
	 *
	 * @param string $field Name of the field
	 *
	 * @return string The field type of the given field
	 */
	 public function fieldExists($field) {
		if($field == "ID") return "Int";
		if($field == "ClassName" && get_parent_class($this) == "DataObject") return "Enum";
		if($field == "LastEdited" && get_parent_class($this) == "DataObject") return "Datetime";
		if($field == "Created" && get_parent_class($this) == "DataObject") return "Datetime";

		if($field == "Version") return $this->hasExtension('Versioned') ? "Int" : false;
		$fieldMap = $this->uninherited('fieldExists');
		if(!$fieldMap) {
			$fieldMap = $this->uninherited('db', true);
			$has = $this->uninherited('has_one', true);
			if($has) foreach($has as $fieldName => $fieldSchema) {
				$fieldMap[$fieldName . 'ID'] = "Int";
			}
			$this->set_uninherited('fieldExists', $fieldMap);
		}

		return isset($fieldMap[$field]) ? strtok($fieldMap[$field],'(') : null;
	}

	/**
	 * Return the DBField object that represents the given field.
	 * This works similarly to obj() but still returns an object even when the field has no value.
	 *
	 * @param string $fieldName Name of the field
	 *
	 * @return DBField The field as a DBField object
	 */
	public function dbObject($fieldName) {
		$helperPair = $this->castingHelperPair($fieldName);
		$constructor = $helperPair['castingHelper'];

		if($obj = eval($constructor)) {
			$obj->setVal($this->$fieldName);
		}

		return $obj;
	}

	/**
	 * Build a {@link SQLQuery} object to perform the given query.
	 *
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param string $sort A sort expression to be inserted into the ORDER BY clause. If omitted, self::$default_sort will be used.
	 * @param string $limit A limit expression to be inserted into the LIMIT clause.
	 * @param string $join A single join clause. This can be used for filtering, only 1 instance of each DataObject will be returned.
	 * @param boolean $restictClasses Restrict results to only objects of either this class of a subclass of this class
	 * @param string $having A filter to be inserted into the HAVING clause.
	 *
	 * @return SQLQuery Query built.
	 */
	public function buildSQL($filter = "", $sort = "", $limit = "", $join = "", $restrictClasses = true, $having = "") {
		// Find a default sort
		if(!$sort) {
			$sort = $this->stat('default_sort');
		}

		// Get the tables to join to
		$tableClasses = ClassInfo::dataClassesFor($this->class);
		if(!$tableClasses) {
			user_error("DataObject::buildSQL: Can't find data classes (classes linked to tables) for $this->class", E_USER_ERROR);
		}

		$baseClass = array_shift($tableClasses);
		$select = array("`$baseClass`.*");
		
		// If sort contains a function call, let's move the sort clause into a separate selected field.
		// Some versions of MySQL choke if you have a group function referenced directly in the ORDER BY
		if($sort && strpos($sort,'(') !== false) {
			// Sort can be "Col1 DESC|ASC, Col2 DESC|ASC", we need to handle that
			$sortParts = explode(",", $sort);
			
			// If you have select if(X,A,B),C then the array will return 'if(X','A','B)','C'.
			// Turn this into 'if(X,A,B)','C' by counting brackets
			while(list($i,$sortPart) = each($sortParts)) {
				while(substr_count($sortPart,'(') > substr_count($sortPart,')')) {
					list($i,$nextSortPart) = each($sortParts);
					if($i === null) break;
					$sortPart .= ',' . $nextSortPart;
				}
				$lumpedSortParts[] = $sortPart;
			}
			
			foreach($lumpedSortParts as $i => $sortPart) {
				$sortPart = trim($sortPart);
				if(substr(strtolower($sortPart),-5) == ' desc') {
					$select[] = substr($sortPart,0,-5) . " AS _SortColumn{$i}";
					$newSorts[] = "_SortColumn{$i} DESC";
				} else if(substr(strtolower($sortPart),-4) == ' asc') {
					$select[] = substr($sortPart,0,-4) . " AS _SortColumn{$i}";
					$newSorts[] = "_SortColumn{$i} ASC";
				} else {
					$select[] = "$sortPart AS _SortColumn{$i}";
					$newSorts[] = "_SortColumn{$i} ASC";
				}
			}
			
			$sort =  implode(", ", $newSorts);
		}

		// Build our intial query
		$query = new SQLQuery($select, "`$baseClass`", $filter, $sort);

		// Join all the tables
		if($tableClasses) {
			foreach($tableClasses as $tableClass) {
				$query->from[$tableClass] = "LEFT JOIN `$tableClass` ON `$tableClass`.ID = `$baseClass`.ID";
				$query->select[] = "`$tableClass`.*";
			}
		}
		$query->select[] = "`$baseClass`.ID";
		$query->select[] = "if(`$baseClass`.ClassName,`$baseClass`.ClassName,'$baseClass') AS RecordClassName";

		// Get the ClassName values to filter to
		$classNames = ClassInfo::subclassesFor($this->class);

		if(!$classNames) {
			user_error("DataObject::get() Can't find data sub-classes for '$callerClass'");
		}

		// If querying the base class, don't bother filtering on class name
		if($restrictClasses && $this->class != $baseClass) {
			// Get the ClassName values to filter to
			$classNames = ClassInfo::subclassesFor($this->class);
			if(!$classNames) {
				user_error("DataObject::get() Can't find data sub-classes for '$callerClass'");
			}

			$query->where[] = "`$baseClass`.ClassName IN ('" . implode("','", $classNames) . "')";
		}

		if($limit) {
			$query->limit = $limit;
		}

		if($having) {
			$query->having[] = $having;
		}

		if($join) {
			$query->from[] = $join;
			$query->groupby[] = reset($query->from) . ".ID";
		}

		return $query;
	}

	/**
	 * Like {@link buildSQL}, but applies the extension modifications.
	 *
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param string $sort A sort expression to be inserted into the ORDER BY clause. If omitted, self::$default_sort will be used.
	 * @param string $limit A limit expression to be inserted into the LIMIT clause.
	 * @param string $join A single join clause. This can be used for filtering, only 1 instance of each DataObject will be returned.
	 * @param string $having A filter to be inserted into the HAVING clause.
	 *
	 * @return SQLQuery Query built
	 */
	public function extendedSQL($filter = "", $sort = "", $limit = "", $join = "", $having = ""){
		$query = $this->buildSQL($filter, $sort, $limit, $join, true, $having);
		$this->extend('augmentSQL', $query);
		return $query;
	}

	/**
	 * Get a bunch of fields in an HTML LI, like this:
	 *  - name: value
	 *  - name: value
	 *  - name: value
	 *
	 * @return string The fields as an HTML unordered list
	 */
	function listOfFields() {
		$fields = func_get_args();
		$result = "<ul>\n";
		foreach($fields as $field)
			$result .= "<li><b>$field:</b> " . $this->$field . "</li>\n";
		$result .= "</ul>";
		return $result;
	}

	/**
	 * Return all objects matching the filter
	 * sub-classes are automatically selected and included
	 *
	 * @param string $callerClass The class of objects to be returned
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param string $sort A sort expression to be inserted into the ORDER BY clause.  If omitted, self::$default_sort will be used.
	 * @param string $join A single join clause.  This can be used for filtering, only 1 instance of each DataObject will be returned.
	 * @param string $limit A limit expression to be inserted into the LIMIT clause.
	 * @param string $containerClass The container class to return the results in.
	 *
	 * @return mixed The objects matching the filter, in the class specified by $containerClass
	 */
	public static function get($callerClass, $filter = "", $sort = "", $join = "", $limit = "", $containerClass = "DataObjectSet") {
		return singleton($callerClass)->instance_get($filter, $sort, $join, $limit, $containerClass);
	}

	/**
	 * The internal function that actually performs the querying for get().
	 * DataObject::get("Table","filter") is the same as singleton("Table")->instance_get("filter")
	 *
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param string $sort A sort expression to be inserted into the ORDER BY clause.  If omitted, self::$default_sort will be used.
	 * @param string $join A single join clause.  This can be used for filtering, only 1 instance of each DataObject will be returned.
	 * @param string $limit A limit expression to be inserted into the LIMIT clause.
	 * @param string $containerClass The container class to return the results in.
	 *
	 * @return mixed The objects matching the filter, in the class specified by $containerClass
	 */
	public function instance_get($filter = "", $sort = "", $join = "", $limit="", $containerClass = "DataObjectSet") {
		$query = $this->extendedSQL($filter, $sort, $limit, $join);
		$records = $query->execute();
		
		$ret = $this->buildDataObjectSet($records, $containerClass, $query, $this->class);
		if($ret) $ret->parseQueryLimit($query);

		return $ret;
	}

	/**
	 * Take a database {@link Query} and instanciate an object for each record.
	 *
	 * @param Query|array $records The database records, a {@link Query} object or an array of maps.
	 * @param string $containerClass The class to place all of the objects into.
	 *
	 * @return mixed The new objects in an object of type $containerClass
	 */
	function buildDataObjectSet($records, $containerClass = "DataObjectSet", $query = null, $baseClass = null) {
		foreach($records as $record) {
			if(!$record['RecordClassName']) {
				$record['RecordClassName'] = $record['ClassName'];
			}
			if(class_exists($record['RecordClassName'])) {
				$results[] = new $record['RecordClassName']($record);
			} else {
				$results[] = new $baseClass($record);
			}
		}

		if(isset($results)) {
			return new $containerClass($results);
		}
	}

 	/**
 	 * A cache used by get_one.
 	 * @var array
 	 */
 	protected static $cache_get_one;

	/**
	 * Return the first item matching the given query.
	 * All calls to get_one() are cached.
	 *
	 * @param string $callerClass The class of objects to be returned
	 * @param string $filter A filter to be inserted into the WHERE clause
	 * @param boolean $cache Use caching
	 * @param string $orderby A sort expression to be inserted into the ORDER BY clause.
	 *
	 * @return DataObject The first item matching the query
	 */
	public static function get_one($callerClass, $filter = "", $cache = true, $orderby = "") {
		$sum = md5("{$filter}_{$orderby}");
		if(!$cache || !isset(DataObject::$cache_get_one[$callerClass][$sum]) || !DataObject::$cache_get_one[$callerClass][$sum] || DataObject::$cache_get_one[$callerClass][$sum]->destroyed) {
			$item = singleton($callerClass)->instance_get_one($filter, $orderby);
			if($cache) {
				DataObject::$cache_get_one[$callerClass][$sum] = $item;
				if(!DataObject::$cache_get_one[$callerClass][$sum]) {
					DataObject::$cache_get_one[$callerClass][$sum] = false;
				}
			}
		}
		return $cache ? DataObject::$cache_get_one[$callerClass][$sum] : $item;
	}

	/**
	 * Flush the cached results for get_one()
	 */
	public function flushCache() {
		if($this->class == 'DataObject') {
			DataObject::$cache_get_one = array();
			return;			
		}

		$classes = ClassInfo::ancestry($this->class);
		foreach($classes as $class) {
			// If someone else has called get_one and flushCache() is called, then that object will be destroyed.
			// Not very friendly.  We need a better way of dealing with PHP's garbage collection limitations.
			// Until then, this line is being commented out.
			// if(DataObject::$cache_get_one[$class]) foreach(DataObject::$cache_get_one[$class] as $obj) if($obj) $obj->destroy();
			DataObject::$cache_get_one[$class] = null;
		}
	}

	/**
	 * Does the hard work for get_one()
	 *
	 * @param string $filter A filter to be inserted into the WHERE clause
	 * @param string $orderby A sort expression to be inserted into the ORDER BY clause.
	 *
	 * @return DataObject The first item matching the query
	 */
	public function instance_get_one($filter, $orderby = null) {
		$query = $this->buildSQL($filter);
		$query->limit = "1";
		if($orderby) {
			$query->orderby = $orderby;
		}

		$this->extend('augmentSQL', $query);

		$records = $query->execute();
		$records->rewind();
		$record = $records->current();

		if($record) {
			// Mid-upgrade, the database can have invalid RecordClassName values that need to be guarded against.
			if(class_exists($record['RecordClassName'])) {
				$record = new $record['RecordClassName']($record);
			} else {
				$record = new $this->class($record);
			}

			// Rather than restrict classes at the SQL-query level, we now check once the object has been instantiated
			// This lets us check up on weird errors where the class has been incorrectly set, and give warnings to our
			// developers
			return $record;
		}
	}

	/**
	 * Return the SiteTree object with the given URL segment.
	 *
	 * @param string $urlSegment The URL segment, eg 'home'
	 *
	 * @return SiteTree The object with the given URL segment
	 */
	public static function get_by_url($urlSegment) {
		return DataObject::get_one("SiteTree", "URLSegment = '" . addslashes((string) $urlSegment) . "'");
	}

	/**
	 * Return the given element, searching by ID
	 *
	 * @param string $callerClass The class of the object to be returned
	 * @param int $id The id of the element
	 *
	 * @return DataObject The element
	 */
	public static function get_by_id($callerClass, $id) {
		if(is_numeric($id)) {
			if(singleton($callerClass) instanceof DataObject) {
				$tableClasses = ClassInfo::dataClassesFor($callerClass);
				$baseClass = array_shift($tableClasses);
				return DataObject::get_one($callerClass,"`$baseClass`.`ID` = $id");
				
			// This simpler code will be used by non-DataObject classes that implement DataObjectInterface
			} else {
				return DataObject::get_one($callerClass,"`ID` = $id");
			}
		} else {
			user_error("DataObject::get_by_id passed a non-numeric ID #$id", E_USER_WARNING);
		}
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
	 */
	public function requireTable() {
		// Only build the table if we've actually got fields
		$fields = $this->databaseFields();
    	$indexes = $this->databaseIndexes();

		if($fields) {
			DB::requireTable($this->class, $fields, $indexes);
		} else {
			DB::dontRequireTable($this->class);
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
				if($extras[$relationship]) {
					$manymanyFields = array_merge($manymanyFields, $extras[$relationship]);
				}

				// Build index list
				$manymanyIndexes = array(
					"{$this->class}ID" => true,
					(($this->class == $childClass) ? "ChildID" : "{$childClass}ID") => true,
				);

				DB::requireTable("{$this->class}_$relationship", $manymanyFields, $manymanyIndexes);
			}
		}

		// Let any extentions make their own database fields
		$this->extend('augmentDatabase', $dummy);
	}

	/**
	 * Add default records to database. This function is called whenever the
	 * database is built, after the database tables have all been created. Overload
	 * this to add default records when the database is built, but make sure you
	 * call parent::requireDefaultRecords().
	 */
	public function requireDefaultRecords() {
		$defaultRecords = $this->stat('default_records');

		if(!empty($defaultRecords)) {
			// Populate with default data if table is empty
			$baseClass = ClassInfo::baseDataClass($this->class);
			if($baseClass) {
				$hasData = (DB::query("SELECT ID FROM `{$baseClass}`")->value());
				if(!$hasData) {
					foreach($defaultRecords as $record) {
						$obj = new $baseClass($record);
						$obj->write();
					}
					Database::alteration_message("Added default records to $baseClass table","created");			
				}
			}
		}

		// Let any extentions make their own database default data
		$this->extend('augmentDefaultRecords', $dummy);
	}

	/**
	 * Return the complete set of database fields, including Created, LastEdited and ClassName.
	 *
	 * @return array A map of field name to class of all databases fields on this object
	 *
	 */
	public function databaseFields() {
		// For base tables, add a classname field
		if($this->parentClass() == 'DataObject') {
			$childClasses = ClassInfo::subclassesFor($this->class);
			return array_merge(
				array(
					"ClassName" => "Enum('" . implode(", ", $childClasses) . "')",
					"Created" => "Datetime",
					"LastEdited" => "Datetime",
				),
				(array)$this->customDatabaseFields()
			);

		// Child table
		} else {
			return $this->customDatabaseFields();
		}
	}

	/**
	 * Get the custom database fields for this object, from self::$db and self::$has_one
	 */
	public function customDatabaseFields() {
		$db = $this->uninherited('db',true);
		$has_one = $this->uninherited('has_one',true);

		$def = $db;
		if($has_one) {
			foreach($has_one as $field => $joinTo) {
				$def[$field . 'ID'] = "Int";
			}
		}

		return $def;
	}

    /**
    * @return boolean True if the object is in the database
    */
    public function isInDB() {
        return is_numeric( $this->ID ) && $this->ID > 0;
    }

	/**
	 * Sets a 'context object' that can be used to provide hints about how to process a particular get / get_one request.  
	 * In particular, DataObjectDecorators can use this to amend queries more effectively.
	 * Care must be taken to unset the context object after you're done with it, otherwise you will have a stale context,
	 * which could cause horrible bugs.
	 */
	public static function set_context_obj($obj) {
		if($obj && self::$context_obj) user_error("Dataobject::set_context_obj called when there is already a context.", E_USER_WARNING);
		self::$context_obj = $obj;
	}
	
	/**
	 * Retrieve the current context object.
	 */
	public static function context_obj() {
		return self::$context_obj;
	}

	protected static $context_obj = null;
	
	
	//-------------------------------------------------------------------------------------------//

	/**
	 * Database field definitions.
	 * This is a map from field names to field type. The field
	 * type should be a class that extends .
	 * @var array
	 */
	public static $db = null;

	/**
	 * Use a casting object for a field. This is a map from
	 * field name to class name of the casting object.
	 * @var array
	 */
	public static $casting = array(
		"LastEdited" => "Datetime",
		"Created" => "Datetime",
	);

	/**
	 * If a field is in this array, then create a database index
	 * on that field. This is a map from fieldname to index type.
	 * @var array
	 */
	public static $indexes = null;

	/**
	 * Inserts standard column-values when a DataObject
	 * is instanciated. Does not insert default records {@see $default_records}.
	 * This is a map from classname to default value.
	 * @var array
	 */
	public static $defaults = null;

	/**
	 * Multidimensional array which inserts default data into the database
	 * on a db/build-call as long as the database-table is empty. Please use this only
	 * for simple constructs, not for SiteTree-Objects etc. which need special
	 * behaviour such as publishing and ParentNodes.
	 *
	 * Example:
	 * array(
	 * 	array('Title' => "DefaultPage1", 'PageTitle' => 'page1'),
	 * 	array('Title' => "DefaultPage2")
	 * ).
	 *
	 * @var array
	 */
	public static $default_records = null;

	/**
	 * one-to-one relationship definitions.
	 * This is a map from component name to data type.
	 *	@var array
	 */
	public static $has_one = null;

	/**
	 * one-to-many relationship definitions.
	 * This is a map from component name to data type.
	 * 
	 * Caution: Because this doesn't define any data structure itself, you should
	 * specify a $has_one relationship on the other end of the relationship. 
	 * Also, if the $has_one relationship on the other end has multiple
	 * definitions of this class (e.g. two different relationships to the Member
	 * object), then you need to write a custom accessor (e.g. overload the
	 * function from the key of this array), because sapphire won't know which
	 * to access.
	 * 
	 * @var array
	 */
	public static $has_many = null;

	/**
	 * many-many relationship definitions.
	 * This is a map from component name to data type.
	 * @var array
	 */
	public static $many_many = null;

	/**
	 * Extra fields to include on the connecting many-many table.
	 * This is a map from field name to field type.
	 * @var array
	 */
	public static $many_many_extraFields = null;

	/**
	 * The inverse side of a many-many relationship.
	 * This is a map from component name to data type.
	 * @var array
	 */
	public static $belongs_many_many = null;

	/**
	 * The default sort expression. This will be inserted in the ORDER BY
	 * clause of a SQL query if no other sort expression is provided.
	 * @var string
	 */
	public static $default_sort = null;
}


?>
