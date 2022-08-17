<?php

namespace SilverStripe\ORM\Queries;

use SilverStripe\Core\Injector\Injector;

/**
 * Object representing a SQL UPDATE query.
 * The various parts of the SQL query can be manipulated individually.
 */
class SQLUpdate extends SQLConditionalExpression implements SQLWriteExpression
{

    /**
     * The assignment to create for this update
     *
     * @var SQLAssignmentRow
     */
    protected $assignment = null;

    /**
     * Construct a new SQLUpdate object
     *
     * @param string $table Table name to update (ANSI quoted)
     * @param array $assignment List of column assignments
     * @param array $where List of where clauses
     * @return static
     */
    public static function create(string $table = null, array $assignment = [], array $where = []): SilverStripe\ORM\Queries\SQLUpdate
    {
        return Injector::inst()->createWithArgs(__CLASS__, func_get_args());
    }

    /**
     * Construct a new SQLUpdate object
     *
     * @param string $table Table name to update (ANSI quoted)
     * @param array $assignment List of column assignments
     * @param array $where List of where clauses
     */
    function __construct(string $table = null, array $assignment = [], array $where = []): void
    {
        parent::__construct(null, $where);
        $this->assignment = new SQLAssignmentRow();
        $this->setTable($table);
        $this->setAssignments($assignment);
    }

    /**
     * Sets the table name to update
     *
     * @param string $table
     * @return $this Self reference
     */
    public function setTable(string $table): SilverStripe\ORM\Queries\SQLUpdate
    {
        return $this->setFrom($table);
    }

    /**
     * Gets the table name to update
     *
     * @return string Name of the table
     */
    public function getTable(): string
    {
        return reset($this->from);
    }

    public function addAssignments(array $assignments): SilverStripe\ORM\Queries\SQLUpdate
    {
        $this->assignment->addAssignments($assignments);
        return $this;
    }

    public function setAssignments(array $assignments): SilverStripe\ORM\Queries\SQLUpdate
    {
        $this->assignment->setAssignments($assignments);
        return $this;
    }

    public function getAssignments(): array
    {
        return $this->assignment->getAssignments();
    }

    public function assign(string $field, int|float|string $value): SilverStripe\ORM\Queries\SQLUpdate
    {
        $this->assignment->assign($field, $value);
        return $this;
    }

    public function assignSQL(string $field, string $sql): SilverStripe\ORM\Queries\SQLUpdate
    {
        $this->assignment->assignSQL($field, $sql);
        return $this;
    }

    /**
     * Clears all currently set assignment values
     *
     * @return $this The self reference to this query
     */
    public function clear()
    {
        $this->assignment->clear();
        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->assignment) || $this->assignment->isEmpty() || parent::isEmpty();
    }
}
