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
	 *
	 * @param $connection The connecton object to set as the connection.
	 * @param $name The name to give to this connection.  If you omit this argument, the connection
	 * will be the default one used by the ORM.  However, you can store other named connections to
	 * be accessed through DB::get_conn($name).  This is useful when you have an application that
	 * needs to connect to more than one database.
	 */
	public static function set_conn(SS_Database $connection, $name = 'default') {
		self::$connections[$name] = $connection;
	}

	/**
	 * @deprecated since version 4.0 Use DB::set_conn instead
	 */
	public static function setConn(SS_Database $connection, $name = 'default') {
		Deprecation::notice('4.0', 'Use DB::set_conn instead');
		self::set_conn($connection, $name);
	}

	/**
	 * Get the global database connection.
	 *
	 * @param string $name An optional name given to a connection in the DB::setConn() call.  If omitted,
	 * the default connection is returned.
	 * @return SS_Database
	 */
	public static function get_conn($name = 'default') {
		if(isset(self::$connections[$name])) {
			return self::$connections[$name];
		}
	}

	/**
	 * @deprecated since version 4.0 Use DB::get_conn instead
	 */
	public static function getConn($name = 'default') {
		Deprecation::notice('4.0', 'Use DB::get_conn instead');
		return self::get_conn($name);
	}

	/**
	 * Retrieves the schema manager for the current database
	 *
	 * @param string $name An optional name given to a connection in the DB::setConn() call.  If omitted,
	 * the default connection is returned.
	 * @return DBSchemaManager
	 */
	public static function get_schema($name = 'default') {
		$connection = self::get_conn($name);
		if($connection) return $connection->getSchemaManager();
	}

	/**
	 * Builds a sql query with the specified connection
	 *
	 * @param SQLExpression $expression The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @param string $name An optional name given to a connection in the DB::setConn() call.  If omitted,
	 * the default connection is returned.
	 * @return string The resulting SQL as a string
	 */
	public static function build_sql(SQLExpression $expression, &$parameters, $name = 'default') {
		$connection = self::get_conn($name);
		if($connection) {
			return $connection->getQueryBuilder()->buildSQL($expression, $parameters);
		} else {
			$parameters = array();
			return null;
		}
	}

	/**
	 * Retrieves the connector object for the current database
	 *
	 * @param string $name An optional name given to a connection in the DB::setConn() call.  If omitted,
	 * the default connection is returned.
	 * @return DBConnector
	 */
	public static function get_connector($name = 'default') {
		$connection = self::get_conn($name);
		if($connection) return $connection->getConnector();
	}

	/**
	 * Set an alternative database in a browser cookie,
	 * with the cookie lifetime set to the browser session.
	 * This is useful for integration testing on temporary databases.
	 *
	 * There is a strict naming convention for temporary databases to avoid abuse:
	 * <prefix> (default: 'ss_') + tmpdb + <7 digits>
	 * As an additional security measure, temporary databases will
	 * be ignored in "live" mode.
	 *
	 * Note that the database will be set on the next request.
	 * Set it to null to revert to the main database.
	 */
	public static function set_alternative_database_name($name = null) {
		// Skip if CLI
		if(Director::is_cli()) {
			return;
		}
		if($name) {
			if(!self::valid_alternative_database_name($name)) {
				throw new InvalidArgumentException(sprintf(
					'Invalid alternative database name: "%s"',
					$name
				));
			}

			$key = Config::inst()->get('Security', 'token');
			if(!$key) {
				throw new LogicException('"Security.token" not found, run "sake dev/generatesecuretoken"');
			}
			if(!function_exists('mcrypt_encrypt')) {
				throw new LogicException('DB::set_alternative_database_name() requires the mcrypt PHP extension');
			}

			$key = md5($key); // Ensure key is correct length for chosen cypher
			$ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
			$iv = mcrypt_create_iv($ivSize);
			$encrypted = mcrypt_encrypt(
				MCRYPT_RIJNDAEL_256, $key, $name, MCRYPT_MODE_CFB, $iv
			);

			// Set to browser session lifetime, and restricted to HTTP access only
			Cookie::set("alternativeDatabaseName", base64_encode($encrypted), 0, null, null, false, true);
			Cookie::set("alternativeDatabaseNameIv", base64_encode($iv), 0, null, null, false, true);
		} else {
			Cookie::force_expiry("alternativeDatabaseName", null, null, false, true);
			Cookie::force_expiry("alternativeDatabaseNameIv", null, null, false, true);
		}
	}

	/**
	 * Get the name of the database in use
	 */
	public static function get_alternative_database_name() {
		$name = Cookie::get("alternativeDatabaseName");
		$iv = Cookie::get("alternativeDatabaseNameIv");

		if($name) {
			$key = Config::inst()->get('Security', 'token');
			if(!$key) {
				throw new LogicException('"Security.token" not found, run "sake dev/generatesecuretoken"');
			}
			if(!function_exists('mcrypt_encrypt')) {
				throw new LogicException('DB::set_alternative_database_name() requires the mcrypt PHP extension');
			}
			$key = md5($key); // Ensure key is correct length for chosen cypher
			$decrypted = mcrypt_decrypt(
				MCRYPT_RIJNDAEL_256, $key, base64_decode($name), MCRYPT_MODE_CFB, base64_decode($iv)
			);
			return (self::valid_alternative_database_name($decrypted)) ? $decrypted : false;
		} else {
			return false;
		}
	}

	/**
	 * Determines if the name is valid, as a security
	 * measure against setting arbitrary databases.
	 *
	 * @param  String $name
	 * @return Boolean
	 */
	public static function valid_alternative_database_name($name) {
		if(Director::isLive()) return false;

		$prefix = defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : 'ss_';
		$pattern = strtolower(sprintf('/^%stmpdb\d{7}$/', $prefix));
		return (bool)preg_match($pattern, $name);
	}

	/**
	 * Connect to a database.
	 *
	 * Given the database configuration, this method will create the correct
	 * subclass of {@link SS_Database}.
	 *
	 * @param array $database A map of options. The 'type' is the name of the subclass of SS_Database to use. For the
	 *                        rest of the options, see the specific class.
	 * @param string $name identifier for the connection
	 *
	 * @return SS_Database
	 */
	public static function connect($databaseConfig, $label = 'default') {

		// This is used by the "testsession" module to test up a test session using an alternative name
		if($name = self::get_alternative_database_name()) {
			$databaseConfig['database'] = $name;
		}

		if(!isset($databaseConfig['type']) || empty($databaseConfig['type'])) {
			user_error("DB::connect: Not passed a valid database config", E_USER_ERROR);
		}

		self::$connection_attempted = true;

		$dbClass = $databaseConfig['type'];

		// Using Injector->create allows us to use registered configurations
		// which may or may not map to explicit objects
		$conn = Injector::inst()->create($dbClass);
		$conn->connect($databaseConfig);

		self::set_conn($conn, $label);

		return $conn;
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
	 * @deprecated since version 4.0 DB::getConnect was never implemented and is obsolete
	 */
	public static function getConnect($parameters) {
		Deprecation::notice('4.0', 'DB::getConnect was never implemented and is obsolete');
	}

	/**
	 * Execute the given SQL query.
	 * @param string $sql The SQL query to execute
	 * @param int $errorLevel The level of error reporting to enable for the query
	 * @return SS_Query
	 */
	public static function query($sql, $errorLevel = E_USER_ERROR) {
		self::$lastQuery = $sql;

		return self::get_conn()->query($sql, $errorLevel);
	}

	/**
	 * Helper function for generating a list of parameter placeholders for the
	 * given argument(s)
	 *
	 * @param array|integer $input An array of items needing placeholders, or a
	 * number to specify the number of placeholders
	 * @param string $join The string to join each placeholder together with
	 * @return string|null Either a list of placeholders, or null
	 */
	public static function placeholders($input, $join = ', ') {
		if(is_array($input)) {
			$number = count($input);
		} elseif(is_numeric($input)) {
			$number = intval($input);
		} else {
			return null;
		}
		if($number === 0) return null;
		return implode($join, array_fill(0, $number, '?'));
	}

	/**
	 * @param string $sql The parameterised query
	 * @param array $parameters The parameters to inject into the query
	 *
	 * @return string
	 */
	public static function inline_parameters($sql, $parameters) {
		$segments = preg_split('/\?/', $sql);
		$joined = '';
		$inString = false;
		$numSegments = count($segments);
		for($i = 0; $i < $numSegments; $i++) {
			$input = $segments[$i];
			// Append next segment
			$joined .= $segments[$i];
			// Don't add placeholder after last segment
			if($i === $numSegments - 1) {
				break;
			}
			// check string escape on previous fragment
			// Remove escaped backslashes, count them!
			$input = preg_replace('/\\\\\\\\/', '', $input);
			// Count quotes
			$totalQuotes = substr_count($input, "'"); // Includes double quote escaped quotes
			$escapedQuotes = substr_count($input, "\\'");
			if((($totalQuotes - $escapedQuotes) % 2) !== 0) {
				$inString = !$inString;
			}
			// Append placeholder replacement
			if($inString) {
				// Literal question mark
				$joined .= '?';
				continue;
			}

			// Encode and insert next parameter
			$next = array_shift($parameters);
			if(is_array($next) && isset($next['value'])) {
				$next = $next['value'];
			}
			if (is_bool($next)) {
				$value = $next ? '1' : '0';
			}
			elseif (is_int($next)) {
				$value = $next;
			}
			else {
				$value = DB::is_active() ? Convert::raw2sql($next, true) : $next;
			}
			$joined .= $value;
		}
		return $joined;
	}

	/**
	 * Execute the given SQL parameterised query with the specified arguments
	 *
	 * @param string $sql The SQL query to execute. The ? character will denote parameters.
	 * @param array $parameters An ordered list of arguments.
	 * @param int $errorLevel The level of error reporting to enable for the query
	 * @return SS_Query
	 */
	public static function prepared_query($sql, $parameters, $errorLevel = E_USER_ERROR) {
		self::$lastQuery = $sql;

		return self::get_conn()->preparedQuery($sql, $parameters, $errorLevel);
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
	 * @todo Update this to support paramaterised queries
	 *
	 * @param array $manipulation
	 */
	public static function manipulate($manipulation) {
		self::$lastQuery = $manipulation;
		return self::get_conn()->manipulate($manipulation);
	}

	/**
	 * Get the autogenerated ID from the previous INSERT query.
	 * @return int
	 */
	public static function get_generated_id($table) {
		return self::get_conn()->getGeneratedID($table);
	}

	/**
	 * @deprecated since version 4.0 Use DB::get_generated_id instead
	 */
	public static function getGeneratedID($table) {
		Deprecation::notice('4.0', 'Use DB::get_generated_id instead');
		return self::get_generated_id($table);
	}

	/**
	 * Check if the connection to the database is active.
	 *
	 * @return boolean
	 */
	public static function is_active() {
		return ($conn = self::get_conn()) && $conn->isActive();
	}

	/**
	 * @deprecated since version 4.0 Use DB::is_active instead
	 */
	public static function isActive() {
		Deprecation::notice('4.0', 'Use DB::is_active instead');
		return self::is_active();
	}

	/**
	 * Create the database and connect to it. This can be called if the
	 * initial database connection is not successful because the database
	 * does not exist.
	 *
	 * @param string $database Name of database to create
	 * @return boolean Returns true if successful
	 */
	public static function create_database($database) {
		return self::get_conn()->selectDatabase($database, true);
	}

	/**
	 * @deprecated since version 4.0 Use DB::create_database instead
	 */
	public static function createDatabase($connect, $username, $password, $database) {
		Deprecation::notice('4.0', 'Use DB::create_database instead');
		return self::create_database($database);
	}

	/**
	 * Create a new table.
	 * @param string $tableName The name of the table
	 * @param array$fields A map of field names to field types
	 * @param array $indexes A map of indexes
	 * @param array $options An map of additional options.  The available keys are as follows:
	 *   - 'MSSQLDatabase'/'MySQLDatabase'/'PostgreSQLDatabase' - database-specific options such as "engine"
	 *     for MySQL.
	 *   - 'temporary' - If true, then a temporary table will be created
	 * @return string The table name generated.  This may be different from the table name, for example with
	 * temporary tables.
	 */
	public static function create_table($table, $fields = null, $indexes = null, $options = null,
		$advancedOptions = null
	) {
		return self::get_schema()->createTable($table, $fields, $indexes, $options, $advancedOptions);
	}

	/**
	 * @deprecated since version 4.0 Use DB::create_table instead
	 */
	public static function createTable($table, $fields = null, $indexes = null, $options = null) {
		Deprecation::notice('4.0', 'Use DB::create_table instead');
		return self::create_table($table, $fields, $indexes, $options);
	}

	/**
	 * Create a new field on a table.
	 * @param string $table Name of the table.
	 * @param string $field Name of the field to add.
	 * @param string $spec The field specification, eg 'INTEGER NOT NULL'
	 */
	public static function create_field($table, $field, $spec) {
		return self::get_schema()->createField($table, $field, $spec);
	}

	/**
	 * @deprecated since version 4.0 Use DB::create_field instead
	 */
	public static function createField($table, $field, $spec) {
		Deprecation::notice('4.0', 'Use DB::create_field instead');
		return self::create_field($table, $field, $spec);
	}

	/**
	 * Generate the following table in the database, modifying whatever already exists
	 * as necessary.
	 *
	 * @param string $table The name of the table
	 * @param string $fieldSchema A list of the fields to create, in the same form as DataObject::$db
	 * @param string $indexSchema A list of indexes to create.  The keys of the array are the names of the index.
	 * The values of the array can be one of:
	 *   - true: Create a single column index on the field named the same as the index.
	 *   - array('fields' => array('A','B','C'), 'type' => 'index/unique/fulltext'): This gives you full
	 *     control over the index.
	 * @param boolean $hasAutoIncPK A flag indicating that the primary key on this table is an autoincrement type
	 * @param string $options SQL statement to append to the CREATE TABLE call.
	 * @param array $extensions List of extensions
	 */
	public static function require_table($table, $fieldSchema = null, $indexSchema = null, $hasAutoIncPK = true,
		$options = null, $extensions = null
	) {
		return self::get_schema()->requireTable($table, $fieldSchema, $indexSchema, $hasAutoIncPK, $options,
												$extensions);
	}

	/**
	 * @deprecated since version 4.0 Use DB::require_table instead
	 */
	public static function requireTable($table, $fieldSchema = null, $indexSchema = null, $hasAutoIncPK = true,
		$options = null, $extensions = null
	) {
		Deprecation::notice('4.0', 'Use DB::require_table instead');
		return self::require_table($table, $fieldSchema, $indexSchema, $hasAutoIncPK, $options, $extensions);
	}

	/**
	 * Generate the given field on the table, modifying whatever already exists as necessary.
	 *
	 * @param string $table The table name.
	 * @param string $field The field name.
	 * @param string $spec The field specification.
	 */
	public static function require_field($table, $field, $spec) {
		return self::get_schema()->requireField($table, $field, $spec);
	}

	/**
	 * @deprecated since version 4.0 Use DB::require_field instead
	 */
	public static function requireField($table, $field, $spec) {
		Deprecation::notice('4.0', 'Use DB::require_field instead');
		return self::require_field($table, $field, $spec);
	}

	/**
	 * Generate the given index in the database, modifying whatever already exists as necessary.
	 *
	 * @param string $table The table name.
	 * @param string $index The index name.
	 * @param string|boolean $spec The specification of the index. See requireTable() for more information.
	 */
	public static function require_index($table, $index, $spec) {
		self::get_schema()->requireIndex($table, $index, $spec);
	}

	/**
	 * @deprecated since version 4.0 Use DB::require_index instead
	 */
	public static function requireIndex($table, $index, $spec) {
		Deprecation::notice('4.0', 'Use DB::require_index instead');
		self::require_index($table, $index, $spec);
	}

	/**
	 * If the given table exists, move it out of the way by renaming it to _obsolete_(tablename).
	 *
	 * @param string $table The table name.
	 */
	public static function dont_require_table($table) {
		self::get_schema()->dontRequireTable($table);
	}

	/**
	 * @deprecated since version 4.0 Use DB::dont_require_table instead
	 */
	public static function dontRequireTable($table) {
		Deprecation::notice('4.0', 'Use DB::dont_require_table instead');
		self::dont_require_table($table);
	}

	/**
	 * See {@link SS_Database->dontRequireField()}.
	 *
	 * @param string $table The table name.
	 * @param string $fieldName The field name not to require
	 */
	public static function dont_require_field($table, $fieldName) {
		self::get_schema()->dontRequireField($table, $fieldName);
	}

	/**
	 * @deprecated since version 4.0 Use DB::dont_require_field instead
	 */
	public static function dontRequireField($table, $fieldName) {
		Deprecation::notice('4.0', 'Use DB::dont_require_field instead');
		self::dont_require_field($table, $fieldName);
	}

	/**
	 * Checks a table's integrity and repairs it if necessary.
	 *
	 * @param string $tableName The name of the table.
	 * @return boolean Return true if the table has integrity after the method is complete.
	 */
	public static function check_and_repair_table($table) {
		return self::get_schema()->checkAndRepairTable($table);
	}

	/**
	 * @deprecated since version 4.0 Use DB::check_and_repair_table instead
	 */
	public static function checkAndRepairTable($table) {
		Deprecation::notice('4.0', 'Use DB::check_and_repair_table instead');
		self::check_and_repair_table($table);
	}

	/**
	 * Return the number of rows affected by the previous operation.
	 *
	 * @return integer The number of affected rows
	 */
	public static function affected_rows() {
		return self::get_conn()->affectedRows();
	}

	/**
	 * @deprecated since version 4.0 Use DB::affected_rows instead
	 */
	public static function affectedRows() {
		Deprecation::notice('4.0', 'Use DB::affected_rows instead');
		return self::affected_rows();
	}

	/**
	 * Returns a list of all tables in the database.
	 * The table names will be in lower case.
	 *
	 * @return array The list of tables
	 */
	public static function table_list() {
		return self::get_schema()->tableList();
	}

	/**
	 * @deprecated since version 4.0 Use DB::table_list instead
	 */
	public static function tableList() {
		Deprecation::notice('4.0', 'Use DB::table_list instead');
		return self::table_list();
	}

	/**
	 * Get a list of all the fields for the given table.
	 * Returns a map of field name => field spec.
	 *
	 * @param string $table The table name.
	 * @return array The list of fields
	 */
	public static function field_list($table) {
		return self::get_schema()->fieldList($table);
	}

	/**
	 * @deprecated since version 4.0 Use DB::field_list instead
	 */
	public static function fieldList($table) {
		Deprecation::notice('4.0', 'Use DB::field_list instead');
		return self::field_list($table);
	}

	/**
	 * Enable supression of database messages.
	 */
	public static function quiet() {
		self::get_schema()->quiet();
	}

	/**
	 * Show a message about database alteration
	 *
	 * @param string $message to display
	 * @param string $type one of [created|changed|repaired|obsolete|deleted|error]
	 */
	public static function alteration_message($message, $type = "") {
		self::get_schema()->alterationMessage($message, $type);
	}

}
