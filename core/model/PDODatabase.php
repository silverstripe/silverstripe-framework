<?php

/**
 * PDO (general database) connector class.
 */
class PDODatabase extends Database {
	private $dbConn;
	private $active;
	private $database;
	
	
	/**
	 * Connect to a database (MySQL, PostgreSQL, or MS SQL).
	 * @param parameters An map of parameters, which should include:
	 * <ul><li>database: The database to connect with</li>
	 * <li>server: The server, eg, localhost</li>
	 * <li>username: The username to log on with</li>
	 * <li>password: The password to log on with</li>
	 * <li>database: The database to connect to</li></ul>
	 */
	public function __construct($parameters) {
		switch ($parameters['database']) {
			case "MySQL":
				$connect = 'mysql:host=' . $parameters['server'] . ';dbname=' . $parameters['database'];
				break;
			case "PostgreSQL":
				$connect = 'pgsql:host=' . $parameters['server'] . ';port=5432;dbname=' . $parameters['database'];
				break;
			case "MSSQL":
				$connect = 'mssql:host=' . $parameters['server'] . ';dbname=' . $parameters['database'];
				break;
			default: $this->databaseError("Database not available");
		}
		$this->dbConn = new PDO($connect, $parameters['username'], $parameters['password']);
		$this->database = $parameters['database'];
		if(!$this->dbConn) $this->databaseError("Could connect to MySQL database");
		parent::__construct();
	}
	
	/**
	 * Returns true if this database supports collations
	 */
	public function supportsCollations() {
	}
	
	//private $mysqlVersion;
	public function getVersion() {
	}
	
	public function query($sql, $errorLevel = E_USER_ERROR) {
	}
	public function getGeneratedID() {
	}
	public function getNextID($table) {
	}
	public function isActive() {
	}
	public function createDatabase() {
	}
	/**
	 * Create a new table with an integer primary key called ID.
	 */
	public function createTable($tableName) {
	}
	/**
	 * Create a new table with an integer primary key called ID.
	 */
	public function renameTable($oldTableName, $newTableName) {
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
 * A result-set from a MySQL database.
 */
class MySQLQuery extends Query {
	private $database;
	private $handle;

	/**
	 * Hook the result-set given into a Query class, suitable for use by sapphire.
	 * @param database The database object that created this query.
	 * @param handle the internal mysql handle that is points to the resultset.
	 */
	public function __construct(MySQLDatabase $database, $handle) {
		$this->database = $database;
		$this->handle = $handle;
		parent::__construct();
	}
	
	public function __destroy() {
	}
	
	public function seek($row) {
	}
	public function numRecords() {
	}
	
	public function nextRecord() {
	}
}

?>