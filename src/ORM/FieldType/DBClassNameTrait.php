<?php

namespace SilverStripe\ORM\FieldType;

use RuntimeException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;

trait DBClassNameTrait
{
    /**
     * Base classname of class to enumerate.
     * If 'DataObject' then all classes are included.
     * If empty, then the baseClass of the parent object will be used
     */
    protected ?string $baseClass = null;

    /**
     * Parent object
     */
    protected ?DataObject $record = null;

    private static string|bool $index = true;

    /**
     * Create a new DBClassName field
     *
     * @param string|null $baseClass Optional base class to limit selections
     * @param array $options Optional parameters for this DBField instance
     */
    public function __construct(?string $name = null, ?string $baseClass = null, array $options = [])
    {
        $this->setBaseClass($baseClass);
        if (is_a($this, DBVarchar::class)) {
            parent::__construct($name, 255, $options);
        } elseif (is_a($this, DBEnum::class)) {
            parent::__construct($name, null, null, $options);
        } else {
            throw new RuntimeException('DBClassNameTrait can only be used with DBVarchar or DBEnum');
        }
    }

    /**
     * Get the base dataclass for the list of subclasses
     */
    public function getBaseClass(): string
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
     */
    public function getShortName(): string
    {
        $value = $this->getValue();
        if (empty($value) || !ClassInfo::exists($value)) {
            return '';
        }
        return ClassInfo::shortName($value);
    }

    /**
     * Assign the base class
     */
    public function setBaseClass(?string $baseClass): static
    {
        $this->baseClass = $baseClass;
        return $this;
    }

    /**
     * Get list of classnames that should be selectable
     */
    public function getEnum(): array
    {
        $classNames = ClassInfo::subclassesFor($this->getBaseClass());
        $dataobject = strtolower(DataObject::class);
        unset($classNames[$dataobject]);
        return array_values($classNames ?? []);
    }

    public function setValue(mixed $value, null|array|ViewableData $record = null, bool $markChanged = true): static
    {
        parent::setValue($value, $record, $markChanged);

        if ($record instanceof DataObject) {
            $this->record = $record;
        }

        return $this;
    }

    private function getDefaultClassName(): string
    {
        // Allow classes to set default class
        $baseClass = $this->getBaseClass();
        $defaultClass = Config::inst()->get($baseClass, 'default_classname');
        if ($defaultClass &&  class_exists($defaultClass ?? '')) {
            return $defaultClass;
        }

        // Fallback to first option
        $subClassNames = $this->getEnum();
        return reset($subClassNames);
    }
}
