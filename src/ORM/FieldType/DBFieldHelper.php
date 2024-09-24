<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Injector\Injector;

class DBFieldHelper
{
    /**
     * Create a DBField object that's not bound to any particular field.
     *
     * Useful for accessing the classes behaviour for other parts of your code.
     *
     * @param string $spec Class specification to construct. May include both service name and additional
     * constructor arguments in the same format as DataObject.db config.
     * @param mixed $value value of field
     * @param null|string $name Name of field
     * @param mixed $args Additional arguments to pass to constructor if not using args in service $spec
     * Note: Will raise a warning if using both
     */
    public static function create_field(string $spec, mixed $value, ?string $name = null, mixed ...$args): DBField
    {
        // Raise warning if inconsistent with DataObject::dbObject() behaviour
        // This will cause spec args to be shifted down by the number of provided $args
        if ($args && strpos($spec ?? '', '(') !== false) {
            trigger_error('Additional args provided in both $spec and $args', E_USER_WARNING);
        }
        // Ensure name is always first argument
        array_unshift($args, $name);

        $dbField = Injector::inst()->createWithArgs($spec, $args);
        /** @var DBFieldTrait $dbField */
        $dbField->setValue($value, null, false);
        /** @var DBField $dbField */
        return $dbField;
    }
}
