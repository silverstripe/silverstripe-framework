<?php

namespace SilverStripe\Dev;

use Exception;
use InvalidArgumentException;
use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * A blueprint on how to create instances of a certain {@link DataObject} subclass.
 *
 * Relies on a {@link FixtureFactory} to manage database relationships between instances,
 * and manage the mappings between fixture identifiers and their database IDs.
 */
class FixtureBlueprint
{

    /**
     * @var array Map of field names to values. Supersedes {@link DataObject::$defaults}.
     */
    protected $defaults = [];

    /**
     * @var String Arbitrary name by which this fixture type can be referenced.
     */
    protected $name;

    /**
     * @var String Subclass of {@link DataObject}
     */
    protected $class;

    private FixtureFactory $factory;

    /**
     * @var array
     */
    protected $callbacks = [
        'beforeCreate' => [],
        'afterCreate' => [],
    ];

    /** @config */
    private static $dependencies = [
        'factory' => '%$' . FixtureFactory::class,
    ];

    /**
     * @param string $name
     * @param string $class Defaults to $name
     * @param array $defaults
     */
    public function __construct($name, $class = null, $defaults = [])
    {
        if (!$class) {
            $class = $name;
        }

        if (!is_subclass_of($class, DataObject::class)) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" is not a valid subclass of DataObject',
                $class
            ));
        }

        $this->name = $name;
        $this->class = $class;
        $this->defaults = $defaults;
    }

    public function getFactory(): FixtureFactory
    {
        return $this->factory;
    }

    public function setFactory(FixtureFactory $factory): static
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @param string $identifier Unique identifier for this fixture type
     * @param array $data Map of property names to their values.
     * @param array $fixtures Map of fixture names to an associative array of their in-memory
     *                        identifiers mapped to their database IDs. Used to look up
     *                        existing fixtures which might be referenced in the $data attribute
     *                        via the => notation.
     * @return DataObject
     * @throws Exception
     */
    public function createObject($identifier, $data = null, $fixtures = null)
    {
        // We have to disable validation while we import the fixtures, as the order in
        // which they are imported doesnt guarantee valid relations until after the import is complete.
        // Also disable filesystem manipulations
        Config::nest();
        Config::modify()->set(DataObject::class, 'validation_enabled', false);
        Config::modify()->set(File::class, 'update_filesystem', false);

        $this->invokeCallbacks('beforeCreate', [$identifier, &$data, &$fixtures]);

        try {
            $class = $this->class;
            $schema = DataObject::getSchema();
            $obj = Injector::inst()->create($class);

            // If an ID is explicitly passed, then we'll sort out the initial write straight away
            // This is just in case field setters triggered by the population code in the next block
            // Call $this->write().  (For example, in FileTest)
            if (isset($data['ID'])) {
                $obj->ID = $data['ID'];

                // The database needs to allow inserting values into the foreign key column (ID in our case)
                $conn = DB::get_conn();
                $baseTable = DataObject::getSchema()->baseDataTable($class);
                if (method_exists($conn, 'allowPrimaryKeyEditing')) {
                    $conn->allowPrimaryKeyEditing($baseTable, true);
                }
                $obj->write(false, true);
                if (method_exists($conn, 'allowPrimaryKeyEditing')) {
                    $conn->allowPrimaryKeyEditing($baseTable, false);
                }
            }

            // Populate defaults
            if ($this->defaults) {
                foreach ($this->defaults as $fieldName => $fieldVal) {
                    if (isset($data[$fieldName]) && $data[$fieldName] !== false) {
                        continue;
                    }

                    if (!is_string($fieldVal) && is_callable($fieldVal)) {
                        $obj->$fieldName = $fieldVal($obj, $data, $fixtures);
                    } else {
                        $obj->$fieldName = $fieldVal;
                    }
                }
            }

            // Populate overrides
            if ($data) {
                foreach ($data as $fieldName => $fieldVal) {
                    if ($schema->manyManyComponent($class, $fieldName)
                        || $schema->hasManyComponent($class, $fieldName)
                        || $schema->hasOneComponent($class, $fieldName)
                    ) {
                        continue;
                    }

                    $this->setValue($obj, $fieldName, $fieldVal, $fixtures);
                }
            }

            $obj->write();

            // Save to fixture before relationship processing in case of reflexive relationships
            if (!isset($fixtures[$class])) {
                $fixtures[$class] = [];
            }
            $fixtures[$class][$identifier] = $obj->ID;

            // Populate all relations
            if ($data) {
                foreach ($data as $fieldName => $fieldVal) {
                    $isManyMany = $schema->manyManyComponent($class, $fieldName);
                    $isHasMany = $schema->hasManyComponent($class, $fieldName);
                    if ($isManyMany && $isHasMany) {
                        throw new InvalidArgumentException("$fieldName is both many_many and has_many");
                    }
                    if ($isManyMany || $isHasMany) {
                        $obj->write();

                        // Many many components need a little extra work to extract extrafields
                        if (is_array($fieldVal) && $isManyMany) {
                            // handle lists of many_many relations. Each item can
                            // specify the many_many_extraFields against each
                            // related item.
                            foreach ($fieldVal as $relVal) {
                                // Check for many_many_extrafields
                                $extrafields = [];
                                if (is_array($relVal)) {
                                    // Item is either first row, or key in yet another nested array
                                    $item = key($relVal ?? []);
                                    if (is_array($relVal[$item]) && count($relVal ?? []) === 1) {
                                        // Extra fields from nested array
                                        $extrafields = $relVal[$item];
                                    } else {
                                        // Extra fields from subsequent items
                                        array_shift($relVal);
                                        $extrafields = $relVal;
                                    }
                                } else {
                                    $item = $relVal;
                                }
                                $id = $this->parseValue($item, $fixtures);

                                $obj->getManyManyComponents($fieldName)->add(
                                    $id,
                                    $extrafields
                                );
                            }
                        } else {
                            $items = is_array($fieldVal)
                            ? $fieldVal
                            : preg_split('/ *, */', trim($fieldVal ?? ''));

                            $parsedItems = [];
                            foreach ($items as $item) {
                                // Check for correct format: =><relationname>.<identifier>.
                                // Ignore if the item has already been replaced with a numeric DB identifier
                                if (!is_numeric($item) && !preg_match('/^=>[^\.]+\.[^\.]+/', $item ?? '')) {
                                    throw new InvalidArgumentException(sprintf(
                                        'Invalid format for relation "%s" on class "%s" ("%s")',
                                        $fieldName,
                                        $class,
                                        $item
                                    ));
                                }

                                $parsedItems[] = $this->parseValue($item, $fixtures);
                            }

                            if ($isHasMany) {
                                $obj->getComponents($fieldName)->setByIDList($parsedItems);
                            } elseif ($isManyMany) {
                                $obj->getManyManyComponents($fieldName)->setByIDList($parsedItems);
                            }
                        }
                    } else {
                        $hasOneField = preg_replace('/ID$/', '', $fieldName ?? '');
                        if ($className = $schema->hasOneComponent($class, $hasOneField)) {
                            $obj->{$hasOneField . 'ID'} = $this->parseValue($fieldVal, $fixtures, $fieldClass);
                            // Inject class for polymorphic relation
                            if ($className === 'SilverStripe\\ORM\\DataObject') {
                                $obj->{$hasOneField . 'Class'} = $fieldClass;
                            }
                        }
                    }
                }
            }
            $obj->write();

            // If LastEdited was set in the fixture, set it here
            if ($data && array_key_exists('LastEdited', $data ?? [])) {
                $this->overrideField($obj, 'LastEdited', $data['LastEdited'], $fixtures);
            }
        } catch (Exception $e) {
            Config::unnest();
            throw $e;
        }

        Config::unnest();
        $this->invokeCallbacks('afterCreate', [$obj, $identifier, &$data, &$fixtures]);

        return $obj;
    }

    /**
     * @param array $defaults
     * @return $this
     */
    public function setDefaults($defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * See class documentation.
     *
     * @param string $type
     * @param callable $callback
     * @return $this
     */
    public function addCallback($type, $callback)
    {
        if (!array_key_exists($type, $this->callbacks ?? [])) {
            throw new InvalidArgumentException(sprintf('Invalid type "%s"', $type));
        }

        $this->callbacks[$type][] = $callback;
        return $this;
    }

    /**
     * @param string $type
     * @param callable $callback
     * @return $this
     */
    public function removeCallback($type, $callback)
    {
        $pos = array_search($callback, $this->callbacks[$type] ?? []);
        if ($pos !== false) {
            unset($this->callbacks[$type][$pos]);
        }

        return $this;
    }

    protected function invokeCallbacks($type, $args = [])
    {
        foreach ($this->callbacks[$type] as $callback) {
            call_user_func_array($callback, $args ?? []);
        }
    }

    /**
     * Parse a value from a fixture file.  If it starts with =>
     * it will get an ID from the fixture dictionary
     *
     * @param string $value
     * @param array $fixtures See {@link createObject()}
     * @param string $class If the value parsed is a class relation, this parameter
     * will be given the value of that class's name
     * @return string Fixture database ID, or the original value
     */
    protected function parseValue($value, $fixtures = null, &$class = null)
    {
        if (substr($value ?? '', 0, 2) == '=>') {
            // Parse a dictionary reference - used to set foreign keys
            list($class, $identifier) = explode('.', substr($value ?? '', 2), 2);

            if ($fixtures && !isset($fixtures[$class][$identifier])) {
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

    protected function setValue($obj, $name, $value, $fixtures = null)
    {
        $obj->$name = $this->parseValue($value, $fixtures);
    }

    protected function overrideField($obj, $fieldName, $value, $fixtures = null)
    {
        $class = get_class($obj);
        $table = DataObject::getSchema()->tableForField($class, $fieldName);
        $value = $this->parseValue($value, $fixtures);

        DB::manipulate([
            $table => [
                "command" => "update",
                "id" => $obj->ID,
                "class" => $class,
                "fields" => [$fieldName => $value],
            ]
        ]);
        $obj->$fieldName = $value;
    }
}
