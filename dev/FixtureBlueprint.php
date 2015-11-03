<?php
/**
 * A blueprint on how to create instances of a certain {@link DataObject} subclass.
 *
 * Relies on a {@link FixtureFactory} to manage database relationships between instances,
 * and manage the mappings between fixture identifiers and their database IDs.
 *
 * @package framework
 * @subpackage core
 */
class FixtureBlueprint {

	/**
	 * @var array Map of field names to values. Supersedes {@link DataObject::$defaults}.
	 */
	protected $defaults = array();

	/**
	 * @var String Arbitrary name by which this fixture type can be referenced.
	 */
	protected $name;

	/**
	 * @var String Subclass of {@link DataObject}
	 */
	protected $class;

	/**
	 * @var array
	 */
	protected $callbacks = array(
		'beforeCreate' => array(),
		'afterCreate' => array(),
	);

	/** @config */
	private static $dependencies = array(
		'factory' => '%$FixtureFactory'
	);

	/**
	 * @param String $name
	 * @param String $class Defaults to $name
	 * @param array $defaults
	 */
	public function __construct($name, $class = null, $defaults = array()) {
		if(!$class) $class = $name;

		if(!is_subclass_of($class, 'DataObject')) {
			throw new InvalidArgumentException(sprintf(
				'Class "%s" is not a valid subclass of DataObject',
				$class
			));
		}

		$this->name = $name;
		$this->class = $class;
		$this->defaults = $defaults;
	}

	/**
	 * @param String $identifier Unique identifier for this fixture type
	 * @param Array $data Map of property names to their values.
	 * @param Array $fixtures Map of fixture names to an associative array of their in-memory
	 *                        identifiers mapped to their database IDs. Used to look up
	 *                        existing fixtures which might be referenced in the $data attribute
	 *                        via the => notation.
	 * @return DataObject
	 */
	public function createObject($identifier, $data = null, $fixtures = null) {
		// We have to disable validation while we import the fixtures, as the order in
		// which they are imported doesnt guarantee valid relations until after the import is complete.
		$validationenabled = Config::inst()->get('DataObject', 'validation_enabled');
		Config::inst()->update('DataObject', 'validation_enabled', false);

		$this->invokeCallbacks('beforeCreate', array($identifier, &$data, &$fixtures));

		try {
			$class = $this->class;
			$obj = DataModel::inst()->$class->newObject();

			// If an ID is explicitly passed, then we'll sort out the initial write straight away
			// This is just in case field setters triggered by the population code in the next block
			// Call $this->write().  (For example, in FileTest)
			if(isset($data['ID'])) {
				$obj->ID = $data['ID'];

				// The database needs to allow inserting values into the foreign key column (ID in our case)
				$conn = DB::get_conn();
				if(method_exists($conn, 'allowPrimaryKeyEditing')) {
					$conn->allowPrimaryKeyEditing(ClassInfo::baseDataClass($class), true);
				}
				$obj->write(false, true);
				if(method_exists($conn, 'allowPrimaryKeyEditing')) {
					$conn->allowPrimaryKeyEditing(ClassInfo::baseDataClass($class), false);
				}
			}

			// Populate defaults
			if($this->defaults) foreach($this->defaults as $fieldName => $fieldVal) {
				if(isset($data[$fieldName]) && $data[$fieldName] !== false) continue;

				if(is_callable($fieldVal)) {
					$obj->$fieldName = $fieldVal($obj, $data, $fixtures);
				} else {
					$obj->$fieldName = $fieldVal;
				}
			}

			// Populate overrides
			if($data) foreach($data as $fieldName => $fieldVal) {
				// Defer relationship processing
				if(
					$obj->manyManyComponent($fieldName)
					|| $obj->hasManyComponent($fieldName)
					|| $obj->hasOneComponent($fieldName)
				) {
					continue;
				}

				$this->setValue($obj, $fieldName, $fieldVal, $fixtures);
			}

			$obj->write();

			// Save to fixture before relationship processing in case of reflexive relationships
			if(!isset($fixtures[$class])) {
				$fixtures[$class] = array();
			}
			$fixtures[$class][$identifier] = $obj->ID;

			// Populate all relations
			if($data) foreach($data as $fieldName => $fieldVal) {
				if($obj->manyManyComponent($fieldName) || $obj->hasManyComponent($fieldName)) {
					$obj->write();

					$parsedItems = array();

					if(is_array($fieldVal)) {
						// handle lists of many_many relations. Each item can
						// specify the many_many_extraFields against each
						// related item.
						foreach($fieldVal as $relVal) {
							$item = key($relVal);
							$id = $this->parseValue($item, $fixtures);
							$parsedItems[] = $id;

							array_shift($relVal);

							$obj->getManyManyComponents($fieldName)->add(
								$id, $relVal
							);
						}
					} else {
						$items = preg_split('/ *, */',trim($fieldVal));

						foreach($items as $item) {
							// Check for correct format: =><relationname>.<identifier>.
							// Ignore if the item has already been replaced with a numeric DB identifier
							if(!is_numeric($item) && !preg_match('/^=>[^\.]+\.[^\.]+/', $item)) {
								throw new InvalidArgumentException(sprintf(
									'Invalid format for relation "%s" on class "%s" ("%s")',
									$fieldName,
									$class,
									$item
								));
							}

							$parsedItems[] = $this->parseValue($item, $fixtures);
						}

						if($obj->hasManyComponent($fieldName)) {
							$obj->getComponents($fieldName)->setByIDList($parsedItems);
						} elseif($obj->manyManyComponent($fieldName)) {
							$obj->getManyManyComponents($fieldName)->setByIDList($parsedItems);
						}
					}
				} else {
					$hasOneField = preg_replace('/ID$/', '', $fieldName);
					if($className = $obj->hasOneComponent($hasOneField)) {
						$obj->{$hasOneField.'ID'} = $this->parseValue($fieldVal, $fixtures, $fieldClass);
						// Inject class for polymorphic relation
						if($className === 'DataObject') {
							$obj->{$hasOneField.'Class'} = $fieldClass;
						}
					}
				}
			}
			$obj->write();

			// If LastEdited was set in the fixture, set it here
			if($data && array_key_exists('LastEdited', $data)) {
				$this->overrideField($obj, 'LastEdited', $data['LastEdited'], $fixtures);
			}

			// Ensure Folder objects exist physically, as otherwise future File fixtures can't detect them
			if($obj instanceof Folder) {
				Filesystem::makeFolder($obj->getFullPath());
			}
		} catch(Exception $e) {
			Config::inst()->update('DataObject', 'validation_enabled', $validationenabled);
			throw $e;
		}

		Config::inst()->update('DataObject', 'validation_enabled', $validationenabled);

		$this->invokeCallbacks('afterCreate', array($obj, $identifier, &$data, &$fixtures));

		return $obj;
	}

	/**
	 * @param Array $defaults
	 */
	public function setDefaults($defaults) {
		$this->defaults = $defaults;
		return $this;
	}

	/**
	 * @return Array
	 */
	public function getDefaults() {
		return $this->defaults;
	}

	/**
	 * @return String
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * See class documentation.
	 *
	 * @param String $type
	 * @param callable $callback
	 */
	public function addCallback($type, $callback) {
		if(!array_key_exists($type, $this->callbacks)) {
			throw new InvalidArgumentException(sprintf('Invalid type "%s"', $type));
		}

		$this->callbacks[$type][] = $callback;
		return $this;
	}

	/**
	 * @param String $type
	 * @param callable $callback
	 */
	public function removeCallback($type, $callback) {
		$pos = array_search($callback, $this->callbacks[$type]);
		if($pos !== false) unset($this->callbacks[$type][$pos]);

		return $this;
	}

	protected function invokeCallbacks($type, $args = array()) {
		foreach($this->callbacks[$type] as $callback) {
			call_user_func_array($callback, $args);
		}
	}

	/**
	 * Parse a value from a fixture file.  If it starts with =>
	 * it will get an ID from the fixture dictionary
	 *
	 * @param string $fieldVal
	 * @param array $fixtures See {@link createObject()}
	 * @param string $class If the value parsed is a class relation, this parameter
	 * will be given the value of that class's name
	 * @return string Fixture database ID, or the original value
	 */
	protected function parseValue($value, $fixtures = null, &$class = null) {
		if(substr($value,0,2) == '=>') {
			// Parse a dictionary reference - used to set foreign keys
			list($class, $identifier) = explode('.', substr($value,2), 2);

			if($fixtures && !isset($fixtures[$class][$identifier])) {
				throw new InvalidArgumentException(sprintf(
					'No fixture definitions found for "%s"',
					$value
				));
			}

			return $fixtures[$class][$identifier];
		} else {
			// Regular field value setting
			return $value;
		}
	}

	protected function setValue($obj, $name, $value, $fixtures = null) {
		$obj->$name = $this->parseValue($value, $fixtures);
	}

	protected function overrideField($obj, $fieldName, $value, $fixtures = null) {
		$table = ClassInfo::table_for_object_field(get_class($obj), $fieldName);
		$value = $this->parseValue($value, $fixtures);

		DB::manipulate(array(
			$table => array(
				"command" => "update", "id" => $obj->ID,
				"fields" => array($fieldName => $value)
			)
		));
		$obj->$fieldName = $value;
	}

}