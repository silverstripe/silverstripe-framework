<?php

/**
 * Connector for MySQL using the MySQLi method
 * @package framework
 * @subpackage model
 */
class MySQLiConnector extends DBConnector {

	/**
	 * Connection to the MySQL database
	 *
	 * @var MySQLi
	 */
	protected $dbConn = null;

	/**
	 * Name of the currently selected database
	 *
	 * @var string
	 */
	protected $databaseName = null;

	/**
	 * The most recent statement returned from MySQLiConnector->preparedQuery
	 *
	 * @var mysqli_stmt
	 */
	protected $lastStatement = null;

	/**
	 * Store the most recent statement for later use
	 *
	 * @param mysqli_stmt $statement
	 */
	protected function setLastStatement($statement) {
		$this->lastStatement = $statement;
	}

	/**
	 * Retrieve a prepared statement for a given SQL string
	 *
	 * @param string $sql
	 * @param boolean &$success
	 * @return mysqli_stmt
	 */
	public function prepareStatement($sql, &$success) {
		// Record last statement for error reporting
		$statement = $this->dbConn->stmt_init();
		$this->setLastStatement($statement);
		$success = $statement->prepare($sql);
		return $statement;
	}

	public function connect($parameters, $selectDB = false) {
		// Normally $selectDB is set to false by the MySQLDatabase controller, as per convention
		$selectedDB = ($selectDB && !empty($parameters['database'])) ? $parameters['database'] : null;

		if(!empty($parameters['port'])) {
			$this->dbConn = new MySQLi(
				$parameters['server'],
				$parameters['username'],
				$parameters['password'],
				$selectedDB,
				$parameters['port']
			);
		} else {
			$this->dbConn = new MySQLi(
				$parameters['server'],
				$parameters['username'],
				$parameters['password'],
				$selectedDB
			);
		}

		if ($this->dbConn->connect_error) {
			$this->databaseError("Couldn't connect to MySQL database | " . $this->dbConn->connect_error);
		}

		// Set charset if given and not null. Can explicitly set to empty string to omit
		$charset = isset($parameters['charset'])
				? $parameters['charset']
				: 'utf8';
		if (!empty($charset)) $this->dbConn->set_charset($charset);
	}

	public function __destruct() {
		if ($this->dbConn) {
			mysqli_close($this->dbConn);
			$this->dbConn = null;
		}
	}

	public function escapeString($value) {
		return $this->dbConn->real_escape_string($value);
	}

	public function quoteString($value) {
		$value = $this->escapeString($value);
		return "'$value'";
	}

	public function getVersion() {
		return $this->dbConn->server_info;
	}
	
	/**
	 * Invoked before any query is executed
	 * 
	 * @param string $sql
	 */
	protected function beforeQuery($sql) {
		// Clear the last statement
		$this->setLastStatement(null);
	}

	public function query($sql, $errorLevel = E_USER_ERROR) {
		$this->beforeQuery($sql);

		// Benchmark query
		$handle = $this->dbConn->query($sql, MYSQLI_STORE_RESULT);

		if (!$handle || $this->dbConn->error) {
			$this->databaseError($this->getLastError(), $errorLevel, $sql);
			return null;
		}

		// Some non-select queries return true on success
		return new MySQLQuery($this, $handle);
	}

	/**
	 * Prepares the list of parameters in preparation for passing to mysqli_stmt_bind_param
	 *
	 * @param array $parameters List of parameters
	 * @param array &$blobs Out parameter for list of blobs to bind separately
	 * @return array List of parameters appropriate for mysqli_stmt_bind_param function
	 */
	public function parsePreparedParameters($parameters, &$blobs) {
		$types = '';
		$values = array();
		$blobs = array();
		for($index = 0; $index < count($parameters); $index++) {
			$value = $parameters[$index];
			$phpType = gettype($value);

			// Allow overriding of parameter type using an associative array
			if($phpType === 'array') {
				$phpType = $value['type'];
				$value = $value['value'];
			}

			// Convert php variable type to one that makes mysqli_stmt_bind_param happy
			// @see http://www.php.net/manual/en/mysqli-stmt.bind-param.php
			switch($phpType) {
				case 'boolean':
				case 'integer':
					$types .= 'i';
					break;
				case 'float': // Not actually returnable from gettype
				case 'double':
					$types .= 'd';
					break;
				case 'object': // Allowed if the object or resource has a __toString method
				case 'resource':
				case 'string':
				case 'NULL': // Take care that a where clause should use "where XX is null" not "where XX = null"
					$types .= 's';
					break;
				case 'blob':
					$types .= 'b';
					// Blobs must be sent via send_long_data and set to null here
					$blobs[] = array(
						'index' => $index,
						'value' => $value
					);
					$value = null;
					break;
				case 'array':
				case 'unknown type':
				default:
					user_error("Cannot bind parameter \"$value\" as it is an unsupported type ($phpType)",
						E_USER_ERROR);
					break;
			}
			$values[] = $value;
		}
		return array_merge(array($types), $values);
	}

	/**
	 * Binds a list of parameters to a statement
	 *
	 * @param mysqli_stmt $statement MySQLi statement
	 * @param array $parameters List of parameters to pass to bind_param
	 */
	public function bindParameters(mysqli_stmt $statement, array $parameters) {
		// Because mysqli_stmt::bind_param arguments must be passed by reference
		// we need to do a bit of hackery
		for ($i = 0; $i < count($parameters); $i++)
		{
			$boundName = "param$i";
			$$boundName = $parameters[$i];
			$boundNames[] = &$$boundName;
		}
		call_user_func_array( array($statement, 'bind_param'), $boundNames);
	}

	public function preparedQuery($sql, $parameters, $errorLevel = E_USER_ERROR) {
		// Shortcut to basic query when not given parameters
		if(empty($parameters)) {
			return $this->query($sql, $errorLevel);
		}

		$this->beforeQuery($sql);

		// Type check, identify, and prepare parameters for passing to the statement bind function
		$parsedParameters = $this->parsePreparedParameters($parameters, $blobs);

		// Benchmark query
		$statement = $this->prepareStatement($sql, $success);
		if($success) {
			if($parsedParameters) {
				$this->bindParameters($statement, $parsedParameters);
			}

			// Bind any blobs given
			foreach($blobs as $blob) {
				$statement->send_long_data($blob['index'], $blob['value']);
			}

			// Safely execute the statement
			$statement->execute();
		}
		
		if (!$success || $statement->error) {
			$values = $this->parameterValues($parameters);
			$this->databaseError($this->getLastError(), $errorLevel, $sql, $values);
			return null;
		}

		// Non-select queries will have no result data
		$metaData = $statement->result_metadata();
		if($metaData) {
			return new MySQLStatement($statement, $metaData);
		} else {
			// Replicate normal behaviour of ->query() on non-select calls
			return new MySQLQuery($this, true);
		}
	}

	public function selectDatabase($name) {
		if ($this->dbConn->select_db($name)) {
			$this->databaseName = $name;
			return true;
		} else {
			return false;
		}
	}

	public function getSelectedDatabase() {
		return $this->databaseName;
	}

	public function unloadDatabase() {
		$this->databaseName = null;
	}

	public function isActive() {
		return $this->databaseName && $this->dbConn && empty($this->dbConn->connect_error);
	}

	public function affectedRows() {
		return $this->dbConn->affected_rows;
	}

	public function getGeneratedID($table) {
		return $this->dbConn->insert_id;
	}

	public function getLastError() {
		// Check if a statement was used for the most recent query
		if($this->lastStatement && $this->lastStatement->error) {
			return $this->lastStatement->error;
		}
		return $this->dbConn->error;
	}

}
