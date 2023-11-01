<?php

namespace SilverStripe\ORM\Queries;

use InvalidArgumentException;

/**
 * Represents a list of updates / inserts made to a single row in a table
 */
class SQLAssignmentRow
{

    /**
     * List of field values to store for this query
     *
     * Each item in this array will be in the form of a single-length array
     * in the format ['sql' => [$parameters]].
     * The field name is stored as the key
     *
     * E.g.
     *
     * <code>$assignments['ID'] = ['?' => [1]];</code>
     *
     * This allows for complex, parameterised updates, or explicit field values set
     * without any parameters
     *
     * @var array
     */
    protected $assignments = [];

    /**
     * Instantiate a new SQLAssignmentRow object with the given values
     *
     * @param array $values
     */
    function __construct(array $values = [])
    {
        $this->setAssignments($values);
    }


    /**
     * Given a key / value pair, extract the predicate and any potential parameters
     * in a format suitable for storing internally as a list of paramaterised conditions.
     *
     * @param mixed $value Either a literal field value, or an array with
     * placeholder => parameter(s) as a pair
     * @return array A single item array in the format [$sql => [$parameters]]
     */
    protected function parseAssignment($value)
    {
        // Assume string values (or values saved as customised array objects)
        // represent simple assignment
        if (!is_array($value) || isset($value['type'])) {
            return ['?' => [$value]];
        }

        // If given as array then extract and check both the SQL as well as the parameter(s)
        // Note that there could be multiple parameters, e.g.
        // ['MAX(?,?)' => [1,2]] although the container should
        // have a single item
        if (count($value ?? []) == 1) {
            foreach ($value as $sql => $parameters) {
                if (!is_string($sql)) {
                    continue;
                }
                if (!is_array($parameters)) {
                    $parameters = [$parameters];
                }

                return [$sql => $parameters];
            }
        }

        throw new InvalidArgumentException(
            "Nested field assignments should be given as a single parameterised item array in "
            . "['?' => ['value']] format)"
        );
    }

    /**
     * Given a list of assignments in any user-acceptible format, normalise the
     * value to a common ['SQL' => [parameters]] format
     *
     * @param array $assignments List of assignments.
     * The key of this array should be the field name, and the value the assigned
     * literal value, or an array with parameterised information.
     * @return array List of normalised assignments
     */
    protected function normaliseAssignments(array $assignments)
    {
        $normalised = [];
        foreach ($assignments as $field => $value) {
            $normalised[$field] = $this->parseAssignment($value);
        }
        return $normalised;
    }

    /**
     * Adds assignments for a list of several fields
     *
     * Note that field values must not be escaped, as these will be internally
     * parameterised by the database engine.
     *
     * <code>
     *
     * // Basic assignments
     * $query->addAssignments([
     *      '"Object"."Title"' => 'Bob',
     *      '"Object"."Description"' => 'Bob was here'
     * ])
     *
     * // Parameterised assignments
     * $query->addAssignments([
     *      '"Object"."Title"' => ['?' => 'Bob'],
     *      '"Object"."Description"' => ['?' => null]
     * ])
     *
     * // Complex parameters
     * $query->addAssignments([
     *      '"Object"."Score"' => ['MAX(?,?)' => [1, 3]]
     * ]);
     *
     * // Assignment of literal SQL for a field. The empty array is
     * // important to denote the zero-number parameter list
     * $query->addAssignments([
     *      '"Object"."Score"' => ['NOW()' => []]
     * ]);
     *
     * </code>
     *
     * @param array $assignments The list of fields to assign
     * @return $this The self reference to this row
     */
    public function addAssignments(array $assignments)
    {
        $assignments = $this->normaliseAssignments($assignments);
        $this->assignments = array_merge($this->assignments, $assignments);
        return $this;
    }

    /**
     * Sets the list of assignments to the given list
     *
     * @see SQLWriteExpression::addAssignments() for syntax examples
     *
     * @param array $assignments
     * @return $this The self reference to this row
     */
    public function setAssignments(array $assignments)
    {
        return $this->clear()->addAssignments($assignments);
    }

    /**
     * Retrieves the list of assignments in parameterised format
     *
     * @return array List of assignments. The key of this array will be the
     * column to assign, and the value a parameterised array in the format
     * ['SQL' => [parameters]];
     */
    public function getAssignments()
    {
        return $this->assignments;
    }

    /**
     * Set the value for a single field
     *
     * E.g.
     * <code>
     *
     * // Literal assignment
     * $query->assign('"Object"."Description"', 'lorum ipsum');
     *
     * // Single parameter
     * $query->assign('"Object"."Title"', ['?' => 'Bob']);
     *
     * // Complex parameters
     * $query->assign('"Object"."Score"', ['MAX(?,?)' => [1, 3]]);
     * </code>
     *
     * @param string $field The field name to update
     * @param mixed $value The value to assign to this field. This could be an
     * array containing a parameterised SQL query of any number of parameters,
     * or a single literal value.
     * @return $this The self reference to this row
     */
    public function assign($field, $value)
    {
        return $this->addAssignments([$field => $value]);
    }

    /**
     * Assigns a value to a field using the literal SQL expression, rather than
     * a value to be escaped
     *
     * @param string $field The field name to update
     * @param string $sql The SQL to use for this update. E.g. "NOW()"
     * @return $this The self reference to this row
     */
    public function assignSQL($field, $sql)
    {
        return $this->assign($field, [$sql => []]);
    }

    /**
     * Determine if this assignment is empty
     *
     * @return boolean Flag indicating that this assignment is empty
     */
    public function isEmpty()
    {
        return empty($this->assignments);
    }

    /**
     * Retrieves the list of columns updated
     *
     * @return array
     */
    public function getColumns()
    {
        return array_keys($this->assignments ?? []);
    }

    /**
     * Clears all assignment values
     *
     * @return $this The self reference to this row
     */
    public function clear()
    {
        $this->assignments = [];
        return $this;
    }
}
