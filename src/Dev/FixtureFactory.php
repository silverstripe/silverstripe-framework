<?php

namespace SilverStripe\Dev;

use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\Core\Injector\Injector;
use InvalidArgumentException;

/**
 * Manages a set of database fixtures for {@link DataObject} records
 * as well as raw database table rows.
 *
 * Delegates creation of objects to {@link FixtureBlueprint},
 * which can implement class- and use-case specific fixture setup.
 *
 * Supports referencing model relations through a specialized syntax:
 * <code>
 * $factory = new FixtureFactory();
 * $relatedObj = $factory->createObject(
 *  'MyRelatedClass',
 *  'relation1'
 * );
 * $obj = $factory->createObject(
 *  'MyClass',
 *  'object1'
 *  array('MyRelationName' => '=>MyRelatedClass.relation1')
 * );
 * </code>
 * Relation loading is order dependent.
 */
class FixtureFactory
{

    /**
     * @var array Array of fixture items, keyed by class and unique identifier,
     * with values being the generated database ID. Does not store object instances.
     */
    protected $fixtures = [];

    /**
     * @var FixtureBlueprint[] Callbacks
     */
    protected $blueprints = [];

    /**
     * @param string $name Unique name for this blueprint
     * @param array|FixtureBlueprint $defaults Array of default values, or a blueprint instance
     * @return $this
     */
    public function define($name, $defaults = [])
    {
        if ($defaults instanceof FixtureBlueprint) {
            $this->blueprints[$name] = $defaults;
        } else {
            $class = $name;
            $this->blueprints[$name] = Injector::inst()->create(
                'SilverStripe\\Dev\\FixtureBlueprint',
                $name,
                $class,
                $defaults
            );
        }

        return $this;
    }

    /**
     * Writes the fixture into the database using DataObjects
     *
     * @param string $name Name of the {@link FixtureBlueprint} to use,
     *                     usually a DataObject subclass.
     * @param string $identifier Unique identifier for this fixture type
     * @param array $data Map of properties. Overrides default data.
     * @return DataObject
     */
    public function createObject($name, $identifier, $data = null)
    {
        if (!isset($this->blueprints[$name])) {
            $this->blueprints[$name] = new FixtureBlueprint($name);
        }
        $blueprint = $this->blueprints[$name];
        $obj = $blueprint->createObject($identifier, $data, $this->fixtures);
        $class = $blueprint->getClass();

        if (!isset($this->fixtures[$class])) {
            $this->fixtures[$class] = [];
        }
        $this->fixtures[$class][$identifier] = $obj->ID;

        return $obj;
    }

    /**
     * Writes the fixture into the database directly using a database manipulation.
     * Does not use blueprints. Only supports tables with a primary key.
     *
     * @param string $table Existing database table name
     * @param string $identifier Unique identifier for this fixture type
     * @param array $data Map of properties
     * @return int Database identifier
     */
    public function createRaw($table, $identifier, $data)
    {
        $fields = [];
        foreach ($data as $fieldName => $fieldVal) {
            $fields["\"{$fieldName}\""] = $this->parseValue($fieldVal);
        }
        $insert = new SQLInsert("\"{$table}\"", $fields);
        $insert->execute();
        $id = DB::get_generated_id($table);
        $this->fixtures[$table][$identifier] = $id;

        return $id;
    }

    /**
     * Get the ID of an object from the fixture.
     *
     * @param string $class The data class, as specified in your fixture file.  Parent classes won't work
     * @param string $identifier The identifier string, as provided in your fixture file
     * @return int|false
     */
    public function getId($class, $identifier)
    {
        if (isset($this->fixtures[$class][$identifier])) {
            return $this->fixtures[$class][$identifier];
        } else {
            return false;
        }
    }

    /**
     * Return all of the IDs in the fixture of a particular class name.
     *
     * @param string $class The data class or table name
     * @return array|false A map of fixture-identifier => object-id
     */
    public function getIds($class)
    {
        if (isset($this->fixtures[$class])) {
            return $this->fixtures[$class];
        } else {
            return false;
        }
    }

    /**
     * @param string $class
     * @param string $identifier
     * @param int $databaseId
     * @return $this
     */
    public function setId($class, $identifier, $databaseId)
    {
        $this->fixtures[$class][$identifier] = $databaseId;
        return $this;
    }

    /**
     * Get an object from the fixture.
     *
     * @template T of DataObject
     * @param class-string<T> $class The data class or table name, as specified in your fixture file.  Parent classes won't work
     * @param string $identifier The identifier string, as provided in your fixture file
     * @return T|null
     */
    public function get($class, $identifier)
    {
        $id = $this->getId($class, $identifier);
        if (!$id) {
            return null;
        }

        // If the class doesn't exist, look for a table instead
        if (!class_exists($class ?? '')) {
            $tableNames = DataObject::getSchema()->getTableNames();
            $potential = array_search($class, $tableNames ?? []);
            if (!$potential) {
                throw new \LogicException("'$class' is neither a class nor a table name");
            }
            $class = $potential;
        }

        return DataObject::get_by_id($class, $id);
    }

    /**
     * @return array Map of class names, containing a map of in-memory identifiers
     * mapped to database identifiers.
     */
    public function getFixtures()
    {
        return $this->fixtures;
    }

    /**
     * Remove all fixtures previously defined through {@link createObject()}
     * or {@link createRaw()}, both from the internal fixture mapping and the database.
     * If the $class argument is set, limit clearing to items of this class.
     *
     * @param string $limitToClass
     * @param bool $metadata Clear internal mapping as well as data.
     * Set to false by default since sometimes data is rolled back by translations.
     */
    public function clear($limitToClass = null, $metadata = false)
    {
        $classes = ($limitToClass) ? [$limitToClass] : array_keys($this->fixtures ?? []);
        foreach ($classes as $class) {
            $ids = $this->fixtures[$class];
            foreach ($ids as $id => $dbId) {
                if (class_exists($class ?? '')) {
                    $instance = DataObject::get($class)->byId($dbId);
                    if ($instance) {
                        $instance->delete();
                    }
                } else {
                    $table = $class;
                    $delete = new SQLDelete("\"$table\"", [
                        "\"$table\".\"ID\"" => $dbId
                    ]);
                    $delete->execute();
                }

                if ($metadata) {
                    unset($this->fixtures[$class][$id]);
                }
            }
        }
    }

    /**
     * @return array Of {@link FixtureBlueprint} instances
     */
    public function getBlueprints()
    {
        return $this->blueprints;
    }

    /**
     * @param string $name
     * @return FixtureBlueprint|false
     */
    public function getBlueprint($name)
    {
        return (isset($this->blueprints[$name])) ? $this->blueprints[$name] : false;
    }

    /**
     * Parse a value from a fixture file.  If it starts with =>
     * it will get an ID from the fixture dictionary
     *
     * @param string $value
     * @return string Fixture database ID, or the original value
     */
    protected function parseValue($value)
    {
        if (substr($value ?? '', 0, 2) == '=>') {
            // Parse a dictionary reference - used to set foreign keys
            if (strpos($value ?? '', '.') !== false) {
                list($class, $identifier) = explode('.', substr($value ?? '', 2), 2);
            } else {
                throw new \LogicException("Bad fixture lookup identifier: " . $value);
            }

            if ($this->fixtures && !isset($this->fixtures[$class][$identifier])) {
                throw new InvalidArgumentException(sprintf(
                    'No fixture definitions found for "%s"',
                    $value
                ));
            }

            return $this->fixtures[$class][$identifier];
        } else {
            // Regular field value setting
            return $value;
        }
    }
}
