<?php
/**
 * Global database interface, complete with static methods.
 * Use this class for interacting with the database.
 * 
 * @package framework
 * @subpackage model
 */
class DB {
	/**
	 * This constant was added in SilverStripe 2.4 to indicate that SQL-queries
	 * should now use ANSI-compatible syntax.  The most notable affect of this
	 * change is that table and field names should be escaped with double quotes
	 * and not backticks
	 */
	const USE_ANSI_SQL = true;
	

	/**
	 * The global database connection.
	 * @var SS_Database
	 */
	private static $connections = array();

	/**
	 * The last SQL query run.
	 * @var string
	 */
	public static $lastQuery;

	/**
	 * Internal flag to keep track of when db connection was attempted.
	 */
	private static $connection_attempted = false;

	/**
	 * Set the global database connection.
	 * Pass an object that's a subclass of SS_Database.  This object will be used when {@link DB::query()}
	 * is called.
	 * @param $connection The connecton object to set as the connection.
	 * @param $name The name to give to this connection.  If you omit this argument, the connection
	 * will be the default one used by the ORM.  However, you can store other named connections to
	 * be accessed through DB::getConn($name).  This is useful when you have an application that
	 * needs to connect to more than one database.
	 */
	static function setConn(SS_Database $connection, $name = 'default') {
		self::$connections[$name] = $connection;
	}

	/**
	 * Get the global database connection.
	 * @param $name An optional name given to a connection in the DB::setConn() call.  If omitted, 
	 * the default connection is returned.
	 * @return SS_Database
	 */
	static function getConn($name = 'default') {
		if(isset(self::$connections[$name])) {
			return self::$connections[$name];
		}
	}
	
	/**
	 * Set an alternative database to use for this browser session.
	 * This is useful when using testing systems other than SapphireTest; for example, Windmill.
	 * Set it to null to revert to the main database.
	 */
	static function set_alternative_database_name($dbname) {
		$_SESSION["alternativeDatabaseName"] = $dbname;
	}
	
	/**
	 * Get the name of the database in use
	 */
	static function get_alternative_database_name() {
		return $_SESSION["alternativeDatabaseName"];	
	}

	/**
	 * Connect to a database.
	 * Given the database configuration, this method will create the correct subclass of SS_Database,
	 * and set it as the global connection.
	 * @param array $database A map of options. The 'type' is the name of the subclass of SS_Database to use. For the rest of the options, see the specific class.
	 */
	static function connect($databaseConfig) {
		// This is used by TestRunner::startsession() to test up a test session using an alt
		if(isset($_SESSION) && !empty($_SESSION['alternativeDatabaseName'])) {
			$databaseConfig['database'] = $_SESSION['alternativeDatabaseName'];
		}

		if(!isset($databaseConfig['type']) || empty($databaseConfig['type'])) {
			user_error("DB::connect: Not passed a valid database config", E_USER_ERROR);
		}

		self::$connection_attempted = true;

		$dbClass = $databaseConfig['type'];
		$conn = new $dbClass($databaseConfig);

		self::setConn($conn);
	}
	
	/**
	 * Returns true if a database connection has been attempted.
	 * In particular, it lets the caller know if we're still so early in the execution pipeline that
	 * we haven't even tried to connect to the database yet.
	 */
	public static function connection_attempted() {
		return self::$connection_attempted;
	}

	/**
	 * Build the connection string from input.
	 * @param array $parameters The connection details.
	 * @return string $connect The connection string.
	 **/
	public static function getConnect($parameters) {
		return self::getConn()->getConnect($parameters);
	}

	/**
	 * Execute the given SQL query.
	 * @param string $sql The SQL query to execute
	 * @param int $errorLevel The level of error reporting to enable for the query
	 * @return SS_Query
	 */
	static function query($sql, $errorLevel = E_USER_ERROR) {
		self::$lastQuery = $sql;
		
		return self::getConn()->query($sql, $errorLevel);
	}

	/**
	 * Execute a complex manipulation on the database.
	 * A manipulation is an array of insert / or update sequences.  The keys of the array are table names,
	 * and the values are map containing 'command' and 'fields'.  Command should be 'insert' or 'update',
	 * and fields should be a map of field names to field values, including quotes.  The field value can
	 * also be a SQL function or similar.
	 * 
	 * Example:
	 * <code>
	 * array(
	 *   // Command: insert
	 *   "table name" => array(
	 *      "command" => "insert",
	 *      "fields" => array(
	 *         "ClassName" => "'MyClass'", // if you're setting a literal, you need to escape and provide quotes
	 *         "Created" => "now()", // alternatively, you can call DB functions
	 *         "ID" => 234,
	 *       ),
	 *      "id" => 234 // an alternative to providing ID in the fields list
	 *    ),
	 * 
	 *   // Command: update
	 *   "other table" => array(
	 *      "command" => "update",
	 *      "fields" => array(
	 *         "ClassName" => "'MyClass'",
	 *         "LastEdited" => "now()",
	 *       ),
	 *      "where" => "ID = 234",
	 *      "id" => 234 // an alternative to providing a where clause
	 *    ),
	 * )
	 * </code>
	 * 
	 * You'll note that only one command on a given table can be called.  
	 * That's a limitation of the system that's due to it being written for {@link DataObject::write()}, 
	 * which needs to do a single write on a number of different tables.
	 * 
	 * @param array $manipulation
	 */
	static function manipulate($manipulation) {
		self::$lastQuery = $manipulation;
		return self::getConn()->manipulate($manipulation);
	}

	/**
	 * Get the autogenerated ID from the previous INSERT query.
	 * @return int
	 */
	static function getGeneratedID($table) {
		return self::getConn()->getGeneratedID($table);
	}

	/**
	 * Check if the connection to the database is active.
	 * @return boolean
	 */
	static function isActive() {
		if($conn = self::getConn()) return $conn->isActive();
		else return false;
	}

	/**
	 * Create the database and connect to it. This can be called if the
	 * initial database connection is not successful because the database
	 * does not exist.
	 * @param string $connect Connection string
	 * @param string $username SS_Database username
	 * @param string $password SS_Database Password
	 * @param string $database SS_Database to which to create
	 * @return boolean Returns true if successful
	 */
	static function createDatabase($connect, $username, $password, $database) {
		return self::getConn()->createDatabase($connect, $username, $password, $database);
	}

	/**
	 * Create a new table.
	 * @param $tableName The name of the table
	 * @param $fields A map of field names to field types
	 * @param $indexes A map of indexes
	 * @param $options An map of additional options.  The available keys are as follows:
	 *   - 'MSSQLDatabase'/'MySQLDatabase'/'PostgreSQLDatabase' - database-specific options such as "engine" for MySQL.
	 *   - 'temporary' - If true, then a temporary table will be created
	 * @return The table name generated.  This may be different from the table name, for example with temporary tables.
	 */
	static function createTable($table, $fields = null, $indexes = null, $options = null) {
		return self::getConn()->createTable($table, $fields, $indexes, $options);
	}

	/**
	 * Create a new field on a table.
	 * @param string $table Name of the table.
	 * @param string $field Name of the field to add.
	 * @param string $spec The field specification, eg 'INTEGER NOT NULL'
	 */
	static function createField($table, $field, $spec) {
		return self::getConn()->createField($table, $field, $spec);
	}

	/**
	 * Generate the following table in the database, modifying whatever already exists
	 * as necessary.
	 * @param string $table The name of the table
	 * @param string $fieldSchema A list of the fields to create, in the same form as DataObject::$db
	 * @param string $indexSchema A list of indexes to create.  The keys of the array are the names of the index.
	 * @param boolean $hasAutoIncPK A flag indicating that the primary key on this table is an autoincrement type
	 * The values of the array can be one of:
	 *   - true: Create a single column index on the field named the same as the index.
	 *   - array('fields' => array('A','B','C'), 'type' => 'index/unique/fulltext'): This gives you full
	 *     control over the index.
	 * @param string $options SQL statement to append to the CREATE TABLE call.
	 */
	static function requireTable($table, $fieldSchema = null, $indexSchema = null, $hasAutoIncPK=true, $options = null, $extensions=null) {
		return self::getConn()->requireTable($table, $fieldSchema, $indexSchema, $hasAutoIncPK, $options, $extensions);
	}

	/**
	 * Generate the given field on the table, modifying whatever already exists as necessary.
	 * @param string $table The table name.
	 * @param string $field The field name.
	 * @param string $spec The field specification.
	 */
	static function requireField($table, $field, $spec) {
		return self::getConn()->requireField($table, $field, $spec);
	}

	/**
	 * Generate the given index in the database, modifying whatever already exists as necessary.
	 * @param string $table The table name.
	 * @param string $index The index name.
	 * @param string|boolean $spec The specification of the index. See requireTable() for more information.
	 */
	static function requireIndex($table, $index, $spec) {
		return self::getConn()->requireIndex($table, $index, $spec);
	}

	/**
	 * If the given table exists, move it out of the way by renaming it to _obsolete_(tablename).
	 * @param string $table The table name.
	 */
	static function dontRequireTable($table) {
		return self::getConn()->dontRequireTable($table);
	}
	
	/**
	 * See {@link SS_Database->dontRequireField()}.
	 * 
	 * @param string $table The table name.
	 * @param string $fieldName
	 */
	static function dontRequireField($table, $fieldName) {
		return self::getConn()->dontRequireField($table, $fieldName);
	}

	/**
	 * Checks a table's integrity and repairs it if necessary.
	 * @var string $tableName The name of the table.
	 * @return boolean Return true if the table has integrity after the method is complete.
	 */
	static function checkAndRepairTable($table) {
		return self::getConn()->checkAndRepairTable($table);
	}

	/**
	 * Return the number of rows affected by the previous operation.
	 * @return int
	 */
	static function affectedRows() {
		return self::getConn()->affectedRows();
	}

	/**
	 * Returns a list of all tables in the database.
	 * The table names will be in lower case.
	 * @return array
	 */
	static function tableList() {
		return self::getConn()->tableList();
	}
	
	/**
	 * Get a list of all the fields for the given table.
	 * Returns a map of field name => field spec.
	 * @param string $table The table name.
	 * @return array
	 */
	static function fieldList($table) {
		return self::getConn()->fieldList($table);
	}

	/**
	 * Enable supression of database messages.
	 */
	static function quiet() {
		return self::getConn()->quiet();
	}
	
	/**
	 * Show a message about database alteration.
	 */
	static function alteration_message($message,$type="") {
		return self::getConn()->alterationMessage($message, $type);
	}
	
}
