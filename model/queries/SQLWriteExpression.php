<?php

/**
 * Represents a SQL expression which may have field values assigned
 * (UPDATE/INSERT Expressions)
 *
 * @package framework
 * @subpackage model
 */
interface SQLWriteExpression {

	/**
	 * Adds assignments for a list of several fields.
	 *
	 * For multi-row objects this applies this to the current row.
	 *
	 * Note that field values must not be escaped, as these will be internally
	 * parameterised by the database engine.
	 *
	 * <code>
	 *
	 * // Basic assignments
	 * $query->addAssignments(array(
	 *		'"Object"."Title"' => 'Bob',
	 *		'"Object"."Description"' => 'Bob was here'
	 * ))
	 *
	 * // Parameterised assignments
	 * $query->addAssignments(array(
	 *		'"Object"."Title"' => array('?' => 'Bob')),
	 *		'"Object"."Description"' => array('?' => null))
	 * ))
	 *
	 * // Complex parameters
	 * $query->addAssignments(array(
	 *		'"Object"."Score"' => array('MAX(?,?)' => array(1, 3))
	 * ));
	 *
	 * // Assigment of literal SQL for a field. The empty array is
	 * // important to denote the zero-number paramater list
	 * $query->addAssignments(array(
	 *		'"Object"."Score"' => array('NOW()' => array())
	 * ));
	 *
	 * </code>
	 *
	 * @param array $assignments The list of fields to assign
	 * @return self Self reference
	 */
	public function addAssignments(array $assignments);

	/**
	 * Sets the list of assignments to the given list
	 *
	 * For multi-row objects this applies this to the current row.
	 *
	 * @see SQLWriteExpression::addAssignments() for syntax examples
	 *
	 * @param array $assignments
	 * @return self Self reference
	 */
	public function setAssignments(array $assignments);

	/**
	 * Retrieves the list of assignments in parameterised format
	 *
	 * For multi-row objects returns assignments for the current row.
	 *
	 * @return array List of assigments. The key of this array will be the
	 * column to assign, and the value a parameterised array in the format
	 * array('SQL' => array(parameters));
	 */
	public function getAssignments();

	/**
	 * Set the value for a single field
	 *
	 * For multi-row objects this applies this to the current row.
	 *
	 * E.g.
	 * <code>
	 *
	 * // Literal assignment
	 * $query->assign('"Object"."Description"', 'lorum ipsum'));
	 *
	 * // Single parameter
	 * $query->assign('"Object"."Title"', array('?' => 'Bob'));
	 *
	 * // Complex parameters
	 * $query->assign('"Object"."Score"', array('MAX(?,?)' => array(1, 3));
	 * </code>
	 *
	 * @param string $field The field name to update
	 * @param mixed $value The value to assign to this field. This could be an
	 * array containing a parameterised SQL query of any number of parameters,
	 * or a single literal value.
	 * @return self Self reference
	 */
	public function assign($field, $value);

	/**
	 * Assigns a value to a field using the literal SQL expression, rather than
	 * a value to be escaped
	 *
	 * For multi-row objects this applies this to the current row.
	 *
	 * @param string $field The field name to update
	 * @param string $sql The SQL to use for this update. E.g. "NOW()"
	 * @return self Self reference
	 */
	public function assignSQL($field, $sql);
}
