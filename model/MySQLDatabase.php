<?php
/**
 * MySQL connector class.
 *
 * Supported indexes for {@link requireTable()}:
 *
 * @package framework
 * @subpackage model
 */
class MySQLDatabase extends SS_Database {
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

	private static $connection_charset = null;

	private $supportsTransactions = true;

	/**
	 * Sets the character set for the MySQL database connection.
	 *
	 * The character set connection should be set to 'utf8' for SilverStripe version 2.4.0 and
	 * later.
	 *
	 * However, sites created before version 2.4.0 should leave this unset or data that isn't 7-bit
	 * safe will be corrupted.  As such, the installer comes with this set in mysite/_config.php by
	 * default in versions 2.4.0 and later.
	 */
	public static function set_connection_charset($charset = 'utf8') {
		self::$connection_charset = $charset;
	}

	/**
	 * Connect to a MySQL database.
	 * @param array $parameters An map of parameters, which should include:
	 *  - server: The server, eg, localhost
	 *  - username: The username to log on with
	 *  - password: The password to log on with
	 *  - database: The database to connect to
	 *  - timezone: (optional) The timezone offset. For example: +12:00, "Pacific/Auckland", or "SYSTEM"
	 */
	public function __construct($parameters) {
		$this->dbConn = new MySQLi($parameters['server'], $parameters['username'], $parameters['password']);
		
		if($this->dbConn->connect_error) {
			$this->databaseError("Couldn't connect to MySQL database | " . $this->dbConn->connect_error);
		}
		
		$this->query("SET sql_mode = 'ANSI'");

		if(self::$connection_charset) {
			$this->dbConn->set_charset(self::$connection_charset);
		}

		$this->active = $this->dbConn->select_db($parameters['database']);
		$this->database = $parameters['database'];

		if(isset($parameters['timezone'])) $this->query(sprintf("SET SESSION time_zone = '%s'", $parameters['timezone']));
	}

	/**
	 * Not implemented, needed for PDO
	 */
	public function getConnect($parameters) {
		return null;
	}

	/**
	 * Returns true if this database supports collations
	 * @return boolean
	 */
	public function supportsCollations() {
		return true;
	}

	public function supportsTimezoneOverride() {
		return true;
	}

	/**
	 * Get the version of MySQL.
	 * @return string
	 */
	public function getVersion() {
		return $this->dbConn->server_info;
	}

	/**
	 * Get the database server, namely mysql.
	 * @return string
	 */
	public function getDatabaseServer() {
		return "mysql";
	}

	public function query($sql, $errorLevel = E_USER_ERROR) {
		if(isset($_REQUEST['previewwrite']) && in_array(strtolower(substr($sql,0,strpos($sql,' '))), array('insert','update','delete','replace'))) {
			Debug::message("Will execute: $sql");
			return;
		}

		if(isset($_REQUEST['showqueries']) && Director::isDev(true)) {
			$starttime = microtime(true);
		}

		$handle = $this->dbConn->query($sql);

		if(isset($_REQUEST['showqueries']) && Director::isDev(true)) {
			$endtime = round(microtime(true) - $starttime,4);
			Debug::message("\n$sql\n{$endtime}ms\n", false);
		}

		if(!$handle && $errorLevel) $this->databaseError("Couldn't run query: $sql | " . $this->dbConn->error, $errorLevel);
		return new MySQLQuery($this, $handle);
	}

	public function getGeneratedID($table) {
		return $this->dbConn->insert_id;
	}

	public function isActive() {
		return $this->active ? true : false;
	}

	public function createDatabase() {
		$this->query("CREATE DATABASE \"$this->database\"");
		$this->query("USE \"$this->database\"");

		$this->tableList = $this->fieldList = $this->indexList = null;

		$this->active = $this->dbConn->select_db($this->database);
		return $this->active;
	}

	/**
	 * Drop the database that this object is currently connected to.
	 * Use with caution.
	 */
	public function dropDatabase() {
		$this->dropDatabaseByName($this->database);
	}

	/**
	 * Drop the database that this object is currently connected to.
	 * Use with caution.
	 */
	public function dropDatabaseByName($dbName) {
		$this->query("DROP DATABASE \"$dbName\"");
	}

	/**
	 * Returns the name of the currently selected database
	 */
	public function currentDatabase() {
		return $this->database;
	}

	/**
	 * Switches to the given database.
	 * If the database doesn't exist, you should call createDatabase() after calling selectDatabase()
	 */
	public function selectDatabase($dbname) {
		$this->database = $dbname;
		$this->tableList = $this->fieldList = $this->indexList = null;
		$this->active = false;
		if($this->databaseExists($this->database)) {
			$this->active = $this->dbConn->select_db($this->database);
		}
		return $this->active;
	}

	/**
	 * Returns true if the named database exists.
	 */
	public function databaseExists($name) {
		$SQL_name = Convert::raw2sql($name);
		return $this->query("SHOW DATABASES LIKE '$SQL_name'")->value() ? true : false;
	}

	/**
	 * Returns a column
	 */
	public function allDatabaseNames() {
		return $this->query("SHOW DATABASES")->column();
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
	public function createTable($table, $fields = null, $indexes = null, $options = null, $advancedOptions = null) {
		$fieldSchemas = $indexSchemas = "";
		
		if(!empty($options[get_class($this)])) {
			$addOptions = $options[get_class($this)];
		} elseif(!empty($options[get_parent_class($this)])) {
			$addOptions = $options[get_parent_class($this)];
		} else {
			$addOptions = "ENGINE=InnoDB";
		}
		
		if(!isset($fields['ID'])) $fields['ID'] = "int(11) not null auto_increment";
		if($fields) foreach($fields as $k => $v) $fieldSchemas .= "\"$k\" $v,\n";
		if($indexes) foreach($indexes as $k => $v) $indexSchemas .= $this->getIndexSqlDefinition($k, $v) . ",\n";

		// Switch to "CREATE TEMPORARY TABLE" for temporary tables
		$temporary = empty($options['temporary']) ? "" : "TEMPORARY";

		$this->query("CREATE $temporary TABLE \"$table\" (
				$fieldSchemas
				$indexSchemas
				primary key (ID)
			) {$addOptions}");
		
		return $table;
	}

	/**
	 * Alter a table's schema.
	 * @param $table The name of the table to alter
	 * @param $newFields New fields, a map of field name => field schema
	 * @param $newIndexes New indexes, a map of index name => index type
	 * @param $alteredFields Updated fields, a map of field name => field schema
	 * @param $alteredIndexes Updated indexes, a map of index name => index type
	 * @param $alteredOptions
	 */
	public function alterTable($tableName, $newFields = null, $newIndexes = null, $alteredFields = null, $alteredIndexes = null, $alteredOptions = null, $advancedOptions = null) {
		if($this->isView($tableName)) {
			DB::alteration_message(
				sprintf("Table %s not changed as it is a view", $tableName),
				"changed"
			);
			return;
		}
		$fieldSchemas = $indexSchemas = "";
		$alterList = array();

		if($newFields) foreach($newFields as $k => $v) $alterList[] .= "ADD \"$k\" $v";
		if($newIndexes) foreach($newIndexes as $k => $v) $alterList[] .= "ADD " . $this->getIndexSqlDefinition($k, $v);
		if($alteredFields) foreach($alteredFields as $k => $v) $alterList[] .= "CHANGE \"$k\" \"$k\" $v";
		if($alteredIndexes) foreach($alteredIndexes as $k => $v) {
			$alterList[] .= "DROP INDEX \"$k\"";
			$alterList[] .= "ADD ". $this->getIndexSqlDefinition($k, $v);
 		}
 		
		if($alteredOptions && isset($alteredOptions[get_class($this)])) {
			if(!isset($this->indexList[$tableName])) {
				$this->indexList[$tableName] = $this->indexList($tableName);
			}
			
			$skip = false;
			foreach($this->indexList[$tableName] as $index) {
				if(strpos($index, 'fulltext ') === 0) {
					$skip = true;
					break;
				}
			}
			if($skip) {
				DB::alteration_message(
					sprintf("Table %s options not changed to %s due to fulltextsearch index", $tableName, $alteredOptions[get_class($this)]),
					"changed"
				);
			} else {
				$this->query(sprintf("ALTER TABLE \"%s\" %s", $tableName, $alteredOptions[get_class($this)]));
				DB::alteration_message(
					sprintf("Table %s options changed: %s", $tableName, $alteredOptions[get_class($this)]),
					"changed"
				);
			}
		}

 		$alterations = implode(",\n", $alterList);
		$this->query("ALTER TABLE \"$tableName\" $alterations");
	}
	
	public function isView($tableName) {
		$info = $this->query("SHOW /*!50002 FULL*/ TABLES LIKE '$tableName'")->record();
		return $info && strtoupper($info['Table_type']) == 'VIEW';
	}
	
	public function renameTable($oldTableName, $newTableName) {
		$this->query("ALTER TABLE \"$oldTableName\" RENAME \"$newTableName\"");
	}



	/**
	 * Checks a table's integrity and repairs it if necessary.
	 * @var string $tableName The name of the table.
	 * @return boolean Return true if the table has integrity after the method is complete.
	 */
	public function checkAndRepairTable($tableName) {
		if(!$this->runTableCheckCommand("CHECK TABLE \"$tableName\"")) {
			if($this->runTableCheckCommand("CHECK TABLE \"".strtolower($tableName)."\"")){
				DB::alteration_message("Table $tableName: renamed from lowercase","repaired");
				return $this->renameTable(strtolower($tableName),$tableName);
			}

			DB::alteration_message("Table $tableName: repaired","repaired");
			return $this->runTableCheckCommand("REPAIR TABLE \"$tableName\" USE_FRM");
		} else {
			return true;
		}
	}

	/**
	 * Helper function used by checkAndRepairTable.
	 * @param string $sql Query to run.
	 * @return boolean Returns if the query returns a successful result.
	 */
	protected function runTableCheckCommand($sql) {
		$testResults = $this->query($sql);
		foreach($testResults as $testRecord) {
			if(strtolower($testRecord['Msg_text']) != 'ok') {
				return false;
			}
		}
		return true;
	}

	public function createField($tableName, $fieldName, $fieldSpec) {
		$this->query("ALTER TABLE \"$tableName\" ADD \"$fieldName\" $fieldSpec");
	}

	/**
	 * Change the database type of the given field.
	 * @param string $tableName The name of the tbale the field is in.
	 * @param string $fieldName The name of the field to change.
	 * @param string $fieldSpec The new field specification
	 */
	public function alterField($tableName, $fieldName, $fieldSpec) {
		$this->query("ALTER TABLE \"$tableName\" CHANGE \"$fieldName\" \"$fieldName\" $fieldSpec");
	}

	/**
	 * Change the database column name of the given field.
	 *
	 * @param string $tableName The name of the tbale the field is in.
	 * @param string $oldName The name of the field to change.
	 * @param string $newName The new name of the field
	 */
	public function renameField($tableName, $oldName, $newName) {
		$fieldList = $this->fieldList($tableName);
		if(array_key_exists($oldName, $fieldList)) {
			$this->query("ALTER TABLE \"$tableName\" CHANGE \"$oldName\" \"$newName\" " . $fieldList[$oldName]);
		}
	}

	private static $_cache_collation_info = array();

	public function fieldList($table) {
		$fields = DB::query("SHOW FULL FIELDS IN \"$table\"");
		foreach($fields as $field) {

			// ensure that '' is converted to \' in field specification (mostly for the benefit of ENUM values)
			$fieldSpec = str_replace('\'\'', '\\\'', $field['Type']);
			if(!$field['Null'] || $field['Null'] == 'NO') {
				$fieldSpec .= ' not null';
			}

			if($field['Collation'] && $field['Collation'] != 'NULL') {
				// Cache collation info to cut down on database traffic
				if(!isset(self::$_cache_collation_info[$field['Collation']])) {
					self::$_cache_collation_info[$field['Collation']] = DB::query("SHOW COLLATION LIKE '$field[Collation]'")->record();
				}
				$collInfo = self::$_cache_collation_info[$field['Collation']];
				$fieldSpec .= " character set $collInfo[Charset] collate $field[Collation]";
			}

			if($field['Default'] || $field['Default'] === "0") {
				if(is_numeric($field['Default']))
					$fieldSpec .= " default " . Convert::raw2sql($field['Default']);
				else
					$fieldSpec .= " default '" . Convert::raw2sql($field['Default']) . "'";
			}
			if($field['Extra']) $fieldSpec .= " $field[Extra]";

			$fieldList[$field['Field']] = $fieldSpec;
		}
		return $fieldList;
	}

	/**
	 * Create an index on a table.
	 *
	 * @param string $tableName The name of the table.
	 * @param string $indexName The name of the index.
	 * @param string $indexSpec The specification of the index, see {@link SS_Database::requireIndex()} for more details.
	 */
	public function createIndex($tableName, $indexName, $indexSpec) {
		$this->query("ALTER TABLE \"$tableName\" ADD " . $this->getIndexSqlDefinition($indexName, $indexSpec));
	}

	/**
	 * This takes the index spec which has been provided by a class (ie static $indexes = blah blah)
	 * and turns it into a proper string.
	 * Some indexes may be arrays, such as fulltext and unique indexes, and this allows database-specific
	 * arrays to be created. See {@link requireTable()} for details on the index format.
	 *
	 * @see http://dev.mysql.com/doc/refman/5.0/en/create-index.html
	 *
	 * @param string|array $indexSpec
	 * @return string MySQL compatible ALTER TABLE syntax
	 */
	public function convertIndexSpec($indexSpec){
		if(is_array($indexSpec)){
			//Here we create a db-specific version of whatever index we need to create.
			switch($indexSpec['type']){
				case 'fulltext':
					$indexSpec='fulltext (' . str_replace(' ', '', $indexSpec['value']) . ')';
					break;
				case 'unique':
					$indexSpec='unique (' . $indexSpec['value'] . ')';
					break;
				case 'btree':
				case 'index':
					$indexSpec='using btree (' . $indexSpec['value'] . ')';
					break;
				case 'hash':
					$indexSpec='using hash (' . $indexSpec['value'] . ')';
					break;
			}
		}

		return $indexSpec;
	}

	/**
	 * @param string $indexName
	 * @param string|array $indexSpec See {@link requireTable()} for details
	 * @return string MySQL compatible ALTER TABLE syntax
	 */
	protected function getIndexSqlDefinition($indexName, $indexSpec=null) {

		$indexSpec=$this->convertIndexSpec($indexSpec);

		$indexSpec = trim($indexSpec);
		if($indexSpec[0] != '(') list($indexType, $indexFields) = explode(' ',$indexSpec,2);
	    else $indexFields = $indexSpec;

	    if(!isset($indexType))
			$indexType = "index";

		if($indexType=='using')
			return "index \"$indexName\" using $indexFields";
		else {
			return "$indexType \"$indexName\" $indexFields";
		}

	}

	/**
	 * MySQL does not need any transformations done on the index that's created, so we can just return it as-is
	 */
	function getDbSqlDefinition($tableName, $indexName, $indexSpec){
		return $indexName;
	}

	/**
	 * Alter an index on a table.
	 * @param string $tableName The name of the table.
	 * @param string $indexName The name of the index.
	 * @param string $indexSpec The specification of the index, see {@link SS_Database::requireIndex()} for more details.
	 */
	public function alterIndex($tableName, $indexName, $indexSpec) {

		$indexSpec=$this->convertIndexSpec($indexSpec);

		$indexSpec = trim($indexSpec);
	    if($indexSpec[0] != '(') {
	    	list($indexType, $indexFields) = explode(' ',$indexSpec,2);
	    } else {
	    	$indexFields = $indexSpec;
	    }

	    if(!$indexType) {
	    	$indexType = "index";
	    }

		$this->query("ALTER TABLE \"$tableName\" DROP INDEX \"$indexName\"");
		$this->query("ALTER TABLE \"$tableName\" ADD $indexType \"$indexName\" $indexFields");
	}

	/**
	 * Return the list of indexes in a table.
	 * @param string $table The table name.
	 * @return array
	 */
	public function indexList($table) {
		$indexes = DB::query("SHOW INDEXES IN \"$table\"");
		$groupedIndexes = array();
		$indexList = array();

		foreach($indexes as $index) {
			$groupedIndexes[$index['Key_name']]['fields'][$index['Seq_in_index']] = $index['Column_name'];

			if($index['Index_type'] == 'FULLTEXT') {
				$groupedIndexes[$index['Key_name']]['type'] = 'fulltext ';
			} else if(!$index['Non_unique']) {
				$groupedIndexes[$index['Key_name']]['type'] = 'unique ';
			} else if($index['Index_type'] =='HASH') {
				$groupedIndexes[$index['Key_name']]['type'] = 'hash ';
			} else if($index['Index_type'] =='RTREE') {
				$groupedIndexes[$index['Key_name']]['type'] = 'rtree ';
			} else {
				$groupedIndexes[$index['Key_name']]['type'] = '';
			}
		}

		if($groupedIndexes) {
			foreach($groupedIndexes as $index => $details) {
				ksort($details['fields']);
				$indexList[$index] = $details['type'] . '("' . implode('","',$details['fields']) . '")';
			}
		}

		return $indexList;
	}

	/**
	 * Returns a list of all the tables in the database.
	 * @return array
	 */
	public function tableList() {
		$tables = array();
		foreach($this->query("SHOW TABLES") as $record) {
			$table = reset($record);
			$tables[strtolower($table)] = $table;
		}
		return $tables;
	}

	/**
	 * Return the number of rows affected by the previous operation.
	 * @return int
	 */
	public function affectedRows() {
		return $this->dbConn->affected_rows;
	}

	function databaseError($msg, $errorLevel = E_USER_ERROR) {
		// try to extract and format query
		if(preg_match('/Couldn\'t run query: ([^\|]*)\|\s*(.*)/', $msg, $matches)) {
			$formatter = new SQLFormatter();
			$msg = "Couldn't run query: \n" . $formatter->formatPlain($matches[1]) . "\n\n" . $matches[2];
		}

		user_error($msg, $errorLevel);
	}

	/**
	 * Return a boolean type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function boolean($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'tinyint', 'precision'=>1, 'sign'=>'unsigned', 'null'=>'not null', 'default'=>$this->default);
		//DB::requireField($this->tableName, $this->name, "tinyint(1) unsigned not null default '{$this->defaultVal}'");

		return 'tinyint(1) unsigned not null default ' . (int)$values['default'];
	}

	/**
	 * Return a date type-formatted string
	 * For MySQL, we simply return the word 'date', no other parameters are necessary
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function date($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'date');
		//DB::requireField($this->tableName, $this->name, "date");

		return 'date';
	}

	/**
	 * Return a decimal type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function decimal($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'decimal', 'precision'=>"$this->wholeSize,$this->decimalSize");
		//DB::requireField($this->tableName, $this->name, "decimal($this->wholeSize,$this->decimalSize)");

		// Avoid empty strings being put in the db
		if($values['precision'] == '') {
			$precision = 1;
		} else {
			$precision = $values['precision'];
		}

		$defaultValue = '';
		if(isset($values['default']) && is_numeric($values['default'])) {
			$decs = strpos($precision, ',') !== false ? (int)substr($precision, strpos($precision, ',')+1) : 0;
			$defaultValue = ' default ' . number_format($values['default'], $decs, '.', '');
		}

		return 'decimal(' . $precision . ') not null' . $defaultValue;
	}

	/**
	 * Return a enum type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function enum($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'enum', 'enums'=>$this->enum, 'character set'=>'utf8', 'collate'=> 'utf8_general_ci', 'default'=>$this->default);
		//DB::requireField($this->tableName, $this->name, "enum('" . implode("','", $this->enum) . "') character set utf8 collate utf8_general_ci default '{$this->default}'");

		return 'enum(\'' . implode('\',\'', $values['enums']) . '\') character set utf8 collate utf8_general_ci default \'' . $values['default'] . '\'';
	}

	/**
	 * Return a set type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function set($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'enum', 'enums'=>$this->enum, 'character set'=>'utf8', 'collate'=> 'utf8_general_ci', 'default'=>$this->default);
		//DB::requireField($this->tableName, $this->name, "enum('" . implode("','", $this->enum) . "') character set utf8 collate utf8_general_ci default '{$this->default}'");
		$default = empty($values['default']) ? '' : " default '$values[default]'";
		return 'set(\'' . implode('\',\'', $values['enums']) . '\') character set utf8 collate utf8_general_ci' . $default;
	}

	/**
	 * Return a float type-formatted string
	 * For MySQL, we simply return the word 'date', no other parameters are necessary
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function float($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'float');
		//DB::requireField($this->tableName, $this->name, "float");

		return 'float not null default ' . $values['default'];
	}

	/**
	 * Return a int type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function int($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'int', 'precision'=>11, 'null'=>'not null', 'default'=>(int)$this->default);
		//DB::requireField($this->tableName, $this->name, "int(11) not null default '{$this->defaultVal}'");

		return 'int(11) not null default ' . (int)$values['default'];
	}

	/**
	 * Return a datetime type-formatted string
	 * For MySQL, we simply return the word 'datetime', no other parameters are necessary
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function ss_datetime($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'datetime');
		//DB::requireField($this->tableName, $this->name, $values);

		return 'datetime';
	}

	/**
	 * Return a text type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function text($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'mediumtext', 'character set'=>'utf8', 'collate'=>'utf8_general_ci');
		//DB::requireField($this->tableName, $this->name, "mediumtext character set utf8 collate utf8_general_ci");

		return 'mediumtext character set utf8 collate utf8_general_ci';
	}

	/**
	 * Return a time type-formatted string
	 * For MySQL, we simply return the word 'time', no other parameters are necessary
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function time($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'time');
		//DB::requireField($this->tableName, $this->name, "time");

		return 'time';
	}

	/**
	 * Return a varchar type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function varchar($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'varchar', 'precision'=>$this->size, 'character set'=>'utf8', 'collate'=>'utf8_general_ci');
		//DB::requireField($this->tableName, $this->name, "varchar($this->size) character set utf8 collate utf8_general_ci");

		return 'varchar(' . $values['precision'] . ') character set utf8 collate utf8_general_ci';
	}

	/*
	 * Return the MySQL-proprietary 'Year' datatype
	 */
	public function year($values){
		return 'year(4)';
	}
	/**
	 * This returns the column which is the primary key for each table
	 * In Postgres, it is a SERIAL8, which is the equivalent of an auto_increment
	 *
	 * @return string
	 */
	function IdColumn(){
		return 'int(11) not null auto_increment';
	}

	/**
	 * Returns the SQL command to get all the tables in this database
	 */
	function allTablesSQL(){
		return "SHOW TABLES;";
	}

	/**
	 * Returns true if the given table is exists in the current database
	 * NOTE: Experimental; introduced for db-abstraction and may changed before 2.4 is released.
	 */
	public function hasTable($table) {
		$SQL_table = Convert::raw2sql($table);
		return (bool)($this->query("SHOW TABLES LIKE '$SQL_table'")->value());
	}

	/**
	 * Returns the values of the given enum field
	 * NOTE: Experimental; introduced for db-abstraction and may changed before 2.4 is released.
	 */
	public function enumValuesForField($tableName, $fieldName) {
		// Get the enum of all page types from the SiteTree table
		$classnameinfo = DB::query("DESCRIBE \"$tableName\" \"$fieldName\"")->first();
		preg_match_all("/'[^,]+'/", $classnameinfo["Type"], $matches);

		$classes = array();
		foreach($matches[0] as $value) {
			$classes[] = stripslashes(trim($value, "'"));
		}
		return $classes;
	}

	/**
	 * The core search engine, used by this class and its subclasses to do fun stuff.
	 * Searches both SiteTree and File.
	 *
	 * @param string $keywords Keywords as a string.
	 */
	public function searchEngine($classesToSearch, $keywords, $start, $pageLength, $sortBy = "Relevance DESC", $extraFilter = "", $booleanSearch = false, $alternativeFileFilter = "", $invertedMatch = false) {
		if(!class_exists('SiteTree')) throw new Exception('MySQLDatabase->searchEngine() requires "SiteTree" class');
		if(!class_exists('File')) throw new Exception('MySQLDatabase->searchEngine() requires "File" class');
		
		$fileFilter = '';
	 	$keywords = Convert::raw2sql($keywords);
		$htmlEntityKeywords = htmlentities($keywords, ENT_NOQUOTES, 'UTF-8');

		$extraFilters = array('SiteTree' => '', 'File' => '');

	 	if($booleanSearch) $boolean = "IN BOOLEAN MODE";

	 	if($extraFilter) {
	 		$extraFilters['SiteTree'] = " AND $extraFilter";

	 		if($alternativeFileFilter) $extraFilters['File'] = " AND $alternativeFileFilter";
	 		else $extraFilters['File'] = $extraFilters['SiteTree'];
	 	}

		// Always ensure that only pages with ShowInSearch = 1 can be searched
		$extraFilters['SiteTree'] .= " AND ShowInSearch <> 0";
		
		// File.ShowInSearch was added later, keep the database driver backwards compatible 
		// by checking for its existence first
		$fields = $this->fieldList('File');
		if(array_key_exists('ShowInSearch', $fields)) $extraFilters['File'] .= " AND ShowInSearch <> 0";

		$limit = $start . ", " . (int) $pageLength;

		$notMatch = $invertedMatch ? "NOT " : "";
		if($keywords) {
			$match['SiteTree'] = "
				MATCH (Title, MenuTitle, Content, MetaTitle, MetaDescription, MetaKeywords) AGAINST ('$keywords' $boolean)
				+ MATCH (Title, MenuTitle, Content, MetaTitle, MetaDescription, MetaKeywords) AGAINST ('$htmlEntityKeywords' $boolean)
			";
			$match['File'] = "MATCH (Filename, Title, Content) AGAINST ('$keywords' $boolean) AND ClassName = 'File'";

			// We make the relevance search by converting a boolean mode search into a normal one
			$relevanceKeywords = str_replace(array('*','+','-'),'',$keywords);
			$htmlEntityRelevanceKeywords = str_replace(array('*','+','-'),'',$htmlEntityKeywords);
			$relevance['SiteTree'] = "MATCH (Title, MenuTitle, Content, MetaTitle, MetaDescription, MetaKeywords) AGAINST ('$relevanceKeywords') + MATCH (Title, MenuTitle, Content, MetaTitle, MetaDescription, MetaKeywords) AGAINST ('$htmlEntityRelevanceKeywords')";
			$relevance['File'] = "MATCH (Filename, Title, Content) AGAINST ('$relevanceKeywords')";
		} else {
			$relevance['SiteTree'] = $relevance['File'] = 1;
			$match['SiteTree'] = $match['File'] = "1 = 1";
		}

		// Generate initial DataLists and base table names
		$lists = array();
		$baseClasses = array('SiteTree' => '', 'File' => '');
		foreach($classesToSearch as $class) {
			$lists[$class] = DataList::create($class)->where($notMatch . $match[$class] . $extraFilters[$class], "");
			$baseClasses[$class] = '"'.$class.'"';
		}

		// Make column selection lists
		$select = array(
			'SiteTree' => array("ClassName","$baseClasses[SiteTree].\"ID\"","ParentID","Title","MenuTitle","URLSegment","Content","LastEdited","Created","Filename" => "_utf8''", "Name" => "_utf8''", "Relevance" => $relevance['SiteTree'], "CanViewType"),
			'File' => array("ClassName","$baseClasses[File].\"ID\"","ParentID" => "_utf8''","Title","MenuTitle" => "_utf8''","URLSegment" => "_utf8''","Content","LastEdited","Created","Filename","Name", "Relevance" => $relevance['File'], "CanViewType" => "NULL"),
		);

		// Process and combine queries
		$querySQLs = array();
		$totalCount = 0;
		foreach($lists as $class => $list) {
			$query = $list->dataQuery()->query();

			// There's no need to do all that joining
			$query->setFrom(array(str_replace(array('"','`'), '', $baseClasses[$class]) => $baseClasses[$class]));
			$query->setSelect($select[$class]);
			$query->setOrderBy(array());
			
			$querySQLs[] = $query->sql();
			$totalCount += $query->unlimitedRowCount();
		}
		$fullQuery = implode(" UNION ", $querySQLs) . " ORDER BY $sortBy LIMIT $limit";

		// Get records
		$records = DB::query($fullQuery);

		$objects = array();

		foreach($records as $record) {
			$objects[] = new $record['ClassName']($record);
		}

		$list = new PaginatedList(new ArrayList($objects));
		$list->setPageStart($start);
		$list->setPageLEngth($pageLength);
		$list->setTotalItems($totalCount);

		// The list has already been limited by the query above
		$list->setLimitItems(false);

		return $list;
	}

	/**
	 * MySQL uses NOW() to return the current date/time.
	 */
	function now(){
		return 'NOW()';
	}

	/*
	 * Returns the database-specific version of the random() function
	 */
	function random(){
		return 'RAND()';
	}

	/*
	 * This is a lookup table for data types.
	 * For instance, Postgres uses 'INT', while MySQL uses 'UNSIGNED'
	 * So this is a DB-specific list of equivilents.
	 */
	function dbDataType($type){
		$values=Array(
			'unsigned integer'=>'UNSIGNED'
		);

		if(isset($values[$type]))
			return $values[$type];
		else return '';
	}

	/*
	 * This will return text which has been escaped in a database-friendly manner.
	 */
	function addslashes($value){
		return $this->dbConn->real_escape_string($value);
	}

	/*
	 * This changes the index name depending on database requirements.
	 * MySQL doesn't need any changes.
	 */
	function modifyIndex($index){
		return $index;
	}

	/**
	 * Returns a SQL fragment for querying a fulltext search index
	 * @param $fields array The list of field names to search on
	 * @param $keywords string The search query
	 * @param $booleanSearch A MySQL-specific flag to switch to boolean search
	 */
	function fullTextSearchSQL($fields, $keywords, $booleanSearch = false) {
		$boolean = $booleanSearch ? "IN BOOLEAN MODE" : "";
		$fieldNames = '"' . implode('", "', $fields) . '"';

	 	$SQL_keywords = Convert::raw2sql($keywords);
		$SQL_htmlEntityKeywords = Convert::raw2sql(htmlentities($keywords, ENT_NOQUOTES, 'UTF-8'));

		return "(MATCH ($fieldNames) AGAINST ('$SQL_keywords' $boolean) + MATCH ($fieldNames) AGAINST ('$SQL_htmlEntityKeywords' $boolean))";
	}

	/*
	 * Does this database support transactions?
	 */
	public function supportsTransactions(){
		return $this->supportsTransactions;
	}

	/*
	 * This is a quick lookup to discover if the database supports particular extensions
	 * Currently, MySQL supports no extensions
	 */
	public function supportsExtensions($extensions=Array('partitions', 'tablespaces', 'clustering')){
		if(isset($extensions['partitions']))
			return false;
		elseif(isset($extensions['tablespaces']))
			return false;
		elseif(isset($extensions['clustering']))
			return false;
		else
			return false;
	}

	/*
	 * Start a prepared transaction
	 * See http://developer.postgresql.org/pgdocs/postgres/sql-set-transaction.html for details on transaction isolation options
	 */
	public function transactionStart($transaction_mode=false, $session_characteristics=false){
		// This sets the isolation level for the NEXT transaction, not the current one.
		if($transaction_mode) {
			$this->query('SET TRANSACTION ' . $transaction_mode . ';');
		}

		$this->query('START TRANSACTION;');

		if($session_characteristics) {
			$this->query('SET SESSION TRANSACTION ' . $session_characteristics . ';');
		}
	}

	/*
	 * Create a savepoint that you can jump back to if you encounter problems
	 */
	public function transactionSavepoint($savepoint){
		$this->query("SAVEPOINT $savepoint;");
	}

	/*
	 * Rollback or revert to a savepoint if your queries encounter problems
	 * If you encounter a problem at any point during a transaction, you may
	 * need to rollback that particular query, or return to a savepoint
	 */
	public function transactionRollback($savepoint = false){
		if($savepoint) {
			$this->query('ROLLBACK TO ' . $savepoint . ';');
		} else {
			$this->query('ROLLBACK');
		}
	}

	/*
	 * Commit everything inside this transaction so far
	 */
	public function transactionEnd($chain = false){
		$this->query('COMMIT AND ' . ($chain ? '' : 'NO ') . 'CHAIN;');
	}

	/**
	 * Function to return an SQL datetime expression that can be used with MySQL
	 * used for querying a datetime in a certain format
	 * @param string $date to be formated, can be either 'now', literal datetime like '1973-10-14 10:30:00' or field name, e.g. '"SiteTree"."Created"'
	 * @param string $format to be used, supported specifiers:
	 * %Y = Year (four digits)
	 * %m = Month (01..12)
	 * %d = Day (01..31)
	 * %H = Hour (00..23)
	 * %i = Minutes (00..59)
	 * %s = Seconds (00..59)
	 * %U = unix timestamp, can only be used on it's own
	 * @return string SQL datetime expression to query for a formatted datetime
	 */
	function formattedDatetimeClause($date, $format) {

		preg_match_all('/%(.)/', $format, $matches);
		foreach($matches[1] as $match) if(array_search($match, array('Y','m','d','H','i','s','U')) === false) user_error('formattedDatetimeClause(): unsupported format character %' . $match, E_USER_WARNING);

		if(preg_match('/^now$/i', $date)) {
			$date = "NOW()";
		} else if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date)) {
			$date = "'$date'";
		}

		if($format == '%U') return "UNIX_TIMESTAMP($date)";
		
		return "DATE_FORMAT($date, '$format')";
		
	}
	
	/**
	 * Function to return an SQL datetime expression that can be used with MySQL
	 * used for querying a datetime addition
	 * @param string $date, can be either 'now', literal datetime like '1973-10-14 10:30:00' or field name, e.g. '"SiteTree"."Created"'
	 * @param string $interval to be added, use the format [sign][integer] [qualifier], e.g. -1 Day, +15 minutes, +1 YEAR
	 * supported qualifiers:
	 * - years
	 * - months
	 * - days
	 * - hours
	 * - minutes
	 * - seconds
	 * This includes the singular forms as well
	 * @return string SQL datetime expression to query for a datetime (YYYY-MM-DD hh:mm:ss) which is the result of the addition
	 */
	function datetimeIntervalClause($date, $interval) {

		$interval = preg_replace('/(year|month|day|hour|minute|second)s/i', '$1', $interval);

		if(preg_match('/^now$/i', $date)) {
			$date = "NOW()";
		} else if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date)) {
			$date = "'$date'";
		}

		return "$date + INTERVAL $interval";
	}

	/**
	 * Function to return an SQL datetime expression that can be used with MySQL
	 * used for querying a datetime substraction
	 * @param string $date1, can be either 'now', literal datetime like '1973-10-14 10:30:00' or field name, e.g. '"SiteTree"."Created"'
	 * @param string $date2 to be substracted of $date1, can be either 'now', literal datetime like '1973-10-14 10:30:00' or field name, e.g. '"SiteTree"."Created"'
	 * @return string SQL datetime expression to query for the interval between $date1 and $date2 in seconds which is the result of the substraction
	 */
	function datetimeDifferenceClause($date1, $date2) {

		if(preg_match('/^now$/i', $date1)) {
			$date1 = "NOW()";
		} else if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date1)) {
			$date1 = "'$date1'";
		}

		if(preg_match('/^now$/i', $date2)) {
			$date2 = "NOW()";
		} else if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date2)) {
			$date2 = "'$date2'";
		}

		return "UNIX_TIMESTAMP($date1) - UNIX_TIMESTAMP($date2)";
	}
	
	function supportsLocks() {
		return true;
	}
	
	function canLock($name) {
		$id = $this->getLockIdentifier($name);
		return (bool)DB::query(sprintf("SELECT IS_FREE_LOCK('%s')", $id))->value();
	}
	
	function getLock($name, $timeout = 5) {
		$id = $this->getLockIdentifier($name);
		
		// MySQL auto-releases existing locks on subsequent GET_LOCK() calls,
		// in contrast to PostgreSQL and SQL Server who stack the locks.
		
		return (bool)DB::query(sprintf("SELECT GET_LOCK('%s', %d)", $id, $timeout))->value();
	}
	
	function releaseLock($name) {
		$id = $this->getLockIdentifier($name);
		return (bool)DB::query(sprintf("SELECT RELEASE_LOCK('%s')", $id))->value();
	}
	
	protected function getLockIdentifier($name) {
		// Prefix with database name
		return Convert::raw2sql($this->database . '_' . Convert::raw2sql($name));
	}
}

/**
 * A result-set from a MySQL database.
 * @package framework
 * @subpackage model
 */
class MySQLQuery extends SS_Query {
	/**
	 * The MySQLDatabase object that created this result set.
	 * @var MySQLDatabase
	 */
	private $database;

	/**
	 * The internal MySQL handle that points to the result set.
	 * @var resource
	 */
	private $handle;

	/**
	 * Hook the result-set given into a Query class, suitable for use by SilverStripe.
	 * @param database The database object that created this query.
	 * @param handle the internal mysql handle that is points to the resultset.
	 */
	public function __construct(MySQLDatabase $database, $handle) {
		$this->database = $database;
		$this->handle = $handle;
	}

	public function __destruct() {
		if(is_object($this->handle)) $this->handle->free();
	}
	
	public function seek($row) {
		if(is_object($this->handle)) return $this->handle->data_seek($row);
	}
	
	public function numRecords() {
		if(is_object($this->handle)) return $this->handle->num_rows;
	}

	public function nextRecord() {
		if(is_object($this->handle) && ($data = $this->handle->fetch_assoc())) {
			return $data;
		} else {
			return false;
		}
	}
}
