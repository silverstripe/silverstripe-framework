<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

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
class MockDynamicAssignmentDBField extends DBBoolean
{

    private $scalarOnly;
    private $dynamicAssignment;

    /**
     * @param $name
     * @param $scalarOnly Whether our fake field should be scalar only
     * @param $dynamicAssigment Wheter our fake field will try to do a dynamic assignement
     */
    public function __construct($name = '', $scalarOnly = false, $dynamicAssignment = false)
    {
        $this->scalarOnly = $scalarOnly;
        $this->dynamicAssignment = $dynamicAssignment;
        parent::__construct($name);
    }

    /**
     * If the field value and dynamicAssignment are true, we'll try to do a dynamic assignement
     * @param $value
     * @return array|int|mixed
     */
    public function prepValueForDB($value)
    {
        if ($value) {
            return $this->dynamicAssignment
                ? ['GREATEST(?, ?)' => [0, 1]]
                : 1;
        }

        return 0;
    }

    public function scalarValueOnly()
    {
        return $this->scalarOnly;
    }
}
