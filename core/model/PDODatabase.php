<?php

/**
 * @package sapphire
 * @subpackage core
 */

/**
 * PDO (general database) connector class.
 */
class PDODatabase extends Database {
	/**
	 * Connection to the DBMS.
	 * @var resource
	 */
	private $dbConn;
	
	/**
	 * True if we are connected to a database.
	 * @var boolean
	 */
	private $active;
	
	/**
	 * The name of the database.
	 * @var string
	 */
	private $database;
	
	
	/**
	 * Connect to a database (MySQL, PostgreSQL, or MS SQL).
	 * @param parameters An map of parameters, which should include:
	 * <ul><li>database: The database to connect with</li>
	 * <li>server: The server, eg, localhost</li>
	 * <li>port: The port on which the server is listening (optional)</li>
	 * <li>instance: Instance of the server, MS SQL only (optional)</li>
	 * <li>username: The username to log on with</li>
	 * <li>password: The password to log on with</li>
	 * <li>database: The database to connect to</li></ul>
	 */
	public function __construct($parameters) {
		$connect = PDODatabase::getConnect($parameters);
		$connectWithDB = $connect . ';dbname=' . $parameters['database'];
		try { // Try connect to the database, if it does not exist, create it
			$this->dbConn = new PDO($connectWithDB, $parameters['username'], $parameters['password']);
		} catch (PDOException $e) {
			if (!self::createDatabase($connect, $parameters['username'], $parameters['password'], $parameters['database'])) {
				$this->databaseError("Could not connect to the database, make sure the server is available and user credentials are correct");
			}
		}
		parent::__construct();
	}
	
	/**
	 * Build the connection string from input.
	 * @param array $parameters The connection details.
	 * @return string $connect The connection string.
	 **/
	public function getConnect($parameters) {
		switch ($parameters['type']) {
			case "mysql":
				$port = '3306';
				$type = 'mysql';
				$instance = '';
				break;
			case "postgresql":
				$port = '5432';
				$type = 'pgsql';
				$instance = '';
				break;
			case "mssql":
				$port = '1433';
				if (isset($parameters['instance']) && $parameters['instance'] != '') {
					$instance = '\\' . $parameters['instance'];
				} else {
					$instance = '';
				}
				$type = 'mssql';
				break;
			default:
				$this->databaseError("This database server is not available");
		}
		if (isset($parameters['port']) && is_numeric($parameters['port'])) {
			$port = $parameters['port'];
		}
		$connect = $type . ':host=' . $parameters['server'] . $instance . ';port=' . $port;
		return $connect;
	}
	
	/**
	 * Returns true if this database supports collations
	 */
	public function supportsCollations() {
		$collations = false;
		switch (PDO::ATTR_DRIVER_NAME) {
			case "pgsql": // Generally supported in PostgreSQL (supported versions)
			case "mssql": // Generally supported in MS SQL (supported versions)
				$collations = true;
				break;
			case "mysql":
				if ($this->getVersion() >= 4.1) { // Supported in MySQL since 4.1
					$collations = true;
				}
				break;
		}
		return $collations;
	}
	
	/**
	 * Get the database version.
	 * @return float
	 */
	public function getVersion() {
		switch ($type) {
			case "mysql":
			case "postgresql":
				$query = "SELECT VERSION()";
				break;
			case "mssql":
				$query = "SELECT @@VERSION";
				break;
		}
		$getData = $dbConn->prepare($query);
		$getData->execute();
		$dbVersion = $getData->fetchColumn();
		$version = ereg_replace("([A-Za-z-])", "", $dbVersion);
		return substr(trim($version), 0, 3); // Just get the major and minor version
	}
	
	public function query($sql, $errorLevel = E_USER_ERROR) {
		if(isset($_REQUEST['previewwrite']) && in_array(strtolower(substr($sql,0,6)), array('insert','update'))) {
			echo "<p>Will execute: $sql</p>";
			return;
		}
			//Debug::backtrace();
		if(isset($_REQUEST['showqueries'])) { 
			Debug::message("\n" . $sql . "\n");
			$starttime = microtime(true);
		}
		
		$query = $dbConn->prepare($sql);
		$handle = $query->execute(); // Execute and save the return value (true or false)
		$result = $query->fetchAll(); // Get the result itself
		
		if(isset($_REQUEST['showqueries'])) {
			$duration = microtime(true) - $starttime;
			Debug::message("\n" . $duration . "\n");
		}
		
		if(!$handle && $errorLevel) {
			$error = $query->errorInfo();
			$this->databaseError("Couldn't run query: $sql | " . $error[2], $errorLevel);
		}
		return new PDOQuery($result);
	}
	
	/**
	 * Get the ID for the next new record for the table.
	 * @var string $table The name od the table.
	 * @return int
	 */
	public function getNextID($table) {
		$sql = "SELECT MAX(ID)+1 FROM :table";
		$create->bindParam(":table", $table);
		$query = $dbConn->prepare($sql);
		$handle = $query->execute();
		$result = $query->fetchColumn();
		return $handle ? $result : 1;
	}
	
	public function isActive() {
		return $this->active ? true : false;
	}
	
	/**
	 * Create the database and connect to it. This can be called if the
	 * initial database connection is not successful because the database
	 * does not exist.
	 * @param string $connect Connection string
	 * @param string $username Database username
	 * @param string $password Database Password
	 * @param string $database Database to which to create
	 * @return boolean Returns true if successful
	 */
	public function createDatabase($connect, $username, $password, $database) {
		try {
			$dbConn = new PDO($connect, $username, $password);
			$create = $dbConn->prepare("CREATE DATABASE :database");
			$create->bindParam(":database", $database);
			$create->execute();
			$this->active = true;
		} catch (PDOException $e) {
			$this->databaseError($e->getMessage());
			return false;
		}
		return true;
	}
	/**
	 * Create a new table with an integer primary key called ID.
	 * @var string $tableName The name of the table.
	 * @return void.
	 */
	public function createTable($tableName, $fields = null, $indexes = null) {
		$fieldSchemas = $indexSchemas = "";
		if ($fields) {
			foreach($fields as $k => $v) $fieldSchemas .= "`$k` $v,\n";
		}
		if ($indexes) {
			foreach($indexes as $k => $v) $fieldSchemas .= $this->getIndexSqlDefinition($k, $v) . ",\n";
		}
		
		switch ($parameters['type']) {
			case "mysql": $create = $dbConn->prepare("CREATE TABLE :tableName (ID INT(11) NOT NULL AUTO_INCREMENT, $fieldSchemas $indexSchemas PRIMARY KEY (ID)) TYPE=MyISAM");
				break;
			case "postgresql": $create = $dbConn->prepare("CREATE TABLE :tableName (ID SERIAL, $fieldSchemas $indexSchemas PRIMARY KEY (ID))");
				break;
			case "mssql": $create = $dbConn->prepare("CREATE TABLE :tableName (ID INT(11) IDENTITY(1,1), $fieldSchemas $indexSchemas PRIMARY KEY (ID))");
				break;
			default:
				$this->databaseError("This database server is not available");
		}
		$create->bindParam(":tableName", $tableName);
		$create->execute();
	}
	
	public function alterTable($table, $newFields, $newIndexes, $alteredFields, $alteredIndexes) {
		$fieldSchemas = $indexSchemas = "";
		
		if ($newFields) {
			foreach($newFields as $k => $v) $alterList[] .= "ADD `$k` $v";
		}
		if ($newIndexes) {
			foreach($newIndexes as $k => $v) $alterList[] .= "ADD " . $this->getIndexSqlDefinition($k, $v) . ",\n";
		}
		if ($alteredFields) {
			foreach($alteredFields as $k => $v) $alterList[] .= "CHANGE `$k` `$k` $v";
		}
		if ($alteredIndexes) foreach($alteredIndexes as $k => $v) {
			$alterList[] .= "DROP INDEX `$k`";
			$alterList[] .= "ADD ". $this->getIndexSqlDefinition($k, $v);
		}
		
		$alterations = implode(",\n", $alterList);
		$this->query("ALTER TABLE `$tableName` " . $alterations);
	}
	/**
	 * Rename an existing table, the TO is necessary for PostgreSQL and MS SQL.
	 * @var string $oldTableName The name of the existing  table.
	 * @var string $newTableName How the table should be named from now on.
	 * @return void.
	 */
	public function renameTable($oldTableName, $newTableName) {
		$query = "ALTER TABLE :oldTableName RENAME TO :newTableName";
		$create->bindParam(":oldTableName", $oldTableName);
		$create->bindParam(":newTableName", $newTableName);
		$create = $dbConn->prepare($query);
		$create->execute();
	}
	
	/**
	 * Checks a table's integrity and repairs it if necessary
	 */
	public function checkAndRepairTable($tableName) {
	}
	protected function runTableCheckCommand($sql) {
	}

	/**
	 * Add the given field to the given table.
	 */
	public function createField($tableName, $fieldName, $fieldSpec) {
		$create = $dbConn->prepare("ALTER TABLE :tableName ADD :fieldName :fieldSpec");
		$create->bindParam(":tableName", $tableName);
		$create->bindParam(":fieldName", $fieldName);
		$create->bindParam(":fieldSpec", $fieldSpec);
		$create->execute();
	}
	
	/**
	 * Change the database type of the given field
	 */
	public function alterField($tableName, $fieldName, $fieldSpec) {
	}
	
	/**
	 * Get a list of all the fields for the given table.
	 * Returns a map of field name => field spec
	 */
	public function fieldList($table) {
	}

	public function createIndex($tableName, $indexName, $indexSpec) {
	}
	public function alterIndex($tableName, $indexName, $indexSpec) {
	}
	public function indexList($table) {
	}


	/**
	 * Returns a list of all the tables in the column.
	 * Table names will all be in lowercase
	 */
	public function tableList() {
	}
	
	/**
	 * Return the number of rows affected by the previous operation.
	 */
	public function affectedRows() {
	}
}

/**
 * A result-set from a  database query (array).
 */
class PDOQuery extends Query {
	private $result;

	/**
	 * Hook the result-set given into a Query class, suitable for use by sapphire.
	 * @param array $result The array of all returned values.
	 */
	public function __construct(PDODatabase $result) {
		$this->result = $result;
		parent::__construct();
	}
	
	public function __destroy() {
		$this->result = null;
	}
	
	public function seek($row) {
		return in_array($row, $this->result);
	}
	public function numRecords() {
		return count($this->result);
	}
	
	public function nextRecord() {
		
	}
}

?>