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
	
	/**
	 * Query the database.
	 * @var string $sql The query to be issued to the database.
	 * @return result Return the result of the quers (if any).
	 */
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
	
	/**
	 * Determine if the the table is active.
	 * @return bool
	 */
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
		
		switch ($parameters['type']) {
			case "mysql":
				$create = $dbConn->prepare("CREATE TABLE :tableName (ID INT(11) NOT NULL AUTO_INCREMENT, $fieldSchemas PRIMARY KEY (ID)) TYPE=MyISAM");
				break;
			case "postgresql":
				$create = $dbConn->prepare("CREATE TABLE :tableName (ID SERIAL, $fieldSchemas PRIMARY KEY (ID))");
				break;
			case "mssql":
				$create = $dbConn->prepare("CREATE TABLE :tableName (ID INT(11) IDENTITY(1,1), $fieldSchemas PRIMARY KEY (ID))");
				break;
			default:
				$this->databaseError("This database server is not available");
		}
		$create->bindParam(":tableName", $tableName);
		$create->execute();
		
		if ($indexes) {
			alterTable($tableName, null, $indexes, null, null);
		}
	}
	
	/**
	 * Alter fields and indexes in existing table.
	 * @var string $tableName The name of the table.
	 * @var string $newFields Fields to add.
	 * @var string $newIndexes Indexes to add.
	 * @var string $alteredFields Fields to change.
	 * @var string $alteredIndexes Indexes to change.
	 * @return void.
	 */
	public function alterTable($table, $newFields, $newIndexes, $alteredFields, $alteredIndexes) {
		
		if ($newFields) {
			$add = $dbConn->prepare("ALTER TABLE :table ADD :field :type");
			$add->bindParam(':table', $table);
			$add->bindParam(':field', $field);
			$add->bindParam(':type', $type);
			foreach ($newFields as $k => $v) {
				$field = $k;
				$type = $v;
				$add->execute();
			}
		}
		
		if ($newIndexes) {
			$add = $dbConn->prepare("CREATE INDEX :name ON :table :column");
			$add->bindParam(':table', $table);
			$add->bindParam(':name', $name);
			$add->bindParam(':column', $column);
			foreach ($newIndexes as $k => $v) {
				$name = $k;
				$column = $v;
				$add->execute();
			}
		}
		
		if ($alteredFields) {
			switch ($parameters['type']) {
				case "mysql":
					$alter = $dbConn->prepare("ALTER TABLE :table CHANGE :field :field :type");
					break;
				case "postgresql":
					$alter = $dbConn->prepare("
						BEGIN;
						ALTER TABLE :table RENAME :field TO oldfield;
						ALTER TABLE :table ADD COLUMN :field :type;
						UPDATE :table SET :field = CAST(oldfield AS :type);
						ALTER TABLE :table DROP COLUMN oldfield;
						COMMIT;
					");
				break;
				case "mssql":
					$this->dbh->query("ALTER TABLE :table ALTER COLUMN :field :type");
					break;
				default:
					$this->databaseError("This database server is not available");
			}
			$alter->bindParam(':table', $table);
			$alter->bindParam(':field', $field);
			$alter->bindParam(':type', $type);
			foreach ($alteredFields as $k => $v) {
				$field = $k;
				$type = $v;
				$alter->execute();
			}
		}
		
		if ($alteredIndexes) {
			$drop = $dbConn->prepare("DROP INDEX :drop");
			$alter->bindParam(':drop', $drop);
			$alter = $dbConn->prepare("CREATE INDEX :name ON :table :column");
			$alter->bindParam(':table', $table);
			$alter->bindParam(':name', $name);
			$alter->bindParam(':column', $column);
			foreach ($newIndexes as $k => $v) {
				$drop = $k;
				$drop->execute();
				$name = $k;
				$column = $v;
				$add->execute();
			}
		}
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
	 * Checks a table's integrity and repairs it if necessary - only available in MySQL, not supported in PostgreSQL and MS SQL.
	 * @var string $tableName The name of the table.
	 * @return boolean Return true if the table has integrity after the method is complete.
	 */
	public function checkAndRepairTable($tableName) {
		if ($parameters['type'] == "mysql") {
			if (!$this->runTableCheckCommand("CHECK TABLE `$tableName`")) {
				if(!Database::$supressOutput) {
					echo "<li style=\"color: orange\">Table $tableName: repaired</li>";
				}
				return $this->runTableCheckCommand("REPAIR TABLE `$tableName` USE_FRM");
			} else {
				return true;
			}
		} else {
			$this->databaseError("Checking and repairing of tables is only supported in MySQL, for other databases please do manual checks");
			return false;
		}
	}
	
	/**
	 * Helper function used by checkAndRepairTable.
	 * @param string $sql Query to run.
	 * @return boolean Returns if the query returns a successful result.
	 */
	protected function runTableCheckCommand($sql) {
		foreach($dbConn->query($sql) as $testRecord) {
			if(strtolower($testRecord['Msg_text']) != 'ok') {
				return false;
			}
		}
		return true;
	}

	/**
	 * Add the given field to the given table.
	 * @param string $tableName The name of the table on which to create the field.
	 * @param string $fieldName The field to create.
	 * @param string $fieldSpec The datatype of the field.
	 * @return void
	 */
	public function createField($tableName, $fieldName, $fieldSpec) {
		$create = $dbConn->prepare("ALTER TABLE :tableName ADD :fieldName :fieldSpec");
		$create->bindParam(":tableName", $tableName);
		$create->bindParam(":fieldName", $fieldName);
		$create->bindParam(":fieldSpec", $fieldSpec);
		$create->execute();
	}
	
	/**
	 * Change the database type of the given field.
	 * @param string $table The table where to change the field.
	 * @param string $field The field to change.
	 * @param string $type The new type of the field
	 * @return void
	 */
	public function alterField($table, $field, $type) {
		switch ($parameters['type']) {
			case "mysql":
				$alter = $dbConn->prepare("ALTER TABLE :table CHANGE :field :field :type");
				break;
			case "postgresql":
				$alter = $dbConn->prepare("
					BEGIN;
					ALTER TABLE :table RENAME :field TO oldfield;
					ALTER TABLE :table ADD COLUMN :field :type;
					UPDATE :table SET :field = CAST(oldfield AS :type);
					ALTER TABLE :table DROP COLUMN oldfield;
					COMMIT;
				");
			break;
			case "mssql":
				$this->dbh->query("ALTER TABLE :table ALTER COLUMN :field :type");
				break;
			default:
				$this->databaseError("This database server is not available");
		}
		$alter->bindParam(':table', $table);
		$alter->bindParam(':field', $field);
		$alter->bindParam(':type', $type);
		$alter->execute();
	}
	
	/**
	 * Create an index on a table.
	 * @param string $tableName The name of the table.
	 * @param string $indexName The name of the index.
	 * @param string $indexSpec The specification of the index, see Database::requireIndex() for more details.
	*  @return void
	 */
	public function createIndex($tableName, $indexName, $indexSpec) {
		$add = $dbConn->prepare("CREATE INDEX :name ON :table :column");
		$add->bindParam(':table', $tableName);
		$add->bindParam(':name', $indexName);
		$add->bindParam(':column', $indexSpec);
		$add->execute();
	}
	
	/**
	 * Alter an index on a table.
	 * @param string $tableName The name of the table.
	 * @param string $indexName The name of the index.
	 * @param string $indexSpec The specification of the index, see Database::requireIndex() for more details.
	*  @return void
	 */
	public function alterIndex($tableName, $indexName, $indexSpec) {
		$drop = $dbConn->prepare("DROP INDEX :drop");
		$alter->bindParam(':drop', $indexName);
		$alter = $dbConn->prepare("CREATE INDEX :name ON :table :column");
		$alter->bindParam(':table', $tableName);
		$alter->bindParam(':name', $indexName);
		$alter->bindParam(':column', $indexSpec);
		$drop->execute();
		$add->execute();
	}
	
	/**
	 * Get a list of all the fields for the given table.
	 * @param string $able Table of which to show the fields.
	 * Returns a map of field name => field spec.
	 */
	public function fieldList($table) {
	
	// to be done - SHOW is used extensively but very MySQL specific
	
	}
	
	/**
	 * Get a list of all the indexes for the given table.
	 * @param string $able Table of which to show the indexes.
	 * Returns a map of indexes.
	 */
	public function indexList($table) {
	
	// to be done - SHOW is used extensively but very MySQL specific
	
	}
	
	/**
	 * Returns a list of all the tables in the column.
	 * Table names will all be in lowercase.
	 * Returns a map of a table.
	 */
	public function tableList() {
	
	// to be done - SHOW is used extensively but very MySQL specific
	
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