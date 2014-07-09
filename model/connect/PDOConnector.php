<?php

/**
 * PDO driver database connector
 * @package framework
 * @subpackage model
 */
class PDOConnector extends DBConnector {
	
	/**
	 * Should ATTR_EMULATE_PREPARES flag be used to emulate prepared statements?
	 * 
	 * @config
	 * @var boolean
	 */
	private static $emulate_prepare = false;
	
	/**
	 * The PDO connection instance
	 * 
	 * @var PDO 
	 */
	protected $pdoConnection = null;

	/**
	 * Name of the currently selected database
	 * 
	 * @var string
	 */
	protected $databaseName = null;

	/**
	 * The most recent statement returned from PDODatabase->query
	 * 
	 * @var PDOStatement
	 */
	protected $lastStatement = null;
	
	/**
	 * List of prepared statements, cached by SQL string
	 *
	 * @var array
	 */
	protected $cachedStatements = array();
	
	/**
	 * Flush all prepared statements
	 */
	public function flushStatements() {
		$this->cachedStatements = array();
	}
	
	/**
	 * Retrieve a prepared statement for a given SQL string, or return an already prepared version if
	 * one exists for the given query
	 * 
	 * @param string $sql
	 * @return PDOStatement
	 */
	public function getOrPrepareStatement($sql) {
		if(empty($this->cachedStatements[$sql])) {
			$this->cachedStatements[$sql] = $this->pdoConnection->prepare(
				$sql,
				array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
			);
		}
		return $this->cachedStatements[$sql];
	}
	
	/**
	 * Is PDO running in emulated mode
	 * 
	 * @return boolean
	 */
	public static function is_emulate_prepare() {
		return Config::inst()->get('PDOConnector', 'emulate_prepare');
	}

	public function connect($parameters, $selectDB = false) {
		$this->flushStatements();

		// Build DSN string
		// Note that we don't select the database here until explicitly
		// requested via selectDatabase
		$driver = $parameters['driver'] . ":";
		$dsn = array();
		
		// Typically this is false, but some drivers will request this
		if($selectDB) {
			// Specify complete file path immediately following driver (SQLLite3)
			if(!empty($parameters['filepath'])) {
				$dsn[] = $parameters['filepath'];
			} elseif(!empty($parameters['database'])) {
				// Some databases require a selected database at connection (SQLite3, Azure)
				if($parameters['driver'] === 'sqlsrv') { 
					$dsn[] = "Database={$parameters['database']}";
				} else {
					$dsn[] = "dbname={$parameters['database']}";
				}
			}
		}
		
		// Syntax for sql server is slightly different
		if($parameters['driver'] === 'sqlsrv') {
			$server = $parameters['server'];
			if (!empty($parameters['port'])) {
				$server .= ",{$parameters['port']}";
			}
			$dsn[] = "Server=$server";
		} else {
			if (!empty($parameters['server'])) {
				// Use Server instead of host for sqlsrv
				$dsn[] = "host={$parameters['server']}";
			}

			if (!empty($parameters['port'])) {
				$dsn[] = "port={$parameters['port']}";
			}
		}

		// Set charset if given and not null. Can explicitly set to empty string to omit
		if($parameters['driver'] !== 'sqlsrv') {
			$charset = isset($parameters['charset'])
					? $parameters['charset']
					: 'utf8';
			if (!empty($charset)) $dsn[] = "charset=$charset";
		}

		// Connection commands to be run on every re-connection
		$options = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
			PDO::ATTR_EMULATE_PREPARES => self::is_emulate_prepare()
		);

		// May throw a PDOException if fails
		if(empty($parameters['username']) || empty($parameters['password'])) {
			$this->pdoConnection = new PDO($driver.implode(';', $dsn));
		} else {
			$this->pdoConnection = new PDO($driver.implode(';', $dsn), $parameters['username'],
											$parameters['password'], $options);
		}
		
		// Show selected DB if requested
		if($this->pdoConnection && $selectDB && !empty($parameters['database'])) {
			$this->databaseName = $parameters['database'];
		}
	}

	public function getVersion() {
		return $this->pdoConnection->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

	public function escapeString($value) {
		$value = $this->quoteString($value);

		// Since the PDO library quotes the value, we should remove this to maintain
		// consistency with MySQLDatabase::escapeString
		if (preg_match('/^\'(?<value>.*)\'$/', $value, $matches)) {
			$value = $matches['value'];
		}
		return $value;
	}

	public function quoteString($value) {
		return $this->pdoConnection->quote($value);
	}
	
	/**
	 * Executes a query that doesn't return a resultset
	 * 
	 * @param string $sql
	 * @param string $sql The SQL query to execute
	 * @param integer $errorLevel For errors to this query, raise PHP errors
	 * using this error level.
	 */
	public function exec($sql, $errorLevel = E_USER_ERROR) {
		// Check if we should only preview this query
		if ($this->previewWrite($sql)) return;
		
		// Reset last statement to prevent interference in case of error
		$this->lastStatement = null;

		// Benchmark query
		$pdo = $this->pdoConnection;
		$result = $this->benchmarkQuery($sql, function($sql) use($pdo) {
			return $pdo->exec($sql);
		});

		// Check for errors
		if ($result === false) {
			$this->databaseError($this->getLastError(), $errorLevel, $sql);
			return null;
		}

		return $result;
	}

	public function query($sql, $errorLevel = E_USER_ERROR) {
		// Check if we should only preview this query
		if ($this->previewWrite($sql)) return;

		// Benchmark query
		$pdo = $this->pdoConnection;
		$this->lastStatement = $this->benchmarkQuery($sql, function($sql) use($pdo) {
			return $pdo->query($sql);
		});

		// Check for errors
		if (!$this->lastStatement || $this->hasError($this->lastStatement)) {
			$this->databaseError($this->getLastError(), $errorLevel, $sql);
			return null;
		}

		return new PDOQuery($this->lastStatement);
	}
	
	/**
	 * Determines the PDO::PARAM_* type for a given PHP type string
	 * @param string $phpType Type of object in PHP
	 * @return integer PDO Parameter constant value
	 */
	public function getPDOParamType($phpType) {
		switch($phpType) {
			case 'boolean':
				return PDO::PARAM_BOOL;
			case 'NULL':
				return PDO::PARAM_NULL;
			case 'integer':
				return PDO::PARAM_INT;
			case 'object': // Allowed if the object or resource has a __toString method
			case 'resource':
			case 'float': // Not actually returnable from get_type
			case 'double':
			case 'string':
				return PDO::PARAM_STR;
			case 'blob':
				return PDO::PARAM_LOB;
			case 'array':
			case 'unknown type':
			default:
				user_error("Cannot bind parameter as it is an unsupported type ($phpType)", E_USER_ERROR);
		}
	}
	
	/**
	 * Bind all parameters to a PDOStatement
	 * 
	 * @param PDOStatement $statement
	 * @param array $parameters
	 */
	public function bindParameters(PDOStatement $statement, $parameters) {
		// Bind all parameters
		for($index = 0; $index < count($parameters); $index++) {
			$value = $parameters[$index];
			$phpType = gettype($value);

			// Allow overriding of parameter type using an associative array
			if($phpType === 'array') {
				$phpType = $value['type'];
				$value = $value['value'];
			}
			
			// Check type of parameter
			$type = $this->getPDOParamType($phpType);
			if($type === PDO::PARAM_STR) $value = strval($value);

			// Bind this value
			$statement->bindValue($index+1, $value, $type);
		}
	}
	
	public function preparedQuery($sql, $parameters, $errorLevel = E_USER_ERROR) {
		// Check if we should only preview this query
		if ($this->previewWrite($sql)) return;

		// Benchmark query
		$self = $this;
		$this->lastStatement = $this->benchmarkQuery($sql, function($sql) use($parameters, $self) {
			
			// Prepare statement
			$statement = $self->getOrPrepareStatement($sql);
			if(!$statement) return null;
			
			// Inject parameters
			$self->bindParameters($statement, $parameters);
			
			// Safely execute the statement
			$statement->execute($parameters);
			return $statement;
		});

		// Check for errors
		if (!$this->lastStatement || $this->hasError($this->lastStatement)) {
			$values = $this->parameterValues($parameters);
			$this->databaseError($this->getLastError(), $errorLevel, $sql, $values);
			return null;
		}

		return new PDOQuery($this->lastStatement);
	}
	
	/**
	 * Determine if a resource has an attached error
	 * 
	 * @param PDOStatement|PDO $resource the resource to check
	 * @return boolean Flag indicating true if the resource has an error
	 */
	protected function hasError($resource) {
		// No error if no resource
		if(empty($resource)) return false;
		
		// If the error code is empty the statement / connection has not been run yet
		$code = $resource->errorCode();
		if(empty($code)) return false;

		// Skip 'ok' and undefined 'warning' types.
		// @see http://docstore.mik.ua/orelly/java-ent/jenut/ch08_06.htm
		return $code !== '00000' && $code !== '01000';
	}

	public function getLastError() {
		if ($this->hasError($this->lastStatement)) {
			$error = $this->lastStatement->errorInfo();
		} elseif($this->hasError($this->pdoConnection)) {
			$error = $this->pdoConnection->errorInfo();
		}
		if (isset($error)) {
			return sprintf("%s-%s: %s", $error[0], $error[1], $error[2]);
		}
	}

	public function getGeneratedID($table) {
		return $this->pdoConnection->lastInsertId();
	}

	public function affectedRows() {
		if (empty($this->lastStatement)) return 0;
		return $this->lastStatement->rowCount();
	}

	public function selectDatabase($name) {
		$this->exec("USE \"{$name}\"");
		$this->databaseName = $name;
		return true;
	}

	public function getSelectedDatabase() {
		return $this->databaseName;
	}

	public function unloadDatabase() {
		$this->databaseName = null;
	}

	public function isActive() {
		return $this->databaseName && $this->pdoConnection;
	}

}
