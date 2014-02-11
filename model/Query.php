<?php

/**
 * Abstract query-result class.
 *
 * Once again, this should be subclassed by an actual database implementation 
 * such as {@link MySQLQuery}.
 *
 * It will only ever be constructed by a subclass of {@link SS_Database} and 
 * contain the result of a database query as an iteratable object.
 *
 * Primarily, the SS_Query class takes care of the iterator plumbing, letting 
 * the subclasses focusing on providing the specific data-access methods that 
 * are required: {@link nextRecord()}, {@link numRecords()} and {@link seek()}
 *
 * @package framework
 * @subpackage model
 */
abstract class SS_Query implements Iterator {

	/**
	 * The current record in the interator.
	 *
	 * @var array
	 */
	private $currentRecord = null;
	
	/**
	 * The number of the current row in the interator.
	 *
	 * @var int
	 */
	private $rowNum = -1;
	
	/**
	 * Flag to keep track of whether iteration has begun, to prevent unnecessary 
	 * seeks.
	 *
	 * @var boolean
	 */
	private $queryHasBegun = false;

	/**
	 * Return an array containing all the values from a specific column. If no 
	 * column is set, then the first will be returned.
	 *
	 * @param string $column
	 *
	 * @return array
	 */
	public function column($column = null) {
		$result = array();
		
		while($record = $this->next()) {
			if($column) {
				$result[] = $record[$column];
			} else {
				$result[] = $record[key($record)];
			}
		}

		return $result;
	}

	/**
	 * Return an array containing all values in the leftmost column, where the 
	 * keys are the same as the values.
	 *
	 * @return array
	 */
	public function keyedColumn() {
		$column = array();

		foreach($this as $record) {
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
	public function map() {
		$column = array();

		foreach($this as $record) {
			$key = reset($record);
			$val = next($record);
			$column[$key] = $val;
		}

		return $column;
	}

	/**
	 * Returns the next record in the iterator.
	 *
	 * @return array
	 */
	public function record() {
		return $this->next();
	}

	/**
	 * Returns the first column of the first record.
	 *
	 * @return string
	 */
	public function value() {
		$record = $this->next();

		if($record) {
			return $record[key($record)];
		}
	}

	/**
	 * Return an HTML table containing the full result-set.
	 */
	public function table() {
		$first = true;
		$result = "<table>\n";
		
		foreach($this as $record) {
			if($first) {
				$result .= "<tr>";
				foreach($record as $k => $v) {
					$result .= "<th>" . Convert::raw2xml($k) . "</th> ";
				}
				$result .= "</tr> \n";
			}

			$result .= "<tr>";
			foreach($record as $k => $v) {
				$result .= "<td>" . Convert::raw2xml($v) . "</td> ";
			}
			$result .= "</tr> \n";
			
			$first = false;
		}
		$result .= "</table>\n";
		
		if($first) return "No records found";
		return $result;
	}
	
	/**
	 * Iterator function implementation. Rewind the iterator to the first item 
	 * and return it.
	 *
	 * Makes use of {@link seek()} and {@link numRecords()}, takes care of the 
	 * plumbing.
	 *
	 * @return array
	 */
	public function rewind() {
		if($this->queryHasBegun && $this->numRecords() > 0) {
			$this->queryHasBegun = false;

			return $this->seek(0);
		}
	}

	/**
	 * Iterator function implementation. Return the current item of the 
	 * iterator.
	 *
	 * @return array
	 */
	public function current() {
		if(!$this->currentRecord) {
			return $this->next();
		} else {
			return $this->currentRecord;
		}
	}

	/**
	 * Iterator function implementation. Return the first item of this iterator.
	 * @return array
	 */
	public function first() {
		$this->rewind();

		return $this->current();
	}

	/**
	 * Iterator function implementation. Return the row number of the current 
	 * item.
	 *
	 * @return int
	 */
	public function key() {
		return $this->rowNum;
	}

	/**
	 * Iterator function implementation. Return the next record in the iterator.
	 *
	 * Makes use of {@link nextRecord()}, takes care of the plumbing.
	 *
	 * @return array
	 */
	public function next() {
		$this->queryHasBegun = true;
		$this->currentRecord = $this->nextRecord();
		$this->rowNum++;
		return $this->currentRecord;
	}

	/**
	 * Iterator function implementation. Check if the iterator is pointing to a 
	 * valid item.
	 *
	 * @return boolean
	 */
	public function valid() {
		if(!$this->queryHasBegun) {
			$this->next();
		}

		return $this->currentRecord !== false;
	}

	/**
	 * Return the next record in the query result.
	 *
	 * @return array
	 */
	abstract public function nextRecord();

	/**
	 * Return the total number of items in the query result.
	 *
	 * @return int
	 */
	abstract public function numRecords();

	/**
	 * Go to a specific row number in the query result and return the record.
	 *
	 * @param int $rowNum Tow number to go to.
	 *
	 * @return array
	 */
	abstract public function seek($rowNum);
}
