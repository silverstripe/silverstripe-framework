<?php

/**
 * Provides a record-view for mysqli statements
 *
 * By default streams unbuffered data, but seek(), rewind(), or numRecords() will force the statement to
 * buffer itself and sacrifice any potential performance benefit.
 */
class MySQLStatement extends SS_Query {

	/**
	 * The related mysqli statement object if generated using a prepared query
	 *
	 * @var mysqli_stmt
	 */
	protected $statement;

	/**
	 * Metadata result for this statement
	 *
	 * @var mysqli_result
	 */
	protected $metadata;

	/**
	 * Is the statement bound to the current resultset?
	 *
	 * @var bool
	 */
	protected $bound = false;

	/**
	 * List of column names
	 *
	 * @var array
	 */
	protected $columns = array();

	/**
	 * List of bound variables in the current row
	 *
	 * @var array
	 */
	protected $boundValues = array();

	/**
	 * Array buffer of all rows in this result set
	 *
	 * @var array
	 */
	protected $rows = array();

	/**
	 * If the statement has been closed.
	 * The statement will automatically close once all rows have been buffered.
	 *
	 * @var bool
	 */
	protected $closed = false;

	/**
	 * Force all remaining unbuffered rows to be buffered and close the statement
	 */
	public function buffer() {
		// Force internal pointer to iterate to the end
		while($this->bufferNext()) {}
	}

	/**
	 * Release all resources held by this object
	 */
	protected function close() {
		if($this->isClosed()) {
			return;
		}
		$this->metadata->free();
		$this->statement->close();
		$this->closed = true;
		$this->currentRecord = false;
	}

	/**
	 * Have resources been released?
	 *
	 * @return boolean
	 */
	public function isClosed() {
		return $this->closed;
	}

	/**
	 * Is this result bound?
	 *
	 * @return boolean
	 */
	public function isBound() {
		return $this->bound;
	}

	/**
	 * Binds this statement to the variables
	 */
	protected function bind() {
		if($this->isBound() || $this->isClosed()) {
			return;
		}

		$variables = array();

		// Bind each field
		while($field = $this->metadata->fetch_field()) {
			$this->columns[] = $field->name;
			$variables[] = &$this->boundValues[$field->name];
		}

		call_user_func_array(array($this->statement, 'bind_result'), $variables);
		$this->bound = true;
	}

	/**
	 * Hook the result-set given into a Query class, suitable for use by SilverStripe.
	 * @param mysqli_stmt $statement The related statement, if present
	 * @param mysqli_result $metadata The metadata for this statement
	 */
	public function __construct($statement, $metadata) {
		$this->statement = $statement;
		$this->metadata = $metadata;
	}

	public function __destruct() {
		$this->close();
	}

	public function seek($row) {
		$this->rowNum = $row - 1;
		return $this->next();
	}

	/**
	 * Fetch the next row from the internal statement and buffer it.
	 * If fetchRow() reaches the end of the resultset it will close any held resources.
	 *
	 * @return array|false The result fetched, or false if end of set
	 */
	protected function bufferNext() {
		if($this->isClosed()) {
			return false;
		}

		// Detect end of results
		$this->bind();
		if (!$this->statement->fetch()) {
			$this->close();
			return false;
		}

		// Buffer dereferenced row
		$row = array();
		foreach($this->boundValues as $value) {
			$row[] = $value;
		}
		$this->rows[] = $row;
		return array_combine($this->columns, $row);
	}

	public function numRecords() {
		// Try not to do this on large datasets if performance is critical
		$this->buffer();
		return count($this->rows);
	}

	public function nextRecord() {
		// Index of next row (given rowNum is current now)
		$rowNum = $this->rowNum + 1;
		
		// Buffer up to $rowNum
		while(count($this->rows) <= $rowNum) {
			if(!$this->bufferNext()) {
				return false;
			}
		}
		return array_combine($this->columns, $this->rows[$rowNum]);
	}

	public function rewind() {
		// Don't count records in rewind as it forces a full buffer
		return $this->seek(0);
	}

}
