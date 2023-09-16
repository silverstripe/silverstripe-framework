<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * Represents a classname selector, which respects obsolete clasess.
 */
class DBClassName extends DBEnum
{

    /**
     * Base classname of class to enumerate.
     * If 'DataObject' then all classes are included.
     * If empty, then the baseClass of the parent object will be used
     *
     * @var string|null
     */
    protected $baseClass = null;

    /**
     * Parent object
     *
     * @var DataObject|null
     */
    protected $record = null;

    private static $index = true;

    /**
     * Create a new DBClassName field
     *
     * @param string      $name      Name of field
     * @param string|null $baseClass Optional base class to limit selections
     * @param array       $options   Optional parameters for this DBField instance
     */
    public function __construct($name = null, $baseClass = null, $options = [])
    {
        $this->setBaseClass($baseClass);
        parent::__construct($name, null, null, $options);
    }

    /**
     * @return void
     */
    public function requireField()
    {
        $parts = [
            'datatype' => 'enum',
            'enums' => $this->getEnumObsolete(),
            'character set' => 'utf8',
            'collate' => 'utf8_general_ci',
            'default' => $this->getDefault(),
            'table' => $this->getTable(),
            'arrayValue' => $this->arrayValue
        ];

        $values = [
            'type' => 'enum',
            'parts' => $parts
        ];

        DB::require_field($this->getTable(), $this->getName(), $values);
    }

    /**
     * Get the base dataclass for the list of subclasses
     *
     * @return string
     */
    public function getBaseClass()
    {
        // Use explicit base class
        if ($this->baseClass) {
            return $this->baseClass;
        }
        // Default to the basename of the record
        $schema = DataObject::getSchema();
        if ($this->record) {
            return $schema->baseDataClass($this->record);
        }
        // During dev/build only the table is assigned
        $tableClass = $schema->tableClass($this->getTable());
        if ($tableClass && ($baseClass = $schema->baseDataClass($tableClass))) {
            return $baseClass;
        }
        // Fallback to global default
        return DataObject::class;
    }

    /**
     * Get the base name of the current class
     * Useful as a non-fully qualified CSS Class name in templates.
     *
     * @return string|null
     */
    public function getShortName()
    {
        $value = $this->getValue();
        if (empty($value) || !ClassInfo::exists($value)) {
            return null;
        }
        return ClassInfo::shortName($value);
    }

    /**
     * Assign the base class
     *
     * @param string $baseClass
     * @return $this
     */
    public function setBaseClass($baseClass)
    {
        $this->baseClass = $baseClass;
        return $this;
    }

    /**
     * Get list of classnames that should be selectable
     *
     * @return array
     */
    public function getEnum()
    {
        $classNames = ClassInfo::subclassesFor($this->getBaseClass());
        $dataobject = strtolower(DataObject::class);
        unset($classNames[$dataobject]);
        return array_values($classNames ?? []);
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        parent::setValue($value, $record, $markChanged);

        if ($record instanceof DataObject) {
            $this->record = $record;
        }

        return $this;
    }

    public function getDefault()
    {
        // Check for assigned default
        $default = parent::getDefault();
        if ($default) {
            return $default;
        }

        // Allow classes to set default class
        $baseClass = $this->getBaseClass();
        $defaultClass = Config::inst()->get($baseClass, 'default_classname');
        if ($defaultClass &&  class_exists($defaultClass ?? '')) {
            return $defaultClass;
        }

        // Fallback to first option
        $enum = $this->getEnum();
        return reset($enum);
    }
}
