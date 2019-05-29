<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBField;

/**
 * This is a fake DB field specifically design to test dynamic value assignment. You can set `scalarValueOnly` in
 * the constructor. You can control whetever the field will try to do a dynamic assignment by specifing
 * `$dynamicAssignment` in nthe consturctor.
 *
 * If the field is set to false, it will try to do a plain assignment. This is so you can save the initial value no
 * matter what. If the field is set to true, it will try to do a dynamic assignment.
 */
class MockDynamicAssignmentDBField extends DBBoolean implements TestOnly
{

    private $scalarOnly;
    private $dynamicAssignment;

    /**
     * @param string $name
     * @param boolean $scalarOnly Whether our fake field should be scalar only.
     * @param boolean $dynamicAssignment Whether our fake field will try to do a dynamic assignment.
     */
    public function __construct($name = '', $scalarOnly = false, $dynamicAssignment = false)
    {
        $this->scalarOnly = $scalarOnly;
        $this->dynamicAssignment = $dynamicAssignment;
        parent::__construct($name);
    }

    /**
     * If the field value and $dynamicAssignment are true, we'll try to do a dynamic assignment.
     * @param $value
     * @return array|int
     */
    public function prepValueForDB($value)
    {
        if ($value) {
            return $this->dynamicAssignment
                ? ['ABS(?)' => [1]]
                : 1;
        }

        return 0;
    }

    public function scalarValueOnly()
    {
        return $this->scalarOnly;
    }
}
