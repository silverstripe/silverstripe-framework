<?php
/**
 * MySQL connector class.
 * @package sapphire
 * @subpackage model
 */
class MySQLDatabase extends Database {
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
	 * Connect to a MySQL database.
	 * @param array $parameters An map of parameters, which should include:
	 *  - server: The server, eg, localhost
	 *  - username: The username to log on with
	 *  - password: The password to log on with
	 *  - database: The database to connect to
	 */
	public function __construct($parameters) {
		$this->dbConn = mysql_connect($parameters['server'], $parameters['username'], $parameters['password']);
		$this->active = mysql_select_db($parameters['database'], $this->dbConn);
		$this->database = $parameters['database'];
		if(!$this->dbConn) {
			$this->databaseError("Couldn't connect to MySQL database");
		}
		
		$this->query("SET sql_mode = 'ANSI'");

		parent::__construct();
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
		return $this->getVersion() >= 4.1;
	}
	
	/**
	 * The version of MySQL.
	 * @var float
	 */
	private $mysqlVersion;
	
	/**
	 * Get the version of MySQL.
	 * @return float
	 */
	public function getVersion() {
		if(!$this->mysqlVersion) {
			$this->mysqlVersion = (float)substr(trim(ereg_replace("([A-Za-z-])", "", $this->query("SELECT VERSION()")->value())), 0, 3);
		}
		return $this->mysqlVersion;
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

		if(isset($_REQUEST['showqueries'])) { 
			$starttime = microtime(true);
		}
		
		$handle = mysql_query($sql, $this->dbConn);
		
		if(isset($_REQUEST['showqueries'])) {
			$endtime = round(microtime(true) - $starttime,4);
			Debug::message("\n$sql\n{$endtime}ms\n", false);
		}
		
		if(!$handle && $errorLevel) $this->databaseError("Couldn't run query: $sql | " . mysql_error($this->dbConn), $errorLevel);
		return new MySQLQuery($this, $handle);
	}
	
	public function getGeneratedID($table) {
		return mysql_insert_id($this->dbConn);
	}
	
	/**
	 * OBSOLETE: Get the ID for the next new record for the table.
	 * 
	 * @var string $table The name od the table.
	 * @return int
	 */
	public function getNextID($table) {
		user_error('getNextID is OBSOLETE (and will no longer work properly)', E_USER_WARNING);
		$result = $this->query("SELECT MAX(ID)+1 FROM \"$table\"")->value();
		return $result ? $result : 1;
	}
	
	public function isActive() {
		return $this->active ? true : false;
	}
	
	public function createDatabase() {
		$this->query("CREATE DATABASE `$this->database`");
		$this->query("USE `$this->database`");

		$this->tableList = $this->fieldList = $this->indexList = null;

		if(mysql_select_db($this->database, $this->dbConn)) {
			$this->active = true;
			return true;
		}
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
		if($this->databaseExists($this->database)) {
			if(mysql_select_db($this->database, $this->dbConn)) $this->active = true;
		}
		$this->tableList = $this->fieldList = $this->indexList = null;
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
	
	public function createTable($tableName, $fields = null, $indexes = null) {
		$fieldSchemas = $indexSchemas = "";
		
		if(!isset($fields['ID'])) $fields['ID'] = "int(11) not null auto_increment";
		if($fields) foreach($fields as $k => $v) $fieldSchemas .= "\"$k\" $v,\n";
		if($indexes) foreach($indexes as $k => $v) $indexSchemas .= $this->getIndexSqlDefinition($k, $v) . ",\n";
		
		$this->query("CREATE TABLE \"$tableName\" (
				$fieldSchemas
				$indexSchemas
				primary key (ID)
			) TYPE=MyISAM");
	}

	/**
	 * Alter a table's schema.
	 * @param $table The name of the table to alter
	 * @param $newFields New fields, a map of field name => field schema
	 * @param $newIndexes New indexes, a map of index name => index type
	 * @param $alteredFields Updated fields, a map of field name => field schema
	 * @param $alteredIndexes Updated indexes, a map of index name => index type
	 */
	public function alterTable($tableName, $newFields = null, $newIndexes = null, $alteredFields = null, $alteredIndexes = null) {
		$fieldSchemas = $indexSchemas = "";
		
		if($newFields) foreach($newFields as $k => $v) $alterList[] .= "ADD \"$k\" $v";
		if($newIndexes) foreach($newIndexes as $k => $v) $alterList[] .= "ADD " . $this->getIndexSqlDefinition($k, $v);
		if($alteredFields) foreach($alteredFields as $k => $v) $alterList[] .= "CHANGE \"$k\" \"$k\" $v";
		if($alteredIndexes) foreach($alteredIndexes as $k => $v) {
			$alterList[] .= "DROP INDEX \"$k\"";
			$alterList[] .= "ADD ". $this->getIndexSqlDefinition($k, $v);
 		}
		
 		$alterations = implode(",\n", $alterList);
		$this->query("ALTER TABLE \"$tableName\" $alterations");
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
			Database::alteration_message("Table $tableName: repaired","repaired");
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
			$fieldSpec = $field['Type'];
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
					$fieldSpec .= " default " . addslashes($field['Default']);
				else
					$fieldSpec .= " default '" . addslashes($field['Default']) . "'";
			}
			if($field['Extra']) $fieldSpec .= " $field[Extra]";
			
			$fieldList[$field['Field']] = $fieldSpec;
		}
		return $fieldList;
	}
	
	/**
	 * Create an index on a table.
	 * @param string $tableName The name of the table.
	 * @param string $indexName The name of the index.
	 * @param string $indexSpec The specification of the index, see Database::requireIndex() for more details.
	 */
	public function createIndex($tableName, $indexName, $indexSpec) {
		$this->query("ALTER TABLE \"$tableName\" ADD " . $this->getIndexSqlDefinition($indexName, $indexSpec));
	}
	
	/*
	 * This takes the index spec which has been provided by a class (ie static $indexes = blah blah)
	 * and turns it into a proper string.
	 * Some indexes may be arrays, such as fulltext and unique indexes, and this allows database-specific
	 * arrays to be created.
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
					$indexSpec='using btree (' . $indexSpec['value'] . ')';
					break;
				case 'hash':
					$indexSpec='using hash (' . $indexSpec['value'] . ')';
					break;
			}
		}
		
		return $indexSpec;
	}
	
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
	 * @param string $indexSpec The specification of the index, see Database::requireIndex() for more details.
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
		
		foreach($groupedIndexes as $index => $details) {
			ksort($details['fields']);
			$indexList[$index] = $details['type'] . '(' . implode(',',$details['fields']) . ')';
		}
		
		return $indexList;
	}

	/**
	 * Returns a list of all the tables in the database.
	 * Table names will all be in lowercase.
	 * @return array
	 */
	public function tableList() {
		$tables = array();
		foreach($this->query("SHOW TABLES") as $record) {
			$table = strtolower(reset($record));
			$tables[$table] = $table;
		}
		return $tables;
	}
	
	/**
	 * Return the number of rows affected by the previous operation.
	 * @return int
	 */
	public function affectedRows() {
		return mysql_affected_rows($this->dbConn);
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
	 * @params array $values Contains a tokenised list of info about this data type
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
	 * @params array $values Contains a tokenised list of info about this data type
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
	 * @params array $values Contains a tokenised list of info about this data type
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

		return 'decimal(' . $precision . ') not null';
	}
	
	/**
	 * Return a enum type-formatted string
	 * 
	 * @params array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function enum($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'enum', 'enums'=>$this->enum, 'character set'=>'utf8', 'collate'=> 'utf8_general_ci', 'default'=>$this->default);
		//DB::requireField($this->tableName, $this->name, "enum('" . implode("','", $this->enum) . "') character set utf8 collate utf8_general_ci default '{$this->default}'");
		
		return 'enum(\'' . implode('\',\'', $values['enums']) . '\') character set utf8 collate utf8_general_ci default \'' . $values['default'] . '\'';
	}
	
	/**
	 * Return a float type-formatted string
	 * For MySQL, we simply return the word 'date', no other parameters are necessary
	 * 
	 * @params array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function float($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'float');
		//DB::requireField($this->tableName, $this->name, "float");
		
		return 'float';
	}
	
	/**
	 * Return a int type-formatted string
	 * 
	 * @params array $values Contains a tokenised list of info about this data type
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
	 * @params array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function ssdatetime($values){
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'datetime');
		//DB::requireField($this->tableName, $this->name, $values);

		return 'datetime';
	}
	
	/**
	 * Return a text type-formatted string
	 * 
	 * @params array $values Contains a tokenised list of info about this data type
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
	 * @params array $values Contains a tokenised list of info about this data type
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
	 * @params array $values Contains a tokenised list of info about this data type
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
		
		foreach($matches[0] as $value) {
			$classes[] = trim($value, "'");
		}
		return $classes;
	}
	
	/**
	 * Because NOW() doesn't always work...
	 * MSSQL, I'm looking at you
	 *
	 */
	function now(){
		return 'NOW()';
	}
	
	/*
	 * This will return text which has been escaped in a database-friendly manner
	 * Using PHP's addslashes method won't work in MSSQL
	 */
	function addslashes($value){
		return mysql_escape_string($value);
	}
}

/**
 * A result-set from a MySQL database.
 * @package sapphire
 * @subpackage model
 */
class MySQLQuery extends Query {
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
		mysql_free_result($this->handle);
	}
	
	public function seek($row) {
		return mysql_data_seek($this->handle, $row);
	}
	
	public function numRecords() {
		return mysql_num_rows($this->handle);
	}
	
	public function nextRecord() {
		// Coalesce rather than replace common fields.
		if($data = mysql_fetch_row($this->handle)) {
			foreach($data as $columnIdx => $value) {
				$columnName = mysql_field_name($this->handle, $columnIdx);
				// $value || !$ouput[$columnName] means that the *last* occurring value is shown
				// !$ouput[$columnName] means that the *first* occurring value is shown
				if(isset($value) || !isset($output[$columnName])) {
					$output[$columnName] = $value;
				}
			}
			return $output;
		} else {
			return false;
		}
	}
	
	
}

?>
