<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Core\Convert;
use Iterator;
use Traversable;

/**
 * Abstract query-result class. A query result provides an iterator that returns a map for each record of a query
 * result.
 *
 * The map should be keyed by the column names, and the values should use the following types:
 *
 *  - boolean returned as integer 1 or 0 (to ensure consistency with MySQL that doesn't have native booleans)
 *  - integer types returned as integers
 *  - floating point / decimal types returned as floats
 *  - strings returned as strings
 *  - dates / datetimes returned as strings
 *
 * Note that until SilverStripe 4.3, bugs meant that strings were used for every column type.
 *
 * Once again, this should be subclassed by an actual database implementation.  It will only
 * ever be constructed by a subclass of SS_Database.  The result of a database query - an iteratable object
 * that's returned by DB::SS_Query
 *
 * Primarily, the Query class takes care of the iterator plumbing, letting the subclasses focusing
 * on providing the specific data-access methods that are required: {@link nextRecord()}, {@link numRecords()}
 * and {@link seek()}
 */
abstract class Query implements \IteratorAggregate
{

    /**
     * Return an array containing all the values from a specific column. If no column is set, then the first will be
     * returned
     *
     * @param string $column
     * @return array
     */
    public function column($column = null)
    {
        $result = [];

        foreach ($this as $record) {
            if ($column) {
                $result[] = $record[$column];
            } else {
                $result[] = $record[key($record)];
            }
        }

        return $result;
    }

    /**
     * Return an array containing all values in the leftmost column, where the keys are the
     * same as the values.
     *
     * @return array
     */
    public function keyedColumn()
    {
        $column = [];

        foreach ($this as $record) {
            $val = $record[key($record)];
            $column[$val] = $val;
        }
        return $column;
    }

    /**
     * Return a map from the first column to the second column.
     *
     * @return array
     */
    public function map()
    {
        $column = [];
        foreach ($this as $record) {
            $key = reset($record);
            $val = next($record);
            $column[$key] = $val;
        }
        return $column;
    }

    /**
     * Returns the first record in the result
     *
     * @return array
     */
    public function record()
    {
        return $this->getIterator()->current();
    }

    /**
     * Returns the first column of the first record.
     *
     * @return string
     */
    public function value()
    {
        $record = $this->record();
        if ($record) {
            return $record[key($record)];
        }
        return null;
    }

    /**
     * Return an HTML table containing the full result-set
     *
     * @return string
     */
    public function table()
    {
        $first = true;
        $result = "<table>\n";

        foreach ($this as $record) {
            if ($first) {
                $result .= "<tr>";
                foreach ($record as $k => $v) {
                    $result .= "<th>" . Convert::raw2xml($k) . "</th> ";
                }
                $result .= "</tr> \n";
            }

            $result .= "<tr>";
            foreach ($record as $k => $v) {
                $result .= "<td>" . Convert::raw2xml($v) . "</td> ";
            }
            $result .= "</tr> \n";

            $first = false;
        }
        $result .= "</table>\n";

        if ($first) {
            return "No records found";
        }
        return $result;
    }

    /**
     * Return the next record in the query result.
     */
    abstract public function getIterator(): Traversable;

    /**
     * Return the total number of items in the query result.
     *
     * @return int
     */
    abstract public function numRecords();
}
