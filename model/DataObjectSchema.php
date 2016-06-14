<?php

use SilverStripe\Framework\Core\Configurable;
use SilverStripe\Framework\Core\Injectable;
use SilverStripe\Model\FieldType\DBComposite;

/**
 * Provides dataobject and database schema mapping functionality
 */
class DataObjectSchema {
	use Injectable;
	use Configurable;

	/**
	 * Default separate for table namespaces. Can be set to any string for
	 * databases that do not support some characters.
	 *
	 * Defaults to \ to to conform to 3.x convention.
	 *
	 * @config
	 * @var string
	 */
	private static $table_namespace_separator = '\\';

	/**
	 * Cache of database fields
	 *
	 * @var array
	 */
	protected $databaseFields = [];

	/**
	 * Cache of composite database field
	 *
	 * @var array
	 */
	protected $compositeFields = [];

	/**
	 * Cache of table names
	 *
	 * @var array
	 */
	protected $tableNames = [];

	/**
	 * Clear cached table names
	 */
	public function reset() {
		$this->tableNames = [];
		$this->databaseFields = [];
		$this->compositeFields = [];
	}

	/**
	 * Get all table names
	 *
	 * @return array
	 */
	public function getTableNames() {
		$this->cacheTableNames();
		return $this->tableNames;
	}

	/**
	 * Given a DataObject class and a field on that class, determine the appropriate SQL for
	 * selecting / filtering on in a SQL string. Note that $class must be a valid class, not an
	 * arbitrary table.
	 *
	 * The result will be a standard ANSI-sql quoted string in "Table"."Column" format.
	 *
	 * @param string $class Class name (not a table).
	 * @param string $field Name of field that belongs to this class (or a parent class)
	 * @return string The SQL identifier string for the corresponding column for this field
	 */
	public function sqlColumnForField($class, $field) {
		$table = $this->tableForField($class, $field);
		if(!$table) {
			throw new InvalidArgumentException("\"{$field}\" is not a field on class \"{$class}\"");
		}
		return "\"{$table}\".\"{$field}\"";
	}

	/**
	 * Get table name for the given class.
	 *
	 * Note that this does not confirm a table actually exists (or should exist), but returns
	 * the name that would be used if this table did exist.
	 *
	 * @param string $class
	 * @return string Returns the table name, or null if there is no table
	 */
	public function tableName($class) {
		$tables = $this->getTableNames();
		$class = ClassInfo::class_name($class);
		if(isset($tables[$class])) {
			return $tables[$class];
		}
		return null;
	}
	/**
	 * Returns the root class (the first to extend from DataObject) for the
	 * passed class.
	 *
	 * @param string|object $class
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function baseDataClass($class) {
		$class = ClassInfo::class_name($class);
		$current = $class;
		while ($next = get_parent_class($current)) {
			if ($next === 'DataObject') {
				return $current;
			}
			$current = $next;
		}
		throw new InvalidArgumentException("$class is not a subclass of DataObject");
	}

	/**
	 * Get the base table
	 *
	 * @param string|object $class
	 * @return string
	 */
	public function baseDataTable($class) {
		return $this->tableName($this->baseDataClass($class));
	}

	/**
	 * Find the class for the given table
	 *
	 * @param string $table
	 * @return string|null The FQN of the class, or null if not found
	 */
	public function tableClass($table) {
		$tables = $this->getTableNames();
		$class = array_search($table, $tables, true);
		if($class) {
			return $class;
		}

		// If there is no class for this table, strip table modifiers (e.g. _Live / _versions)
		// from the end and re-attempt a search.
		if(preg_match('/^(?<class>.+)(_[^_]+)$/i', $table, $matches)) {
			$table = $matches['class'];
			$class = array_search($table, $tables, true);
			if($class) {
				return $class;
			}
		}
		return null;
	}

	/**
	 * Cache all table names if necessary
	 */
	protected function cacheTableNames() {
		if($this->tableNames) {
			return;
		}
		$this->tableNames = [];
		foreach(ClassInfo::subclassesFor('DataObject') as $class) {
			if($class === 'DataObject') {
				continue;
			}
			$table = $this->buildTableName($class);

			// Check for conflicts
			$conflict = array_search($table, $this->tableNames, true);
			if($conflict) {
				throw new LogicException(
					"Multiple classes (\"{$class}\", \"{$conflict}\") map to the same table: \"{$table}\""
				);
			}
			$this->tableNames[$class] = $table;
		}
	}

	/**
	 * Generate table name for a class.
	 *
	 * Note: some DB schema have a hard limit on table name length. This is not enforced by this method.
	 * See dev/build errors for details in case of table name violation.
	 *
	 * @param string $class
	 * @return string
	 */
	protected function buildTableName($class) {
		$table = Config::inst()->get($class, 'table_name', Config::UNINHERITED);

		// Generate default table name
		if(!$table) {
			$separator = $this->config()->table_namespace_separator;
			$table = str_replace('\\', $separator, trim($class, '\\'));
		}

		return $table;
	}

	/**
	 * Return the complete map of fields to specification on this object, including fixed_fields.
	 * "ID" will be included on every table.
	 *
	 * @param string $class Class name to query from
	 * @return array Map of fieldname to specification, similiar to {@link DataObject::$db}.
	 */
	public function databaseFields($class) {
		$class = ClassInfo::class_name($class);
		if($class === 'DataObject') {
			return [];
		}
		$this->cacheDatabaseFields($class);
		return $this->databaseFields[$class];
	}

	/**
	 * Returns a list of all the composite if the given db field on the class is a composite field.
	 * Will check all applicable ancestor classes and aggregate results.
	 *
	 * Can be called directly on an object. E.g. Member::composite_fields(), or Member::composite_fields(null, true)
	 * to aggregate.
	 *
	 * Includes composite has_one (Polymorphic) fields
	 *
	 * @param string $class Name of class to check
	 * @param bool $aggregated Include fields in entire hierarchy, rather than just on this table
	 * @return array List of composite fields and their class spec
	 */
	public function compositeFields($class, $aggregated = true) {
		$class = ClassInfo::class_name($class);
		if($class === 'DataObject') {
			return [];
		}
		$this->cacheDatabaseFields($class);

		// Get fields for this class
		$compositeFields = $this->compositeFields[$class];
		if(!$aggregated) {
			return $compositeFields;
		}

		// Recursively merge
		$parentFields = $this->compositeFields(get_parent_class($class));
		return array_merge($compositeFields, $parentFields);
	}

	/**
	 * Cache all database and composite fields for the given class.
	 * Will do nothing if already cached
	 *
	 * @param string $class Class name to cache
	 */
	protected function cacheDatabaseFields($class) {
		// Skip if already cached
		if (isset($this->databaseFields[$class]) && isset($this->compositeFields[$class])) {
			return;
		}
		$compositeFields = array();
		$dbFields = array();

		// Ensure fixed fields appear at the start
		$fixedFields = DataObject::config()->fixed_fields;
		if(get_parent_class($class) === 'DataObject') {
			// Merge fixed with ClassName spec and custom db fields
			$dbFields = $fixedFields;
		} else {
			$dbFields['ID'] = $fixedFields['ID'];
		}

		// Check each DB value as either a field or composite field
		$db = Config::inst()->get($class, 'db', Config::UNINHERITED) ?: array();
		foreach($db as $fieldName => $fieldSpec) {
			$fieldClass = strtok($fieldSpec, '(');
			if(singleton($fieldClass) instanceof DBComposite) {
				$compositeFields[$fieldName] = $fieldSpec;
			} else {
				$dbFields[$fieldName] = $fieldSpec;
			}
		}

		// Add in all has_ones
		$hasOne = Config::inst()->get($class, 'has_one', Config::UNINHERITED) ?: array();
		foreach($hasOne as $fieldName => $hasOneClass) {
			if($hasOneClass === 'DataObject') {
				$compositeFields[$fieldName] = 'PolymorphicForeignKey';
			} else {
				$dbFields["{$fieldName}ID"] = 'ForeignKey';
			}
		}

		// Merge composite fields into DB
		foreach($compositeFields as $fieldName => $fieldSpec) {
			$fieldObj = Object::create_from_string($fieldSpec, $fieldName);
			$fieldObj->setTable($class);
			$nestedFields = $fieldObj->compositeDatabaseFields();
			foreach($nestedFields as $nestedName => $nestedSpec) {
				$dbFields["{$fieldName}{$nestedName}"] = $nestedSpec;
			}
		}

		// Prevent field-less tables
		if(count($dbFields) < 2) {
			$dbFields = [];
		}

		// Return cached results
		$this->databaseFields[$class] = $dbFields;
		$this->compositeFields[$class] = $compositeFields;
	}

	/**
	 * Returns the table name in the class hierarchy which contains a given
	 * field column for a {@link DataObject}. If the field does not exist, this
	 * will return null.
	 *
	 * @param string $candidateClass
	 * @param string $fieldName
	 * @return string
	 */
	public function tableForField($candidateClass, $fieldName) {
		$class = $this->classForField($candidateClass, $fieldName);
		if($class) {
			return $this->tableName($class);
		}
		return null;
	}

	/**
	 * Returns the class name in the class hierarchy which contains a given
	 * field column for a {@link DataObject}. If the field does not exist, this
	 * will return null.
	 *
	 * @param string $candidateClass
	 * @param string $fieldName
	 * @return string
	 */
	public function classForField($candidateClass, $fieldName)  {
		// normalise class name
		$candidateClass = ClassInfo::class_name($candidateClass);
		if($candidateClass === 'DataObject') {
			return null;
		}

		// Short circuit for fixed fields
		$fixed = DataObject::config()->fixed_fields;
		if(isset($fixed[$fieldName])) {
			return $this->baseDataClass($candidateClass);
		}

		// Find regular field
		while($candidateClass) {
			$fields = $this->databaseFields($candidateClass);
			if(isset($fields[$fieldName])) {
				return $candidateClass;
			}
			$candidateClass = get_parent_class($candidateClass);
		}
		return null;
	}
}
