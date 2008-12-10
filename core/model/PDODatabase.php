<?php
/**
 * PDO (general database) connector class.
 * @package sapphire
 * @subpackage model
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
	 * Last PDO statement, needed for affectedRows()
	 * @var PDO object
	 */
	private $stmt;

	/**
	 * Parameters used for creating a connection
	 * @var array
	 */
	private $param;

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
		$this->param = $parameters;
		$connect = self::getConnect($parameters);
		$connectWithDB = $connect . ';dbname=' . $parameters['database'];
		try { // Try connect to the database, if it does not exist, create it
			$this->dbConn = new PDO($connectWithDB, $parameters['username'], $parameters['password']);
		} catch (PDOException $e) {
			// To do - this is an instance method, not a static method.  Call it as such.
			if (!self::createDatabase($connect, $parameters['username'], $parameters['password'], $parameters['database'])) {
				$this->databaseError("Could not connect to the database, make sure the server is available and user credentials are correct");
			} else {
				$this->dbConn = new PDO($connectWithDB, $parameters['username'], $parameters['password']); // After creating the database, connect to it
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
		switch (self::getDatabaseServer()) {
			case "pgsql": // Generally supported in PostgreSQL (supported versions), but handled differently than in MySQL, so do not set
			case "mssql": // Generally supported in MS SQL (supported versions), but handled differently than in MySQL, so do not set
				$collations = false;
				break;
			case "mysql":
				if (self::getVersion() >= 4.1) { // Supported in MySQL since 4.1
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
		switch (self::getDatabaseServer()) {
			case "mysql":
			case "pgsql":
				$query = "SELECT VERSION()";
				break;
			case "mssql":
				$query = "SELECT @@VERSION";
				break;
		}
		$stmt = $this->dbConn->prepare($query);
		$stmt->execute();
		$dbVersion = $stmt->fetchColumn();
		$version = ereg_replace("([A-Za-z-])", "", $dbVersion);
		return substr(trim($version), 0, 3); // Just get the major and minor version
	}

	/**
	 * Get the database server, namely mysql, pgsql, or mssql.
	 * @return string
	 */
	public function getDatabaseServer() {
		return $this->dbConn->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Query the database.
	 * @var string $sql The query to be issued to the database.
	 * @return result Return the result of the quers (if any).
	 */
	public function query($sql, $errorLevel = E_USER_ERROR) {
		if(isset($_REQUEST['previewwrite']) && in_array(strtolower(substr($sql,0,6)), array('insert','update'))) {
			Debug::message("Will execute: $sql");
			return;
		}
		//Debug::backtrace();
		if(isset($_REQUEST['showqueries'])) {
			Debug::message("\n" . $sql . "\n");
			$starttime = microtime(true);
		}
		
		$stmt = $dbConn->prepare($sql);

		$stmt = $this->dbConn->prepare($sql);
		$handle = $stmt->execute(); // Execute and save the return value (true or false)

		if(isset($_REQUEST['showqueries'])) {
			$duration = microtime(true) - $starttime;
			Debug::message("\n" . $duration . "\n");
		}

		if(!$handle && $errorLevel) {
			$error = $stmt->errorInfo();
			$this->databaseError("Couldn't run query: $sql | " . $error[2], $errorLevel);
		}
		return new PDOQuery($this, $stmt);
	}

	/**
	 * Get the ID for the next new record for the table.
	 * Get the autogenerated ID from the previous INSERT query.
	 * Simulate mysql_insert_id by fetching the highest ID as there is no other reliable method across databases.
	 * @return int
	 */
	public function getGeneratedID($table) {
		$stmt = $this->dbConn->prepare("SELECT MAX(ID) FROM $table");
		$handle = $stmt->execute();
		$result = $stmt->fetchColumn();
		return $handle ? $result : 0;
	}


	/**
	 * OBSOLETE: Get the ID for the next new record for the table.
	 * @var string $table The name od the table.
	 * @return int
	 */
	public function getNextID($table) {
		user_error('getNextID is OBSOLETE (and will no longer work properly)', E_USER_WARNING);
		$stmt = $this->dbConn->prepare("SELECT MAX(ID)+1 FROM $table");
		$handle = $stmt->execute();
		$result = $stmt->fetchColumn();
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
	 * @todo This shouldn't take any arguments; it should take the information given in the constructor instead.
	 */
	public function createDatabase() {
		try {
			$dbh = new PDO($connect, $username, $password);
			$stmt = $dbh->prepare("CREATE DATABASE $database");
			$stmt->execute();
			$this->active = true;
		} catch (PDOException $e) {
			$this->databaseError($e->getMessage());
			return false;
		}
		return true;
	}

	/**
	 * Returns true if the named database exists.
	 */
	public function databaseExists($name) {
		$SQL_name = Convert::raw2sql($name);
		$connect = self::getConnect($this->param);
		$connectWithDB = $connect . ';dbname=' . $SQL_name;
		try { // Try connect to the database
			$testConn = new PDO($connectWithDB, $this->param['username'], $this->param['password']);
		} catch (PDOException $e) {
			return false;
		}
		return true;
	}

	/**
	 * Switches to the given database.
	 * Simply switching database in PDO is not possible, you need to create a new PDO object
	 */
	public function selectDatabase($dbname) {
		$this->dbConn = null; // Remove the old connection
		$connect = self::getConnect($param);
		$connectWithDB = $connect . ';dbname=' . $dbname;
		try { // Try connect to the database, if it does not exist, create it
			$this->dbConn = new PDO($connectWithDB, $param['username'], $param['password']);
		} catch (PDOException $e) {
			if (!self::createDatabase($connect, $param['username'], $param['password'], $dbname)) {
				$this->databaseError("Could not connect to the database, make sure the server is available and user credentials are correct");
			} else {
				$this->dbConn = new PDO($connectWithDB, $param['username'], $param['password']); // After creating the database, connect to it
			}
		}
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
		
		switch (self::getDatabaseServer()) {
			case "mysql":
				$stmt = $this->dbConn->prepare("CREATE TABLE $tableName (ID INT(11) NOT NULL AUTO_INCREMENT, $fieldSchemas PRIMARY KEY (ID)) TYPE=MyISAM");
				break;
			case "pgsql":
				$stmt = $this->dbConn->prepare("CREATE TABLE $tableName (ID SERIAL, $fieldSchemas PRIMARY KEY (ID))");
				break;
			case "mssql":
				$stmt = $this->dbConn->prepare("CREATE TABLE $tableName (ID INT(11) IDENTITY(1,1), $fieldSchemas PRIMARY KEY (ID))");
				break;
			default:
				$this->databaseError("This database server is not available");
		}
		$stmt->execute();

		if ($indexes) {
			self::alterTable($tableName, null, $indexes, null, null);
		}
	}

	/**
	 * Alter fields and indexes in existing table.
	 * @param string $tableName The name of the table.
	 * @param string $newFields Fields to add.
	 * @param string $newIndexes Indexes to add.
	 * @param string $alteredFields Fields to change.
	 * @param string $alteredIndexes Indexes to change.
	 * @return void.
	 */
	public function alterTable($table, $newFields = null, $newIndexes = null, $alteredFields = null, $alteredIndexes = null) {

		if ($newFields) {
			foreach ($newFields as $field => $type) {
				$stmt = $this->dbConn->prepare("ALTER TABLE $table ADD $field $type");
				$stmt->execute();
			}
		}

		if ($newIndexes) {
			foreach ($newIndexes as $name => $column) {
				$stmt = $this->dbConn->prepare("CREATE INDEX $name ON $table $column");
				$stmt->execute();
			}
		}

		if ($alteredFields) {
			foreach ($alteredFields as $field => $type) {
				self::alterField($table, $field, $type);
			}
		}

		if ($alteredIndexes) {
			foreach ($newIndexes as $name => $column) {
				$this->dbConn->query("DROP INDEX $name");
				$stmt = $this->dbConn->prepare("CREATE INDEX $name ON $table $column");
				$stmt->execute();
			}
		}
	}

	/**
	 * Rename an existing table, the TO is necessary for PostgreSQL and MS SQL.
	 * @param string $oldTableName The name of the existing table.
	 * @param string $newTableName How the table should be named from now on.
	 * @return void.
	 */
	public function renameTable($oldTableName, $newTableName) {
		$stmt = $this->dbConn->prepare("ALTER TABLE $oldTableName RENAME TO $newTableName");
		$stmt->execute();
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
		foreach($this->dbConn->query($sql) as $testRecord) {
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
		$stmt = $this->dbConn->prepare("ALTER TABLE $tableName ADD $fieldName $fieldSpec");
		$stmt->execute();
	}

	/**
	 * Change the database type of the given field.
	 * @param string $table The table where to change the field.
	 * @param string $field The field to change.
	 * @param string $type The new type of the field
	 * @return void
	 */
	public function alterField($table, $field, $type) {
		switch (self::getDatabaseServer()) {
			case "mysql":
				$stmt = $this->dbConn->prepare("ALTER TABLE $table CHANGE $field $field $type");
				break;
			case "pgsql":
				$stmt = $this->dbConn->prepare("
					BEGIN;
					ALTER TABLE $table RENAME $field TO oldfield;
					ALTER TABLE $table ADD COLUMN $field $type;
					UPDATE $table SET $field = CAST(oldfield AS $type);
					ALTER TABLE $table DROP COLUMN oldfield;
					COMMIT;
				");
			break;
			case "mssql":
				$stmt = $this->dbConn->prepare("ALTER TABLE $table ALTER COLUMN $field $type");
				break;
			default:
				$this->databaseError("This database server is not available");
		}
		$stmt->execute();
	}
	
	/**
	 * @todo implement renameField()
	 */
	public function renameField($tableName, $oldName, $newName) {
		user_error('PDODatabase::renameField() - Not implemented', E_USER_ERROR);
	}

	/**
	 * Create an index on a table.
	 * @param string $tableName The name of the table.
	 * @param string $indexName The name of the index.
	 * @param string $indexSpec The specification of the index, see Database::requireIndex() for more details.
	 *  @return void
	 */
	public function createIndex($tableName, $indexName, $indexSpec) {
		$stmt = $this->dbConn->prepare("CREATE INDEX $indexName ON $tableName $indexSpec");
		$stmt->execute();
	}

	/**
	 * Alter an index on a table.
	 * @param string $tableName The name of the table.
	 * @param string $indexName The name of the index.
	 * @param string $indexSpec The specification of the index, see Database::requireIndex() for more details.
	 *  @return void
	 */
	public function alterIndex($tableName, $indexName, $indexSpec) {
		$this->dbConn->query("DROP INDEX $indexName");
		$stmt = $this->dbConn->prepare("CREATE INDEX $indexName ON $tableName $indexSpec");
		$stmt->execute();
	}

	/**
	 * Get a list of all the fields for the given table.
	 * The results are not totally equal for all databases (for example collations are handled very differently, PostgreSQL disregards zerofill,...)
	 * but as close as possible and necessary.
	 * @param string $able Table of which to show the fields.
	 * Returns a map of field name => field spec.
	 */
	public function fieldList($table) {
		switch (self::getDatabaseServer()) {
			case "mysql":
				foreach ($this->dbConn->query("SHOW FULL FIELDS IN $table") as $field) {
					$fieldSpec = $field['Type'];
					if(!$field['Null'] || $field['Null'] == 'NO') {
						$fieldSpec .= ' not null';
					}
					if($field['Collation'] && $field['Collation'] != 'NULL') {
						$values = $this->dbConn->prepare("SHOW COLLATION LIKE '$field[Collation]'");
						$values->execute();
						$collInfo = $values->fetchColumn();
						$fieldSpec .= " character set $collInfo[Charset] collate $field[Collation]";
					}
					if($field['Default'] || $field['Default'] === "0") {
						$fieldSpec .= " default '" . addslashes($field['Default']) . "'";
					}
					if($field['Extra']) $fieldSpec .= " $field[Extra]";
					$fieldList[$field['Field']] = $fieldSpec;
				}
				break;
			case "pgsql":
				foreach ($this->dbConn->query("
					SELECT
						column_name AS cname,
						column_default AS cdefault,
						is_nullable AS nullable,
						data_type AS dtype,
						character_maximum_length AS maxlength
					FROM
						information_schema.columns
					WHERE
						table_name = $table
				") as $field) {
					if ($field['maxlength']) {
						$fieldSpec = $field['dtype'] . "(" . $field['maxlength'] . ")";
					} else {
						$fieldSpec = $field['dtype'];
					}
					if ($field['nullable'] == 'NO') {
						$fieldSpec .= ' not null';
					}
					if($field['cdefault'] || $field['cdefault'] === "0") {
						$fieldSpec .= " default '" . addslashes($field['cdefault']) . "'";
					}
					$fieldList[$field['cname']] = $fieldSpec;
				}
				break;
			case "mssql":
				foreach ($this->dbConn->query("
					SELECT
						COLUMN_NAME AS 'cname',
						COLUMN_DEFAULT AS 'cdefault',
						IS_NULLABLE AS 'nullable',
						DATA_TYPE AS 'dtype',
						COLLATION_NAME AS 'collname',
						CHARACTER_SET_NAME AS 'cset',
						CHARACTER_MAXIMUM_LENGTH AS 'maxlength'
					FROM
						information_schema.columns
					WHERE
						TABLE_NAME = '$table'
				") as $field) {
					if ($field['maxlength']) {
						$fieldSpec = $field['dtype'] . "(" . $field['maxlength'] . ")";
					} else {
						$fieldSpec = $field['dtype'];
					}
					if ($field['nullable'] == 'NO') {
						$fieldSpec .= ' not null';
					}
					
					if($field['collname'] && $field['collname'] != 'NULL') {
						$fieldSpec .= " character set $field[cset] collate $field[collname]";
					}
					
					if($field['cdefault'] || $field['cdefault'] === "0") {
						$fieldSpec .= " default '" . addslashes($field['cdefault']) . "'";
					}
					
					$fieldList[$field['cname']] = $fieldSpec;
				}
				break;
			default:
				$this->databaseError("This database server is not available");
		}
		
		return $fieldList;
	}

	/**
	 * Get a list of all the indexes for the given table.
	 * @param string $able Table of which to show the indexes.
	 * Returns a map of indexes.
	 */
	public function indexList($table) {
		switch (self::getDatabaseServer()) {
			case "mysql":
				foreach($this->dbConn->query("SHOW INDEXES IN '$table'") as $index) {
					$groupedIndexes[$index['Key_name']]['fields'][$index['Seq_in_index']] = $index['Column_name'];
					if($index['Index_type'] == 'FULLTEXT') {
						$groupedIndexes[$index['Key_name']]['type'] = 'fulltext ';
					} else if(!$index['Non_unique']) {
						$groupedIndexes[$index['Key_name']]['type'] = 'unique ';
					} else {
						$groupedIndexes[$index['Key_name']]['type'] = '';
					}
				}
				foreach($groupedIndexes as $index => $details) {
					ksort($details['fields']);
					$indexList[$index] = $details['type'] . '(' . implode(',',$details['fields']) . ')';
				}
				break;
			case "pgsql":
				foreach($this->dbConn->query("SELECT indexname, indexdef FROM pg_indexes WHERE tablename = '$table'") as $index) {
					$indexList[$index['indexname']] = $index['indexdef'];
				}
				break;
			case "mssql":
				foreach($this->dbConn->query("
					SELECT
						i.name AS 'iname',
						i.type_desc AS 'itype',
						s.name AS 'sname'
					FROM
						sys.indexes i,
						sys.objects o,
						sys.index_columns c,
						sys.columns s
					WHERE
						o.name = '$table'
						AND o.object_id = i.object_id
						AND o.object_id = c.object_id
						AND o.object_id = s.object_id
						AND s.column_id = c.column_id
				") as $index) {
					$indexList[$index['iname']] = $index['itype'] . " (" . $index['sname'] . ")";
				}
				break;
			default:
				$this->databaseError("This database server is not available");
		}
		
		return $indexList;
	}

	/**
	 * Returns a list of all the tables in the database.
	 * Table names will all be in lowercase.
	 * Returns a map of a table.
	 */
	public function tableList() {
		switch (self::getDatabaseServer()) {
			case "mysql":
				$sql = "SHOW TABLES";
				break;
			case "pgsql":
				$sql = "SELECT tablename FROM pg_tables WHERE tablename NOT ILIKE 'pg_%' AND tablename NOT ILIKE 'sql_%'";
				break;
			case "mssql":
				$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME NOT LIKE 'sysdiagrams%'";
				break;
			default:
				$this->databaseError("This database server is not available");
		}
		if (is_array($this->dbConn->query($sql))) {
			foreach($this->dbConn->query($sql) as $record) {
				$table = strtolower(reset($record));
				$tables[$table] = $table;
			}
		}
		return isset($tables) ? $tables : null;
	}

	/**
	 * Return the number of rows affected (DELETE, INSERT, or UPDATE) by the previous operation.
	 */
	public function affectedRows() {
		return $stmt->rowCount();
	}
}

/**
 * A result-set from a database query (array).
 * @package sapphire
 * @subpackage model
 */
class PDOQuery extends Query {
	private $database;
	private $handle;

	/**
	 * The object that holds the result set.
	 * @var $stmt
	 */
	private $stmt;

	/**
	 * Hook the result-set given into a Query class, suitable for use by sapphire.
	 * @param PDO object $stmt The object of all returned values.
	 */
	public function __construct(PDODatabase $database, $stmt) {
		$this->database = $database;
		$this->stmt = $stmt;
		parent::__construct();
	}
	

	/**
	 * Free the result-set given into a Query class.
	 */
	public function __destroy() {
		$this->stmt = null;
	}
	

	/**
	* Determine if a given element is part of the result set.
	* @param string string $row The element to search for.
	*/
	public function seek($row) {
		return in_array($row, $this->stmt->fetchAll());
	}

	/**
	* Return the number of results.
	*/
	public function numRecords() {
		$value = $this->stmt->fetchAll();
		return count($value);
	}
	

	/**
	*
	*/
	public function nextRecord() {
		$record = $this->stmt->fetch(PDO::FETCH_ASSOC);
		if (count($record)) {
			return $record;
		} else {
			return false;
		}
	}
}

?>